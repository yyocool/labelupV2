using System.Net.Http.Json;
using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

/// <summary>
/// editor-src/formtecWmf 의 Placeable WMF.
/// 빌드 시 wwwroot/assets/formtec_wmf 로 복사된다. 목록에 없는 파일은 요청하지 않는다.
/// </summary>
public sealed class FormtecWmfCatalog(HttpClient http)
{
    public const string MissingMessage = "폼텍 용지 모양 파일(wmf)가 없습니다.";
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
                var file = Path.GetFileName(name.Trim());
                if (file.Length == 0) continue;
                try
                {
                    var bytes = await http.GetByteArrayAsync("assets/formtec_wmf/" + file);
                    if (bytes.Length > 22)
                        _files[file] = bytes;
                }
                catch (Exception ex)
                {
                    EditorLog.Warn($"폼텍 WMF 로드 실패: {file} ({ex.Message})");
                }
            }

            EditorLog.Info($"폼텍 WMF {_files.Count}개 로드 (formtecWmf)");
        }
        catch (Exception ex)
        {
            EditorLog.Warn("폼텍 WMF 목록 로드 실패: " + ex.Message);
        }

        _loaded = true;
    }

    public bool Has(string? fileName)
    {
        if (string.IsNullOrWhiteSpace(fileName) || _files.Count == 0)
            return false;
        var name = Path.GetFileName(fileName.Trim());
        if (_files.ContainsKey(name)) return true;
        var stem = Path.GetFileNameWithoutExtension(name);
        return _files.ContainsKey(stem + ".wmf") || _files.ContainsKey(stem + ".WMF");
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
