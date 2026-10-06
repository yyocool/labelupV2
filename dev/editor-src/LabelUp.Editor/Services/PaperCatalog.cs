using System.Text.Json;
using LabelUp.Editor.Models;
using LabelUp.Editor.Rendering;
using SkiaSharp;

namespace LabelUp.Editor.Services;

public sealed class PaperCatalog
{
    /// <summary>용지선택 전용 API. 로컬 빌드에서도 서버 DB를 그대로 조회한다.</summary>
    private const string PapersEndpoint = "/api/editor/papers";

    /// <summary>구버전 서버 대비 폴백(같은 출처 배포본에서만 동작).</summary>
    private const string LegacyPapersEndpoint = "/api/shop/editor-papers";

    private readonly HttpClient _http;
    private readonly EditorApiOptions _api;
    private readonly List<PaperSpec> _papers = [];
    private readonly List<ShopPaperItem> _shopPapers = [];
    private readonly List<ShopPaperCategory> _shopCategories = [];
    private VendorPaperMap _map = new();
    private bool _loaded;
    private bool _shopLoaded;

    public PaperCatalog(HttpClient http, EditorApiOptions api)
    {
        _http = http;
        _api = api;
        FormtecWmf = new FormtecWmfCatalog(http);
        AniLabelWmf = new AniLabelWmfCatalog(http);
    }

    public FormtecWmfCatalog FormtecWmf { get; }
    public AniLabelWmfCatalog AniLabelWmf { get; }

    public IReadOnlyList<PaperSpec> Papers => _papers;
    public IReadOnlyList<ShopPaperItem> ShopPapers => _shopPapers;
    public IReadOnlyList<ShopPaperCategory> ShopCategories => _shopCategories;
    public VendorPaperMap Map => _map;

    /// <summary>
    /// 상점 용지 목록을 못 받아 온 까닭. 받아 왔으면 null이다. 목록이 비는 것과 서버가
    /// 오류를 낸 것은 고쳐야 할 곳이 전혀 달라서, 화면에 그대로 구분해 보여 준다.
    /// </summary>
    public string? ShopLoadError { get; private set; }

    public async Task EnsureLoadedAsync()
    {
        if (_loaded) return;
        try
        {
            var indexJson = await _http.GetStringAsync("paperData/index.json");
            var index = JsonSerializer.Deserialize<PaperIndex>(indexJson, LabelDocumentJson.Options);
            if (index?.Papers is { Count: > 0 })
            {
                foreach (var file in index.Papers)
                {
                    try
                    {
                        var json = await _http.GetStringAsync("paperData/" + file);
                        var spec = JsonSerializer.Deserialize<PaperSpec>(json, LabelDocumentJson.Options);
                        if (spec != null) _papers.Add(spec);
                    }
                    catch (Exception ex)
                    {
                        EditorLog.Warn($"용지 파일 로드 실패: {file} ({ex.Message})");
                    }
                }
            }

            try
            {
                var mapJson = await _http.GetStringAsync("paperData/maps/vendor-map.json");
                _map = JsonSerializer.Deserialize<VendorPaperMap>(mapJson, LabelDocumentJson.Options) ?? new VendorPaperMap();
            }
            catch (Exception ex)
            {
                EditorLog.Warn("용지 변환표 로드 실패: " + ex.Message);
            }
        }
        catch (Exception ex)
        {
            EditorLog.Warn("paperData 인덱스 로드 실패, 내장 규격을 사용합니다: " + ex.Message);
        }

        if (_papers.Count == 0)
            _papers.AddRange(BuiltInPapers.All());
        if (_map.Entries.Count == 0)
            _map.Entries.AddRange(DefaultMap());

        await FormtecWmf.EnsureLoadedAsync();
        await AniLabelWmf.EnsureLoadedAsync();
        _loaded = true;
        EditorLog.Info($"용지 카탈로그 {_papers.Count}종 로드");
    }

    public async Task EnsureShopPapersAsync()
    {
        if (_shopLoaded) return;

        if (!await TryLoadShopPapersAsync(_api.Url(PapersEndpoint)))
        {
            // 예전 주소로도 한 번 더 해 본다. 둘 다 실패하면 먼저 쓴 주소의 까닭이 더
            // 쓸모 있으므로 그것을 남긴다.
            var first = ShopLoadError;
            if (!await TryLoadShopPapersAsync(_api.Url(LegacyPapersEndpoint)))
                ShopLoadError = first ?? ShopLoadError;
        }

        _shopLoaded = true;
        EditorLog.Info($"상점 라벨 {_shopPapers.Count}종 로드");
    }

    private async Task<bool> TryLoadShopPapersAsync(string url)
    {
        try
        {
            var json = await _http.GetStringAsync(url);
            var env = JsonSerializer.Deserialize<ApiEnvelope<ShopPaperCatalogDto>>(json, LabelDocumentJson.Options);
            if (env?.Success != true || env.Data is null)
            {
                EditorLog.Warn($"상점 라벨 응답 오류: {url} ({env?.Message})");
                ShopLoadError = $"용지 목록 서버 오류: {env?.Message ?? "알 수 없는 오류"}";
                return false;
            }

            _shopPapers.Clear();
            foreach (var item in env.Data.Items ?? [])
            {
                item.ThumbnailUrl = _api.ResolveAssetUrl(item.ThumbnailUrl);
                _shopPapers.Add(item);
            }
            _shopCategories.Clear();
            _shopCategories.AddRange(env.Data.Categories ?? []);
            if (_shopPapers.Count == 0)
            {
                ShopLoadError = "서버가 보낸 용지 목록이 비어 있습니다.";
                return false;
            }
            ShopLoadError = null;
            return true;
        }
        catch (Exception ex)
        {
            EditorLog.Warn($"상점 라벨 목록 로드 실패: {url} ({ex.Message})");
            ShopLoadError = $"용지 목록 서버 응답 실패: {ex.Message}";
            return false;
        }
    }

    public PaperSpec FromShopProduct(ShopPaperItem item)
    {
        // DB(label_specs)에 열·행·여백·간격이 있으면 추정하지 않고 그대로 쓴다.
        if (item.HasLayout)
            return FromShopLayout(item);

        PaperSpec? paper = null;
        if (!string.IsNullOrWhiteSpace(item.Sku))
            paper = Find(item.Sku)?.Clone();

        if (paper is null)
        {
            foreach (var (vendor, codes) in new (string, IReadOnlyList<string>)[]
            {
                ("formtec", item.CompatFormtecCodes),
                ("ilabel", item.CompatIlabelCodes),
                ("anylabel", item.CompatAnylabelCodes)
            })
            {
                foreach (var code in codes)
                {
                    var mapped = MapVendor(vendor, code) ?? code;
                    paper = Find(mapped)?.Clone();
                    if (paper is not null) break;
                }
                if (paper is not null) break;
            }
        }

        var width = item.WidthMm > 0 ? item.WidthMm : paper?.LabelWidthMm ?? 70f;
        var height = item.HeightMm > 0 ? item.HeightMm : paper?.LabelHeightMm ?? 36f;
        var labels = item.LabelsPerSheet > 0 ? item.LabelsPerSheet : paper?.LabelsPerPage ?? 1;

        if (paper is null)
            paper = FindBestMatch(width, height, labels)?.Clone()
                    ?? CreateFromSize(width, height, labels, item.Shape, item.Name);

        if (item.WidthMm > 0) paper.LabelWidthMm = item.WidthMm;
        if (item.HeightMm > 0) paper.LabelHeightMm = item.HeightMm;
        if (item.LabelsPerSheet > 0 && paper.LabelsPerPage != item.LabelsPerSheet)
        {
            var rebuilt = CreateFromSize(paper.LabelWidthMm, paper.LabelHeightMm, item.LabelsPerSheet, item.Shape, item.Name);
            rebuilt.PaperNo = paper.PaperNo;
            rebuilt.Name = paper.Name;
            rebuilt.Category = paper.Category;
            paper = rebuilt;
        }

        paper.PaperNo = string.IsNullOrWhiteSpace(item.Sku) ? $"P{item.Id}" : item.Sku.Trim();
        paper.Name = string.IsNullOrWhiteSpace(item.Name) ? paper.Name : item.Name;
        if (!string.IsNullOrWhiteSpace(item.CategoryName))
            paper.Category = item.CategoryName!;

        // 규격에 커스텀 외곽 Path가 적혀 있으면 그 모양을 그대로 쓴다. 예전에는 여기서 Kind만
        // 'svg'로 바꾸고 Path는 넘기지 않아, 형태가 '맞춤'인 용지가 아무 말 없이 네모로 그려졌다.
        if (!string.IsNullOrWhiteSpace(item.CustomPathSvg))
        {
            var (shape, read) = BuildShopShape(item, paper.LabelWidthMm, paper.LabelHeightMm);
            paper.Shape = shape;
            ApplyClosedShapeSlots(paper, read);
        }
        else if (!string.IsNullOrWhiteSpace(item.Shape))
        {
            var kind = MapShapeKind(item.Shape);
            // '맞춤'인데 Path가 어디에도 없으면 그릴 것이 없다. 네모로 낮춰 두어야 모양 테두리를
            // 덧그리는 쪽에서 헛되이 네모 윤곽을 한 번 더 그리지 않는다.
            if (kind == "svg" && string.IsNullOrWhiteSpace(paper.Shape.Svg))
                kind = paper.Shape.CornerRadiusMm > 0f ? "roundrect" : "rect";
            paper.Shape.Kind = kind;
        }
        return paper;
    }

    /// <summary>
    /// 상점 규격(label_specs)의 배치값으로 용지를 만든다.
    /// 미리보기·인쇄가 모두 이 PaperSpec의 슬롯을 쓰므로 여기 값이 곧 출력 결과가 된다.
    /// </summary>
    private static PaperSpec FromShopLayout(ShopPaperItem item)
    {
        var cols = Math.Max(1, item.ColumnsCount ?? 1);
        var rows = Math.Max(1, item.RowsCount ?? 1);
        var lw = Math.Max(1f, item.WidthMm);
        var lh = Math.Max(1f, item.HeightMm);
        var hGap = Math.Max(0f, item.HGapMm ?? 0f);
        var vGap = Math.Max(0f, item.VGapMm ?? 0f);
        var left = item.LeftMarginMm is { } l && l >= 0f ? l : (float?)null;
        var top = item.TopMarginMm is { } t && t >= 0f ? t : (float?)null;

        var usedW = lw * cols + hGap * (cols - 1);
        var usedH = lh * rows + vGap * (rows - 1);
        var (pageW, pageH) = ResolvePaperSizeMm(item.PaperSize, usedW, usedH);

        // 규격이 시트를 넘치면 잘려 인쇄되므로 시트를 늘리고 사용자에게 알린다.
        var needW = usedW + (left ?? 0f);
        var needH = usedH + (top ?? 0f);
        string? issue = null;
        if (needW > pageW + 0.5f || needH > pageH + 0.5f)
        {
            issue = "용지 규격에 이상이 있습니다. 용지 여백과 라벨 크기의 합이 용지 규격보다 큽니다.\n"
                    + $"용지번호 {(string.IsNullOrWhiteSpace(item.Sku) ? $"P{item.Id}" : item.Sku.Trim())} · "
                    + $"{cols}열 × {rows}행 · 필요 {needW:0.#}×{needH:0.#}mm > 용지 {pageW:0.#}×{pageH:0.#}mm";
            EditorLog.Warn($"규격 배치가 용지를 넘칩니다: {item.Sku} {cols}×{rows} "
                           + $"({needW:0.#}×{needH:0.#}mm > {pageW:0.#}×{pageH:0.#}mm)");
            pageW = Math.Max(pageW, needW);
            pageH = Math.Max(pageH, needH);
        }

        // 칸수가 맞는지는 맨 끝에서 본다. 형태가 '맞춤'이면 SVG 안의 닫힌 도형만큼 칸이 늘어나
        // 열×행보다 많아지므로, 펼치기 전에 비교하면 멀쩡한 규격을 틀렸다고 알리게 된다.
        var (shape, svgRead) = BuildShopShape(item, lw, lh);

        var paper = new PaperSpec
        {
            PaperNo = string.IsNullOrWhiteSpace(item.Sku) ? $"P{item.Id}" : item.Sku.Trim(),
            Name = string.IsNullOrWhiteSpace(item.Name) ? $"{lw:0.#}×{lh:0.#} mm" : item.Name,
            Category = string.IsNullOrWhiteSpace(item.CategoryName)
                ? (string.IsNullOrWhiteSpace(item.PaperSize) ? "A4" : item.PaperSize!.Trim())
                : item.CategoryName!,
            PaperWidthMm = pageW,
            PaperHeightMm = pageH,
            LabelWidthMm = lw,
            LabelHeightMm = lh,
            Columns = cols,
            Rows = rows,
            HGapMm = hGap,
            VGapMm = vGap,
            LabelColor = NormalizeLabelColor(item.LabelColor),
            Shape = shape,
            LayoutIssue = issue
        };

        // 여백이 비어 있으면 가운데 정렬, 있으면 DB 값을 왼쪽·위 기준으로 삼는다.
        paper.RecalcMarginsFromGaps();
        if (left is { } lm)
        {
            paper.LeftMarginMm = lm;
            paper.RightMarginMm = Math.Max(0f, pageW - usedW - lm);
        }
        if (top is { } tm)
        {
            paper.TopMarginMm = tm;
            paper.BottomMarginMm = Math.Max(0f, pageH - usedH - tm);
        }

        // 여백이 다 정해진 뒤에 펼쳐야 칸 자리가 맞는다. 격자 자리를 기준으로 삼기 때문이다.
        ApplyClosedShapeSlots(paper, svgRead);
        AppendCountIssue(paper, item);
        return paper;
    }

    /// <summary>
    /// 규격 SVG 안의 닫힌 도형을 편집 칸으로 펼친다. 격자 칸 하나가 SVG 한 장이고,
    /// 그 안의 닫힌 도형 하나하나가 편집할 수 있는 라벨 한 칸이 된다.
    /// 도형이 하나뿐일 때도 같은 규칙으로 다뤄 칸이 도형 크기에 딱 맞게 된다.
    /// </summary>
    private static void ApplyClosedShapeSlots(PaperSpec paper, SvgShapeImporter.Result? read)
    {
        if (read is null || read.Closed.Count == 0) return;

        // CustomSlots를 아직 안 넣었으므로 여기서는 격자 자리가 나온다.
        var grid = paper.EnumerateSlots().ToList();
        if (grid.Count == 0) return;

        var closed = read.Closed;
        var slots = new List<LabelSlot>(grid.Count * closed.Count);
        var cuts = new List<string>(grid.Count * read.FrameCuts.Count);
        foreach (var cell in grid)
        {
            foreach (var piece in closed)
            {
                slots.Add(new LabelSlot(
                    cell.Col, cell.Row, slots.Count,
                    cell.X + piece.XMm, cell.Y + piece.YMm,
                    piece.WMm, piece.HMm,
                    piece.Shape.Clone()));
            }

            // 칸 밖 칼선은 격자 칸마다 되풀이되므로 여기서 시트 좌표로 옮겨 둔다.
            foreach (var d in read.FrameCuts)
            {
                var moved = TranslatePathData(d, cell.X, cell.Y);
                if (moved is not null) cuts.Add(moved);
            }
        }

        paper.CustomSlots = slots;
        paper.SheetCutPaths = cuts.Count > 0 ? cuts : null;
        paper.LabelWidthMm = slots[0].W;
        paper.LabelHeightMm = slots[0].H;
        // 칸마다 제 모양을 들고 있으니 용지 기본 모양은 받침으로만 남긴다.
        paper.Shape = new PaperShape { Kind = "rect", CornerRadiusMm = 0f };

        EditorLog.Info($"규격 SVG를 편집 칸으로 펼쳤습니다: 격자 {grid.Count}칸 × 닫힌 도형 "
                       + $"{closed.Count}개 = {slots.Count}칸 · 칸 밖 칼선 {cuts.Count}개 "
                       + $"· 첫 칸 {slots[0].W:0.###}×{slots[0].H:0.###}mm");
    }

    /// <summary>Path를 통째로 옮긴다. 라벨 칸 기준 칼선을 시트 기준으로 바꿀 때 쓴다.</summary>
    private static string? TranslatePathData(string d, float dx, float dy)
    {
        var path = SKPath.ParseSvgPathData(d);
        if (path is null) return null;
        using (path)
        {
            path.Transform(SKMatrix.CreateTranslation(dx, dy));
            var moved = path.ToSvgPathData();
            return string.IsNullOrWhiteSpace(moved) ? null : moved;
        }
    }

    /// <summary>실제 칸수가 규격에 적힌 칸수와 다르면 사용자에게 알린다.</summary>
    private static void AppendCountIssue(PaperSpec paper, ShopPaperItem item)
    {
        var actual = paper.LabelsPerPage;
        if (item.LabelsPerSheet <= 0 || actual == item.LabelsPerSheet) return;

        var no = string.IsNullOrWhiteSpace(item.Sku) ? $"P{item.Id}" : item.Sku.Trim();
        var how = paper.CustomSlots is { Count: > 0 }
            ? $"SVG 닫힌 도형으로 펼친 {actual}칸"
            : $"{paper.Columns}열 × {paper.Rows}행 = {actual}칸";
        var mismatch = "용지 규격에 이상이 있습니다. 칸수가 맞지 않습니다.\n"
                       + $"용지번호 {no} · {how} ≠ 규격에 적힌 {item.LabelsPerSheet}칸";
        paper.LayoutIssue = paper.LayoutIssue is null ? mismatch : paper.LayoutIssue + "\n\n" + mismatch;
        EditorLog.Warn($"규격 칸수 불일치: {no} {how} ≠ {item.LabelsPerSheet}칸");
    }

    private static readonly Dictionary<string, (float W, float H)> StandardPaperSizes =
        new(StringComparer.OrdinalIgnoreCase)
        {
            ["A3"] = (297f, 420f),
            ["A4"] = (210f, 297f),
            ["A5"] = (148f, 210f),
            ["A6"] = (105f, 148f),
            ["B4"] = (257f, 364f),
            ["B5"] = (182f, 257f),
            ["B6"] = (128f, 182f),
            ["LETTER"] = (215.9f, 279.4f),
            ["LEGAL"] = (215.9f, 355.6f)
        };

    /// <summary>용지 규격 이름을 mm로 바꾼다. "210x297" 같은 직접 입력도 받는다.</summary>
    private static (float W, float H) ResolvePaperSizeMm(string? name, float needW, float needH)
    {
        var key = (name ?? "").Trim();
        if (key.Length > 0)
        {
            var landscape = false;
            foreach (var word in new[] { "가로", "landscape", "LANDSCAPE" })
            {
                if (!key.Contains(word, StringComparison.OrdinalIgnoreCase)) continue;
                landscape = true;
                key = key.Replace(word, "", StringComparison.OrdinalIgnoreCase);
            }
            key = key.Replace("세로", "", StringComparison.OrdinalIgnoreCase).Trim();

            if (StandardPaperSizes.TryGetValue(key, out var hit))
                return landscape ? (hit.H, hit.W) : hit;

            var parts = key.Split(['x', 'X', '×', '*'], 2);
            if (parts.Length == 2
                && TryParseMm(parts[0], out var w)
                && TryParseMm(parts[1], out var h))
                return (w, h);
        }

        // 규격을 모르면 A4 기준으로 두되, 배치가 넘치면 배치에 맞춘다.
        return (Math.Max(210f, needW), Math.Max(297f, needH));
    }

    private static bool TryParseMm(string raw, out float value)
    {
        var text = new string(raw.Where(c => char.IsDigit(c) || c == '.').ToArray());
        return float.TryParse(text, System.Globalization.NumberStyles.Float,
                   System.Globalization.CultureInfo.InvariantCulture, out value)
               && value > 0f;
    }

    private static string NormalizeLabelColor(string? raw)
    {
        var text = (raw ?? "").Trim();
        if (text.Length == 0) return "#FFFFFF";
        if (!text.StartsWith('#')) text = "#" + text;
        if (text.Length == 4 && IsHex(text[1..]))
            return $"#{text[1]}{text[1]}{text[2]}{text[2]}{text[3]}{text[3]}".ToUpperInvariant();
        return text.Length == 7 && IsHex(text[1..]) ? text.ToUpperInvariant() : "#FFFFFF";

        static bool IsHex(string s) => s.All(Uri.IsHexDigit);
    }

    /// <summary>
    /// DB의 형태·모서리반경·커스텀 Path를 편집기 도형으로 바꾼다.
    /// 형태가 '맞춤'이고 올린 것이 SVG 마크업이면, 읽어 낸 칸·칼선도 함께 돌려준다.
    /// 부르는 쪽이 여백을 정한 뒤 이것을 편집 칸으로 펼친다.
    /// </summary>
    private static (PaperShape Shape, SvgShapeImporter.Result? Svg) BuildShopShape(
        ShopPaperItem item, float lw, float lh)
    {
        var limit = Math.Min(lw, lh) / 2f;
        var rx = Math.Clamp(item.CornerRadiusXMm ?? 0f, 0f, limit);
        var ry = Math.Clamp(item.CornerRadiusYMm ?? rx, 0f, limit);
        var svg = (item.CustomPathSvg ?? "").Trim();
        var kind = MapShapeKind(item.Shape);

        if (svg.Length > 0)
            kind = "svg";
        else if (kind == "svg")
            kind = rx > 0f ? "roundrect" : "rect"; // 맞춤인데 Path가 없으면 사각으로 낮춘다.
        else if (kind is "rect" or "roundrect")
            kind = rx > 0f ? "roundrect" : "rect";

        // 형태가 원형이면 '모서리 세로 반경'을 원의 반지름으로 읽는다(없으면 가로 반경).
        // 값이 있으면 칸 가운데에 그 크기의 정원을 그리고, 없으면 칸에 내접하는 타원을 그린다.
        float? circle = null;
        if (kind == "ellipse" && ry > 0f)
        {
            circle = ry;
            var raw = item.CornerRadiusYMm ?? item.CornerRadiusXMm ?? 0f;
            if (raw > limit + 0.005f)
                EditorLog.Warn($"원형 규격의 반지름이 칸보다 큽니다: {item.Sku} "
                               + $"{raw:0.###}mm → {limit:0.###}mm (칸 {lw:0.#}×{lh:0.#}mm)");
        }

        var shape = new PaperShape
        {
            Kind = kind,
            CornerRadiusMm = rx,
            CornerRadiusYMm = Math.Abs(ry - rx) > 0.005f ? ry : null,
            CircleRadiusMm = circle,
            Svg = svg.Length > 0 ? svg : null,
            SvgIsLabelMm = false
        };

        if (kind != "svg") return (shape, null);

        // 올린 것이 SVG 마크업이면 안에 든 요소를 모두 꺼내 라벨 칸 mm 좌표로 바꾼다.
        // 맨 path 문자열이거나 읽지 못하면 결과가 null이고, 그때는 예전처럼 외곽 한 장만 쓴다.
        if (SvgShapeImporter.Read(svg, lw, lh, SlotModeOf(item.Shape)) is not { } read)
            return (shape, null);

        // 닫힌 도형을 칸으로 펼칠 수 있으면 이 모양은 받침으로만 쓰이고 칸 모양이 대신 쓰인다.
        // 펼칠 수 없을 때(닫힌 도형이 없을 때)는 SVG를 외곽 + 가이드로 그려 적어도 보이게 한다.
        if (read.Closed.Count == 0)
        {
            shape.Svg = read.Outline;
            shape.Guides = read.Guides;
            shape.GuidesIncludeOutline = true;
            shape.SvgIsLabelMm = true;
        }

        return (shape, read);
    }

    /// <summary>
    /// 규격의 형태 값으로 겹친 칼선을 어떻게 다룰지 정한다.
    /// '맞춤 도넛'은 바깥·안쪽 칼선 사이가 편집 칸이고, 그 밖의 맞춤은 안쪽 칼선 안이 편집 칸이다.
    /// </summary>
    private static SvgShapeImporter.SlotMode SlotModeOf(string? shape)
    {
        var text = (shape ?? "").Trim().ToLowerInvariant();
        return text.Contains("donut") || text.Contains("도넛")
            ? SvgShapeImporter.SlotMode.Donut
            : SvgShapeImporter.SlotMode.Inner;
    }

    public PaperSpec? Find(string? paperNo)
        => _papers.FirstOrDefault(p => string.Equals(p.PaperNo, paperNo, StringComparison.OrdinalIgnoreCase));

    public PaperSpec ResolveOrDefault(string? paperNo) => Find(paperNo) ?? _papers[0];

    /// <summary>
    /// 라벨 치수(및 칸수)로 가장 가까운 카탈로그 용지를 찾는다.
    /// </summary>
    public PaperSpec? FindBestMatch(float widthMm, float heightMm, int? labelsPerSheet = null)
    {
        if (_papers.Count == 0) return null;
        PaperSpec? best = null;
        var bestScore = double.MaxValue;
        foreach (var p in _papers)
        {
            var dw = Math.Abs(p.LabelWidthMm - widthMm);
            var dh = Math.Abs(p.LabelHeightMm - heightMm);
            if (dw > 1.5f || dh > 1.5f) continue;
            double score = dw + dh;
            if (labelsPerSheet is > 0)
                score += Math.Abs(p.LabelsPerPage - labelsPerSheet.Value) * 0.15;
            if (score < bestScore)
            {
                bestScore = score;
                best = p;
            }
        }
        return best;
    }

    /// <summary>
    /// 치수 매칭 실패 시 임시 커스텀 용지를 만든다.
    /// 칸수(labelsPerSheet)와 열×행이 일치하도록 잡고, A4에 실제로 들어가는 최대 열·행만 쓴다.
    /// </summary>
    public PaperSpec CreateFromSize(float widthMm, float heightMm, int labelsPerSheet = 1, string? shape = null, string? name = null)
    {
        var paper = PaperSpec.CreateDefault().Clone();
        paper.PaperNo = "CUSTOM";
        paper.Name = string.IsNullOrWhiteSpace(name)
            ? $"{widthMm:0.#}×{heightMm:0.#} mm"
            : name!;
        paper.LabelWidthMm = Math.Max(1f, widthMm);
        paper.LabelHeightMm = Math.Max(1f, heightMm);
        var labels = Math.Max(1, labelsPerSheet);
        const float pageW = 210f, pageH = 297f;
        // 주소용(~99mm) 2열이 들어가도록 여백·간격은 실측 규격에 가깝게 잡는다.
        const float gap = 2.5f;
        const float minMargin = 2f;
        var lw = paper.LabelWidthMm;
        var lh = paper.LabelHeightMm;
        var maxCols = MaxLabelsAlong(pageW, lw, gap, minMargin);
        var maxRows = MaxLabelsAlong(pageH, lh, gap, minMargin);
        if (!TryPickExactGrid(labels, maxCols, maxRows, out var cols, out var rows))
        {
            // 나누어떨어지지 않으면 폭을 우선해 맞추되, 칸수를 초과하는 격자는 만들지 않는다.
            cols = Math.Min(labels, Math.Max(1, maxCols));
            rows = Math.Max(1, (int)Math.Ceiling(labels / (double)cols));
            if (rows > maxRows)
            {
                rows = Math.Max(1, maxRows);
                cols = Math.Max(1, (int)Math.Ceiling(labels / (double)rows));
            }
            // 여전히 초과 칸이 생기면 칸수에 맞는 약수로 내린다.
            if (cols * rows != labels && !TryPickExactGrid(labels, Math.Max(cols, maxCols), Math.Max(rows, maxRows), out cols, out rows))
            {
                // 최후: 1×N 또는 N×1 (용지가 늘어날 수 있음)
                if (labels <= maxRows)
                {
                    cols = 1;
                    rows = labels;
                }
                else if (labels <= maxCols)
                {
                    cols = labels;
                    rows = 1;
                }
                else
                {
                    cols = Math.Min(labels, Math.Max(1, maxCols));
                    rows = Math.Max(1, (int)Math.Ceiling(labels / (double)cols));
                }
            }
        }

        paper.Columns = cols;
        paper.Rows = rows;
        paper.HGapMm = gap;
        paper.VGapMm = gap;
        var needW = cols * lw + Math.Max(0, cols - 1) * gap + minMargin * 2;
        var needH = rows * lh + Math.Max(0, rows - 1) * gap + minMargin * 2;
        paper.PaperWidthMm = Math.Max(pageW, needW);
        paper.PaperHeightMm = Math.Max(pageH, needH);
        paper.Shape.Kind = MapShapeKind(shape);
        if (paper.Shape.Kind == "ellipse")
        {
            paper.LabelHeightMm = paper.LabelWidthMm = Math.Min(paper.LabelWidthMm, paper.LabelHeightMm);
        }
        paper.RecalcMarginsFromGaps();
        return paper;
    }

    /// <summary>용지 한 변에 실제로 들어가는 최대 라벨 개수.</summary>
    private static int MaxLabelsAlong(float pageMm, float labelMm, float gapMm, float minMarginMm)
    {
        var usable = pageMm - minMarginMm * 2f;
        if (usable < labelMm - 0.05f)
            return 1;
        // n*label + (n-1)*gap <= usable  →  n <= (usable + gap) / (label + gap)
        var n = (int)Math.Floor((usable + gapMm) / (labelMm + gapMm) + 1e-3f);
        return Math.Max(1, n);
    }

    /// <summary>labels = cols×rows 인 배치를 고른다. 가능한 한 열 수를 크게(폭 활용).</summary>
    private static bool TryPickExactGrid(int labels, int maxCols, int maxRows, out int cols, out int rows)
    {
        cols = 1;
        rows = labels;
        var found = false;
        var limit = Math.Min(Math.Max(1, maxCols), labels);
        for (var c = limit; c >= 1; c--)
        {
            if (labels % c != 0) continue;
            var r = labels / c;
            if (r > Math.Max(1, maxRows)) continue;
            cols = c;
            rows = r;
            found = true;
            break;
        }
        return found;
    }

    public ShopPaperItem? FindMatchingShopProduct(PaperSpec paper)
    {
        if (_shopPapers.Count == 0) return null;
        var no = (paper.PaperNo ?? "").Trim();
        if (no.Length > 0)
        {
            var bySku = _shopPapers.FirstOrDefault(p => string.Equals(p.Sku, no, StringComparison.OrdinalIgnoreCase));
            if (bySku is not null) return bySku;
        }

        foreach (var alias in PaperCodeAliases(no))
        {
            foreach (var item in _shopPapers)
            {
                if (string.Equals(item.Sku, alias, StringComparison.OrdinalIgnoreCase))
                    return item;
                var codes = item.CompatFormtecCodes.Concat(item.CompatIlabelCodes).Concat(item.CompatAnylabelCodes);
                if (!codes.Any(c => string.Equals(c, alias, StringComparison.OrdinalIgnoreCase)))
                    continue;
                if (ShopSizeFits(item, paper))
                    return item;
            }
        }

        ShopPaperItem? best = null;
        var bestScore = double.MaxValue;
        foreach (var item in _shopPapers)
        {
            if (item.WidthMm <= 0 || item.HeightMm <= 0) continue;
            var dw = Math.Abs(item.WidthMm - paper.LabelWidthMm);
            var dh = Math.Abs(item.HeightMm - paper.LabelHeightMm);
            if (dw > 1.6f || dh > 1.6f) continue;
            double score = dw + dh;
            if (paper.LabelsPerPage > 0 && item.LabelsPerSheet > 0)
                score += Math.Abs(item.LabelsPerSheet - paper.LabelsPerPage) * 0.2;
            if (score < bestScore)
            {
                bestScore = score;
                best = item;
            }
        }
        return best;
    }

    public ShopPaperItem? PreferredDefaultShopPaper(PaperSpec? prefer = null)
    {
        if (prefer is not null)
        {
            var match = FindMatchingShopProduct(prefer);
            if (match is not null) return match;
        }

        static bool Usable(ShopPaperItem p) =>
            p.WidthMm > 0 && p.HeightMm > 0 && !string.Equals(p.Kind, "tag", StringComparison.OrdinalIgnoreCase);

        var usable = _shopPapers.Where(Usable).ToList();
        if (usable.Count == 0) return _shopPapers.FirstOrDefault();

        if (prefer is not null && prefer.LabelsPerPage > 0)
        {
            var sameLabels = usable
                .Where(p => p.LabelsPerSheet == prefer.LabelsPerPage)
                .OrderBy(p => Math.Abs(p.WidthMm - prefer.LabelWidthMm) + Math.Abs(p.HeightMm - prefer.LabelHeightMm))
                .ThenBy(p => p.Sku.EndsWith("-100", StringComparison.OrdinalIgnoreCase) ? 0 : 1)
                .FirstOrDefault();
            if (sameLabels is not null) return sameLabels;
        }

        return usable
            .OrderBy(p => p.Sku.EndsWith("-100", StringComparison.OrdinalIgnoreCase) ? 0 : 1)
            .ThenBy(p => p.Id)
            .FirstOrDefault();
    }

    private static bool ShopSizeFits(ShopPaperItem item, PaperSpec paper)
    {
        if (item.WidthMm <= 0 || item.HeightMm <= 0) return true;
        return Math.Abs(item.WidthMm - paper.LabelWidthMm) <= 1.6f
               && Math.Abs(item.HeightMm - paper.LabelHeightMm) <= 1.6f;
    }

    private static IEnumerable<string> PaperCodeAliases(string code)
    {
        var trimmed = (code ?? "").Trim();
        var upper = trimmed.ToUpperInvariant();
        if (upper.Length == 0) yield break;
        yield return trimmed;
        if (!string.Equals(trimmed, upper, StringComparison.Ordinal))
            yield return upper;

        var num = upper;
        if (upper.StartsWith("LU-", StringComparison.Ordinal)) num = upper[3..];
        else if (upper.StartsWith("LU", StringComparison.Ordinal) && upper.Length > 2 && char.IsDigit(upper[2]))
            num = upper[2..];
        else if (upper.StartsWith('V') && upper.Length > 1 && char.IsDigit(upper[1]))
            num = upper[1..];

        if (num.Length is >= 3 and <= 5 && num.All(char.IsDigit))
        {
            yield return num;
            yield return "V" + num;
            yield return "LU-" + num;
            yield return "LU" + num;
        }
    }

    private static string MapShapeKind(string? shape)
    {
        var s = (shape ?? "").Trim().ToLowerInvariant();
        return s switch
        {
            // DB shape ENUM: rect(사각) / round(원형) / custom(맞춤)
            "circle" or "원형" or "ellipse" or "round" => "ellipse",
            "rect" or "사각" or "사각형" => "rect",
            "roundrect" or "라운드" => "roundrect",
            "heart" or "하트" or "custom" or "맞춤" => "svg",
            _ => "roundrect"
        };
    }

    public void Upsert(PaperSpec spec)
    {
        var i = _papers.FindIndex(p => string.Equals(p.PaperNo, spec.PaperNo, StringComparison.OrdinalIgnoreCase));
        if (i >= 0) _papers[i] = spec.Clone();
        else _papers.Add(spec.Clone());
    }

    public void ReplaceMap(VendorPaperMap map) => _map = map.Clone();

    public string? MapVendor(string vendor, string vendorPaperNo)
    {
        var mapped = _map.Resolve(vendor, vendorPaperNo);
        if (!string.IsNullOrWhiteSpace(mapped)) return mapped;

        var needle = vendorPaperNo.Trim();
        if (needle.Length == 0) return null;

        foreach (var item in _shopPapers)
        {
            IReadOnlyList<string> codes = vendor.ToLowerInvariant() switch
            {
                "formtec" => item.CompatFormtecCodes,
                "ilabel" => item.CompatIlabelCodes,
                "anylabel" => item.CompatAnylabelCodes,
                _ => []
            };
            if (codes.Any(c => string.Equals(c, needle, StringComparison.OrdinalIgnoreCase)))
                return string.IsNullOrWhiteSpace(item.Sku) ? $"P{item.Id}" : item.Sku.Trim();
        }

        return null;
    }

    private static IEnumerable<VendorPaperMapEntry> DefaultMap() =>
    [
        new() { Vendor = "anylabel", VendorPaperNo = "V3230", OurPaperNo = "LU-3230" },
        new() { Vendor = "anylabel", VendorPaperNo = "V3630", OurPaperNo = "LU-3630" },
        new() { Vendor = "anylabel", VendorPaperNo = "V3775", OurPaperNo = "LU-3775" },
        new() { Vendor = "formtec", VendorPaperNo = "3230", OurPaperNo = "LU-3230" },
        new() { Vendor = "formtec", VendorPaperNo = "3102", OurPaperNo = "LU-3102" },
        new() { Vendor = "ilabel", VendorPaperNo = "100", OurPaperNo = "LU-H100" }
    ];

    private sealed class PaperIndex
    {
        public List<string> Papers { get; set; } = [];
    }
}
