using LabelUp.Editor.Models;
using SkiaSharp;

namespace LabelUp.Editor.Services;

public sealed record EditorFontSpec(
    string Id, string Label, string Group, string[] RegularUrls,
    string[]? BoldUrls = null, string[]? ItalicUrls = null);

/// <summary>무료 TTF/OTF를 필요할 때만 불러온다. WASM에는 시스템 한글 글꼴이 없다.</summary>
public sealed class FontCatalog : IAsyncDisposable
{
    public const float MmPerPt = 0.3528f;

    private readonly HttpClient _http;
    private readonly SemaphoreSlim _gate = new(1, 1);
    private SKTypeface? _regular;
    private SKTypeface? _bold;
    private SKTypeface? _symbols;
    private bool _loaded;
    private readonly Dictionary<string, SKTypeface?> _faces = new(StringComparer.OrdinalIgnoreCase);
    private readonly Dictionary<string, SKTypeface?> _boldFaces = new(StringComparer.OrdinalIgnoreCase);
    private readonly Dictionary<string, SKTypeface?> _italicFaces = new(StringComparer.OrdinalIgnoreCase);
    private readonly HashSet<string> _loading = new(StringComparer.OrdinalIgnoreCase);

    public FontCatalog(HttpClient http) => _http = http;

    public bool IsReady => _loaded && _regular is not null;

    public IReadOnlyList<EditorFontSpec> Choices { get; } = Specs;

    private static string Local(string file) => "fonts/" + file;
    private static string Gf(string path)
        => "https://raw.githubusercontent.com/google/fonts/main/" + path;
    private static string Gh(string repoPath)
        => "https://raw.githubusercontent.com/" + repoPath;

    private static readonly string[] PretendardReg =
    [
        Local("Pretendard-Regular.otf"),
        Gh("orioncactus/pretendard/v1.3.9/packages/pretendard/dist/public/static/alternative/Pretendard-Regular.otf")
    ];
    private static readonly string[] PretendardBold =
    [
        Local("Pretendard-Bold.otf"),
        Gh("orioncactus/pretendard/v1.3.9/packages/pretendard/dist/public/static/alternative/Pretendard-Bold.otf")
    ];
    private static readonly string[] NotoSansReg =
    [
        Local("NotoSansKR-Regular.otf"),
        Gh("googlefonts/noto-cjk/Sans2.004/Sans/SubsetOTF/KR/NotoSansKR-Regular.otf"),
        Gf("ofl/notosanskr/NotoSansKR%5Bwght%5D.ttf")
    ];
    private static readonly string[] NotoSansBold =
    [
        Local("NotoSansKR-Bold.otf"),
        Gh("googlefonts/noto-cjk/Sans2.004/Sans/SubsetOTF/KR/NotoSansKR-Bold.otf")
    ];
    private static readonly string[] NotoSerifReg =
    [
        Local("NotoSerifKR-Regular.otf"),
        Gh("googlefonts/noto-cjk/Serif2.002/Serif/SubsetOTF/KR/NotoSerifKR-Regular.otf"),
        Gf("ofl/notoserifkr/NotoSerifKR%5Bwght%5D.ttf")
    ];
    private static readonly string[] NotoSerifBold =
    [
        Local("NotoSerifKR-Bold.otf"),
        Gh("googlefonts/noto-cjk/Serif2.002/Serif/SubsetOTF/KR/NotoSerifKR-Bold.otf")
    ];
    private static readonly string[] NanumGothicReg =
    [
        Local("NanumGothic.ttf"),
        Gf("ofl/nanumgothic/NanumGothic-Regular.ttf")
    ];
    private static readonly string[] NanumGothicBold =
    [
        Local("NanumGothicBold.ttf"),
        Gf("ofl/nanumgothic/NanumGothic-Bold.ttf")
    ];
    private static readonly string[] NanumMyeongReg =
    [
        Local("NanumMyeongjo.ttf"),
        Gf("ofl/nanummyeongjo/NanumMyeongjo-Regular.ttf")
    ];
    private static readonly string[] NanumMyeongBold =
    [
        Local("NanumMyeongjoBold.ttf"),
        Gf("ofl/nanummyeongjo/NanumMyeongjo-Bold.ttf")
    ];
    private static readonly string[] LiberationSansReg =
    [
        Local("LiberationSans-Regular.ttf"),
        ..PretendardReg
    ];
    private static readonly string[] LiberationSansBold =
    [
        Local("LiberationSans-Bold.ttf"),
        ..PretendardBold
    ];
    private static readonly string[] LiberationNarrowReg =
    [
        Local("LiberationSansNarrow-Regular.ttf"),
        Gf("ofl/archivonarrow/ArchivoNarrow-Regular.ttf"),
        Gf("ofl/archivonarrow/ArchivoNarrow%5Bwght%5D.ttf")
    ];
    private static readonly string[] LiberationNarrowBold =
    [
        Local("LiberationSansNarrow-Bold.ttf"),
        Gf("ofl/archivonarrow/ArchivoNarrow-Bold.ttf")
    ];
    private static readonly string[] LiberationNarrowItalic =
    [
        Local("LiberationSansNarrow-Italic.ttf"),
        Gf("ofl/archivonarrow/ArchivoNarrow-Italic.ttf")
    ];

    private static EditorFontSpec F(string id, string group, string[] regular, string[]? bold = null, string[]? italic = null)
        => new(id, id, group, regular, bold, italic);

    private static readonly EditorFontSpec[] Specs =
    [
        F("Pretendard", "기본", PretendardReg, PretendardBold),

        F("맑은 고딕", "한글 고딕", NotoSansReg, NotoSansBold),
        F("Malgun Gothic", "한글 고딕", NotoSansReg, NotoSansBold),
        F("Noto Sans KR", "한글 고딕", NotoSansReg, NotoSansBold),
        F("나눔고딕", "한글 고딕", NanumGothicReg, NanumGothicBold),
        F("Nanum Gothic", "한글 고딕", NanumGothicReg, NanumGothicBold),
        F("굴림", "한글 고딕", NanumGothicReg, NanumGothicBold),
        F("돋움", "한글 고딕", NanumGothicReg, NanumGothicBold),
        F("Gothic A1", "한글 고딕", [Local("GothicA1-Regular.ttf"), Gf("ofl/gothica1/GothicA1-Regular.ttf")], [Gf("ofl/gothica1/GothicA1-Bold.ttf")]),
        F("IBM Plex Sans KR", "한글 고딕", [Local("IBMPlexSansKR-Regular.ttf"), Gf("ofl/ibmplexsanskr/IBMPlexSansKR-Regular.ttf")], [Gf("ofl/ibmplexsanskr/IBMPlexSansKR-Bold.ttf")]),
        F("Black Han Sans", "한글 고딕", [Gf("ofl/blackhansans/BlackHanSans-Regular.ttf")]),
        F("Do Hyeon", "한글 고딕", [Gf("ofl/dohyeon/DoHyeon-Regular.ttf")]),
        F("Jua", "한글 고딕", [Gf("ofl/jua/Jua-Regular.ttf")]),
        F("Orbit", "한글 고딕", [Gf("ofl/orbit/Orbit-Regular.ttf")]),
        F("Sunflower", "한글 고딕", [Gf("ofl/sunflower/Sunflower-Medium.ttf")]),

        F("바탕", "명조·손글씨", NanumMyeongReg, NanumMyeongBold),
        F("궁서", "명조·손글씨", NanumMyeongReg, NanumMyeongBold),
        F("나눔명조", "명조·손글씨", NanumMyeongReg, NanumMyeongBold),
        F("Nanum Myeongjo", "명조·손글씨", NanumMyeongReg, NanumMyeongBold),
        F("Noto Serif KR", "명조·손글씨", NotoSerifReg, NotoSerifBold),
        F("Gowun Batang", "명조·손글씨", [Gf("ofl/gowunbatang/GowunBatang-Regular.ttf")], [Gf("ofl/gowunbatang/GowunBatang-Bold.ttf")]),
        F("Gowun Dodum", "명조·손글씨", [Gf("ofl/gowundodum/GowunDodum-Regular.ttf")]),
        F("Song Myung", "명조·손글씨", [Gf("ofl/songmyung/SongMyung-Regular.ttf")]),
        F("Hahmlet", "명조·손글씨", [Gf("ofl/hahmlet/Hahmlet%5Bwght%5D.ttf")]),
        F("휴먼편지체", "명조·손글씨", [Gf("ofl/gaegu/Gaegu-Regular.ttf"), Gf("ofl/nanumpenscript/NanumPenScript-Regular.ttf")]),
        F("Gaegu", "명조·손글씨", [Gf("ofl/gaegu/Gaegu-Regular.ttf")]),
        F("Nanum Pen Script", "명조·손글씨", [Gf("ofl/nanumpenscript/NanumPenScript-Regular.ttf")]),
        F("Nanum Brush Script", "명조·손글씨", [Gf("ofl/nanumbrushscript/NanumBrushScript-Regular.ttf")]),
        F("Hi Melody", "명조·손글씨", [Gf("ofl/himelody/HiMelody-Regular.ttf")]),
        F("Poor Story", "명조·손글씨", [Gf("ofl/poorstory/PoorStory-Regular.ttf")]),
        F("Single Day", "명조·손글씨", [Gf("ofl/singleday/SingleDay-Regular.ttf")]),
        F("Stylish", "명조·손글씨", [Gf("ofl/stylish/Stylish-Regular.ttf")]),
        F("Gamja Flower", "명조·손글씨", [Gf("ofl/gamjaflower/GamjaFlower-Regular.ttf")]),
        F("Cute Font", "명조·손글씨", [Gf("ofl/cutefont/CuteFont-Regular.ttf")]),
        F("Kirang Haerang", "명조·손글씨", [Gf("ofl/kiranghaerang/KirangHaerang-Regular.ttf")]),
        F("East Sea Dokdo", "명조·손글씨", [Gf("ofl/eastseadokdo/EastSeaDokdo-Regular.ttf")]),
        F("Bagel Fat One", "명조·손글씨", [Gf("ofl/bagelfatone/BagelFatOne-Regular.ttf")]),
        F("Gasoek One", "명조·손글씨", [Gf("ofl/gasoekone/GasoekOne-Regular.ttf")]),
        F("Grandiflora One", "명조·손글씨", [Gf("ofl/grandifloraone/GrandifloraOne-Regular.ttf")]),
        F("Diphylleia", "명조·손글씨", [Gf("ofl/diphylleia/Diphylleia-Regular.ttf")]),

        F("Arial", "영문", LiberationSansReg, LiberationSansBold),
        F("Arial Narrow", "영문", LiberationNarrowReg, LiberationNarrowBold, LiberationNarrowItalic),
        F("Times New Roman", "영문", NotoSerifReg, NotoSerifBold),
        F("Georgia", "영문", NotoSerifReg, NotoSerifBold),
        F("Calibri", "영문", PretendardReg, PretendardBold),
        F("Tahoma", "영문", PretendardReg, PretendardBold),
        F("Verdana", "영문", PretendardReg, PretendardBold),
        F("Inter", "영문", [Gf("ofl/inter/Inter%5Bopsz%2Cwght%5D.ttf"), Local("Pretendard-Regular.otf")]),
        F("Roboto", "영문", [Gf("apache/roboto/Roboto%5Bwdth%2Cwght%5D.ttf"), Gf("apache/roboto/static/Roboto-Regular.ttf")], [Gf("apache/roboto/static/Roboto-Bold.ttf")]),
        F("Open Sans", "영문", [Gf("ofl/opensans/OpenSans%5Bwdth%2Cwght%5D.ttf")]),
        F("Lato", "영문", [Gf("ofl/lato/Lato-Regular.ttf")], [Gf("ofl/lato/Lato-Bold.ttf")]),
        F("Montserrat", "영문", [Gf("ofl/montserrat/Montserrat%5Bwght%5D.ttf")]),
        F("Poppins", "영문", [Gf("ofl/poppins/Poppins-Regular.ttf")], [Gf("ofl/poppins/Poppins-Bold.ttf")]),
        F("Nunito", "영문", [Gf("ofl/nunito/Nunito%5Bwght%5D.ttf")]),
        F("Oswald", "영문", [Gf("ofl/oswald/Oswald%5Bwght%5D.ttf")]),
        F("Raleway", "영문", [Gf("ofl/raleway/Raleway%5Bwght%5D.ttf")]),
        F("Ubuntu", "영문", [Gf("ofl/ubuntu/Ubuntu-Regular.ttf")], [Gf("ofl/ubuntu/Ubuntu-Bold.ttf")]),
        F("PT Sans", "영문", [Gf("ofl/ptsans/PT_Sans-Web-Regular.ttf")], [Gf("ofl/ptsans/PT_Sans-Web-Bold.ttf")]),
        F("PT Serif", "영문", [Gf("ofl/ptserif/PT_Serif-Web-Regular.ttf")], [Gf("ofl/ptserif/PT_Serif-Web-Bold.ttf")]),
        F("Playfair Display", "영문", [Gf("ofl/playfairdisplay/PlayfairDisplay%5Bwght%5D.ttf")]),
        F("Merriweather", "영문", [Gf("ofl/merriweather/Merriweather-Regular.ttf")], [Gf("ofl/merriweather/Merriweather-Bold.ttf")]),
        F("Libre Baskerville", "영문", [Gf("ofl/librebaskerville/LibreBaskerville-Regular.ttf")], [Gf("ofl/librebaskerville/LibreBaskerville-Bold.ttf")]),
        F("Source Sans 3", "영문", [Gf("ofl/sourcesans3/SourceSans3%5Bwght%5D.ttf")]),

        F("Inconsolata", "고정폭", [Gf("ofl/inconsolata/Inconsolata%5Bwdth%2Cwght%5D.ttf")]),
        F("Courier Prime", "고정폭", [Gf("ofl/courierprime/CourierPrime-Regular.ttf")], [Gf("ofl/courierprime/CourierPrime-Bold.ttf")]),
        F("Courier New", "고정폭", [Gf("ofl/courierprime/CourierPrime-Regular.ttf")])
    ];

    private static readonly Dictionary<string, string> Aliases = new(StringComparer.OrdinalIgnoreCase)
    {
        ["Gulim"] = "굴림",
        ["Dotum"] = "돋움",
        ["Batang"] = "바탕",
        ["Gungsuh"] = "궁서",
        ["NanumGothic"] = "나눔고딕",
        ["NanumMyeongjo"] = "나눔명조",
        ["Liberation Sans"] = "Arial",
        ["Liberation Sans Narrow"] = "Arial Narrow",
        ["Times"] = "Times New Roman",
        ["Courier"] = "Courier New"
    };

    public static string CanonicalId(string? family)
    {
        var name = StripStyleSuffixes((family ?? "").Trim().TrimStart('@'));
        if (name.Length == 0) return "Pretendard";
        if (Aliases.TryGetValue(name, out var alias))
            name = alias;
        foreach (var spec in Specs)
        {
            if (spec.Id.Equals(name, StringComparison.OrdinalIgnoreCase))
                return spec.Id;
        }
        return name;
    }

    /// <summary>폼텍 LOGFONT 이름에서 Bold/Italic 접미를 떼고 객체 스타일에 반영한다.</summary>
    public static void ApplyImportedFamily(DesignObject obj, string? raw)
    {
        var name = (raw ?? "").Trim().TrimStart('@');
        if (name.Length == 0) return;
        var lower = name.Replace('-', ' ');
        if (lower.Contains("bold", StringComparison.OrdinalIgnoreCase)
            || lower.Contains("굵게", StringComparison.OrdinalIgnoreCase))
            obj.Bold = true;
        if (lower.Contains("italic", StringComparison.OrdinalIgnoreCase)
            || lower.Contains("oblique", StringComparison.OrdinalIgnoreCase)
            || lower.Contains("기울", StringComparison.OrdinalIgnoreCase))
            obj.Italic = true;
        obj.FontFamily = CanonicalId(name);
    }

    private static string StripStyleSuffixes(string name)
    {
        if (name.Length == 0) return name;
        string[] tails =
        [
            "Bold Italic", "Bold Oblique", "BoldItalic", "BoldOblique",
            "Italic", "Oblique", "Bold", "Regular",
            "굵게 기울임", "기울임", "굵게"
        ];
        var changed = true;
        while (changed)
        {
            changed = false;
            foreach (var tail in tails)
            {
                if (name.Length <= tail.Length) continue;
                if (name.EndsWith(tail, StringComparison.OrdinalIgnoreCase)
                    && name[name.Length - tail.Length - 1] is ' ' or '-')
                {
                    name = name[..(name.Length - tail.Length)].TrimEnd(' ', '-');
                    changed = true;
                    break;
                }
            }
        }
        return name.Trim();
    }

    public static bool IsKnownFamily(string? family)
        => Specs.Any(s => s.Id.Equals(CanonicalId(family), StringComparison.OrdinalIgnoreCase));

    public static IEnumerable<IGrouping<string, EditorFontSpec>> GroupedChoices(string? extraFamily = null, string? filter = null)
    {
        var q = (filter ?? "").Trim();
        var list = new List<EditorFontSpec>(Specs);
        var extra = (extraFamily ?? "").Trim();
        if (extra.Length > 0 && !list.Any(s => s.Id.Equals(extra, StringComparison.OrdinalIgnoreCase)))
            list.Insert(0, new EditorFontSpec(extra, extra + " (문서)", "현재", []));

        IEnumerable<EditorFontSpec> items = list;
        if (q.Length > 0)
        {
            items = list.Where(s =>
                s.Id.Contains(q, StringComparison.OrdinalIgnoreCase)
                || s.Label.Contains(q, StringComparison.OrdinalIgnoreCase)
                || s.Group.Contains(q, StringComparison.OrdinalIgnoreCase));
            if (extra.Length > 0 && items.All(s => !s.Id.Equals(extra, StringComparison.OrdinalIgnoreCase)))
            {
                var current = list.First(s => s.Id.Equals(extra, StringComparison.OrdinalIgnoreCase));
                items = items.Prepend(current);
            }
        }

        return items.GroupBy(s => s.Group);
    }

    public static float ToPt(float mm) => mm / MmPerPt;
    public static float FromPt(float pt) => Math.Clamp(pt * MmPerPt, 1f, 40f);

    public static bool IsNarrowFamily(string? family)
    {
        var raw = (family ?? "").Trim();
        var id = CanonicalId(raw);
        return id.Equals("Arial Narrow", StringComparison.OrdinalIgnoreCase)
            || raw.Contains("Narrow", StringComparison.OrdinalIgnoreCase)
            || raw.Contains("좁", StringComparison.OrdinalIgnoreCase);
    }

    public static float WidthScale(string? family)
        => IsNarrowFamily(family) ? 0.78f : 1f;

    /// <summary>실제 Narrow 얼굴을 불러왔으면 다시 누르지 않는다. 기본 글꼴 대체일 때만 가로로 줄인다.</summary>
    public float WidthScaleFor(string? family)
    {
        if (!IsNarrowFamily(family)) return 1f;
        var id = CanonicalId(family);
        if (_faces.TryGetValue(id, out var face) && face is not null && !ReferenceEquals(face, _regular))
            return 1f;
        return 0.78f;
    }

    public bool HasItalicFace(string? family)
    {
        var id = CanonicalId(family);
        return _italicFaces.TryGetValue(id, out var face) && face is not null;
    }

    public async Task EnsureLoadedAsync()
    {
        if (_loaded) return;
        _regular = await LoadFirstAsync(PretendardReg);
        _bold = await LoadFirstAsync(PretendardBold) ?? _regular;
        _symbols = await LoadAsync(Local("FormtecSymbols.otf"));
        _faces["Pretendard"] = _regular;
        _boldFaces["Pretendard"] = _bold;
        _loaded = true;
    }

    public bool IsFamilyReady(string? family)
    {
        var id = CanonicalId(family);
        return _faces.TryGetValue(id, out var face) && face is not null;
    }

    public async Task<bool> EnsureFamilyAsync(string? family)
    {
        try
        {
            var id = CanonicalId(family);
            if (_faces.ContainsKey(id))
                return false;
            if (!_loading.Add(id)) return false;

            try
            {
                var spec = Specs.FirstOrDefault(s => s.Id.Equals(id, StringComparison.OrdinalIgnoreCase));
                if (spec is null || spec.RegularUrls.Length == 0)
                {
                    _faces[id] = _regular;
                    return false;
                }

                var face = await LoadFirstAsync(spec.RegularUrls);
                _faces[id] = face ?? _regular;
                if (spec.BoldUrls is { Length: > 0 })
                    _boldFaces[id] = await LoadFirstAsync(spec.BoldUrls) ?? face ?? _bold;
                if (spec.ItalicUrls is { Length: > 0 })
                    _italicFaces[id] = await LoadFirstAsync(spec.ItalicUrls);
                var ok = face is not null;
                EditorLog.Info(ok ? $"글꼴 로드: {id}" : $"글꼴 로드 실패, 기본 글꼴 사용: {id}");
                return ok;
            }
            finally
            {
                _loading.Remove(id);
            }
        }
        catch (Exception ex)
        {
            EditorLog.Error("글꼴 로드 실패", ex);
            return false;
        }
    }

    public async Task<bool> EnsureFamiliesAsync(IEnumerable<string?> families)
    {
        var any = false;
        foreach (var family in families.Distinct(StringComparer.OrdinalIgnoreCase))
        {
            if (await EnsureFamilyAsync(family))
                any = true;
        }
        return any;
    }

    public SKTypeface Resolve(bool bold = false, int codepoint = 0)
        => Resolve(null, bold, codepoint);

    public SKTypeface Resolve(string? family, bool bold = false, int codepoint = 0, bool italic = false)
    {
        var id = CanonicalId(family);
        SKTypeface? primary = null;
        if (italic && _italicFaces.TryGetValue(id, out var it) && it is not null)
            primary = it;
        else if (bold && _boldFaces.TryGetValue(id, out var bf) && bf is not null)
            primary = bf;
        else if (_faces.TryGetValue(id, out var rf) && rf is not null)
            primary = rf;
        primary ??= bold && _bold is not null ? _bold : _regular;

        if (codepoint > 0 && HasGlyph(_symbols, codepoint)
            && (PrefersSymbolRange(codepoint) || !HasGlyph(primary, codepoint)))
            return _symbols!;
        if (codepoint > 0 && !HasGlyph(primary, codepoint) && HasGlyph(_regular, codepoint))
            return bold && _bold is not null && HasGlyph(_bold, codepoint) ? _bold : _regular!;
        if (primary is not null) return primary;
        return SKTypeface.Default;
    }

    public static bool PrefersSymbolRange(int codepoint)
        => codepoint is >= 0x2500 and <= 0x257F
            or >= 0x25A0 and <= 0x25FF
            or >= 0x3000 and <= 0x303F
            or >= 0x3300 and <= 0x33FF
            or >= 0x2070 and <= 0x209F
            or 0x00B2 or 0x00B3 or 0x03A9 or 0x6C34;

    public SKTypeface? Symbols => _symbols;

    private async Task<SKTypeface?> LoadFirstAsync(IReadOnlyList<string> urls)
    {
        foreach (var url in urls)
        {
            var face = await LoadAsync(url);
            if (face is not null) return face;
        }
        return null;
    }

    private async Task<SKTypeface?> LoadAsync(string relativePath)
    {
        try
        {
            using var cts = new CancellationTokenSource(TimeSpan.FromSeconds(8));
            var bytes = await _http.GetByteArrayAsync(relativePath, cts.Token);
            if (bytes.Length < 100) return null;
            using var data = SKData.CreateCopy(bytes);
            return SKTypeface.FromData(data);
        }
        catch (Exception ex)
        {
            EditorLog.Warn($"글꼴 파일 실패: {relativePath} · {ex.Message}");
            return null;
        }
    }

    private static bool HasGlyph(SKTypeface? typeface, int codepoint)
    {
        if (typeface is null || codepoint <= 0) return false;
        try
        {
            using var font = new SKFont(typeface, 12);
            var glyphs = font.GetGlyphs(char.ConvertFromUtf32(codepoint));
            return glyphs.Length > 0 && glyphs[0] != 0;
        }
        catch
        {
            return false;
        }
    }

    public ValueTask DisposeAsync()
    {
        foreach (var face in _faces.Values.Concat(_boldFaces.Values).Concat(_italicFaces.Values))
        {
            if (face is null) continue;
            if (ReferenceEquals(face, _regular) || ReferenceEquals(face, _bold) || ReferenceEquals(face, _symbols))
                continue;
            face.Dispose();
        }
        _faces.Clear();
        _boldFaces.Clear();
        _italicFaces.Clear();
        _regular?.Dispose();
        if (!ReferenceEquals(_bold, _regular))
            _bold?.Dispose();
        _symbols?.Dispose();
        _regular = null;
        _bold = null;
        _symbols = null;
        return ValueTask.CompletedTask;
    }
}
