using System.IO.Compression;
using System.Text;

namespace LabelUp.Editor.Services;

public sealed record FaGlyph(string Slug, string Style, int Width, int Height, string Path);

/// <summary>Font Awesome Free 6.5.2 SVG path. 아이라벨 Cont 이름과 슬러그를 맞춘다.</summary>
public sealed class FontAwesomeCatalog(HttpClient http)
{
    public static FontAwesomeCatalog? Current { get; private set; }

    private readonly Dictionary<string, string> _paths = new(StringComparer.OrdinalIgnoreCase);
    private readonly List<FaGlyph> _glyphs = [];
    private bool _loaded;

    public bool IsReady => _loaded && _paths.Count > 0;
    public IReadOnlyList<FaGlyph> Glyphs => _glyphs;
    public int GlyphCount => _glyphs.Count;

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
                var styleKey = parts[1] switch { "b" => "brands", "r" => "regular", _ => "solid" };
                if (!int.TryParse(parts[2], out var w)) w = 512;
                if (!int.TryParse(parts[3], out var h)) h = 512;
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

                _paths.TryAdd($"{slug}-{styleKey}", d);
                // Prefer solid for bare slug; only overwrite if not yet set (solid usually first in file)
                _glyphs.Add(new FaGlyph(slug, styleKey, Math.Max(1, w), Math.Max(1, h), d));
            }

            EditorLog.Info($"Font Awesome 카탈로그: {glyphs}개 (피커 {_glyphs.Count})");
        }
        catch (Exception ex)
        {
            EditorLog.Error("Font Awesome 카탈로그 로드 실패", ex);
        }
    }

    public IReadOnlyList<FaGlyph> Search(string? query, string style = "solid", int skip = 0, int take = 96)
    {
        if (_glyphs.Count == 0) return [];
        skip = Math.Max(0, skip);
        take = Math.Clamp(take, 1, 300);

        var styleKey = NormalizeStyle(style);
        IEnumerable<FaGlyph> q = _glyphs;
        if (!string.IsNullOrEmpty(styleKey))
            q = q.Where(g => g.Style == styleKey);

        if (!string.IsNullOrWhiteSpace(query))
        {
            var needle = Normalize(query);
            if (needle.Length > 0)
            {
                var compact = needle.Replace("-", "", StringComparison.Ordinal);
                q = q.Where(g =>
                    g.Slug.Contains(needle, StringComparison.OrdinalIgnoreCase)
                    || g.Slug.Replace("-", "", StringComparison.Ordinal)
                        .Contains(compact, StringComparison.OrdinalIgnoreCase));
            }
        }

        return q.Skip(skip).Take(take).ToList();
    }

    public int Count(string? query, string style = "solid")
    {
        if (_glyphs.Count == 0) return 0;
        var styleKey = NormalizeStyle(style);
        IEnumerable<FaGlyph> q = _glyphs;
        if (!string.IsNullOrEmpty(styleKey))
            q = q.Where(g => g.Style == styleKey);

        if (!string.IsNullOrWhiteSpace(query))
        {
            var needle = Normalize(query);
            if (needle.Length > 0)
            {
                var compact = needle.Replace("-", "", StringComparison.Ordinal);
                q = q.Where(g =>
                    g.Slug.Contains(needle, StringComparison.OrdinalIgnoreCase)
                    || g.Slug.Replace("-", "", StringComparison.Ordinal)
                        .Contains(compact, StringComparison.OrdinalIgnoreCase));
            }
        }

        return q.Count();
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

    private static string NormalizeStyle(string? style)
    {
        var s = (style ?? "").Trim().ToLowerInvariant();
        return s switch
        {
            "" or "all" or "*" => "",
            "s" or "solid" => "solid",
            "r" or "regular" => "regular",
            "b" or "brands" or "brand" => "brands",
            _ => s
        };
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
