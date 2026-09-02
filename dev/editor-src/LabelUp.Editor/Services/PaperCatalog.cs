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

    public PaperCatalog(HttpClient http) => _http = http;

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
            foreach (var (vendor, code) in new (string, string?)[]
            {
                ("formtec", item.CompatFormtec),
                ("ilabel", item.CompatIlabel),
                ("anylabel", item.CompatAnylabel)
            })
            {
                if (string.IsNullOrWhiteSpace(code)) continue;
                var mapped = MapVendor(vendor, code.Trim()) ?? code.Trim();
                paper = Find(mapped)?.Clone();
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
        // A4 기준 대략 배치
        paper.Columns = labels >= 14 ? 2 : (labels >= 6 ? 2 : 1);
        paper.Rows = Math.Max(1, (int)Math.Ceiling(labels / (double)paper.Columns));
        if (paper.Columns * paper.Rows < labels)
            paper.Rows = Math.Max(1, (int)Math.Ceiling(labels / (double)paper.Columns));
        paper.PaperWidthMm = 210f;
        paper.PaperHeightMm = 297f;
        paper.HGapMm = 4f;
        paper.VGapMm = 3f;
        paper.Shape.Kind = MapShapeKind(shape);
        if (paper.Shape.Kind == "ellipse")
        {
            paper.LabelHeightMm = paper.LabelWidthMm = Math.Min(paper.LabelWidthMm, paper.LabelHeightMm);
        }
        paper.RecalcMarginsFromGaps();
        return paper;
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
        => _map.Resolve(vendor, vendorPaperNo);

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
