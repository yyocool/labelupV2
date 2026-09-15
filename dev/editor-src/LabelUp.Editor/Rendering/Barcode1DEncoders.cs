using System.Text;
using LabelUp.Editor.Models;

namespace LabelUp.Editor.Rendering;

/// <summary>변환 출처별 1D 인코딩. 폼텍 실측과 Okapi/Zint 표준을 섞지 않는다.</summary>
internal enum BarcodeVendorKind
{
    Native,
    Formtec,
    AniLabel,
    ILabel
}

/// <summary>
/// ZXing이 없는 1D 심볼로지 모듈 인코더.
/// 폼텍은 화면 실측, 그 외는 Okapi/Zint 표준.
/// </summary>
internal static class Barcode1DEncoders
{
    public static BarcodeVendorKind VendorOf(DesignObject obj)
    {
        var v = (obj.BarcodeVendor ?? "").Trim().ToLowerInvariant();
        return v switch
        {
            "formtec" => BarcodeVendorKind.Formtec,
            "anylabel" => BarcodeVendorKind.AniLabel,
            "ilabel" => BarcodeVendorKind.ILabel,
            _ => BarcodeVendorKind.Native
        };
    }

    public static bool IsFormtec(BarcodeVendorKind vendor) => vendor == BarcodeVendorKind.Formtec;

    /// <summary>폼텍은 실측 여백 0. 표준은 심볼로지 권장 quiet.</summary>
    public static int Quiet(BarcodeVendorKind vendor, int standard)
        => vendor == BarcodeVendorKind.Formtec ? 0 : standard;
    /// <summary>IATA/Industrial 숫자: 막대 5개 중 2개 wide. 간격은 항상 좁음.</summary>
    private static readonly string[] Industrial25 =
    [
        "1111313111", "3111111131", "1131111131", "3131111111", "1111311131",
        "3111311111", "1131311111", "1111113131", "3111113111", "1131113111"
    ];

    /// <summary>Matrix/Datalogic 숫자: 막대·간격 모두 굵기 변화. 6요소.</summary>
    private static readonly string[] Matrix25 =
    [
        "113311", "311131", "131131", "331111", "113131",
        "313111", "133111", "111331", "311311", "131311"
    ];

    /// <summary>Coop/NEC 2 of 5. Matrix과 가중치 방향이 반대(7-4-2-1).</summary>
    private static readonly string[] Coop25 =
    [
        "331111", "111331", "113131", "113311", "131131",
        "131311", "133111", "311131", "311311", "313111"
    ];

    /// <summary>Codabar / ABC Codabar. 7요소, 글자 사이 좁은 간격. C는 nnnwnww (폼텍 ABCCBA 실측, ZXing 0x00B).</summary>
    private static readonly Dictionary<char, string> CodabarNw = new()
    {
        ['0'] = "nnnnnww", ['1'] = "nnnnwwn", ['2'] = "nnnwnnw", ['3'] = "wwnnnnn",
        ['4'] = "nnwnnwn", ['5'] = "wnnnnwn", ['6'] = "nwnnnnw", ['7'] = "nwnnwnn",
        ['8'] = "nwwnnnn", ['9'] = "wnnwnnn", ['-'] = "nnnwwnn", ['$'] = "nnwwnnn",
        [':'] = "wnnnwnw", ['/'] = "wnwnnnw", ['.'] = "wnwnwnn", ['+'] = "nnwnwnw",
        ['A'] = "nnwwnwn", ['B'] = "nwnwnnw", ['C'] = "nnnwnww", ['D'] = "nnnwwwn"
    };

    /// <summary>Code 11. 0–9와 '-', 시작/종료 112211. Zint/Okapi 표.</summary>
    private static readonly Dictionary<char, string> Code11 =
        new()
        {
            ['0'] = "111121", ['1'] = "211121", ['2'] = "121121", ['3'] = "221111",
            ['4'] = "112121", ['5'] = "212111", ['6'] = "122111", ['7'] = "111221",
            ['8'] = "211211", ['9'] = "211111", ['-'] = "112211"
        };

    private static readonly string[] Postnet =
        ["LLSSS", "SSSLL", "SSLSL", "SSLLS", "SLSSL", "SLSLS", "SLLSS", "LSSSL", "LSSLS", "LSLSS"];

    private static readonly string[] Planet =
        ["SSLLL", "LLLSS", "LLSLS", "LLSSL", "LSLLS", "LSLSL", "LSSLL", "SLLLS", "SLLSL", "SLSLL"];

    /// <summary>Code 39 Full ASCII (ISO/IEC 16388 Annex A).</summary>
    private static readonly string[] Code39FullAscii =
    [
        "%U", "$A", "$B", "$C", "$D", "$E", "$F", "$G", "$H", "$I", "$J", "$K", "$L", "$M", "$N", "$O",
        "$P", "$Q", "$R", "$S", "$T", "$U", "$V", "$W", "$X", "$Y", "$Z", "%A", "%B", "%C", "%D", "%E",
        " ", "/A", "/B", "/C", "/D", "/E", "/F", "/G", "/H", "/I", "/J", "/K", "/L", "-", ".", "/O",
        "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "/Z", "%F", "%G", "%H", "%I", "%J",
        "%V", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O",
        "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "%K", "%L", "%M", "%N", "%O",
        "%W", "+A", "+B", "+C", "+D", "+E", "+F", "+G", "+H", "+I", "+J", "+K", "+L", "+M", "+N", "+O",
        "+P", "+Q", "+R", "+S", "+T", "+U", "+V", "+W", "+X", "+Y", "+Z", "%P", "%Q", "%R", "%S", "%T"
    ];

    /// <summary>Code 93 기본 43문자. 값 43–46은 Full ASCII 시프트 ($)%(/)(+).</summary>
    private static readonly Dictionary<char, int> Code93Basic = CreateCode93Basic();

    private static Dictionary<char, int> CreateCode93Basic()
    {
        const string chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%";
        var map = new Dictionary<char, int>(chars.Length);
        for (var i = 0; i < chars.Length; i++)
            map[chars[i]] = i;
        return map;
    }

    private static readonly string[] Code93Widths =
    [
        "131112", "111213", "111312", "111411", "121113", "121212", "121311", "111114",
        "131211", "141111", "211113", "211212", "211311", "221112", "221211", "231111",
        "112113", "112212", "112311", "122112", "132111", "111123", "111222", "111321",
        "121122", "131121", "212112", "212211", "211122", "211221", "221121", "222111",
        "112122", "112221", "122121", "123111", "121131", "311112", "311211", "321111",
        "112131", "113121", "211131", "121221", "312111", "311121", "122211"
    ];

    /// <summary>
    /// Code 93. Extended는 Full ASCII를 시프트 코드(43–46)로 넣는다.
    /// 일반 '+'를 쓰면 폼텍과 막대가 달라진다. 폼텍 Extended만 C/K가 없다.
    /// </summary>
    public static bool[]? EncodeCode93(string value, bool extended, bool checksum)
    {
        var values = new List<int>(value.Length * 2 + 2);
        if (extended)
        {
            foreach (var ch in value)
            {
                if (ch > 127) return null;
                var token = Code39FullAscii[ch];
                if (token.Length == 1)
                {
                    if (!Code93Basic.TryGetValue(token[0], out var basic))
                        return null;
                    values.Add(basic);
                }
                else
                {
                    var shift = token[0] switch
                    {
                        '$' => 43,
                        '%' => 44,
                        '/' => 45,
                        '+' => 46,
                        _ => -1
                    };
                    if (shift < 0 || !Code93Basic.TryGetValue(token[1], out var basic))
                        return null;
                    values.Add(shift);
                    values.Add(basic);
                }
            }
        }
        else
        {
            foreach (var ch in value.ToUpperInvariant())
            {
                if (!Code93Basic.TryGetValue(ch, out var v))
                    return null;
                values.Add(v);
            }
        }

        if (values.Count == 0) return null;
        if (checksum)
        {
            values.Add(Code93Checksum(values, 20));
            values.Add(Code93Checksum(values, 15));
        }

        var bits = new List<bool>(10 + (values.Count + 2) * 9);
        var black = true;
        void Append(string pat)
        {
            foreach (var d in pat)
            {
                var n = d - '0';
                for (var i = 0; i < n; i++) bits.Add(black);
                black = !black;
            }
        }

        Append("111141");
        foreach (var v in values)
            Append(Code93Widths[v]);
        Append("111141");
        bits.Add(true);
        return bits.ToArray();
    }

    private static int Code93Checksum(List<int> values, int maxWeight)
    {
        var sum = 0;
        var weight = 1;
        for (var i = values.Count - 1; i >= 0; i--)
        {
            sum += values[i] * weight;
            weight++;
            if (weight > maxWeight)
                weight = 1;
        }
        return sum % 47;
    }

    private static readonly string[] Code128Widths =
    [
        "212222", "222122", "222221", "121223", "121322", "131222", "122213", "122312", "132212", "221213",
        "221312", "231212", "112232", "122132", "122231", "113222", "123122", "123221", "223211", "221132",
        "221231", "213212", "223112", "312131", "311222", "321122", "321221", "312212", "322112", "322211",
        "212123", "212321", "232121", "111323", "131123", "131321", "112313", "132113", "132311", "211313",
        "231113", "231311", "112133", "112331", "132131", "113123", "113321", "133121", "313121", "211331",
        "231131", "213113", "213311", "213131", "311123", "311321", "331121", "312113", "312311", "332111",
        "314111", "221411", "431111", "111224", "111422", "121124", "121421", "141122", "141221", "112214",
        "112412", "122114", "122411", "142112", "142211", "241211", "221114", "413111", "241112", "134111",
        "111242", "121142", "121241", "114212", "124112", "124211", "411212", "421112", "421211", "212141",
        "214121", "412121", "111143", "111341", "131141", "114113", "114311", "411113", "411311", "113141",
        "114131", "311141", "411131", "211412", "211214", "211232", "233111"
    ];

    internal enum Code128Subset
    {
        Auto,
        A,
        B,
        C
    }

    /// <summary>
    /// Code 128 / GS1-128(UCC/EAN-128).
    /// 폼텍 UCC/EAN-128 실측: Start A + 값69 + 데이터. ISO GS1은 Start 뒤 FNC1(102).
    /// 아이라벨 Code128Alphabet: Auto=숫자면 C, A=Start A, B=Start B, C=숫자 쌍.
    /// </summary>
    public static bool[]? EncodeCode128(
        string value, bool gs1, bool formtecEan128, Code128Subset subset = Code128Subset.Auto,
        bool fnc1 = false)
    {
        var codes = formtecEan128 || (gs1 && !fnc1)
            ? BuildCode128Legacy(value, gs1, formtecEan128)
            : BuildCode128Subset(value, subset, fnc1);
        if (codes is null || codes.Count < 2) return null;
        var checksum = 0;
        for (var i = 0; i < codes.Count; i++)
            checksum += codes[i] * (i == 0 ? 1 : i);
        codes.Add(checksum % 103);

        var bits = new List<bool>((codes.Count + 1) * 11 + 2);
        var black = true;
        void Append(string pat)
        {
            foreach (var d in pat)
            {
                var n = d - '0';
                for (var i = 0; i < n; i++) bits.Add(black);
                black = !black;
            }
        }

        foreach (var c in codes)
            Append(Code128Widths[c]);
        Append("233111");
        bits.Add(true);
        bits.Add(true);
        return bits.ToArray();
    }

    private static List<int>? BuildCode128Legacy(string value, bool gs1, bool formtecEan128)
    {
        var codes = new List<int>(value.Length + 4);
        var setA = formtecEan128;
        if (formtecEan128)
        {
            codes.Add(103);
            codes.Add(69);
        }
        else
        {
            codes.Add(104);
            codes.Add(102);
        }

        foreach (var ch in value)
        {
            if (ch <= 31)
            {
                if (!setA)
                {
                    codes.Add(101);
                    setA = true;
                }
                codes.Add(ch + 64);
            }
            else if (ch <= 95)
            {
                codes.Add(ch - 32);
            }
            else if (ch <= 126)
            {
                if (setA)
                {
                    codes.Add(100);
                    setA = false;
                }
                codes.Add(ch - 32);
            }
            else
                return null;
        }
        return codes;
    }

    /// <summary>아이라벨 Auto는 숫자면 Set C(실측 28막대), 제어문자면 A, 그 외 B.</summary>
    private static List<int>? BuildCode128Subset(string value, Code128Subset subset, bool fnc1 = false)
    {
        if (subset == Code128Subset.Auto)
            return BuildCode128Auto(value, fnc1);
        if (subset == Code128Subset.C)
            return BuildCode128C(value, fnc1);
        if (subset == Code128Subset.A)
            return BuildCode128Fixed(value, start: 103, setA: true, fnc1);
        return BuildCode128Fixed(value, start: 104, setA: false, fnc1);
    }

    private static List<int>? BuildCode128Auto(string value, bool fnc1 = false)
    {
        if (value.Length > 0 && value.All(char.IsAsciiDigit))
            return BuildCode128C(value, fnc1);
        if (value.Any(ch => ch <= 31))
            return BuildCode128Fixed(value, start: 103, setA: true, fnc1);
        return BuildCode128Fixed(value, start: 104, setA: false, fnc1);
    }

    private static List<int>? BuildCode128Fixed(string value, int start, bool setA, bool fnc1 = false)
    {
        var codes = new List<int>(value.Length + 4) { start };
        if (fnc1) codes.Add(102);
        foreach (var ch in value)
        {
            if (ch <= 31)
            {
                if (!setA)
                {
                    codes.Add(101);
                    setA = true;
                }
                codes.Add(ch + 64);
            }
            else if (ch <= 95)
            {
                codes.Add(ch - 32);
            }
            else if (ch <= 126)
            {
                if (setA)
                {
                    codes.Add(100);
                    setA = false;
                }
                codes.Add(ch - 32);
            }
            else
                return null;
        }
        return codes;
    }

    private static List<int>? BuildCode128C(string value, bool fnc1 = false)
    {
        if (value.Length == 0 || !value.All(char.IsAsciiDigit))
            return BuildCode128Fixed(value, start: 104, setA: false, fnc1);

        var codes = new List<int>(value.Length / 2 + 5) { 105 };
        if (fnc1) codes.Add(102);
        var i = 0;
        while (i + 1 < value.Length)
        {
            codes.Add((value[i] - '0') * 10 + (value[i + 1] - '0'));
            i += 2;
        }
        if (i < value.Length)
        {
            codes.Add(100);
            codes.Add(value[i] - 32);
        }
        return codes;
    }

    /// <summary>ITF 숫자. 막대 5개 중 2개 wide. 0=nnwwn … 9=nwnwn.</summary>
    private static readonly string[] ItfDigits =
    [
        "nnwwn", "wnnnw", "nwnnw", "wwnnn", "nnwnw",
        "wnwnn", "nwwnn", "nnnww", "wnnwn", "nwnwn"
    ];

    /// <summary>
    /// Interleaved 2 of 5 / ITF-14(UPC Shipping).
    /// 폼텍 실측 N:W=1:2. ZXing·ISO 공칭은 1:3.
    /// </summary>
    public static bool[]? EncodeItf(string digits, BarcodeVendorKind vendor)
    {
        if (digits.Length < 2) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;
        if (digits.Length % 2 == 1)
            digits = "0" + digits;

        var wide = vendor == BarcodeVendorKind.Formtec ? 2 : 3;
        var bits = new List<bool>(8 + digits.Length * (3 + 2 * wide));
        void Elem(bool black, bool isWide)
        {
            var n = isWide ? wide : 1;
            for (var i = 0; i < n; i++) bits.Add(black);
        }

        Elem(true, false);
        Elem(false, false);
        Elem(true, false);
        Elem(false, false);
        for (var i = 0; i < digits.Length; i += 2)
        {
            var a = ItfDigits[digits[i] - '0'];
            var b = ItfDigits[digits[i + 1] - '0'];
            for (var k = 0; k < 5; k++)
            {
                Elem(true, a[k] == 'w');
                Elem(false, b[k] == 'w');
            }
        }
        Elem(true, true);
        Elem(false, false);
        Elem(true, false);
        return bits.ToArray();
    }

    /// <summary>MSI 숫자 0–9. 각 비트 1=wn, 0=nw. ZXing MSIWriter와 동일(N:W=1:2).</summary>
    private static readonly int[][] MsiDigits =
    [
        [1, 2, 1, 2, 1, 2, 1, 2],
        [1, 2, 1, 2, 1, 2, 2, 1],
        [1, 2, 1, 2, 2, 1, 1, 2],
        [1, 2, 1, 2, 2, 1, 2, 1],
        [1, 2, 2, 1, 1, 2, 1, 2],
        [1, 2, 2, 1, 1, 2, 2, 1],
        [1, 2, 2, 1, 2, 1, 1, 2],
        [1, 2, 2, 1, 2, 1, 2, 1],
        [2, 1, 1, 2, 1, 2, 1, 2],
        [2, 1, 1, 2, 1, 2, 2, 1]
    ];

    /// <summary>
    /// MSI (Modified Plessey). 폼텍은 왼쪽 짝수 자리×2 Mod10을 두 번(4912345678904→63).
    /// 일반 MSI-1010(오른쪽 이어붙여 ×2)은 22가 되어 폼텍과 다르다. ZXing은 체크 없음.
    /// </summary>
    public static bool[]? EncodeMsi(string digits, int checkCount, bool formtecChecks = false)
    {
        if (digits.Length == 0) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;

        var payload = digits;
        for (var i = 0; i < checkCount; i++)
            payload += formtecChecks ? MsiMod10Formtec(payload) : MsiMod10(payload);

        // 폼텍 실측 N:W≈1:2.3 (3:7). ZXing 표는 1:2.
        var narrow = formtecChecks ? 3 : 1;
        var wide = formtecChecks ? 7 : 2;
        int Map(int x) => x == 1 ? narrow : wide;

        var bits = new List<bool>(3 + payload.Length * 12 + 4);
        void Add(int[] widths)
        {
            var black = bits.Count == 0 || !bits[^1];
            foreach (var n in widths)
            {
                var run = Map(n);
                for (var i = 0; i < run; i++) bits.Add(black);
                black = !black;
            }
        }

        Add([2, 1]);
        foreach (var ch in payload)
            Add(MsiDigits[ch - '0']);
        Add([1, 2, 1]);
        return bits.ToArray();
    }

    /// <summary>폼텍 MSI 체크. 왼쪽부터 2·4·6… 자리를 2배(9 넘으면 −9)한 뒤 (10−합%10)%10.</summary>
    private static char MsiMod10Formtec(string digits)
    {
        var sum = 0;
        for (var i = 0; i < digits.Length; i++)
        {
            var n = digits[i] - '0';
            if (i % 2 == 1)
            {
                n *= 2;
                if (n > 9) n -= 9;
            }
            sum += n;
        }
        return (char)('0' + (10 - sum % 10) % 10);
    }

    /// <summary>MSI Mod 10. 오른쪽부터 홀수 자리를 이어 붙여 ×2, 자릿수 합 + 짝수 자리.</summary>
    private static char MsiMod10(string digits)
    {
        var odd = new StringBuilder((digits.Length + 1) / 2);
        var even = 0;
        for (var i = digits.Length - 1; i >= 0; i -= 2)
            odd.Append(digits[i]);
        for (var i = digits.Length - 2; i >= 0; i -= 2)
            even += digits[i] - '0';
        var prod = (long.Parse(odd.ToString()) * 2).ToString();
        var sum = even;
        foreach (var ch in prod)
            sum += ch - '0';
        return (char)('0' + (10 - sum % 10) % 10);
    }

    public static bool[]? EncodeDiscrete25(string id, string digits, BarcodeVendorKind vendor)
    {
        if (digits.Length == 0) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;

        var wide = vendor == BarcodeVendorKind.Formtec ? 2 : 3;
        return id switch
        {
            "I25_IATA" or "IATA" => Widths("1111", Industrial25, digits, "311", wide),
            "I25_DATALOGIC" or "DATALOGIC" => Widths("1111", Matrix25, digits, "311", wide),
            "I25_INDUSTRIAL" or "INDUSTRIAL" => Widths("313111", Industrial25, digits, "31113", wide),
            "I25_MATRIX" or "MATRIX" => Widths("311111", Matrix25, digits, "31111", wide),
            "COOP25" or "COOP" => Widths("311111", Coop25, digits, "31111", wide),
            "I25_INVERT" or "INVERT" => EncodeInvertFormtec(digits),
            _ => null
        };
    }

    public static bool[]? EncodeCodabar(string value, BarcodeVendorKind vendor, bool abc = false)
    {
        // 글자표는 rationalized(C=nnnwnww). 폼텍 일반 Codabar만 바깥 가드+Mod16.
        var s = (value ?? "").Trim().ToUpperInvariant();
        static bool Guard(char c) => c is >= 'A' and <= 'D';
        if (vendor == BarcodeVendorKind.Formtec && !abc)
        {
            // 폼텍 CODABAR(0x02) 실측: 저장값 그대로 + 바깥 A + (합%16) + A.
            // ABC_CODABAR(0x08)는 가드/체크를 넣지 않는다(ABCCBA).
            s = "A" + s + CodabarMod16(s) + "A";
        }
        else if (s.Length == 0)
            s = "A0A";
        else
        {
            if (!Guard(s[0])) s = "A" + s;
            if (!Guard(s[^1])) s += "A";
        }

        var widths = new StringBuilder(s.Length * 8);
        for (var i = 0; i < s.Length; i++)
        {
            if (!CodabarNw.TryGetValue(s[i], out var pat)) return null;
            widths.Append(pat);
            if (i < s.Length - 1)
                widths.Append('n');
        }
        // 폼텍은 1:2. 아이라벨 실측은 굵은 막대가 더 넓다(1:3).
        var wide = vendor == BarcodeVendorKind.ILabel ? 3 : 2;
        return NwToModules(widths.ToString(), wide);
    }

    public static bool[]? EncodeCode11(string value)
    {
        var sb = new StringBuilder(8 + value.Length * 6);
        sb.Append("112211");
        foreach (var ch in value)
        {
            if (!Code11.TryGetValue(ch, out var pat)) return null;
            sb.Append(pat);
        }
        sb.Append("112211");
        return WidthPatternToModules(sb.ToString());
    }

    public static bool[]? EncodeEan2(string digits)
    {
        if (digits.Length != 2) return null;
        var n = (digits[0] - '0') * 10 + (digits[1] - '0');
        var parity = (n % 4) switch
        {
            0 => "LL",
            1 => "LG",
            2 => "GL",
            _ => "GG"
        };
        return BuildEanAddon(digits, parity);
    }

    /// <summary>
    /// UPC-E0/E1. 6자리 데이터 + 체크 패리티.
    /// 넘버 시스템 1은 0의 패리티를 반전한다. 8자리 01234565 → 123456, 체크 5.
    /// </summary>
    private static readonly string[] UpcE0Parity =
    [
        "EEEOOO", "EEOEOO", "EEOOEO", "EEOOOE", "EOEEOO",
        "EOOEEO", "EOOOEE", "EOEOEO", "EOEOOE", "EOOEOE"
    ];

    /// <param name="numberSystem1">
    /// UPC-E0/E1처럼 넘버 시스템이 형식으로 정해진 경우 그 값. null이면 값의 첫 자리에서 읽는다.
    /// </param>
    /// <param name="hri">넘버 시스템 1자리 + 데이터 6자리 + 체크 1자리.</param>
    public static bool[]? EncodeUpcE(string digits, bool? numberSystem1, out string hri)
    {
        hri = "";
        if (!TrySplitUpcE(digits, numberSystem1, out var data, out var check, out var ns1))
            return null;

        var parity = UpcE0Parity[check];
        var bits = new List<bool>(51);
        void Add(string p)
        {
            foreach (var ch in p)
                bits.Add(ch == '1');
        }

        Add("101");
        for (var i = 0; i < 6; i++)
        {
            var even = parity[i] == 'E';
            if (ns1) even = !even;
            Add(Ean7(data[i] - '0', even ? 'G' : 'L'));
        }
        Add("010101");
        hri = $"{(ns1 ? '1' : '0')}{data}{check}";
        return bits.ToArray();
    }

    private static bool TrySplitUpcE(
        string digits, bool? numberSystem1, out string data, out int check, out bool ns1)
    {
        data = "";
        check = 0;
        ns1 = numberSystem1 ?? false;
        if (digits.Length == 0)
            return false;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return false;

        if (digits.Length == 8)
        {
            ns1 = numberSystem1 ?? digits[0] == '1';
            data = digits[1..7];
            check = digits[7] - '0';
            return true;
        }
        if (digits.Length == 7 && digits[0] is '0' or '1')
        {
            ns1 = numberSystem1 ?? digits[0] == '1';
            data = digits[1..];
            check = UpcECheckDigit(data, ns1);
            return true;
        }
        if (digits.Length == 7)
        {
            data = digits[..6];
            check = digits[6] - '0';
            return true;
        }
        if (digits.Length == 6)
        {
            data = digits;
            check = UpcECheckDigit(data, ns1);
            return true;
        }
        return false;
    }

    private static int UpcECheckDigit(string data6, bool ns1)
    {
        var ns = ns1 ? '1' : '0';
        var last = data6[5];
        var body = last switch
        {
            <= '2' => $"{ns}{data6[0]}{data6[1]}{last}0000{data6[2]}{data6[3]}{data6[4]}",
            '3' => $"{ns}{data6[0]}{data6[1]}{data6[2]}00000{data6[3]}{data6[4]}",
            '4' => $"{ns}{data6[0]}{data6[1]}{data6[2]}{data6[3]}00000{data6[4]}",
            _ => $"{ns}{data6[0]}{data6[1]}{data6[2]}{data6[3]}{data6[4]}0000{last}"
        };
        var sum = 0;
        for (var i = 0; i < 11; i++)
        {
            var n = body[i] - '0';
            var fromRight = 11 - i;
            sum += fromRight % 2 == 1 ? n * 3 : n;
        }
        return (10 - sum % 10) % 10;
    }

    public static bool[]? EncodeEan5(string digits)
    {
        if (digits.Length != 5) return null;
        var sum = 0;
        for (var i = 0; i < 5; i++)
            sum += (digits[i] - '0') * (i % 2 == 0 ? 3 : 9);
        var parity = (sum % 10) switch
        {
            0 => "GGLLL",
            1 => "GLGLL",
            2 => "GLLGL",
            3 => "GLLLG",
            4 => "LGGLL",
            5 => "LLGGL",
            6 => "LLLGG",
            7 => "LGLGL",
            8 => "LGLLG",
            _ => "LLGLG"
        };
        return BuildEanAddon(digits, parity);
    }

    public static bool[]? EncodeFim(string value)
    {
        var key = value.Trim().ToUpperInvariant();
        if (key.StartsWith("FIM")) key = key[3..].TrimStart('-', '_', ' ');
        var bits = key switch
        {
            "A" or "1" => "110010011",
            "B" or "2" => "101101101",
            "C" or "3" => "110101011",
            "D" or "4" => "111010111",
            _ => null
        };
        if (bits is null) return null;
        var modules = new bool[bits.Length];
        for (var i = 0; i < bits.Length; i++)
            modules[i] = bits[i] == '1';
        return modules;
    }

    /// <summary>
    /// 한국 우체국 우편번호.
    /// 폼텍: 5자리 앞에 9를 붙여 6자리로 만든 뒤 Zint처럼 역순 + Mod10.
    /// 아이라벨: 앞에 0을 붙이고, 0 + 나머지 5자리 역순 + Mod10. 숫자 1 표만 실측(08)과 같다.
    /// </summary>
    public static bool[]? EncodeKoreaPost(string raw, BarcodeVendorKind vendor = BarcodeVendorKind.Native)
    {
        var sb = new StringBuilder(6);
        foreach (var ch in raw)
        {
            if (char.IsAsciiDigit(ch))
                sb.Append(ch);
        }
        if (sb.Length == 0) return null;
        var digits = sb.ToString();
        if (digits.Length > 6)
            digits = digits[^6..];

        if (vendor == BarcodeVendorKind.ILabel)
            return EncodeKoreaPostILabel(digits);

        if (digits.Length == 6 && digits[0] == '9')
            digits = digits[1..];
        if (digits.Length <= 5)
            digits = "9" + digits.PadLeft(5, '0');
        else
            digits = digits.PadLeft(6, '0');

        return KoreaPostModules(digits, KoreaPostTableZint, reverseAllSix: true);
    }

    /// <summary>
    /// 아이라벨 Korean PostCode. 12345 → 패딩 012345, 그릴 자리 0543215.
    /// Zint(5432105)·폼텍(5432196)과 자리 순서가 다르다. 표의 1만 0813131313(실측 간격 11).
    /// </summary>
    private static bool[]? EncodeKoreaPostILabel(string digits)
    {
        var padded = digits.Length <= 5
            ? "0" + digits.PadLeft(5, '0')
            : digits.PadLeft(6, '0');
        var ordered = string.Concat(padded[0], ReverseDigits(padded[1..]));
        return KoreaPostModules(ordered, KoreaPostTableILabel, reverseAllSix: false);
    }

    private static readonly string[] KoreaPostTableZint =
    {
        "1313150613", "0713131313", "0417131313", "1506131313", "0413171313",
        "17171313", "1315061313", "0413131713", "17131713", "13171713"
    };

    /// <summary>Zint와 같으나 숫자 1의 선행 공백이 7이 아니라 8이다. 앞 숫자 끝 3과 합쳐 11이 된다.</summary>
    private static readonly string[] KoreaPostTableILabel =
    {
        "1313150613", "0813131313", "0417131313", "1506131313", "0413171313",
        "17171313", "1315061313", "0413131713", "17131713", "13171713"
    };

    private static bool[]? KoreaPostModules(string six, string[] table, bool reverseAllSix)
    {
        var pat = new StringBuilder(70);
        var sum = 0;
        if (reverseAllSix)
        {
            for (var i = 5; i >= 0; i--)
            {
                var d = six[i] - '0';
                pat.Append(table[d]);
                sum += d;
            }
        }
        else
        {
            foreach (var ch in six)
            {
                var d = ch - '0';
                pat.Append(table[d]);
                sum += d;
            }
        }
        pat.Append(table[(10 - sum % 10) % 10]);
        return DigitWidthsToModules(pat.ToString());
    }

    private static string ReverseDigits(string digits)
    {
        var chars = digits.ToCharArray();
        Array.Reverse(chars);
        return new string(chars);
    }

    /// <summary>폭 숫자 문자열. 홀수 위치=막대, 짝수=간격. 0은 해당 색을 건너뛴다.</summary>
    private static bool[]? DigitWidthsToModules(string widths)
    {
        var bits = new List<bool>(widths.Length * 4);
        var black = true;
        foreach (var ch in widths)
        {
            var n = ch - '0';
            if (n is < 0 or > 9)
            {
                black = !black;
                continue;
            }
            for (var i = 0; i < n; i++)
                bits.Add(black);
            black = !black;
        }
        return bits.Count > 0 ? bits.ToArray() : null;
    }

    /// <summary>POSTNET/PLANET. L=긴 막대, S=짧은 막대. 체크디지트 포함.</summary>
    public static string? EncodePostnetPattern(string digits, bool planet)
    {
        if (digits.Length == 0) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;

        var table = planet ? Planet : Postnet;
        var sb = new StringBuilder(2 + (digits.Length + 1) * 5);
        sb.Append('L');
        var sum = 0;
        foreach (var ch in digits)
        {
            var n = ch - '0';
            sb.Append(table[n]);
            sum += n;
        }
        sb.Append(table[(10 - sum % 10) % 10]);
        sb.Append('L');
        return sb.ToString();
    }

    public static string ToCode39FullAscii(string value)
    {
        var sb = new StringBuilder(value.Length * 2);
        foreach (var ch in value)
        {
            if (ch <= 127)
                sb.Append(Code39FullAscii[ch]);
        }
        return sb.ToString();
    }

    /// <summary>Pharmacode Two-track. 0=아래, 1=위, 2=전높이. Zint 규칙(base-3, LSB 오른쪽).</summary>
    public static int[]? EncodePharmaTwo(string digits)
    {
        if (digits.Length == 0 || !long.TryParse(digits, out var n) || n < 1 || n > 64570080)
            return null;
        var bars = new List<int>(16);
        while (n > 0)
        {
            switch (n % 3)
            {
                case 0:
                    bars.Add(2);
                    n = (n - 3) / 3;
                    break;
                case 1:
                    bars.Add(0);
                    n = (n - 1) / 3;
                    break;
                default:
                    bars.Add(1);
                    n = (n - 2) / 3;
                    break;
            }
        }
        bars.Reverse();
        return bars.ToArray();
    }

    /// <summary>폼텍 Codabar 체크: 문자값 합을 16으로 나눈 나머지. A–D는 16–19(≡0–3).</summary>
    private static char CodabarMod16(string payload)
    {
        const string alphabet = "0123456789-$:/.+ABCD";
        var sum = 0;
        foreach (var ch in payload)
        {
            var i = alphabet.IndexOf(ch);
            if (i >= 0) sum += i;
        }
        return alphabet[sum % 16];
    }

    private static bool[] NwToModules(string nw, int wide = 2)
    {
        var bits = new List<bool>(nw.Length * wide);
        var black = true;
        foreach (var ch in nw)
        {
            var n = ch == 'w' ? wide : 1;
            for (var i = 0; i < n; i++) bits.Add(black);
            black = !black;
        }
        return bits.ToArray();
    }

    public static bool[] WidthPatternToModules(string widths, int wide = 2)
    {
        var bits = new List<bool>(widths.Length * wide);
        var black = true;
        foreach (var ch in widths)
        {
            var n = ch == '1' ? 1 : wide;
            for (var i = 0; i < n; i++) bits.Add(black);
            black = !black;
        }
        return bits.ToArray();
    }

    private static bool[]? Widths(string start, string[] table, string digits, string stop, int wide)
    {
        var sb = new StringBuilder(start.Length + digits.Length * table[0].Length + stop.Length);
        sb.Append(start);
        foreach (var ch in digits)
            sb.Append(table[ch - '0']);
        sb.Append(stop);
        return WidthPatternToModules(sb.ToString(), wide);
    }

    /// <summary>
    /// 폼텍 2/5 Invert. 막대는 항상 좁고 Industrial 막대 굵기가 간격이 된다. N:W=1:3.
    /// </summary>
    private static bool[]? EncodeInvertFormtec(string digits)
    {
        var raw = new StringBuilder(6 + digits.Length * 10 + 6);
        raw.Append("313111");
        foreach (var ch in digits)
            raw.Append(Industrial25[ch - '0']);
        raw.Append("311131");
        var swapped = new StringBuilder(raw.Length + 1);
        var pat = raw.ToString();
        for (var i = 0; i + 1 < pat.Length; i += 2)
        {
            swapped.Append('1');
            swapped.Append(pat[i]);
        }
        swapped.Append('1');
        return WidthPatternToModules(swapped.ToString(), wide: 3);
    }

    private static bool[]? BuildEanAddon(string digits, string parity)
    {
        var bits = new List<bool>(6 + digits.Length * 9);
        void Add(string p)
        {
            foreach (var ch in p) bits.Add(ch == '1');
        }
        Add("01011");
        for (var i = 0; i < digits.Length; i++)
        {
            if (i > 0) Add("01");
            Add(Ean7(digits[i] - '0', parity[i]));
        }
        return bits.ToArray();
    }

    private static string Ean7(int digit, char set) => (set, digit) switch
    {
        ('L', 0) => "0001101",
        ('L', 1) => "0011001",
        ('L', 2) => "0010011",
        ('L', 3) => "0111101",
        ('L', 4) => "0100011",
        ('L', 5) => "0110001",
        ('L', 6) => "0101111",
        ('L', 7) => "0111011",
        ('L', 8) => "0110111",
        ('L', 9) => "0001011",
        ('G', 0) => "0100111",
        ('G', 1) => "0110011",
        ('G', 2) => "0011011",
        ('G', 3) => "0100001",
        ('G', 4) => "0011101",
        ('G', 5) => "0111001",
        ('G', 6) => "0000101",
        ('G', 7) => "0010001",
        ('G', 8) => "0001001",
        _ => "0010111"
    };
}
