using System.IO.Compression;
using System.Text;

namespace LabelUp.Editor.Services;

/// <summary>Font Awesome Free 6.5.2 SVG path. 아이라벨 Cont 이름과 슬러그를 맞춘다.</summary>
public sealed class FontAwesomeCatalog(HttpClient http)
{
    public static FontAwesomeCatalog? Current { get; private set; }

    private readonly Dictionary<string, string> _paths = new(StringComparer.OrdinalIgnoreCase);
    private bool _loaded;

    public bool IsReady => _loaded && _paths.Count > 0;

    public static bool TryResolve(string? name, out string path)
    {
        path = "";
        return Current?.TryGetPath(name, out path) == true;
    }

    public async Task EnsureLoadedAsync()
    {
        if (_loaded) return;
        _loaded = true;
        Current = this;
        try
        {
            await using var stream = await http.GetStreamAsync("assets/fontawesome/fa-free-6.5.2.catalog.gz");
            await using var gz = new GZipStream(stream, CompressionMode.Decompress);
            using var reader = new StreamReader(gz, Encoding.UTF8);
            var glyphs = 0;
            while (await reader.ReadLineAsync() is { } line)
            {
                if (line.Length == 0 || line[0] == '#') continue;
                var parts = line.Split('\t');
                if (parts.Length >= 4 && parts[1] == ">")
                {
                    var alias = Normalize(parts[0]);
                    var target = Normalize(parts[2]);
                    if (alias.Length > 0 && _paths.TryGetValue(target, out var aliased))
                        _paths.TryAdd(alias, aliased);
                    continue;
                }

                if (parts.Length < 5) continue;
                var slug = Normalize(parts[0]);
                var paths = parts[4].Split('\u001e', StringSplitOptions.RemoveEmptyEntries);
                if (slug.Length == 0 || paths.Length == 0) continue;
                var d = string.Join(" ", paths);
                if (_paths.TryAdd(slug, d))
                {
                    glyphs++;
                    var compact = slug.Replace("-", "", StringComparison.Ordinal);
                    if (compact != slug)
                        _paths.TryAdd(compact, d);
                }

                var style = parts[1] switch { "b" => "brands", "r" => "regular", _ => "solid" };
                _paths.TryAdd($"{slug}-{style}", d);
            }

            EditorLog.Info($"Font Awesome 카탈로그: {glyphs}개");
        }
        catch (Exception ex)
        {
            EditorLog.Error("Font Awesome 카탈로그 로드 실패", ex);
        }
    }

    public bool TryGetPath(string? name, out string path)
    {
        path = "";
        if (string.IsNullOrWhiteSpace(name) || _paths.Count == 0) return false;
        foreach (var key in CandidateKeys(name))
        {
            if (_paths.TryGetValue(key, out var d) && d.Length > 0)
            {
                path = d;
                return true;
            }
        }

        return false;
    }

    private static IEnumerable<string> CandidateKeys(string name)
    {
        var kebab = Normalize(name);
        if (kebab.Length > 0) yield return kebab;
        var fromPascal = Normalize(ToKebabFromPascal(name.Trim()));
        if (fromPascal.Length > 0 && fromPascal != kebab) yield return fromPascal;
        var compact = kebab.Replace("-", "", StringComparison.Ordinal);
        if (compact.Length > 0 && compact != kebab) yield return compact;
    }

    private static string Normalize(string name)
    {
        var sb = new StringBuilder(name.Length);
        foreach (var ch in name.Trim().ToLowerInvariant())
        {
            if (char.IsLetterOrDigit(ch))
                sb.Append(ch);
            else if (ch is '-' or '_' or ' ' or '.')
            {
                if (sb.Length > 0 && sb[^1] != '-')
                    sb.Append('-');
            }
        }

        return sb.ToString().Trim('-');
    }

    private static string ToKebabFromPascal(string name)
    {
        var sb = new StringBuilder(name.Length + 8);
        for (var i = 0; i < name.Length; i++)
        {
            var ch = name[i];
            if (i > 0 && char.IsUpper(ch) && (char.IsLower(name[i - 1]) || i + 1 < name.Length && char.IsLower(name[i + 1])))
                sb.Append('-');
            sb.Append(ch);
        }

        return sb.ToString();
    }
}
