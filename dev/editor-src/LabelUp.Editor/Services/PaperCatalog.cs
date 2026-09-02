using System.Text.Json;
using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

public sealed class PaperCatalog
{
    private readonly HttpClient _http;
    private readonly List<PaperSpec> _papers = [];
    private VendorPaperMap _map = new();
    private bool _loaded;

    public PaperCatalog(HttpClient http) => _http = http;

    public IReadOnlyList<PaperSpec> Papers => _papers;
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

    public PaperSpec? Find(string? paperNo)
        => _papers.FirstOrDefault(p => string.Equals(p.PaperNo, paperNo, StringComparison.OrdinalIgnoreCase));

    public PaperSpec ResolveOrDefault(string? paperNo) => Find(paperNo) ?? _papers[0];

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
