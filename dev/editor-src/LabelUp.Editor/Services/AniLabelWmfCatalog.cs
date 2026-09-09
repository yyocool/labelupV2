using System.Net.Http.Json;
using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

/// <summary>
/// editor-src/anylabelWMF 의 Placeable WMF.
/// 빌드 시 wwwroot/assets/anylabel_wmf 로 복사된다. 목록에 없는 파일은 요청하지 않는다.
/// </summary>
public sealed class AniLabelWmfCatalog(HttpClient http)
{
    public const string MissingMessage = "애니라벨 용지 모양 파일(wmf)가 없습니다.";
    private const string IndexUrl = "assets/anylabel_wmf/index.json";
    private const string AssetDir = "assets/anylabel_wmf/";
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
                    var bytes = await http.GetByteArrayAsync(AssetDir + file);
                    if (bytes.Length > 22)
                        _files[file] = bytes;
                }
                catch (Exception ex)
                {
                    EditorLog.Warn($"애니라벨 WMF 로드 실패: {file} ({ex.Message})");
                }
            }

            EditorLog.Info($"애니라벨 WMF {_files.Count}개 로드 (anylabelWMF)");
        }
        catch (Exception ex)
        {
            EditorLog.Warn("애니라벨 WMF 목록 로드 실패: " + ex.Message);
        }

        _loaded = true;
    }

    public bool Has(string? fileName)
    {
        if (string.IsNullOrWhiteSpace(fileName) || _files.Count == 0)
            return false;
        return TryResolve(fileName, out _);
    }

    public bool TryResolve(string? fileName, out byte[] data)
    {
        data = [];
        if (string.IsNullOrWhiteSpace(fileName) || _files.Count == 0)
            return false;

        foreach (var key in CandidateNames(fileName))
        {
            if (_files.TryGetValue(key, out var found))
            {
                data = found;
                return true;
            }
        }

        return false;
    }

    public bool TryConvert(string? fileName, float labelW, float labelH, out string outer, out List<PaperGuidePath> guides)
    {
        outer = "";
        guides = [];
        if (!TryResolve(fileName, out var data)) return false;
        return FormtecWmfShape.TryConvert(data, labelW, labelH, out outer, out guides);
    }

    private static IEnumerable<string> CandidateNames(string fileName)
    {
        var name = Path.GetFileName(fileName.Trim());
        yield return name;
        var stem = Path.GetFileNameWithoutExtension(name);
        yield return stem + ".wmf";
        yield return stem + ".WMF";

        var cut = stem.IndexOf('(');
        if (cut > 0)
        {
            var baseStem = stem[..cut].Trim();
            yield return baseStem + ".wmf";
            yield return baseStem + ".WMF";
        }
    }
}
