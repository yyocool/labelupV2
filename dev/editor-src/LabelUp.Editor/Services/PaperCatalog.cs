using System.Text.Json;
using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

public sealed class PaperCatalog
{
    private readonly HttpClient _http;
    private readonly List<PaperSpec> _papers = [];
    private readonly List<ShopPaperItem> _shopPapers = [];
    private readonly List<ShopPaperCategory> _shopCategories = [];
    private VendorPaperMap _map = new();
    private bool _loaded;
    private bool _shopLoaded;

    public PaperCatalog(HttpClient http)
    {
        _http = http;
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
        try
        {
            var json = await _http.GetStringAsync("/api/shop/editor-papers");
            var env = JsonSerializer.Deserialize<ApiEnvelope<ShopPaperCatalogDto>>(json, LabelDocumentJson.Options);
            if (env?.Success == true && env.Data is not null)
            {
                _shopPapers.Clear();
                _shopPapers.AddRange(env.Data.Items ?? []);
                _shopCategories.Clear();
                _shopCategories.AddRange(env.Data.Categories ?? []);
            }
        }
        catch (Exception ex)
        {
            EditorLog.Warn("상점 라벨 목록 로드 실패: " + ex.Message);
        }

        _shopLoaded = true;
        EditorLog.Info($"상점 라벨 {_shopPapers.Count}종 로드");
    }

    public PaperSpec FromShopProduct(ShopPaperItem item)
    {
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
            "circle" or "원형" or "ellipse" => "ellipse",
            "round" or "roundrect" or "라운드" => "roundrect",
            "heart" or "하트" => "svg",
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
