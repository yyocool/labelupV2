using System.Text;

namespace LabelUp.Editor.Services;

/// <summary>
/// 폼텍 DGZ 텍스트는 Unicode UTF-16LE 그대로 저장된다.
/// WASM(Pretendard)에 글리프가 없는 CJK 호환 단위 기호는
/// 폼텍/맑은고딕과 같은 모양의 일반 문자로 펼친다.
/// </summary>
internal static class FormtecCompat
{
    private static readonly Dictionary<char, string> CompatUnits = new()
    {
        ['\u3371'] = "hPa",
        ['\u3372'] = "da",
        ['\u3373'] = "AU",
        ['\u3374'] = "bar",
        ['\u3375'] = "oV",
        ['\u3376'] = "pc",
        ['\u3377'] = "dm",
        ['\u3378'] = "dm\u00b2",
        ['\u3379'] = "dm\u00b3",
        ['\u337a'] = "IU",
        ['\u3380'] = "pA",
        ['\u3381'] = "nA",
        ['\u3382'] = "\u03bcA",
        ['\u3383'] = "mA",
        ['\u3384'] = "kA",
        ['\u3385'] = "KB",
        ['\u3386'] = "MB",
        ['\u3387'] = "GB",
        ['\u3388'] = "cal",
        ['\u3389'] = "kcal",
        ['\u338a'] = "pF",
        ['\u338b'] = "nF",
        ['\u338c'] = "\u03bcF",
        ['\u338d'] = "\u03bcg",
        ['\u338e'] = "mg",
        ['\u338f'] = "kg",
        ['\u3390'] = "Hz",
        ['\u3391'] = "kHz",
        ['\u3392'] = "MHz",
        ['\u3393'] = "GHz",
        ['\u3394'] = "THz",
        ['\u3395'] = "\u03bcl",
        ['\u3396'] = "ml",
        ['\u3397'] = "dl",
        ['\u3398'] = "kl",
        ['\u3399'] = "fm",
        ['\u339a'] = "nm",
        ['\u339b'] = "\u03bcm",
        ['\u339c'] = "mm",
        ['\u339d'] = "cm",
        ['\u339e'] = "km",
        ['\u339f'] = "mm\u00b2",
        ['\u33a0'] = "cm\u00b2",
        ['\u33a1'] = "m\u00b2",
        ['\u33a2'] = "km\u00b2",
        ['\u33a3'] = "mm\u00b3",
        ['\u33a4'] = "cm\u00b3",
        ['\u33a5'] = "m\u00b3",
        ['\u33a6'] = "km\u00b3",
        ['\u33a7'] = "m/s",
        ['\u33a8'] = "m/s\u00b2",
        ['\u33a9'] = "Pa",
        ['\u33aa'] = "kPa",
        ['\u33ab'] = "MPa",
        ['\u33ac'] = "GPa",
        ['\u33ad'] = "rad",
        ['\u33ae'] = "rad/s",
        ['\u33af'] = "rad/s\u00b2",
        ['\u33b0'] = "ps",
        ['\u33b1'] = "ns",
        ['\u33b2'] = "\u03bcs",
        ['\u33b3'] = "ms",
        ['\u33b4'] = "pV",
        ['\u33b5'] = "nV",
        ['\u33b6'] = "\u03bcV",
        ['\u33b7'] = "mV",
        ['\u33b8'] = "kV",
        ['\u33b9'] = "MV",
        ['\u33ba'] = "pW",
        ['\u33bb'] = "nW",
        ['\u33bc'] = "\u03bcW",
        ['\u33bd'] = "mW",
        ['\u33be'] = "kW",
        ['\u33bf'] = "MW",
        ['\u33c0'] = "k\u03a9",
        ['\u33c1'] = "M\u03a9",
        ['\u33c2'] = "a.m.",
        ['\u33c3'] = "Bq",
        ['\u33c4'] = "cc",
        ['\u33c5'] = "cd",
        ['\u33c6'] = "C/kg",
        ['\u33c8'] = "dB",
        ['\u33c9'] = "Gy",
        ['\u33ca'] = "ha",
        ['\u33cb'] = "HP",
        ['\u33cc'] = "in",
        ['\u33cd'] = "KK",
        ['\u33ce'] = "KM",
        ['\u33cf'] = "kt",
        ['\u33d0'] = "lm",
        ['\u33d1'] = "ln",
        ['\u33d2'] = "log",
        ['\u33d3'] = "lx",
        ['\u33d4'] = "mb",
        ['\u33d5'] = "mil",
        ['\u33d6'] = "mol",
        ['\u33d7'] = "PH",
        ['\u33d8'] = "p.m.",
        ['\u33d9'] = "PPM",
        ['\u33da'] = "PR",
        ['\u33db'] = "sr",
        ['\u33dc'] = "Sv",
        ['\u33dd'] = "Wb",
        ['\u33de'] = "V/m",
        ['\u33df'] = "A/m",
        ['\u33ff'] = "gal",
    };

    public static string ExpandForDisplay(string? text)
    {
        if (string.IsNullOrEmpty(text)) return text ?? "";
        StringBuilder? sb = null;
        for (var i = 0; i < text.Length; i++)
        {
            var ch = text[i];
            if (!CompatUnits.TryGetValue(ch, out var exp))
            {
                sb?.Append(ch);
                continue;
            }

            sb ??= new StringBuilder(text.Length + 16).Append(text, 0, i);
            sb.Append(exp);
        }
        return sb?.ToString() ?? text;
    }

    public static bool NeedsDrawnGlyph(int code)
        => code is >= 0x2500 and <= 0x257F or >= 0x25A0 and <= 0x25FF;

    public static bool ContainsDrawnGlyph(string text)
    {
        foreach (var ch in text)
        {
            if (NeedsDrawnGlyph(ch)) return true;
        }
        return false;
    }
}
