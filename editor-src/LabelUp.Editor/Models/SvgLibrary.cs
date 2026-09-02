namespace LabelUp.Editor.Models;

/// <summary>클립아트·폰트어썸·용지 형상에 쓰는 내장 SVG path (viewBox 0 0 100 100 기준).</summary>
public static class SvgLibrary
{
    public const string HeartPath =
        "M50 88 C20 62 8 48 8 32 C8 18 20 10 32 10 C40 10 46 14 50 22 C54 14 60 10 68 10 C80 10 92 18 92 32 C92 48 80 62 50 88 Z";

    public const string StarPath =
        "M50 6 L61 38 L95 38 L68 58 L78 90 L50 70 L22 90 L32 58 L5 38 L39 38 Z";

    public const string HexPath =
        "M50 4 L90 27 L90 73 L50 96 L10 73 L10 27 Z";

    public const string CloudPath =
        "M32 72 H78 C88 72 94 64 94 56 C94 46 86 40 76 42 C74 28 62 20 50 22 C36 24 28 36 30 48 C18 50 12 60 16 68 C20 74 26 72 32 72 Z";

    public const string BonePath =
        "M18 32 C10 24 10 12 20 12 C28 12 32 20 34 26 L66 26 C68 20 72 12 80 12 C90 12 90 24 82 32 L82 68 C90 76 90 88 80 88 C72 88 68 80 66 74 L34 74 C32 80 28 88 20 88 C10 88 10 76 18 68 Z";

    public const string CirclePath =
        "M50 8 A42 42 0 1 1 49.9 8 Z";

    public static readonly (string Id, string Label, string Path)[] Cliparts =
    [
        ("heart", "하트", HeartPath),
        ("star", "별", StarPath),
        ("hex", "육각", HexPath),
        ("cloud", "구름", CloudPath),
        ("bone", "뼈다귀", BonePath),
        ("circle", "원", CirclePath),
        ("arrow", "화살", "M12 46 H58 L46 34 L54 26 L88 50 L54 74 L46 66 L58 54 H12 Z"),
        ("tag", "태그", "M8 18 H58 L92 50 L58 82 H8 Z M28 40 A8 8 0 1 1 27.9 40 Z"),
        ("leaf", "잎", "M50 8 C78 18 92 48 50 92 C8 48 22 18 50 8 Z M50 18 L50 80"),
        ("badge", "배지", "M50 6 L62 28 L88 28 L68 46 L76 72 L50 58 L24 72 L32 46 L12 28 L38 28 Z")
    ];

    public static readonly (string Id, string Label, string Path)[] Icons =
    [
        ("star", "별", StarPath),
        ("heart", "하트", HeartPath),
        ("home", "홈", "M50 12 L92 48 H80 V88 H58 V60 H42 V88 H20 V48 H8 Z"),
        ("user", "사용자", "M50 12 A18 18 0 1 1 49.9 12 Z M18 88 C18 62 32 52 50 52 C68 52 82 62 82 88 Z"),
        ("phone", "전화", "M28 8 H44 L50 28 L38 36 C42 46 54 58 64 62 L72 50 L92 56 V72 C92 84 80 92 64 88 C32 80 12 52 12 28 C8 16 16 8 28 8 Z"),
        ("mail", "메일", "M8 24 H92 V76 H8 Z M8 24 L50 52 L92 24"),
        ("check", "체크", "M18 52 L38 72 L82 28"),
        ("xmark", "닫기", "M22 22 L78 78 M78 22 L22 78"),
        ("plus", "더하기", "M50 16 V84 M16 50 H84"),
        ("gear", "설정", "M42 8 H58 L62 22 L76 16 L84 30 L72 40 L84 50 L76 64 L62 58 L58 92 H42 L38 58 L24 64 L16 50 L28 40 L16 30 L24 16 L38 22 Z M50 38 A12 12 0 1 1 49.9 38 Z"),
        ("cart", "카트", "M12 20 H28 L36 64 H84 L92 32 H40 M40 80 A8 8 0 1 1 39.9 80 Z M80 80 A8 8 0 1 1 79.9 80 Z"),
        ("info", "정보", "M50 8 A42 42 0 1 1 49.9 8 Z M50 42 V72 M50 28 A4 4 0 1 1 49.9 28 Z")
    ];

    public static string ToSvg(string path, string fill = "#7B2840", int size = 100)
        => $"<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100' width='{size}' height='{size}'><path d='{path}' fill='{fill}'/></svg>";
}
