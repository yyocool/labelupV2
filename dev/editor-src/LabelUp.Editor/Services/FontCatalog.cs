using LabelUp.Editor.Models;
using Microsoft.JSInterop;
using SkiaSharp;

namespace LabelUp.Editor.Services;

public sealed record EditorFontSpec(
    string Id, string Label, string Group, string[] RegularUrls,
    string[]? BoldUrls = null, string[]? ItalicUrls = null,
    bool Picker = true);

/// <summary>웹 글꼴 URL과, Chrome/Edge의 로컬 설치 글꼴을 필요할 때만 불러온다.</summary>
public sealed class FontCatalog : IAsyncDisposable
{
    public const float MmPerPt = 0.3528f;

    private readonly HttpClient _http;
    private readonly IJSRuntime _js;
    private readonly SemaphoreSlim _gate = new(1, 1);
    private SKTypeface? _regular;
    private SKTypeface? _bold;
    private SKTypeface? _symbols;
    private bool _loaded;
    private bool _localDisabled;
    private readonly Dictionary<string, SKTypeface?> _faces = new(StringComparer.OrdinalIgnoreCase);
    private readonly Dictionary<string, SKTypeface?> _boldFaces = new(StringComparer.OrdinalIgnoreCase);
    private readonly Dictionary<string, SKTypeface?> _italicFaces = new(StringComparer.OrdinalIgnoreCase);
    private readonly HashSet<string> _loading = new(StringComparer.OrdinalIgnoreCase);
    private readonly HashSet<string> _localFaces = new(StringComparer.OrdinalIgnoreCase);

    public FontCatalog(HttpClient http, IJSRuntime js)
    {
        _http = http;
        _js = js;
    }

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

    private static EditorFontSpec F(string id, string group, string[] regular, string[]? bold = null, string[]? italic = null, bool picker = true)
        => new(id, id, group, regular, bold, italic, picker);

    /// <summary>윈도우 시스템 글꼴. 로컬 글꼴 API로 불러오고, 없으면 대체 얼굴로 그린다. 변환 문서에만 목록에 나온다.</summary>
    private static EditorFontSpec Win(string id, string group, string[] regular, string[]? bold = null, string[]? italic = null)
        => F(id, group, regular, bold, italic, picker: false);

    private static readonly EditorFontSpec[] Specs =
    [
        F("Pretendard", "기본", PretendardReg, PretendardBold),

        Win("맑은 고딕", "한글 고딕", NotoSansReg, NotoSansBold),
        F("Noto Sans KR", "한글 고딕", NotoSansReg, NotoSansBold),
        F("나눔고딕", "한글 고딕", NanumGothicReg, NanumGothicBold),
        F("Nanum Gothic", "한글 고딕", NanumGothicReg, NanumGothicBold),
        Win("굴림", "한글 고딕", NanumGothicReg, NanumGothicBold),
        Win("돋움", "한글 고딕", NanumGothicReg, NanumGothicBold),
        F("Gothic A1", "한글 고딕", [Local("GothicA1-Regular.ttf"), Gf("ofl/gothica1/GothicA1-Regular.ttf")], [Gf("ofl/gothica1/GothicA1-Bold.ttf")]),
        F("IBM Plex Sans KR", "한글 고딕", [Local("IBMPlexSansKR-Regular.ttf"), Gf("ofl/ibmplexsanskr/IBMPlexSansKR-Regular.ttf")], [Gf("ofl/ibmplexsanskr/IBMPlexSansKR-Bold.ttf")]),
        F("Black Han Sans", "한글 고딕", [Gf("ofl/blackhansans/BlackHanSans-Regular.ttf")]),
        F("Do Hyeon", "한글 고딕", [Gf("ofl/dohyeon/DoHyeon-Regular.ttf")]),
        F("Jua", "한글 고딕", [Gf("ofl/jua/Jua-Regular.ttf")]),
        F("Orbit", "한글 고딕", [Gf("ofl/orbit/Orbit-Regular.ttf")]),
        F("Sunflower", "한글 고딕", [Gf("ofl/sunflower/Sunflower-Medium.ttf")]),

        Win("바탕", "명조·손글씨", NanumMyeongReg, NanumMyeongBold),
        Win("궁서", "명조·손글씨", NanumMyeongReg, NanumMyeongBold),
        F("나눔명조", "명조·손글씨", NanumMyeongReg, NanumMyeongBold),
        F("Nanum Myeongjo", "명조·손글씨", NanumMyeongReg, NanumMyeongBold),
        F("Noto Serif KR", "명조·손글씨", NotoSerifReg, NotoSerifBold),
        F("Gowun Batang", "명조·손글씨", [Gf("ofl/gowunbatang/GowunBatang-Regular.ttf")], [Gf("ofl/gowunbatang/GowunBatang-Bold.ttf")]),
        F("Gowun Dodum", "명조·손글씨", [Gf("ofl/gowundodum/GowunDodum-Regular.ttf")]),
        F("Song Myung", "명조·손글씨", [Gf("ofl/songmyung/SongMyung-Regular.ttf")]),
        F("Hahmlet", "명조·손글씨", [Gf("ofl/hahmlet/Hahmlet%5Bwght%5D.ttf")]),
        Win("휴먼편지체", "명조·손글씨", [Gf("ofl/gaegu/Gaegu-Regular.ttf"), Gf("ofl/nanumpenscript/NanumPenScript-Regular.ttf")]),
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

        Win("Arial", "영문", LiberationSansReg, LiberationSansBold),
        Win("Arial Narrow", "영문", LiberationNarrowReg, LiberationNarrowBold, LiberationNarrowItalic),
        Win("Times New Roman", "영문", NotoSerifReg, NotoSerifBold),
        Win("Georgia", "영문", NotoSerifReg, NotoSerifBold),
        Win("Calibri", "영문", PretendardReg, PretendardBold),
        Win("Candara", "영문", PretendardReg, PretendardBold),
        Win("Tahoma", "영문", PretendardReg, PretendardBold),
        Win("Verdana", "영문", PretendardReg, PretendardBold),
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
        Win("Courier New", "고정폭", [Gf("ofl/courierprime/CourierPrime-Regular.ttf")])
    ];

    private static readonly Dictionary<string, string> Aliases = new(StringComparer.OrdinalIgnoreCase)
    {
        ["맑은 고딕 Semilight"] = "맑은 고딕",
        ["Malgun Gothic"] = "맑은 고딕",
        ["Malgun Gothic Semilight"] = "맑은 고딕",
        ["Gulim"] = "굴림",
        ["굴림체"] = "굴림",
        ["Dotum"] = "돋움",
        ["돋움체"] = "돋움",
        ["문체부 돋움체"] = "돋움",
        ["Batang"] = "바탕",
        ["BatangChe"] = "바탕",
        ["Gungsuh"] = "궁서",
        ["GungsuhChe"] = "궁서",
        ["NanumGothic"] = "나눔고딕",
        ["NanumMyeongjo"] = "나눔명조",
        ["Liberation Sans"] = "Arial",
        ["Liberation Sans Narrow"] = "Arial Narrow",
        ["Times"] = "Times New Roman",
        ["Courier"] = "Courier New"
    };

    private static readonly Dictionary<string, string[]> LocalQueryNames = new(StringComparer.OrdinalIgnoreCase)
    {
        ["맑은 고딕"] = ["맑은 고딕", "Malgun Gothic"],
        ["Malgun Gothic"] = ["Malgun Gothic", "맑은 고딕"],
        ["굴림"] = ["굴림", "굴림체", "Gulim", "GulimChe"],
        ["돋움"] = ["돋움", "돋움체", "Dotum", "DotumChe"],
        ["바탕"] = ["바탕", "Batang", "BatangChe"],
        ["궁서"] = ["궁서", "Gungsuh", "GungsuhChe"],
        ["휴먼편지체"] = ["휴먼편지체"],
        ["Arial"] = ["Arial"],
        ["Arial Narrow"] = ["Arial Narrow"],
        ["Times New Roman"] = ["Times New Roman"],
        ["Georgia"] = ["Georgia"],
        ["Calibri"] = ["Calibri"],
        ["Candara"] = ["Candara"],
        ["Tahoma"] = ["Tahoma"],
        ["Verdana"] = ["Verdana"],
        ["Courier New"] = ["Courier New"]
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
            "Semi Light", "Semilight", "Italic", "Oblique", "Bold", "Regular",
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

    /// <summary>에디터에서 고를 수 있는 웹 글꼴인가. 윈도우 전용·미등록 이름은 false.</summary>
    public static bool IsPickerFamily(string? family)
    {
        var raw = (family ?? "").Trim();
        if (raw.Length == 0) return true;
        var id = CanonicalId(raw);
        return Specs.Any(s => s.Picker && s.Id.Equals(id, StringComparison.OrdinalIgnoreCase));
    }

    public bool IsLocalFamily(string? family)
    {
        var raw = (family ?? "").Trim();
        return raw.Length > 0 && _localFaces.Contains(CanonicalId(raw));
    }

    public IEnumerable<IGrouping<string, EditorFontSpec>> GroupedChoices(string? extraFamily = null, string? filter = null)
    {
        var q = (filter ?? "").Trim();
        var list = Specs.Where(s => s.Picker).ToList();
        var raw = (extraFamily ?? "").Trim();
        var extra = raw.Length == 0 ? "" : CanonicalId(raw);
        // 변환 문서의 현재 글꼴만. 목록에서 바꿀 때는 미사용 항목이 없다.
        if (extra.Length > 0 && !list.Any(s => s.Id.Equals(extra, StringComparison.OrdinalIgnoreCase)))
        {
            var label = _localFaces.Contains(extra) ? extra : extra + " (미사용폰트)";
            list.Insert(0, new EditorFontSpec(extra, label, "현재", [], Picker: false));
        }

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

    /// <summary>에디터 웹 글꼴이 아니라 시스템에서 읽어야 하는 이름인가.</summary>
    public static bool NeedsLocalLookup(string? family)
    {
        var id = CanonicalId(family);
        if (id.Equals("Pretendard", StringComparison.OrdinalIgnoreCase))
            return false;
        var spec = Specs.FirstOrDefault(s => s.Id.Equals(id, StringComparison.OrdinalIgnoreCase));
        return spec is null || !spec.Picker;
    }

    public IReadOnlyList<string> MissingLocalFamilies(IEnumerable<string?> families)
        => families
            .Where(f => !string.IsNullOrWhiteSpace(f))
            .Select(CanonicalId)
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .Where(id => NeedsLocalLookup(id) && !IsVerifiedLocal(id))
            .OrderBy(id => id, StringComparer.OrdinalIgnoreCase)
            .ToList();

    /// <summary>윈도우 전용으로 표시할 이름. 이미 읽었는지는 보지 않는다.</summary>
    public static IReadOnlyList<string> WindowsFamilies(IEnumerable<string?> families)
        => families
            .Where(f => !string.IsNullOrWhiteSpace(f))
            .Select(CanonicalId)
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .Where(NeedsLocalLookup)
            .OrderBy(id => id, StringComparer.OrdinalIgnoreCase)
            .ToList();

    /// <summary>Noto/나눔 등 웹 대체 얼굴을 윈도우 글꼴로 취급하지 않는다.</summary>
    public void DemoteWebFallbacks(IEnumerable<string?> families)
    {
        foreach (var family in families)
        {
            var id = CanonicalId(family);
            if (id.Length == 0 || !_localFaces.Contains(id))
                continue;
            if (!_faces.TryGetValue(id, out var face) || face is null || IsWebFallbackFace(face.FamilyName)
                || !FaceNameMatches(id, face.FamilyName))
            {
                _localFaces.Remove(id);
                EditorLog.Warn($"웹 대체 글꼴을 로컬로 보지 않음: {id} face={face?.FamilyName}");
            }
        }
    }

    public bool IsVerifiedLocal(string? family)
    {
        var id = CanonicalId(family);
        if (!_localFaces.Contains(id))
            return false;
        if (!_faces.TryGetValue(id, out var face) || face is null)
            return false;
        if (IsWebFallbackFace(face.FamilyName))
        {
            _localFaces.Remove(id);
            EditorLog.Warn($"웹 대체 글꼴을 로컬로 보지 않음: {id} face={face.FamilyName}");
            return false;
        }
        if (NeedsHangul(id) && !HasGlyph(face, 0xAC00))
            return false;
        if (!FaceNameMatches(id, face.FamilyName))
        {
            _localFaces.Remove(id);
            EditorLog.Warn($"로컬 글꼴 이름 불일치, 다시 읽음: {id} face={face.FamilyName}");
            return false;
        }
        return true;
    }

    private static bool FaceNameMatches(string id, string? faceName)
    {
        var fn = StripStyleSuffixes((faceName ?? "").Trim());
        if (fn.Length == 0 || IsWebFallbackFace(fn))
            return false;
        if (LocalQueryNames.TryGetValue(id, out var names)
            && names.Any(n => fn.Equals(n, StringComparison.OrdinalIgnoreCase)))
            return true;
        return fn.Equals(id, StringComparison.OrdinalIgnoreCase);
    }

    private static bool IsWebFallbackFace(string? faceName)
    {
        var fn = (faceName ?? "").Trim();
        if (fn.Length == 0) return true;
        string[] marks =
        [
            "Pretendard", "Noto Sans", "Noto Serif", "NotoSans", "NotoSerif",
            "Nanum", "Liberation", "Gothic A1", "IBM Plex"
        ];
        return marks.Any(m => fn.Contains(m, StringComparison.OrdinalIgnoreCase));
    }

    public void ResetLocalAccess() => _localDisabled = false;

    public bool AttachRawBytes(string fileName, byte[] bytes, IEnumerable<string>? preferred = null)
    {
        if (bytes is not { Length: >= 100 }) return false;
        var prefer = preferred?
            .Select(CanonicalId)
            .FirstOrDefault(id => id.Length > 0 && !id.Equals("Pretendard", StringComparison.OrdinalIgnoreCase));
        SKTypeface? regular = null, bold = null, italic = null;
        CollectFaces(bytes, ref regular, ref bold, ref italic, prefer);
        var face = regular ?? bold ?? italic;
        if (face is null) return false;

        var id = string.IsNullOrWhiteSpace(prefer)
            ? GuessFamilyId(fileName, face.FamilyName, preferred)
            : prefer;
        if (NeedsHangul(id) && !HasGlyph(face, 0xAC00))
        {
            EditorLog.Warn($"한글 글리프 없음, 글꼴 거부: {id} ← {fileName} face={face.FamilyName}");
            return false;
        }
        if (NeedsLocalLookup(id) && !FileImpliesFamily(fileName, id) && !FaceNameMatches(id, face.FamilyName))
        {
            EditorLog.Warn($"글꼴 파일 이름 불일치, 거부: {id} ← {fileName} face={face.FamilyName}");
            return false;
        }

        _faces[id] = regular ?? face;
        if (bold is not null) _boldFaces[id] = bold;
        if (italic is not null) _italicFaces[id] = italic;
        _localFaces.Add(id);
        EditorLog.Info($"글꼴 파일 적용: {id} ← {fileName} face={face.FamilyName}");
        return true;
    }

    public async Task<bool> TryLoadLocalOnlyAsync(IEnumerable<string?> families)
    {
        var any = false;
        foreach (var family in families.Distinct(StringComparer.OrdinalIgnoreCase))
        {
            var id = CanonicalId(family);
            if (id.Length == 0 || IsVerifiedLocal(id))
                continue;
            _localFaces.Remove(id);
            ResetLocalAccess();
            if (await TryAttachLocalAsync(id) || await TryAttachSystemApiAsync(id))
                any = true;
        }
        return any;
    }

    /// <summary>Chrome 로컬 글꼴 허용 뒤에만 호출. 이 PC에 있는 얼굴을 읽는다.</summary>
    public async Task<bool> TryChromeLocalFontsAsync(IEnumerable<string?> families)
    {
        var any = false;
        ResetLocalAccess();
        foreach (var family in families.Distinct(StringComparer.OrdinalIgnoreCase))
        {
            var id = CanonicalId(family);
            if (id.Length == 0 || IsVerifiedLocal(id))
                continue;
            _localFaces.Remove(id);
            if (await TryAttachLocalAsync(id))
                any = true;
        }
        return any;
    }

    private static bool NeedsHangul(string id)
        => id is "맑은 고딕" or "굴림" or "돋움" or "바탕" or "궁서" or "휴먼편지체"
           || id.Contains("고딕", StringComparison.Ordinal)
           || id.Contains("Gothic", StringComparison.OrdinalIgnoreCase);

    private static bool FileImpliesFamily(string fileName, string id)
    {
        var file = Path.GetFileNameWithoutExtension(fileName ?? "").ToLowerInvariant();
        return file switch
        {
            var n when n.StartsWith("malgun") => id.Equals("맑은 고딕", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("gulim") => id is "굴림" or "돋움",
            var n when n.StartsWith("dotum") => id.Equals("돋움", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("batang") => id is "바탕" or "궁서",
            var n when n.StartsWith("gungsuh") || n.StartsWith("gungseh")
                => id.Equals("궁서", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("arialn") => id.Equals("Arial Narrow", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("arial") => id.Equals("Arial", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("times") => id.Equals("Times New Roman", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("georgia") => id.Equals("Georgia", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("calibri") => id.Equals("Calibri", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("candara") => id.Equals("Candara", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("tahoma") => id.Equals("Tahoma", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("verdana") => id.Equals("Verdana", StringComparison.OrdinalIgnoreCase),
            var n when n.StartsWith("cour") => id.Equals("Courier New", StringComparison.OrdinalIgnoreCase),
            _ => false
        };
    }

    private string GuessFamilyId(string fileName, string? faceFamily, IEnumerable<string>? preferred)
    {
        var file = Path.GetFileNameWithoutExtension(fileName ?? "").ToLowerInvariant();
        var guessed = file switch
        {
            var n when n.StartsWith("malgun") => "맑은 고딕",
            var n when n.StartsWith("gulim") => "굴림",
            var n when n.StartsWith("dotum") => "돋움",
            var n when n.StartsWith("batang") => "바탕",
            var n when n.StartsWith("gungsuh") || n.StartsWith("gungseh") => "궁서",
            var n when n.StartsWith("arialn") => "Arial Narrow",
            var n when n.StartsWith("arial") => "Arial",
            var n when n.StartsWith("times") => "Times New Roman",
            var n when n.StartsWith("georgia") => "Georgia",
            var n when n.StartsWith("calibri") => "Calibri",
            var n when n.StartsWith("candara") => "Candara",
            var n when n.StartsWith("tahoma") => "Tahoma",
            var n when n.StartsWith("verdana") => "Verdana",
            var n when n.StartsWith("cour") => "Courier New",
            _ => ""
        };
        if (guessed.Length > 0) return CanonicalId(guessed);

        var fromFace = CanonicalId(faceFamily);
        if (preferred is not null)
        {
            foreach (var raw in preferred)
            {
                var id = CanonicalId(raw);
                if (id.Equals(fromFace, StringComparison.OrdinalIgnoreCase))
                    return id;
                if (QueryNamesFor(id).Any(n => n.Equals(faceFamily, StringComparison.OrdinalIgnoreCase)))
                    return id;
            }
        }
        return fromFace;
    }

    public async Task<bool> EnsureFamilyAsync(string? family)
    {
        try
        {
            var id = CanonicalId(family);
            var spec = Specs.FirstOrDefault(s => s.Id.Equals(id, StringComparison.OrdinalIgnoreCase));
            var have = _faces.ContainsKey(id);
            if (have && (_localFaces.Contains(id) || !ShouldTryLocal(spec) || _localDisabled))
                return false;
            if (!_loading.Add(id)) return false;

            try
            {
                if (ShouldTryLocal(spec) && await TryAttachSystemApiAsync(id))
                    return true;
                if (ShouldTryLocal(spec) && await TryAttachLocalAsync(id))
                    return true;
                if (have)
                    return false;

                if (spec is null || spec.RegularUrls.Length == 0)
                {
                    _faces[id] = _regular;
                    return false;
                }

                var face = await LoadFirstAsync(spec.RegularUrls, id);
                _faces[id] = face ?? _regular;
                if (face is not null && FaceNameMatches(id, face.FamilyName) && !IsWebFallbackFace(face.FamilyName))
                    _localFaces.Add(id);
                if (spec.BoldUrls is { Length: > 0 })
                    _boldFaces[id] = await LoadFirstAsync(spec.BoldUrls, id) ?? face ?? _bold;
                if (spec.ItalicUrls is { Length: > 0 })
                    _italicFaces[id] = await LoadFirstAsync(spec.ItalicUrls, id);
                var ok = face is not null;
                EditorLog.Info(ok ? $"글꼴 로드: {id} face={face?.FamilyName}" : $"글꼴 로드 실패, 기본 글꼴 사용: {id}");
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

    public SKTypeface ResolveOrDefault(bool bold = false)
    {
        if (bold && _bold is not null) return _bold;
        if (_regular is not null) return _regular;
        return SKTypeface.Default;
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
        var local = _localFaces.Contains(id);

        if (codepoint > 0 && HasGlyph(_symbols, codepoint)
            && (PrefersSymbolRange(codepoint) || !HasGlyph(primary, codepoint)))
            return _symbols!;
        // 로컬에서 읽은 얼굴은 글리프 검사가 틀려도 Pretendard로 바꾸지 않는다.
        if (!local && codepoint > 0 && !HasGlyph(primary, codepoint) && HasGlyph(_regular, codepoint))
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

    private static bool ShouldTryLocal(EditorFontSpec? spec)
        => spec is null || !spec.Picker;

    private string[] QueryNamesFor(string id)
    {
        if (LocalQueryNames.TryGetValue(id, out var mapped))
            return mapped;
        var aliases = Aliases
            .Where(kv => kv.Value.Equals(id, StringComparison.OrdinalIgnoreCase))
            .Select(kv => kv.Key);
        return aliases.Prepend(id).Distinct(StringComparer.OrdinalIgnoreCase).ToArray();
    }

    private async Task<bool> TryAttachSystemApiAsync(string id)
    {
        try
        {
            foreach (var name in QueryNamesFor(id))
            {
                var (regular, regularName) = await FetchSystemFontAsync(name, "regular");
                if (regular is not { Length: >= 100 })
                    continue;
                if (AttachRawBytes(string.IsNullOrWhiteSpace(regularName) ? WinFileName(id) : regularName, regular, [id]))
                {
                    var (bold, boldName) = await FetchSystemFontAsync(name, "bold");
                    if (bold is { Length: >= 100 })
                        AttachRawBytes(boldName, bold, [id]);
                    return true;
                }
            }
        }
        catch (Exception ex)
        {
            EditorLog.Warn($"시스템 글꼴 API 실패: {id} · {ex.Message}");
        }
        return false;
    }

    private static string WinFileName(string id) => id switch
    {
        "맑은 고딕" => "malgun.ttf",
        "굴림" or "돋움" => "gulim.ttc",
        "바탕" or "궁서" => "batang.ttc",
        "Arial" => "arial.ttf",
        _ => id + ".ttf"
    };

    private async Task<(byte[]? Bytes, string FileName)> FetchSystemFontAsync(string family, string style)
    {
        try
        {
            try
            {
                var viaJs = await _js.InvokeAsync<byte[]?>("labelUpEditor.fetchSystemFont", family, style);
                if (viaJs is { Length: >= 100 })
                    return (viaJs, WinFileName(CanonicalId(family)));
            }
            catch (JSException)
            {
                /* 아래 HTTP로 재시도 */
            }

            var origin = _http.BaseAddress?.GetLeftPart(UriPartial.Authority) ?? "";
            var url = origin + "/api/editor/system-font?family=" + Uri.EscapeDataString(family)
                      + "&style=" + Uri.EscapeDataString(style);
            using var resp = await _http.GetAsync(url);
            if (!resp.IsSuccessStatusCode)
            {
                EditorLog.Warn($"시스템 글꼴 API {((int)resp.StatusCode)}: {family}");
                return (null, "");
            }
            var bytes = await resp.Content.ReadAsByteArrayAsync();
            if (bytes.Length < 100)
                return (null, "");
            var fileName = resp.Content.Headers.ContentDisposition?.FileNameStar
                           ?? resp.Content.Headers.ContentDisposition?.FileName?.Trim('"')
                           ?? family + ".ttf";
            return (bytes, fileName);
        }
        catch (Exception ex)
        {
            EditorLog.Warn($"시스템 글꼴 API 실패: {family} · {ex.Message}");
            return (null, "");
        }
    }

    private async Task<bool> TryAttachLocalAsync(string id)
    {
        if (_localDisabled) return false;
        try
        {
            var names = QueryNamesFor(id);
            var regularBytes = await _js.InvokeAsync<byte[]?>("labelUpEditor.loadLocalFont", names, "regular");
            if (regularBytes is not { Length: >= 100 })
                return false;

            SKTypeface? regular = null, bold = null, italic = null;
            CollectFaces(regularBytes, ref regular, ref bold, ref italic, id);
            if (bold is null)
            {
                var boldBytes = await _js.InvokeAsync<byte[]?>("labelUpEditor.loadLocalFont", names, "bold");
                if (boldBytes is { Length: >= 100 })
                    CollectFaces(boldBytes, ref regular, ref bold, ref italic, id);
            }
            if (italic is null)
            {
                var italicBytes = await _js.InvokeAsync<byte[]?>("labelUpEditor.loadLocalFont", names, "italic");
                if (italicBytes is { Length: >= 100 })
                    CollectFaces(italicBytes, ref regular, ref bold, ref italic, id);
            }

            var face = regular ?? bold ?? italic;
            if (face is null) return false;
            if (NeedsHangul(id) && !HasGlyph(face, 0xAC00))
            {
                EditorLog.Warn($"로컬 글꼴에 한글 없음: {id} face={face.FamilyName}");
                return false;
            }
            if (!FaceNameMatches(id, face.FamilyName)
                && !(NeedsHangul(id) && HasGlyph(face, 0xAC00)))
            {
                EditorLog.Warn($"로컬 글꼴 이름 불일치: {id} face={face.FamilyName}");
                return false;
            }

            _faces[id] = face;
            if (bold is not null) _boldFaces[id] = bold;
            if (italic is not null) _italicFaces[id] = italic;
            _localFaces.Add(id);
            EditorLog.Info($"로컬 글꼴 로드: {id} face={face.FamilyName}");
            return true;
        }
        catch (JSException ex)
        {
            EditorLog.Warn("로컬 글꼴 API 사용 불가: " + ex.Message);
            return false;
        }
        catch (Exception ex)
        {
            EditorLog.Warn($"로컬 글꼴 로드 실패: {id} · {ex.Message}");
            return false;
        }
    }

    private static void CollectFaces(
        byte[] bytes, ref SKTypeface? regular, ref SKTypeface? bold, ref SKTypeface? italic, string? preferId = null)
    {
        using var data = SKData.CreateCopy(bytes);
        var faces = new List<SKTypeface>();
        SKTypeface? prev = null;
        for (var i = 0; i < 24; i++)
        {
            SKTypeface? face;
            try { face = SKTypeface.FromData(data, i); }
            catch { break; }
            if (face is null) break;
            if (prev is not null
                && face.FontWeight == prev.FontWeight
                && face.IsItalic == prev.IsItalic
                && string.Equals(face.FamilyName, prev.FamilyName, StringComparison.OrdinalIgnoreCase))
            {
                face.Dispose();
                break;
            }
            prev = face;
            faces.Add(face);
        }

        var prefer = (preferId ?? "").Trim();
        var wanted = prefer.Length == 0
            ? faces
            : faces.Where(f => FaceNameMatches(prefer, f.FamilyName)).ToList();
        var pool = wanted.Count > 0 ? wanted : faces;
        foreach (var face in faces)
        {
            if (!pool.Contains(face))
            {
                face.Dispose();
                continue;
            }
            var isBold = face.IsBold || (int)face.FontWeight >= 600;
            var isItalic = face.IsItalic;
            if (!isBold && !isItalic && regular is null) { regular = face; continue; }
            if (isBold && !isItalic && bold is null) { bold = face; continue; }
            if (isItalic && italic is null) { italic = face; continue; }
            face.Dispose();
        }
    }

    private async Task<SKTypeface?> LoadFirstAsync(IReadOnlyList<string> urls, string? preferId = null)
    {
        foreach (var url in urls)
        {
            var face = await LoadAsync(url, preferId);
            if (face is not null) return face;
        }
        return null;
    }

    private async Task<SKTypeface?> LoadAsync(string relativePath, string? preferId = null)
    {
        try
        {
            var bytes = await _http.GetByteArrayAsync(relativePath);
            if (bytes.Length < 100) return null;
            SKTypeface? regular = null, bold = null, italic = null;
            CollectFaces(bytes, ref regular, ref bold, ref italic, preferId);
            bold?.Dispose();
            italic?.Dispose();
            if (regular is not null) return regular;
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
