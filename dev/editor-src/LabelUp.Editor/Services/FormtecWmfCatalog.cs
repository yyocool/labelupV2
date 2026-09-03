using System.Net.Http.Json;
using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

/// <summary>wwwroot/assets/formtec_wmf 의 Placeable WMF 를 미리 읽어 둔다.</summary>
public sealed class FormtecWmfCatalog(HttpClient http)
{
    private const string IndexUrl = "assets/formtec_wmf/index.json";
    private readonly Dictionary<string, byte[]> _files = new(StringComparer.OrdinalIgnoreCase);
    private bool _loaded;

    public async Task EnsureLoadedAsync()
    {
        if (_loaded) return;
        try
        {
            var names = await http.GetFromJsonAsync<List<string>>(IndexUrl) ?? [];
            foreach (var name in names)
            {
                if (string.IsNullOrWhiteSpace(name)) continue;
                try
                {
                    var bytes = await http.GetByteArrayAsync("assets/formtec_wmf/" + name);
                    if (bytes.Length > 22)
                        _files[name] = bytes;
                }
                catch (Exception ex)
                {
                    EditorLog.Warn($"폼텍 WMF 로드 실패: {name} ({ex.Message})");
                }
            }

            EditorLog.Info($"폼텍 WMF {_files.Count}개 로드");
        }
        catch (Exception ex)
        {
            EditorLog.Warn("폼텍 WMF 목록 로드 실패: " + ex.Message);
        }

        _loaded = true;
    }

    public bool TryResolve(string? fileName, out byte[] data)
    {
        data = [];
        if (string.IsNullOrWhiteSpace(fileName) || _files.Count == 0)
            return false;

        var name = Path.GetFileName(fileName.Trim());
        if (_files.TryGetValue(name, out var found))
        {
            data = found;
            return true;
        }

        foreach (var kv in _files)
        {
            if (kv.Key.Equals(name, StringComparison.OrdinalIgnoreCase))
            {
                data = kv.Value;
                return true;
            }
        }

        var stem = Path.GetFileNameWithoutExtension(name);
        if (_files.TryGetValue(stem + ".wmf", out found) || _files.TryGetValue(stem + ".WMF", out found))
        {
            data = found;
            return true;
        }

        return false;
    }

    public bool TrySvgPath(string? fileName, float labelW, float labelH, out string path)
        => TryConvert(fileName, labelW, labelH, out path, out _);

    public bool TryConvert(string? fileName, float labelW, float labelH, out string outer, out List<PaperGuidePath> guides)
    {
        outer = "";
        guides = [];
        if (!TryResolve(fileName, out var data)) return false;
        return FormtecWmfShape.TryConvert(data, labelW, labelH, out outer, out guides);
    }
}
