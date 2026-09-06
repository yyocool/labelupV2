using System.Globalization;

namespace LabelUp.Editor.Models;

/// <summary>
/// 폼텍 사용자정의문자열 서식. md_formtec/폼텍_DGZ_사용자정의문자열_분석_확정.md
/// 날짜·시간은 DATE:n / TIME:n. 일련번호 반복회수는 DGF에 없고 편집기에서만 쓴다.
/// </summary>
public static class CustomTextFormats
{
    public static readonly (string Id, string Label)[] Dates =
    [
        ("1", "YY-MM-DD"),
        ("2", "YYYY-MM-DD"),
        ("3", "YYYY-MM-DD WEEK"),
        ("4", "WEEK, MM, DD, YYYY")
    ];

    public static readonly (string Id, string Label)[] Times =
    [
        ("1", "HH MM"),
        ("2", "(AM PM) HH MM"),
        ("3", "HH MM SS")
    ];

    public static string NormalizeDate(string? raw) => raw switch
    {
        "1" or "yy-MM-dd" or "YY-MM-DD" or "yyMMdd" or "YYMMDD" => "1",
        "3" or "yyyy-MM-dd dddd" or "YYYY-MM-DD WEEK" => "3",
        "4" or "dddd, MM, dd, yyyy" or "WEEK, MM, DD, YYYY" => "4",
        _ => "2"
    };

    public static string NormalizeTime(string? raw) => raw switch
    {
        "2" or "tt hh mm" or "(AM PM) HH MM" or "(tt) hh mm" => "2",
        "3" or "HH mm ss" or "HH MM SS" or "HH:mm:ss" => "3",
        _ => "1"
    };

    public static string Token(string? kind, string? format)
        => kind switch
        {
            "date" => "{DATE:" + NormalizeDate(format) + "}",
            "time" => "{TIME:" + NormalizeTime(format) + "}",
            "hexserial" => "{HEX-SERIALNO}",
            "serial" => "{SERIALNO}",
            _ => ""
        };

    public static void ApplyToken(DesignObject obj)
    {
        var token = Token(obj.CustomKind, obj.CustomFormat);
        if (token.Length > 0)
            obj.Text = token;
    }

    public static string FormatDate(DateTime clock, string? raw)
    {
        var weekdays = new[] { "일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일" };
        return NormalizeDate(raw) switch
        {
            "1" => clock.ToString("yy-MM-dd", CultureInfo.InvariantCulture),
            "3" => clock.ToString("yyyy-MM-dd", CultureInfo.InvariantCulture) + " " + weekdays[(int)clock.DayOfWeek],
            "4" => $"{weekdays[(int)clock.DayOfWeek]}, {clock:MM}, {clock:dd}, {clock:yyyy}",
            _ => clock.ToString("yyyy-MM-dd", CultureInfo.InvariantCulture)
        };
    }

    public static string FormatTime(DateTime clock, string? raw)
        => NormalizeTime(raw) switch
        {
            "2" => clock.ToString("(tt) hh mm", CultureInfo.GetCultureInfo("en-US")),
            "3" => clock.ToString("HH mm ss", CultureInfo.InvariantCulture),
            _ => clock.ToString("HH mm", CultureInfo.InvariantCulture)
        };

    public static int SerialValue(DesignObject obj, int labelIndex)
        => obj.SerialStart + Math.Max(0, labelIndex) * Math.Max(1, obj.SerialStep);

    public static bool IsSerial(DesignObject obj)
        => obj.CustomKind is "serial" or "hexserial"
           || obj.TextMode == TextMode.Custom && obj.CustomKind is "serial" or "hexserial";

    public static bool IsClock(DesignObject obj)
        => obj.CustomKind is "date" or "time";

    public static int TotalLabels(IEnumerable<DesignObject> objects)
    {
        var serials = objects.Where(o => o.CustomKind is "serial" or "hexserial").ToList();
        if (serials.Count == 0) return 0;
        return Math.Min(200, Math.Max(1, serials.Max(o => o.SerialRepeat)));
    }

    public static string FormatSerial(DesignObject obj, int labelIndex, bool hex)
    {
        var value = SerialValue(obj, labelIndex);
        var digits = Math.Clamp(obj.SerialDigits, 1, 12);
        return hex
            ? value.ToString("X" + digits, CultureInfo.InvariantCulture)
            : value.ToString("D" + digits, CultureInfo.InvariantCulture);
    }
}
