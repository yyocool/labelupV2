using System.Globalization;

namespace LabelUp.Editor.Models;

/// <summary>데이터 열을 라벨에 넣을 때 쓰는 표시 형식.</summary>
public static class DataDisplayFormats
{
    public const string Text = "text";
    public const string Date = "date";
    public const string Image = "image";
    public const string Barcode = "barcode";

    public static readonly (string Id, string Label)[] Dates =
    [
        ("yyyy-MM-dd", "YYYY-MM-DD"),
        ("yy-MM-dd", "YY-MM-DD"),
        ("yyyy.MM.dd", "YYYY.MM.DD"),
        ("yyyy년 M월 d일", "YYYY년 M월 D일"),
        ("MM/dd/yyyy", "MM/DD/YYYY"),
        ("dd/MM/yyyy", "DD/MM/YYYY"),
        ("yyyy-MM-dd dddd", "YYYY-MM-DD 요일"),
        ("D", "긴 형식")
    ];

    public static string FormatDate(string? raw, string? pattern)
    {
        if (!TryParseDate(raw, out var clock))
            return raw ?? "";
        var fmt = string.IsNullOrWhiteSpace(pattern) ? "yyyy-MM-dd" : pattern;
        try
        {
            if (fmt == "D")
                return clock.ToString("D", CultureInfo.GetCultureInfo("ko-KR"));
            if (fmt.Contains("dddd", StringComparison.Ordinal))
            {
                var days = new[] { "일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일" };
                return clock.ToString("yyyy-MM-dd", CultureInfo.InvariantCulture) + " " + days[(int)clock.DayOfWeek];
            }
            return clock.ToString(fmt, CultureInfo.GetCultureInfo("ko-KR"));
        }
        catch
        {
            return clock.ToString("yyyy-MM-dd", CultureInfo.InvariantCulture);
        }
    }

    public static bool TryParseDate(string? raw, out DateTime clock)
    {
        clock = default;
        if (string.IsNullOrWhiteSpace(raw)) return false;
        var text = raw.Trim();
        string[] exact =
        [
            "yyyy-MM-dd", "yy-MM-dd", "yyyy.MM.dd", "yyyy/MM/dd",
            "MM/dd/yyyy", "dd/MM/yyyy", "yyyyMMdd", "yyMMdd",
            "yyyy-MM-ddTHH:mm:ss", "yyyy-MM-dd HH:mm:ss"
        ];
        if (DateTime.TryParseExact(text, exact, CultureInfo.InvariantCulture, DateTimeStyles.AllowWhiteSpaces, out clock))
            return true;
        return DateTime.TryParse(text, CultureInfo.GetCultureInfo("ko-KR"), DateTimeStyles.AllowWhiteSpaces, out clock)
            || DateTime.TryParse(text, CultureInfo.InvariantCulture, DateTimeStyles.AllowWhiteSpaces, out clock);
    }
}
