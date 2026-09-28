using System.Text.Json;
using LabelUp.Editor.Models;

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
            await TryLoadShopPapersAsync(_api.Url(LegacyPapersEndpoint));

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
            return _shopPapers.Count > 0;
        }
        catch (Exception ex)
        {
            EditorLog.Warn($"상점 라벨 목록 로드 실패: {url} ({ex.Message})");
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
        if (!string.IsNullOrWhiteSpace(item.Shape))
            paper.Shape.Kind = MapShapeKind(item.Shape);
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

        if (item.LabelsPerSheet > 0 && cols * rows != item.LabelsPerSheet)
        {
            var mismatch = "용지 규격에 이상이 있습니다. 열 × 행 수가 칸수와 맞지 않습니다.\n"
                           + $"용지번호 {(string.IsNullOrWhiteSpace(item.Sku) ? $"P{item.Id}" : item.Sku.Trim())} · "
                           + $"{cols}열 × {rows}행 = {cols * rows}칸 ≠ {item.LabelsPerSheet}칸";
            issue = issue is null ? mismatch : issue + "\n\n" + mismatch;
            EditorLog.Warn($"규격 칸수 불일치: {item.Sku} {cols}×{rows}={cols * rows} ≠ {item.LabelsPerSheet}칸");
        }

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
            Shape = BuildShopShape(item, lw, lh),
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

        return paper;
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

    /// <summary>DB의 형태·모서리반경·커스텀 Path를 편집기 도형으로 바꾼다.</summary>
    private static PaperShape BuildShopShape(ShopPaperItem item, float lw, float lh)
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

        return new PaperShape
        {
            Kind = kind,
            CornerRadiusMm = rx,
            CornerRadiusYMm = Math.Abs(ry - rx) > 0.005f ? ry : null,
            Svg = svg.Length > 0 ? svg : null,
            SvgIsLabelMm = false
        };
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
        const float pageW = 210f, pageH = 297f, margin = 5f, gap = 3f;
        var lw = paper.LabelWidthMm;
        var lh = paper.LabelHeightMm;
        var maxCols = Math.Max(1, (int)Math.Floor((pageW - margin * 2 + gap) / (lw + gap)));
        var maxRows = Math.Max(1, (int)Math.Floor((pageH - margin * 2 + gap) / (lh + gap)));
        int cols;
        int rows;
        if (labels == 1)
        {
            cols = 1;
            rows = 1;
        }
        else
        {
            cols = Math.Min(labels, Math.Max(1, maxCols));
            rows = Math.Max(1, (int)Math.Ceiling(labels / (double)cols));
            while (rows > maxRows && cols < labels)
            {
                cols++;
                rows = Math.Max(1, (int)Math.Ceiling(labels / (double)cols));
            }
        }
        paper.Columns = cols;
        paper.Rows = rows;
        paper.HGapMm = gap;
        paper.VGapMm = gap;
        var needW = cols * lw + Math.Max(0, cols - 1) * gap + margin * 2;
        var needH = rows * lh + Math.Max(0, rows - 1) * gap + margin * 2;
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
