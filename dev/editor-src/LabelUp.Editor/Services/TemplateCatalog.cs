using System.Text.Json;
using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

public sealed class TemplateCatalog
{
    private readonly HttpClient _http;
    private bool _loaded;
    private readonly List<LabelThemeItem> _items = [];
    private readonly List<LabelThemeCategory> _categories = [];

    public TemplateCatalog(HttpClient http) => _http = http;

    public IReadOnlyList<LabelThemeItem> Items => _items;
    public IReadOnlyList<LabelThemeCategory> Categories => _categories;

    public async Task EnsureLoadedAsync()
    {
        if (_loaded) return;
        try
        {
            var json = await _http.GetStringAsync("/api/editor/templates");
            var env = JsonSerializer.Deserialize<ApiEnvelope<LabelThemeCatalogDto>>(json, LabelDocumentJson.Options);
            if (env?.Success == true && env.Data is not null)
            {
                _items.Clear();
                _items.AddRange(env.Data.Items ?? []);
                _categories.Clear();
                _categories.AddRange(env.Data.Categories ?? []);
            }
        }
        catch (Exception ex)
        {
            EditorLog.Warn("테마 목록 로드 실패: " + ex.Message);
        }

        _loaded = true;
        EditorLog.Info($"테마 템플릿 {_items.Count}종 로드");
    }

    public async Task<LabelDocument?> LoadDocumentAsync(string idOrSlug)
    {
        if (string.IsNullOrWhiteSpace(idOrSlug)) return null;
        var json = await _http.GetStringAsync("/api/editor/templates/" + Uri.EscapeDataString(idOrSlug.Trim()));
        var env = JsonSerializer.Deserialize<ApiEnvelope<LabelThemeDetailDto>>(json, LabelDocumentJson.Options);
        if (env?.Success != true || env.Data is null)
            return null;

        var raw = env.Data.Document.ValueKind is JsonValueKind.Object or JsonValueKind.Array
            ? env.Data.Document.GetRawText()
            : "";
        if (string.IsNullOrWhiteSpace(raw)) return null;

        var doc = LabelDocumentJson.Parse(raw);
        if (!string.IsNullOrWhiteSpace(env.Data.Name))
            doc.Name = env.Data.Name;
        return doc;
    }
}
