using System.Numerics;
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
    /// <summary>
    /// 구형 PSOFT 엔진으로 만든 애니라벨 바코드(LBL 0x07 객체).
    /// 그리기는 신형과 같지만 코다바 인코딩 규칙이 달라 따로 구분한다.
    /// </summary>
    public const string AniLabelLegacyVendor = "anylabel-psoft";

    public static BarcodeVendorKind VendorOf(DesignObject obj)
    {
        var v = (obj.BarcodeVendor ?? "").Trim().ToLowerInvariant();
        return v switch
        {
            "formtec" => BarcodeVendorKind.Formtec,
            "anylabel" or AniLabelLegacyVendor => BarcodeVendorKind.AniLabel,
            "ilabel" => BarcodeVendorKind.ILabel,
            _ => BarcodeVendorKind.Native
        };
    }

    public static bool IsAniLabelLegacy(DesignObject obj)
        => string.Equals(obj.BarcodeVendor, AniLabelLegacyVendor, StringComparison.OrdinalIgnoreCase);

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

    /// <summary>
    /// RM4SCC·KIX 4상태의 막대 4개짜리 비트열. 1이 상승부(또는 하강부)가 있는 자리다.
    /// 값 0~35을 6×6으로 나눠 몫이 윗변, 나머지가 아랫변 비트열을 고른다.
    /// 가중치 4·2·1·0 합이 곧 (색인+1)이라 체크문자 계산에 그대로 쓴다.
    /// </summary>
    private static readonly string[] FourStateBits =
        ["0011", "0101", "0110", "1001", "1010", "1100"];

    /// <summary>
    /// RM4SCC·KIX 4상태. 막대마다 T(가운데)·A(위)·D(아래)·F(전체) 한 글자를 돌려준다.
    /// 시작 막대는 A, 정지 막대는 F다. 체크문자는 윗변·아랫변 값 합을 각각 6으로 나눈 나머지다.
    /// 애니라벨 구형 EMF 실측으로 확인했다(27번 칸 `123456789`+체크, 29번 칸 `3224BC10`).
    /// </summary>
    public static string? EncodeFourState(string value, bool startStop, bool check)
    {
        var values = new List<int>(value.Length);
        foreach (var ch in value.Trim().ToUpperInvariant())
        {
            if (ch is ' ' or '-') continue;
            var v = ch switch
            {
                >= '0' and <= '9' => ch - '0',
                >= 'A' and <= 'Z' => ch - 'A' + 10,
                _ => -1
            };
            if (v < 0) return null;
            values.Add(v);
        }
        if (values.Count == 0) return null;

        if (check)
        {
            var top = 0;
            var bottom = 0;
            foreach (var v in values)
            {
                top += v / 6 + 1;
                bottom += v % 6 + 1;
            }
            values.Add((top + 5) % 6 * 6 + (bottom + 5) % 6);
        }

        var sb = new StringBuilder((values.Count + 2) * 4);
        if (startStop) sb.Append('A');
        foreach (var v in values)
        {
            var top = FourStateBits[v / 6];
            var bottom = FourStateBits[v % 6];
            for (var i = 0; i < 4; i++)
                sb.Append((top[i] == '1', bottom[i] == '1') switch
                {
                    (true, true) => 'F',
                    (true, false) => 'A',
                    (false, true) => 'D',
                    _ => 'T'
                });
        }
        if (startStop) sb.Append('F');
        return sb.ToString();
    }

    /// <summary>
    /// USPS-B-3200 부록 D 표 IV. 10개 문자의 13비트가 막대 130자리(앞 65 = 상승, 뒤 65 = 하강) 중
    /// 어디로 가는지 알려주는 역방향 표다. 값은 1부터 시작한다.
    /// </summary>
    private static readonly byte[] ImbBarMap =
    [
        67, 6, 78, 16, 86, 95, 34, 40, 45, 113, 117, 121, 62, 87, 18, 104, 41, 76, 57, 119,
        115, 72, 97, 2, 127, 26, 105, 35, 122, 52, 114, 7, 24, 82, 68, 63, 94, 44, 77, 112,
        70, 100, 39, 30, 107, 15, 125, 85, 10, 65, 54, 88, 20, 106, 46, 66, 8, 116, 29, 61,
        99, 80, 90, 37, 123, 51, 25, 84, 129, 56, 4, 109, 96, 28, 36, 47, 11, 71, 33, 102,
        21, 9, 17, 49, 124, 79, 64, 91, 42, 69, 53, 60, 14, 1, 27, 103, 126, 75, 89, 50,
        120, 19, 32, 110, 92, 111, 130, 59, 31, 12, 81, 43, 55, 5, 74, 22, 101, 128, 58, 118,
        48, 108, 38, 98, 93, 23, 83, 13, 73, 3
    ];

    private static ushort[]? _imb5Of13;
    private static ushort[]? _imb2Of13;

    /// <summary>
    /// USPS-B-3200 부록 C의 표 생성 절차. 13비트 중 1이 <paramref name="ones"/>개인 값을
    /// (값, 비트 뒤집은 값) 쌍으로 앞에서부터 채우고, 좌우 대칭인 값은 뒤에서부터 채운다.
    /// </summary>
    private static ushort[] BuildNOf13(int ones, int size)
    {
        var table = new ushort[size];
        int lo = 0, hi = size - 1;
        for (var v = 0; v < 1 << 13; v++)
        {
            var bits = 0;
            for (var i = 0; i < 13; i++) bits += (v >> i) & 1;
            if (bits != ones) continue;

            var rev = 0;
            for (var i = 0; i < 13; i++)
                if ((v & (1 << i)) != 0)
                    rev |= 1 << (12 - i);
            if (rev < v) continue;

            if (rev == v)
            {
                table[hi--] = (ushort)v;
            }
            else
            {
                table[lo++] = (ushort)v;
                table[lo++] = (ushort)rev;
            }
        }
        return table;
    }

    /// <summary>USPS-B-3200의 11비트 프레임 검사열. 102비트를 오른쪽 정렬한 13바이트를 받는다.</summary>
    private static int ImbFrameCheck(byte[] bytes)
    {
        const int generator = 0x0F35;
        var fcs = 0x07FF;
        var data = bytes[0] << 5;
        for (var bit = 2; bit < 8; bit++)
        {
            fcs = ((fcs ^ data) & 0x400) != 0 ? (fcs << 1) ^ generator : fcs << 1;
            fcs &= 0x7FF;
            data <<= 1;
        }
        for (var i = 1; i < 13; i++)
        {
            data = bytes[i] << 3;
            for (var bit = 0; bit < 8; bit++)
            {
                fcs = ((fcs ^ data) & 0x400) != 0 ? (fcs << 1) ^ generator : fcs << 1;
                fcs &= 0x7FF;
                data <<= 1;
            }
        }
        return fcs;
    }

    /// <summary>
    /// USPS Intelligent Mail(OneCode) 4상태 65막대. 추적번호 20자리 뒤에 배달구역 0·5·9·11자리가 붙는다.
    /// 막대마다 T(추적)·A(상승)·D(하강)·F(전체) 한 글자를 돌려준다.
    /// 애니라벨 구형 0x32 실측(40번 칸 `01-234-567094-987654321-01234567891`)과 65막대가 모두 같다.
    /// </summary>
    /// <remarks>
    /// 자릿수가 모자라면 null 을 돌려준다. 애니라벨은 이때 제 샘플값을 대신 찍는데
    /// (52번 칸은 값이 `1234567890` 인데 `20704987654321654321123456789` 를 그린다)
    /// 남의 우편 추적번호를 인쇄물에 싣는 셈이라 따라 하지 않는다.
    /// 그리는 쪽에서 `BarcodeRenderer.ImbShortage` 가 먼저 걸러 무엇이 모자란지 알려 준다.
    /// </remarks>
    public static string? EncodeImb(string value)
    {
        var digits = new StringBuilder(32);
        foreach (var ch in value ?? "")
            if (char.IsAsciiDigit(ch))
                digits.Append(ch);
        if (digits.Length < 20) return null;

        var tracker = digits.ToString(0, 20);
        var routing = digits.ToString(20, digits.Length - 20);
        if (routing.Length is not (0 or 5 or 9 or 11)) return null;

        var accum = routing.Length == 0 ? BigInteger.Zero : BigInteger.Parse(routing);
        accum += routing.Length switch
        {
            11 => 1000100001,
            9 => 100001,
            5 => 1,
            _ => 0
        };
        accum = accum * 10 + (tracker[0] - '0');
        accum = accum * 5 + (tracker[1] - '0');
        for (var i = 2; i < 20; i++)
            accum = accum * 10 + (tracker[i] - '0');

        var masked = accum & ((BigInteger.One << 102) - 1);
        var bytes = new byte[13];
        for (var i = 12; i >= 0; i--)
        {
            bytes[i] = (byte)(masked & 0xFF);
            masked >>= 8;
        }
        var fcs = ImbFrameCheck(bytes);

        var codewords = new int[10];
        codewords[9] = (int)(accum % 636);
        accum /= 636;
        for (var j = 8; j > 0; j--)
        {
            codewords[j] = (int)(accum % 1365);
            accum /= 1365;
        }
        codewords[0] = (int)accum;

        codewords[9] *= 2;
        if (fcs >= 1024) codewords[0] += 659;

        _imb5Of13 ??= BuildNOf13(5, 1287);
        _imb2Of13 ??= BuildNOf13(2, 78);

        var characters = new int[10];
        for (var i = 0; i < 10; i++)
        {
            var cw = codewords[i];
            if (cw < 0 || cw >= 1365) return null;
            characters[i] = cw < 1287 ? _imb5Of13[cw] : _imb2Of13[cw - 1287];
            if ((fcs & (1 << i)) != 0)
                characters[i] = 0x1FFF - characters[i];
        }

        var map = new int[130];
        for (var i = 0; i < 10; i++)
            for (var j = 0; j < 13; j++)
                map[ImbBarMap[13 * i + j] - 1] = (characters[i] >> j) & 1;

        var sb = new StringBuilder(65);
        for (var i = 0; i < 65; i++)
        {
            var ascender = map[i] == 0;
            var descender = map[i + 65] == 0;
            sb.Append((ascender, descender) switch
            {
                (true, true) => 'T',
                (true, false) => 'A',
                (false, true) => 'D',
                _ => 'F'
            });
        }
        return sb.ToString();
    }

    /// <summary>Australia Post N 표. 숫자 한 자리가 막대 2개가 된다(0=전체·1=상승·2=하강·3=추적).</summary>
    private static readonly string[] AusPostN =
        ["00", "01", "02", "10", "11", "12", "20", "21", "22", "30"];

    /// <summary>Australia Post 고객정보(C) 표에 쓰는 문자 64개. 자리가 곧 표 번호다.</summary>
    private const string AusPostGdSet =
        "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz #";

    /// <summary>Australia Post C 표. 글자 하나가 막대 3개가 된다.</summary>
    private static readonly string[] AusPostC =
    [
        "222", "300", "301", "302", "310", "311", "312", "320",
        "321", "322", "000", "001", "002", "010", "011", "012",
        "020", "021", "022", "100", "101", "102", "110", "111",
        "112", "120", "121", "122", "200", "201", "202", "210",
        "211", "212", "220", "221", "023", "030", "031", "032",
        "033", "103", "113", "123", "130", "131", "132", "133",
        "203", "213", "223", "230", "231", "232", "233", "303",
        "313", "323", "330", "331", "332", "333", "003", "013"
    ];

    /// <summary>
    /// Australia Post 4상태. 시작 `13` + FCC 2자리 + 배달구역 8자리 + 고객정보 + 채움막대
    /// + Reed-Solomon 4심볼 + 정지 `13` 으로, 막대 수가 37·52·67 중 하나가 된다.
    ///
    /// 고객정보가 숫자만이면 N 표(한 자리 막대 2개), 아니면 C 표(한 글자 막대 3개)를 쓴다.
    /// 오류정정은 GF(2^6), 원시다항식 x^6+x+1(0x43), 생성다항식 (x-a^1)..(x-a^4)이다.
    ///
    /// 애니라벨은 값 앞 두 자리를 FCC 로 준다. 구형 0x33(41번 칸 `1196184209`)은
    /// FCC 11 + 배달구역 96184209 로 37막대, 신형 0x30(49번 칸 `5932211324A124B`)은
    /// FCC 59 + 배달구역 32211324 + 고객정보 `A124B` 로 52막대다.
    /// 뒤쪽 검사심볼 26·23·20·52 까지 원본 그림과 같은 것을 확인했다.
    /// </summary>
    public static string? EncodeAusPost(string value, out string hri)
    {
        hri = "";
        var body = new StringBuilder(24);
        foreach (var ch in value ?? "")
            if (!char.IsWhiteSpace(ch) || ch == ' ')
                body.Append(ch);
        var payload = body.ToString().Trim();
        if (payload.Length < 10 || payload.Length > 23) return null;
        for (var i = 0; i < 10; i++)
            if (!char.IsAsciiDigit(payload[i])) return null;

        var states = new StringBuilder(67);
        states.Append("13");
        for (var i = 0; i < 10; i++)
            states.Append(AusPostN[payload[i] - '0']);

        var customer = payload[10..];
        var allDigits = customer.Length > 0 && customer.All(char.IsAsciiDigit);
        foreach (var ch in customer)
        {
            if (allDigits)
            {
                states.Append(AusPostN[ch - '0']);
                continue;
            }
            var idx = AusPostGdSet.IndexOf(ch);
            if (idx < 0) return null;
            states.Append(AusPostC[idx]);
        }

        // 채움막대는 막대 수가 23·38·53 중 하나가 될 때까지 넣는다(검사심볼 12개와 정지 2개를 빼고 센 값).
        if (states.Length > 53) return null;
        while (states.Length is not (23 or 38 or 53))
            states.Append('3');

        var data = new int[(states.Length - 2) / 3];
        for (var i = 0; i < data.Length; i++)
            data[i] = (states[2 + i * 3] - '0') * 16
                      + (states[3 + i * 3] - '0') * 4
                      + (states[4 + i * 3] - '0');

        var parity = AusPostReedSolomon(data);
        foreach (var sym in parity)
        {
            states.Append((char)('0' + sym / 16));
            states.Append((char)('0' + sym / 4 % 4));
            states.Append((char)('0' + sym % 4));
        }
        states.Append("13");

        // 애니라벨 캡션 그대로: FCC · 배달구역 · 고객정보 · 검사심볼 네 개를 띄어 쓴다.
        hri = $"{payload[..2]} {payload[2..10]}"
              + (customer.Length > 0 ? " " + customer : "")
              + " " + string.Join(' ', parity);

        var sb = new StringBuilder(states.Length);
        foreach (var st in states.ToString())
            sb.Append(st switch { '0' => 'F', '1' => 'A', '2' => 'D', _ => 'T' });
        return sb.ToString();
    }

    /// <summary>
    /// PostBar(캐나다우정 4상태) 우편번호 알파벳. 막대 3개짜리다.
    /// 만료된 캐나다우정 특허 US 5,602,382 표 1 그대로다. M=0 부터 시작하고 14번이 비어 있다.
    /// </summary>
    private static readonly Dictionary<char, string> PostBarAlpha = new()
    {
        ['M'] = "HHH", ['H'] = "HHA", ['A'] = "HHD", ['B'] = "HAH", ['C'] = "HAA",
        ['D'] = "HAD", ['E'] = "HDH", ['F'] = "HDA", ['G'] = "HDD", ['Z'] = "AHH",
        ['I'] = "AHA", ['J'] = "AHD", ['K'] = "AAH", ['L'] = "AAA", ['N'] = "ADH",
        ['O'] = "ADA", ['P'] = "ADD", ['Q'] = "DHH", ['R'] = "DHA", ['S'] = "DHD",
        ['T'] = "DAH", ['U'] = "DAA", ['V'] = "DAD", ['W'] = "DDH", ['X'] = "DDA",
        ['Y'] = "DDD"
    };

    /// <summary>
    /// PostBar 우편번호 숫자. 막대 2개짜리다. 9만 T 막대를 쓰는데,
    /// T가 제일 짧아 뭉개지기 쉬워서 우편번호에 T가 적게 나오게 한 규격 의도다.
    /// </summary>
    private static readonly string[] PostBarNum =
        ["HH", "HA", "HD", "AH", "AA", "AD", "DH", "DA", "DD", "TH"];

    /// <summary>PostBar 영숫자(Z 문자). A~Z=0~25, 0~9=26~35 을 3진 세 자리로 편다. 공백만 HHT 로 따로 둔다.</summary>
    private static string? PostBarZ(char ch)
    {
        if (ch == ' ') return "HHT";
        var v = ch switch
        {
            >= 'A' and <= 'Z' => ch - 'A',
            >= '0' and <= '9' => 26 + (ch - '0'),
            _ => -1
        };
        if (v < 0) return null;
        const string bars = "HADT";
        return $"{bars[v / 9]}{bars[v / 3 % 3]}{bars[v % 3]}";
    }

    /// <summary>
    /// PostBar(캐나다우정 4상태) D12 형식 49막대.
    /// 시작 `AT` + 형식문자 1 + 우편번호 ANANAN + 주소코드 4 + 공백 + 검사심볼 4 + 정지 `AT`.
    ///
    /// 막대 셋을 2비트씩 이어 붙여 GF(2^6) 심볼 하나로 보고(H=0 A=1 D=2 T=3),
    /// Australia Post 와 같은 원시다항식 x^6+x+1 로 Reed-Solomon 검사심볼 4개를 만든다.
    /// 심볼 차례가 오른쪽에서 왼쪽이라, 맨 오른쪽 세 막대가 최하위 심볼이다.
    ///
    /// 규격은 만료된 캐나다우정 특허 US 5,602,382 에서 가져왔다(BWIPP·Zint 모두 이 심볼이 없다).
    /// 애니라벨 신형 0x32(51번 칸 `BK1A4S21234`)의 49막대와 한 개도 다르지 않고,
    /// 캡션 뒤 `18283160` 도 검사심볼 18·28·31·60 을 두 자리씩 붙여 쓴 것이다.
    /// </summary>
    public static string? EncodePostBar(string value, out string hri)
    {
        hri = "";
        var src = (value ?? "").Trim().ToUpperInvariant().Replace(" ", "");
        if (src.Length != 11) return null;

        var dci = PostBarZ(src[0]);
        if (dci is null) return null;

        var bars = new StringBuilder(49);
        bars.Append("AT").Append(dci);
        for (var i = 0; i < 6; i++)
        {
            var ch = src[1 + i];
            if (i % 2 == 0)
            {
                if (!PostBarAlpha.TryGetValue(ch, out var a)) return null;
                bars.Append(a);
            }
            else
            {
                if (!char.IsAsciiDigit(ch)) return null;
                bars.Append(PostBarNum[ch - '0']);
            }
        }
        for (var i = 0; i < 4; i++)
        {
            var z = PostBarZ(src[7 + i]);
            if (z is null) return null;
            bars.Append(z);
        }
        bars.Append("HHT");     // 자리 채우는 공백 칸

        static int Val(char b) => b switch { 'H' => 0, 'A' => 1, 'D' => 2, _ => 3 };
        var data = new int[(bars.Length - 2) / 3];
        for (var i = 0; i < data.Length; i++)
            data[i] = Val(bars[2 + i * 3]) * 16 + Val(bars[3 + i * 3]) * 4 + Val(bars[4 + i * 3]);

        var parity = AusPostReedSolomon(data);
        foreach (var sym in parity)
        {
            bars.Append("HADT"[sym >> 4]);
            bars.Append("HADT"[sym >> 2 & 3]);
            bars.Append("HADT"[sym & 3]);
        }
        bars.Append("AT");

        // 애니라벨 캡션 그대로: 형식문자 · 우편번호 · 주소코드 · 검사심볼 네 개를 띄어 쓴다.
        hri = $"{src[0]} {src[1..7]} {src[7..]} " + string.Concat(parity.Select(p => p.ToString("D2")));

        var sb = new StringBuilder(bars.Length);
        foreach (var b in bars.ToString())
            sb.Append(b == 'H' ? 'F' : b);
        return sb.ToString();
    }

    /// <summary>CPC Binary 글자 네 자리 값. 우편번호 알파벳 20개 중 열세 개가 이 자리를 쓴다.</summary>
    private static readonly Dictionary<char, int> CpcLetter = new()
    {
        ['L'] = 0x2, ['K'] = 0x3, ['M'] = 0x4, ['R'] = 0x5, ['J'] = 0x6, ['A'] = 0x7,
        ['H'] = 0x8, ['G'] = 0x9, ['S'] = 0xA, ['C'] = 0xB, ['B'] = 0xC, ['E'] = 0xD,
        ['Y'] = 0xE
    };

    /// <summary>CPC Binary 숫자 네 자리 값. 자리가 뒤섞여 있어 표로 둔다.</summary>
    private static readonly int[] CpcDigit = [0xA, 0x2, 0x9, 0x3, 0xB, 0x5, 0x6, 0x7, 0xD, 0xE];

    /// <summary>글자·숫자 짝의 자리가 뒤바뀌는 알파벳 다섯 개. 이 글자들은 아래 네 자리로 간다.</summary>
    private static readonly Dictionary<char, int> CpcLetterLow = new()
    {
        ['Z'] = 0x0, ['N'] = 0x1, ['T'] = 0x4, ['W'] = 0x8, ['P'] = 0xC
    };

    /// <summary>X 로 시작하는 짝은 표 어디에도 규칙이 없어 열 개를 통째로 적어 둔다.</summary>
    private static readonly int[] CpcX =
        [0x11, 0x14, 0x1C, 0x41, 0x44, 0x4C, 0xC1, 0xC4, 0xCC, 0x84];

    private static int CpcPair(char letter, char digit)
    {
        var d = digit - '0';
        if (letter == 'X') return CpcX[d];
        if (CpcLetterLow.TryGetValue(letter, out var low)) return (CpcDigit[d] << 4) | low;
        return CpcLetter.TryGetValue(letter, out var hi) ? (hi << 4) | CpcDigit[d] : -1;
    }

    /// <summary>CPC Binary 가운데 글자 한 개. 값은 다섯 자리 안에 들지만 자리는 여덟 칸을 쓴다.</summary>
    private static readonly Dictionary<char, int> CpcMid = new()
    {
        ['L'] = 0x02, ['K'] = 0x03, ['M'] = 0x04, ['R'] = 0x05, ['J'] = 0x06, ['A'] = 0x07,
        ['H'] = 0x08, ['G'] = 0x09, ['S'] = 0x0A, ['C'] = 0x0B, ['B'] = 0x0C, ['E'] = 0x0D,
        ['Y'] = 0x0E, ['V'] = 0x11, ['X'] = 0x13, ['T'] = 0x14, ['N'] = 0x16, ['W'] = 0x18,
        ['Z'] = 0x1A, ['P'] = 0x1C
    };

    /// <summary>CPC Binary 한 심볼의 자리 수. 아래 자리 나눔의 합이다.</summary>
    private const int CpcSlots = 30;

    /// <summary>
    /// CPC Binary(캐나다우정 이진 코드). 캐나다 우편번호 여섯 글자를 30자리 비트로 만든다.
    /// 자리마다 막대가 있고 없고로만 읽는 위치 변조라, 막대 폭과 높이는 모두 같다.
    ///
    /// 자리 나눔은 [패리티 1][앞 두 글자 8][가운데 글자 8][가운데 숫자 4][뒤 두 글자 8][정렬 1] 이다.
    /// 정렬 막대는 늘 찍고, 패리티 막대는 찍히는 막대 수가 홀수가 되게 할 때만 찍는다.
    /// 앞 두 글자와 뒤 두 글자는 같은 표를 같은 차례로 쓴다
    /// (`A0B4A0` 의 1~8번 자리와 21~28번 자리가 `01111010` 으로 똑같다).
    ///
    /// 캐나다우정이 규격을 공개한 적이 없어 공개된 코드포인트 표를 옮겼다.
    ///
    /// 애니라벨 신형 0x31 표본 여섯 개(`A0B4A0`·`A2B4A0`·`B0B4A0`·`C0B4A0`·`L3B4A0`·`L3B4T9`)로
    /// 자리 나눔을 실측해 맞췄다. 전에 가운데 글자를 다섯 자리로 본 것이 틀려 심볼이 세 자리
    /// 짧았고, 그 탓에 뒤쪽 자리가 통째로 밀려 값이 다 어긋나 보였다. 표 자체는 맞았다.
    /// 이제 여섯 중 다섯이 한 자리도 다르지 않다.
    ///
    /// 남는 하나가 `T9` 이고, 이건 애니라벨 쪽 고장이다. 글자·숫자 자리가 뒤바뀌는 다섯 글자
    /// (Z·N·T·W·P)를 표본으로 받아 보니 `T0B4A0`·`W0B4A0`·`Z0B4A0`·`P0B4A0`·`N0B4A0` 의
    /// 막대 띠가 **바이트 단위로 똑같다**. 애니라벨은 이 다섯 글자를 구별하지 못하고 숫자만
    /// 보고 찍는다(숫자 0 이면 `0xB0`, 9 면 `0xF0`, 아랫자리는 늘 0). 서로 다른 우편번호가
    /// 한 그림으로 나오니 되읽을 수가 없다. 우리 표는 다섯을 제대로 가른다.
    /// </summary>
    public static bool[]? EncodeCpcBinary(string value)
    {
        var pc = (value ?? "").Trim().ToUpperInvariant().Replace(" ", "").Replace("-", "");
        if (pc.Length != 6) return null;
        if (!char.IsAsciiDigit(pc[1]) || !char.IsAsciiDigit(pc[3]) || !char.IsAsciiDigit(pc[5]))
            return null;

        var f1 = CpcPair(pc[0], pc[1]);
        var f4 = CpcPair(pc[4], pc[5]);
        if (f1 < 0 || f4 < 0) return null;
        if (!CpcMid.TryGetValue(pc[2], out var f2)) return null;
        var f3 = CpcDigit[pc[3] - '0'];

        var bits = new bool[CpcSlots];
        for (var i = 0; i < 8; i++) bits[1 + i] = (f1 >> (7 - i) & 1) != 0;
        for (var i = 0; i < 8; i++) bits[9 + i] = (f2 >> (7 - i) & 1) != 0;
        for (var i = 0; i < 4; i++) bits[17 + i] = (f3 >> (3 - i) & 1) != 0;
        for (var i = 0; i < 8; i++) bits[21 + i] = (f4 >> (7 - i) & 1) != 0;
        bits[CpcSlots - 1] = true;                        // 정렬 막대
        bits[0] = bits.Count(b => b) % 2 == 0;            // 찍히는 막대 수를 홀수로

        // 패리티 막대가 없으면 그 한 자리를 통째로 뺀다. 애니라벨 표본 여섯 개가 모두 그렇다
        // (`L3B4T9` 는 패리티가 비어 29자리로 나오고, 그 뒤 빈 자리 둘은 그대로 남는다).
        // 읽을 때는 오른쪽 정렬막대부터 세므로 앞자리가 빠져도 자리 번호가 흔들리지 않는다.
        var first = bits[0] ? 0 : 1;
        var count = CpcSlots - first;

        // 한 자리가 막대 1모듈 + 빈칸 2모듈이다. 마지막 빈칸은 두지 않는다.
        var modules = new bool[count * 3 - 2];
        for (var i = first; i < CpcSlots; i++)
            if (bits[i]) modules[(i - first) * 3] = true;
        return modules;
    }

    /// <summary>Japan Post 문자 19개. 자리가 곧 표 번호다.</summary>
    private const string JapanPostSet = "1234567890-abcdefgh";

    /// <summary>검사문자를 셀 때 쓰는 차례. 위 표와 숫자 자리만 다르다.</summary>
    private const string JapanPostCheckSet = "0123456789-abcdefgh";

    /// <summary>Japan Post 표. 글자 하나가 막대 3개다(0=전체·1=상승·2=하강·3=추적).</summary>
    private static readonly string[] JapanPostTable =
    [
        "003", "021", "201", "012", "030",
        "210", "102", "120", "300", "033",
        "303", "213", "231", "123", "321",
        "132", "312", "330", "000"
    ];

    /// <summary>
    /// Japan Post 고객 바코드(카스타마 바코드) 4상태 67막대.
    /// 시작 `02` + 20글자 × 막대 3개 + 검사문자 3개 + 정지 `20`.
    ///
    /// 숫자와 `-` 는 그대로 쓰고 영문은 두 글자로 편다(A~J=a0~a9, K~T=b0~b9, U~Z=c0~c5).
    /// 20글자가 안 되면 `d` 로 채운다. 검사문자는 스무 글자의 표 번호 합을 19로 나눈 나머지로 셈한다.
    ///
    /// 우편번호는 `123-4567` 처럼 적어도 막대에는 하이픈 없이 일곱 자리로 들어간다(규격 그대로다).
    /// 애니라벨 신형 0x2E(47번 칸 `123-4567-890-A`)가 `1234567-890-a0` + 채움 `d` 여섯 개로
    /// 나오고 검사문자도 `a` 로 같다.
    /// </summary>
    public static string? EncodeJapanPost(string value, out string hri)
    {
        hri = "";
        var src = (value ?? "").Trim().ToUpperInvariant();
        if (src.Length == 0) return null;

        // 앞머리가 `NNN-NNNN` 이면 우편번호라 하이픈을 뺀다.
        if (src.Length >= 8 && src[3] == '-'
            && src[..3].All(char.IsAsciiDigit) && src.Substring(4, 4).All(char.IsAsciiDigit))
            src = src[..3] + src[4..];

        var inter = new StringBuilder(20);
        foreach (var ch in src)
        {
            if (inter.Length >= 20) break;
            if (char.IsAsciiDigit(ch) || ch == '-')
            {
                inter.Append(ch);
            }
            else if (ch is >= 'A' and <= 'Z')
            {
                if (inter.Length > 18) break;
                inter.Append(ch <= 'J' ? 'a' : ch <= 'T' ? 'b' : 'c');
                inter.Append((char)('0' + (ch <= 'J' ? ch - 'A' : ch <= 'T' ? ch - 'K' : ch - 'U')));
            }
            else
            {
                return null;
            }
        }
        while (inter.Length < 20) inter.Append('d');

        var states = new StringBuilder(67);
        states.Append("02");
        var sum = 0;
        foreach (var ch in inter.ToString())
        {
            var idx = JapanPostSet.IndexOf(ch);
            if (idx < 0) return null;
            states.Append(JapanPostTable[idx]);
            sum += JapanPostCheckSet.IndexOf(ch);
        }

        var check = 19 - sum % 19;
        if (check == 19) check = 0;
        var checkChar = check <= 9 ? (char)('0' + check) : check == 10 ? '-' : (char)('a' + check - 11);
        states.Append(JapanPostTable[JapanPostSet.IndexOf(checkChar)]);
        states.Append("20");

        hri = src + char.ToUpperInvariant(checkChar);

        var sb = new StringBuilder(states.Length);
        foreach (var st in states.ToString())
            sb.Append(st switch { '0' => 'F', '1' => 'A', '2' => 'D', _ => 'T' });
        return sb.ToString();
    }

    private static int[] AusPostReedSolomon(int[] data, int nsym = 4)
    {
        var exp = new int[63];
        var log = new int[64];
        var x = 1;
        for (var i = 0; i < 63; i++)
        {
            exp[i] = x;
            log[x] = i;
            x <<= 1;
            if ((x & 0x40) != 0) x ^= 0x43;
        }

        int Mul(int a, int b) => a == 0 || b == 0 ? 0 : exp[(log[a] + log[b]) % 63];

        var gen = new int[] { 1 };
        for (var i = 1; i <= nsym; i++)
        {
            var next = new int[gen.Length + 1];
            for (var j = 0; j < gen.Length; j++)
            {
                next[j] ^= gen[j];                       // gen[0]이 최고차
                next[j + 1] ^= Mul(gen[j], exp[i]);
            }
            gen = next;
        }

        var rem = new int[nsym];
        foreach (var d in data)
        {
            var feed = d ^ rem[0];
            Array.Copy(rem, 1, rem, 0, nsym - 1);
            rem[nsym - 1] = 0;
            if (feed == 0) continue;
            for (var j = 0; j < nsym; j++)
                rem[j] ^= Mul(gen[j + 1], feed);
        }
        return rem;
    }

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
        bool fnc1 = false, bool aniLabelEan128 = false)
    {
        var codes = aniLabelEan128
            ? BuildCode128Ean128AniLabel(value)
            : formtecEan128 || (gs1 && !fnc1)
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

    /// <summary>
    /// 애니라벨 신형 EAN-128. Start B + FNC1 로 열고, 중간에 숫자가 길게 이어지면 Set C 로 묶는다.
    ///
    /// 「1D barcode 타입.lbl」 36번 칸(AB1234567890) 원본 그림을 풀면
    /// StartB FNC1 A B →C 12 34 56 78 90 79 Stop 으로 11심볼 134모듈이다.
    /// 우리 EAN-128 은 Set B 로만 찍어 15심볼 178모듈이라 눈에 띄게 길었다.
    /// 묶는 기준은 ISO/IEC 15417 부속서 B 와 같다. 처음 네 자리, 중간 여섯 자리다.
    /// 폼텍·아이라벨 경로는 실측으로 맞춰 둔 것이라 이 함수를 쓰지 않는다.
    /// </summary>
    private static List<int>? BuildCode128Ean128AniLabel(string value)
    {
        var codes = new List<int>(value.Length + 6) { 104, 102 };
        var inC = false;
        var i = 0;
        while (i < value.Length)
        {
            var run = 0;
            while (i + run < value.Length && char.IsAsciiDigit(value[i + run])) run++;

            // 끝까지 가는 짝수 자리는 네 자리부터, 중간에 끊기는 자리는 여섯 자리부터 묶는다.
            var tail = i + run == value.Length;
            var take = run - (run % 2);
            if (take >= (tail || i == 0 ? 4 : 6))
            {
                if (!inC)
                {
                    codes.Add(99);
                    inC = true;
                }
                for (var k = 0; k < take; k += 2)
                    codes.Add((value[i + k] - '0') * 10 + (value[i + k + 1] - '0'));
                i += take;
                continue;
            }

            if (inC)
            {
                codes.Add(100);
                inC = false;
            }
            var ch = value[i];
            if (ch is < ' ' or > '\u007e') return null;
            codes.Add(ch - 32);
            i++;
        }
        return codes;
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

    /// <summary>
    /// BC412(SEMI T1-95) 문자 순서. 글자 하나가 이 문자열에서 갖는 자리가 곧 심볼 값이다.
    /// 알파벳 순이 아니고 O 가 빠져 있다(숫자 0 과 헷갈려서).
    /// </summary>
    private const string Bc412Alphabet = "0R9GLVHA8EZ4NTS1J2Q6C7DYKBUIX3FWP5M";

    /// <summary>
    /// BC412 문자 35개. 막대·공백을 번갈아 적은 폭이고 한 글자가 12모듈이다.
    /// 막대는 모두 1모듈이라 실제로 변하는 값은 공백 네 개뿐이다(4 bars in 12 positions).
    ///
    /// 「1D barcode 타입.lbl」 40번 칸(ABC1234567890) 원본 그림을 풀어
    /// 13글자가 이 표와 한 자리도 어긋나지 않는 것을 확인했다.
    /// </summary>
    private static readonly string[] Bc412Table =
    [
        "11111115", "13111212", "11131113", "12111213", "12121311", "13131111", "12111312",
        "11131212", "11121411", "11151111", "15111111", "11111511", "12131211", "13121112",
        "13111311", "11111214", "12121113", "11111313", "13111113", "11121213", "11141112",
        "11121312", "11141211", "14121111", "12121212", "11131311", "13121211", "12111411",
        "14111211", "11111412", "12111114", "14111112", "12141111", "11121114", "12131112"
    ];

    /// <summary>
    /// BC309 숫자 10개. BC412 와 같은 방식인데 한 글자가 막대 3개 9모듈이다.
    /// 「1D barcode 타입.lbl」 39번 칸(1234567890)에서 열 자리를 모두 읽어 세웠다.
    /// 9모듈을 막대 셋과 공백 셋으로 나누는 경우의 수가 딱 열 가지라 표가 이걸로 끝난다.
    /// </summary>
    private static readonly string[] Bc309Table =
    [
        "111114", "111213", "111312", "111411", "121113",
        "121212", "121311", "131112", "131211", "141111"
    ];

    /// <summary>
    /// BC412. 시작은 막대 하나에 공백 셋, 끝은 막대·공백·막대다.
    ///
    /// 애니라벨은 mod-35 검사문자를 붙이지 않는다(40번 칸이 13글자 그대로 55막대다).
    /// 규격은 첫 글자 뒤에 검사문자를 끼우도록 하지만, 화면에 보이는 대로 찍는 쪽을 택했다.
    /// </summary>
    public static bool[]? EncodeBc412(string value)
        => EncodeSingleWidth(value, Bc412Alphabet, Bc412Table);

    /// <summary>
    /// Flattermarken. 제책할 때 책등에 찍는 접지 표시라 스캐너로 읽는 바코드가 아니다.
    ///
    /// 숫자 하나가 9모듈 칸을 차지하고 그 안에서 1모듈짜리 막대가 놓이는 자리가 값이다.
    /// 1이면 맨 앞, 9면 맨 뒤, 0이면 막대를 아예 찍지 않는다.
    ///
    /// 「1D barcode 타입.lbl」 37번 칸 값이 1234567890 이라 막대가 0·10·20…80 모듈에
    /// 고르게 놓여 아무 규칙 없는 그림처럼 보였는데, 자리를 하나씩 옮긴 결과였다.
    /// </summary>
    public static bool[]? EncodeFlattermarken(string digits)
    {
        if (digits.Length is 0 or > 128) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;

        var bits = new bool[digits.Length * 9];
        for (var i = 0; i < digits.Length; i++)
        {
            var v = digits[i] - '0';
            if (v > 0) bits[i * 9 + v - 1] = true;
        }
        return bits;
    }

    /// <summary>BC309. 숫자만 받는다. 구조는 BC412 와 같다.</summary>
    public static bool[]? EncodeBc309(string value)
        => EncodeSingleWidth(value, "0123456789", Bc309Table);

    /// <summary>Clocked-35 한 글자가 차지하는 모듈 수.</summary>
    private const int Clocked35Cell = 20;

    /// <summary>
    /// Clocked-35 의 숫자표. {1,2,3,4,5} 에서 셋을 고르는 열 가지를 사전순으로 늘어놓은 것이다.
    /// 자리 번호는 시계막대 쪽부터 1,2,3,4,5 로 센다(오른쪽에서 왼쪽).
    /// </summary>
    private static readonly (int A, int B, int C)[] Clocked35Slots =
    {
        (1, 2, 3), (1, 2, 4), (1, 2, 5), (1, 3, 4), (1, 3, 5),
        (1, 4, 5), (2, 3, 4), (2, 3, 5), (2, 4, 5), (3, 4, 5)
    };

    /// <summary>
    /// Clocked-35. 이름 그대로 「시계막대가 있는 다섯 중 셋」이다.
    ///
    /// 글자 한 칸이 20모듈이고 막대는 모두 1모듈 폭이다. 칸 안에서 모듈 15 에는 글자마다
    /// 반드시 막대가 오는데 이것이 칸 경계를 알리는 시계막대다. 모듈 0·3·6·9·12 가
    /// 데이터 자리 다섯이고 그중 정확히 셋에만 막대가 온다. C(5,3)=10 이라 숫자 열 개와 맞는다.
    /// 남는 모듈 16~19 가 글자 사이 빈칸이라 시계막대 뒤 간격만 넓어 보인다.
    ///
    /// 자리 번호를 시계막대 쪽부터 1~5 로 세면 숫자 n 이 사전순 n 번째 조합이다(열 번째가 0).
    /// 글자는 오른쪽 칸부터 채운다. 값의 첫 글자가 맨 오른쪽에 온다.
    ///
    /// 공개 규격이 없어 애니라벨 신형 0x28 표본 세 개로 역산했다.
    /// `1234567`·`8901234567`·`9999999` 모두 막대 위치가 한 개도 다르지 않다.
    /// 앞 두 표본은 뒤집으면 `7654321` 과 `7654321098` 이라 앞부분이 겹쳐,
    /// 자리만 따라가는 그림으로 잘못 볼 수 있으니 주의한다. `9999999` 가 그것을 갈랐다.
    /// </summary>
    public static bool[]? EncodeClocked35(string digits)
    {
        if (digits.Length is 0 or > 64) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;

        var bits = new bool[digits.Length * Clocked35Cell];
        for (var i = 0; i < digits.Length; i++)
        {
            // 값의 첫 글자가 맨 오른쪽 칸이다.
            var ch = digits[digits.Length - 1 - i];
            var (a, b, c) = Clocked35Slots[(ch - '0' + 9) % 10];
            var clock = i * Clocked35Cell + 15;
            bits[clock] = true;
            bits[clock - 3 * a] = true;
            bits[clock - 3 * b] = true;
            bits[clock - 3 * c] = true;
        }
        return bits;
    }

    private static bool[]? EncodeSingleWidth(string value, string alphabet, string[] table)
    {
        var payload = (value ?? "").Trim().ToUpperInvariant();
        if (payload.Length == 0) return null;

        var runs = new List<char>(payload.Length * 8 + 8);
        runs.AddRange("13");
        foreach (var ch in payload)
        {
            var idx = alphabet.IndexOf(ch);
            if (idx < 0) return null;
            runs.AddRange(table[idx]);
        }
        runs.AddRange("111");
        return RunsToModules(runs);
    }

    /// <summary>
    /// Channel Code(ANSI/AIM BC12-1998). 값 하나를 막대·공백 폭의 조합 자체로 적는다.
    /// 찾기 무늬 9모듈 뒤에 채널 3~8개가 붙고, 채널 하나가 공백·막대 한 쌍이다.
    ///
    /// 조합을 규격 부속서 D 그림 D5 순서대로 세어 나가다 목표 값에서 멈춘다.
    /// 값이 커지면 수백만 번을 도니 결과를 기억해 둔다.
    /// </summary>
    public static bool[]? EncodeChannelCode(string digits)
    {
        if (digits.Length is 0 or > 7) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;
        if (!int.TryParse(digits, out var target) || target < 0) return null;

        var channels = digits.Length + 1;
        if (target > 576688) channels = Math.Max(channels, 8);
        else if (target > 44072) channels = Math.Max(channels, 7);
        else if (target > 3493) channels = Math.Max(channels, 6);
        else if (target > 292) channels = Math.Max(channels, 5);
        else if (target > 26) channels = Math.Max(channels, 4);
        if (channels < 3) channels = 3;
        if (channels > 8 || target > ChannelMax[channels]) return null;

        var key = channels * 10_000_000L + target;
        if (!ChannelCache.TryGetValue(key, out var runs))
        {
            runs = BuildChannelRuns(channels, target);
            ChannelCache[key] = runs;
        }
        return RunsToModules(runs);
    }

    private static readonly int[] ChannelMax = [-1, -1, -1, 26, 292, 3493, 44072, 576688, 7742862];

    private static readonly System.Collections.Concurrent.ConcurrentDictionary<long, List<char>> ChannelCache = new();

    /// <summary>
    /// 채널 수마다 정해진 출발 조합. 규격 부속서 D 가 정한 차례에서 맨 앞에 오는 것이다.
    /// 위에서부터 3채널·4채널·…·8채널 순이고, 한 줄이 B[8]·S[8]·bmax[7]·smax[7] 이다.
    /// 쓰는 자리는 뒤쪽 channels 개뿐이고 앞쪽은 자리를 맞추려고 채워 둔 값이다.
    /// </summary>
    private static readonly int[][][] ChannelSeed =
    [
        [[1, 1, 1, 1, 1, 2, 1, 2], [1, 1, 1, 1, 1, 1, 1, 3], [1, 1, 1, 1, 1, 3, 2], [1, 1, 1, 1, 1, 3, 3]],
        [[1, 1, 1, 1, 2, 1, 1, 3], [1, 1, 1, 1, 1, 1, 1, 4], [1, 1, 1, 1, 4, 3, 3], [1, 1, 1, 1, 4, 4, 4]],
        [[1, 1, 1, 2, 1, 1, 2, 3], [1, 1, 1, 1, 1, 1, 1, 5], [1, 1, 1, 5, 4, 4, 4], [1, 1, 1, 5, 5, 5, 5]],
        [[1, 1, 2, 1, 1, 2, 1, 4], [1, 1, 1, 1, 1, 1, 1, 6], [1, 1, 6, 5, 5, 5, 4], [1, 1, 6, 6, 6, 6, 6]],
        [[1, 2, 1, 1, 2, 1, 1, 5], [1, 1, 1, 1, 1, 1, 1, 7], [1, 7, 6, 6, 6, 5, 5], [1, 7, 7, 7, 7, 7, 7]],
        [[2, 1, 1, 2, 1, 1, 2, 5], [1, 1, 1, 1, 1, 1, 1, 8], [8, 7, 7, 7, 6, 6, 6], [8, 8, 8, 8, 8, 8, 8]]
    ];

    /// <summary>
    /// 규격 부속서 D 그림 D5 의 CHNCHR. 채널 폭을 규격이 정한 차례대로 하나씩 넘겨 가며
    /// 목표 값 번째 조합에서 멈춘다.
    ///
    /// 원문이 일곱 겹 goto 로 짜여 있고 겹 수가 채널 수와 상관없이 늘 일곱이다.
    /// 채널 수는 출발 조합(ChannelSeed)으로만 들어간다. 반복문으로 바꿔 쓰면
    /// 세는 차례가 미묘하게 달라져 값이 어긋나므로 이 구조를 그대로 옮긴다.
    /// </summary>
    private static List<char> BuildChannelRuns(int channels, int target)
    {
        var seed = ChannelSeed[channels - 3];
        var b = (int[])seed[0].Clone();
        var s = (int[])seed[1].Clone();
        var bmax = new int[8];
        var smax = new int[8];
        Array.Copy(seed[2], bmax, 7);
        Array.Copy(seed[3], smax, 7);
        var value = 0;

        goto chkchr;

        ls0:
        smax[1] = smax[0] + 1 - s[0];
        b[0] = 1;
        if (s[0] == 1) goto nb0;
        lb0:
        bmax[1] = bmax[0] + 1 - b[0];
        s[1] = 1;
        ls1:
        smax[2] = smax[1] + 1 - s[1];
        b[1] = 1;
        if (s[0] + b[0] + s[1] == 3) goto nb1;
        lb1:
        bmax[2] = bmax[1] + 1 - b[1];
        s[2] = 1;
        ls2:
        smax[3] = smax[2] + 1 - s[2];
        b[2] = 1;
        if (b[0] + s[1] + b[1] + s[2] == 4) goto nb2;
        lb2:
        bmax[3] = bmax[2] + 1 - b[2];
        s[3] = 1;
        ls3:
        smax[4] = smax[3] + 1 - s[3];
        b[3] = 1;
        if (b[1] + s[2] + b[2] + s[3] == 4) goto nb3;
        lb3:
        bmax[4] = bmax[3] + 1 - b[3];
        s[4] = 1;
        ls4:
        smax[5] = smax[4] + 1 - s[4];
        b[4] = 1;
        if (b[2] + s[3] + b[3] + s[4] == 4) goto nb4;
        lb4:
        bmax[5] = bmax[4] + 1 - b[4];
        s[5] = 1;
        ls5:
        smax[6] = smax[5] + 1 - s[5];
        b[5] = 1;
        if (b[3] + s[4] + b[4] + s[5] == 4) goto nb5;
        lb5:
        bmax[6] = bmax[5] + 1 - b[5];
        s[6] = 1;
        ls6:
        s[7] = smax[6] + 1 - s[6];
        b[6] = 1;
        if (b[4] + s[5] + b[5] + s[6] == 4) goto nb6;
        lb6:
        b[7] = bmax[6] + 1 - b[6];
        if (b[5] + s[6] + b[6] + s[7] + b[7] == 5) goto nb6;
        chkchr:
        if (value == target) goto done;
        value++;
        nb6:
        if (++b[6] <= bmax[6]) goto lb6;
        if (++s[6] <= smax[6]) goto ls6;
        nb5:
        if (++b[5] <= bmax[5]) goto lb5;
        if (++s[5] <= smax[5]) goto ls5;
        nb4:
        if (++b[4] <= bmax[4]) goto lb4;
        if (++s[4] <= smax[4]) goto ls4;
        nb3:
        if (++b[3] <= bmax[3]) goto lb3;
        if (++s[3] <= smax[3]) goto ls3;
        nb2:
        if (++b[2] <= bmax[2]) goto lb2;
        if (++s[2] <= smax[2]) goto ls2;
        nb1:
        if (++b[1] <= bmax[1]) goto lb1;
        if (++s[1] <= smax[1]) goto ls1;
        nb0:
        if (++b[0] <= bmax[0]) goto lb0;
        if (++s[0] <= smax[0]) goto ls0;

        done:
        var runs = new List<char>(9 + channels * 2);
        for (var i = 0; i < 9; i++) runs.Add('1');
        for (var i = 8 - channels; i < 8; i++)
        {
            runs.Add((char)('0' + s[i]));
            runs.Add((char)('0' + b[i]));
        }
        return runs;
    }

    private static bool[] RunsToModules(List<char> runs)
    {
        var bits = new List<bool>(runs.Count * 3);
        var ink = true;
        foreach (var d in runs)
        {
            for (var i = d - '0'; i > 0; i--) bits.Add(ink);
            ink = !ink;
        }
        return bits.ToArray();
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
    public static bool[]? EncodeItf(string digits, BarcodeVendorKind vendor, bool legacyPsoft = false)
    {
        if (digits.Length < 2) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;
        if (digits.Length % 2 == 1)
            digits = "0" + digits;

        var wide = NarrowWideRatio(vendor, legacyPsoft);
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

    public static bool[]? EncodeDiscrete25(
        string id, string digits, BarcodeVendorKind vendor, bool legacyPsoft = false)
    {
        if (digits.Length == 0) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;

        var wide = NarrowWideRatio(vendor, legacyPsoft);
        return id switch
        {
            "I25_IATA" or "IATA" => Widths("1111", Industrial25, digits, "311", wide),
            "I25_DATALOGIC" or "DATALOGIC" => Widths("1111", Matrix25, digits, "311", wide),
            "I25_INDUSTRIAL" or "INDUSTRIAL" => Widths("313111", Industrial25, digits, "31113", wide),
            // 애니라벨 신형 8번 칸 실측: 71요소가 한 자리만 빼고 같고, 시작·정지 첫 막대만 3X다.
            "I25_MATRIX" or "MATRIX" => Widths(
                "311111", Matrix25, digits, "31111", wide,
                vendor == BarcodeVendorKind.AniLabel && !legacyPsoft ? 3 : 0),
            // 애니라벨 구형 0x27 실측(34번 칸, 123456): 43요소 = 시작 4 + 글자 6×6 + 정지 3.
            // 글자표는 같고 굵기비만 1:2, 시작이 넓·좁·넓·좁, 정지가 좁·넓·넓이다.
            "COOP25" or "COOP" => vendor == BarcodeVendorKind.AniLabel
                ? Widths("3131", Coop25, digits, "133", 2)
                : Widths("311111", Coop25, digits, "31111", wide),
            "I25_INVERT" or "INVERT" => EncodeInvert(digits, wide),
            _ => null
        };
    }

    /// <summary>
    /// 두 굵기 심볼로지의 굵은 요소 : 좁은 요소 배수.
    ///
    /// 애니라벨 신형(LBL 0x1B)은 심볼로지를 가리지 않고 2:1이다.
    /// 「1D barcode 타입.lbl」 EMF에 든 애니라벨 원본 그림을 Codabar·Code 11·Code 39·MSI와
    /// 2/5 다섯 갈래까지 훑어 보니 좁은 요소가 전부 6px, 굵은 요소가 전부 12px이었다.
    /// 구형(0x07)은 심볼로지마다 달라 손대지 않는다(IATA만 3:1, 나머지는 2:1로 보인다).
    /// </summary>
    private static int NarrowWideRatio(BarcodeVendorKind vendor, bool legacyPsoft) => vendor switch
    {
        BarcodeVendorKind.Formtec => 2,
        BarcodeVendorKind.AniLabel when !legacyPsoft => 2,
        _ => 3
    };

    public static bool[]? EncodeCodabar(
        string value, BarcodeVendorKind vendor, bool abc = false, bool legacyPsoft = false)
    {
        // 글자표는 rationalized(C=nnnwnww). 폼텍·애니라벨만 바깥 가드를 따로 붙인다.
        var s = (value ?? "").Trim().ToUpperInvariant();
        static bool Guard(char c) => c is >= 'A' and <= 'D';
        if (vendor == BarcodeVendorKind.Formtec && !abc)
        {
            // 폼텍 CODABAR(0x02) 실측: 저장값 그대로 + 바깥 A + (합%16) + A.
            // ABC_CODABAR(0x08)는 가드/체크를 넣지 않는다(ABCCBA).
            s = "A" + s + CodabarMod16(s) + "A";
        }
        else if (vendor == BarcodeVendorKind.AniLabel && !abc)
        {
            // 애니라벨도 저장값 전체를 데이터로 보고 바깥 가드를 스스로 붙인다.
            // 구형 PSOFT(LBL 0x07)는 폼텍과 같은 A + 데이터 + (합%16) + A.
            // 신형(LBL 0x1B)은 체크문자 없이 A + 데이터 + B. EMF 실측으로 확인했다.
            s = legacyPsoft ? "A" + s + CodabarMod16(s) + "A" : "A" + s + "B";
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

    /// <param name="checks">
    /// 붙일 체크문자 수. C는 오른쪽부터 가중치 1~10, K는 1~9를 돌려 11로 나눈 나머지다.
    /// 애니라벨 구형 0x28은 2개를 붙인다(35번 칸 실측 59요소 = 시작 + 8글자 + 정지).
    /// </param>
    public static bool[]? EncodeCode11(string value, int checks = 0)
    {
        var data = new List<char>(value.Length + checks);
        foreach (var ch in value)
        {
            if (!Code11.ContainsKey(ch)) return null;
            data.Add(ch);
        }
        if (data.Count == 0) return null;

        for (var k = 0; k < checks; k++)
        {
            var period = k == 0 ? 10 : 9;
            var sum = 0;
            for (var i = 0; i < data.Count; i++)
            {
                var ch = data[data.Count - 1 - i];
                sum += (i % period + 1) * (ch == '-' ? 10 : ch - '0');
            }
            var v = sum % 11;
            data.Add(v == 10 ? '-' : (char)('0' + v));
        }

        var sb = new StringBuilder(12 + data.Count * 6);
        sb.Append("112211");
        foreach (var ch in data)
            sb.Append(Code11[ch]);
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
    /// <param name="aniLabelSplit">
    /// 애니라벨 신형(0x1B~0x1D)이 값을 나누는 방식. 일곱 자리를 「데이터 여섯 + 체크 하나」로 보고
    /// 넘버 시스템은 형식에서 가져온다. 우리 기본값은 앞자리를 넘버 시스템으로 보는 것이라 서로 다르다.
    /// 실린 체크문자는 쓰지 않고 늘 다시 셈한다(애니라벨 값이 규격과 어긋나 스캐너가 거부한다).
    /// </param>
    public static bool[]? EncodeUpcE(string digits, bool? numberSystem1, out string hri,
        bool aniLabelSplit = false)
    {
        hri = "";
        if (!TrySplitUpcE(digits, numberSystem1, aniLabelSplit, out var data, out var check, out var ns1))
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
        string digits, bool? numberSystem1, bool aniLabelSplit,
        out string data, out int check, out bool ns1)
    {
        data = "";
        check = 0;
        ns1 = numberSystem1 ?? false;
        if (digits.Length == 0)
            return false;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return false;

        if (aniLabelSplit)
        {
            // 여덟 자리만 앞자리를 넘버 시스템으로 본다.
            // 그보다 짧으면 일곱 자리가 되도록 왼쪽을 0으로 채운 뒤 앞 여섯을 데이터로 쓴다.
            // 「1D barcode 타입.lbl」 29~31번 칸(1234567 / 123456 / 123456)이
            // 각각 123456 / 012345 / 012345 로 나오는 것을 원본 그림에서 읽어 확정했다.
            if (digits.Length == 8)
            {
                ns1 = numberSystem1 ?? digits[0] == '1';
                data = digits[1..7];
            }
            else if (digits.Length <= 7)
                data = digits.PadLeft(7, '0')[..6];
            else
                return false;
            check = UpcECheckDigit(data, ns1);
            return true;
        }

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

    /// <summary>
    /// USPS FIM(Facing Identification Mark). USPS Pub 25 10-1.3의 9자리 비트열이며 1이 막대다.
    /// 규격은 한 자리가 늘 2모듈(막대/빈자리 1 + 공백 1)이라 A는 17모듈이 된다.
    /// </summary>
    /// <param name="compact">
    /// 애니라벨 구형 0x30은 빈자리를 1모듈로만 잡아 A가 13모듈로 줄어든다.
    /// 38번 칸(`ABC`) EMF 실측 요소열 `111313111`과 같다.
    /// </param>
    public static bool[]? EncodeFim(string value, bool compact = false)
    {
        var key = value.Trim().ToUpperInvariant();
        if (key.StartsWith("FIM")) key = key[3..].TrimStart('-', '_', ' ');
        // 애니라벨 샘플은 값이 `ABC`처럼 여러 글자라 첫 글자만 본다.
        var bits = (key.Length > 0 ? key[0] : ' ') switch
        {
            'A' or '1' => "110010011",
            'B' or '2' => "101101101",
            'C' or '3' => "110101011",
            'D' or '4' => "111010111",
            'E' or '5' => "101000101",
            _ => null
        };
        if (bits is null) return null;

        var modules = new List<bool>(bits.Length * 2);
        foreach (var bit in bits)
        {
            modules.Add(bit == '1');
            if (bit == '1' || !compact) modules.Add(false);
        }
        while (modules.Count > 0 && !modules[^1])
            modules.RemoveAt(modules.Count - 1);
        return modules.Count == 0 ? null : modules.ToArray();
    }

    /// <summary>
    /// Telepen(ASCII 모드). 문자마다 짝수 패리티를 붙인 8비트를 LSB부터 이어 붙여 하나의 비트열을 만들고,
    /// 그 비트열을 낱개 1과 `0 1* 0` 덩어리로 끊어 막대·공백 폭으로 바꾼다.
    /// 1은 좁은 막대+좁은 공백, `00`은 넓은 막대+좁은 공백, `010`은 넓은 막대+넓은 공백,
    /// 그보다 긴 덩어리는 앞 `01`과 뒤 `10`을 좁은 막대+넓은 공백으로 둔다.
    /// 좁은 요소 1모듈 / 넓은 요소 3모듈이라 한 문자가 늘 16모듈이다.
    /// 시작은 `_`(0x5F), 정지는 `z`(패리티 포함 0xFA), 검사문자는 자료 합의 127 보수다.
    /// 애니라벨 구형 0x31 실측(39번 칸 `123456789`, 131요소 191모듈)과 요소 단위로 일치한다.
    /// </summary>
    /// <param name="withCheck">
    /// 검사문자를 붙일지. 애니라벨 신형(0x1B 0x17)은 붙이지 않는다.
    /// 「1D barcode 타입.lbl」 25번 칸 원본 그림이 175요소인데, 검사문자를 붙인 우리 것은 185요소다.
    /// 앞 166요소가 똑같고 남은 차이가 한 문자(16모듈)뿐이라 검사문자 자리가 통째로 빠진 것이다.
    /// </param>
    public static bool[]? EncodeTelepen(string value, bool withCheck = true)
    {
        var payload = value ?? "";
        if (payload.Length == 0) return null;

        var sum = 0;
        foreach (var ch in payload)
        {
            if (ch > 0x7F) return null;
            sum += ch;
        }
        var check = 127 - sum % 127;
        if (check == 127) check = 0;

        var chars = new List<int>(payload.Length + 3) { 0x5F };
        foreach (var ch in payload) chars.Add(ch);
        if (withCheck) chars.Add(check);
        chars.Add(0x7A);

        var bits = new List<int>(chars.Count * 8);
        foreach (var v in chars)
        {
            var ones = 0;
            for (var i = 0; i < 7; i++) ones += (v >> i) & 1;
            var b = ones % 2 == 0 ? v : v | 0x80;
            for (var i = 0; i < 8; i++) bits.Add((b >> i) & 1);
        }

        var widths = new StringBuilder(bits.Count * 2);
        for (var i = 0; i < bits.Count;)
        {
            if (bits[i] == 1)
            {
                widths.Append("11");
                i++;
                continue;
            }
            var j = i + 1;
            while (j < bits.Count && bits[j] == 1) j++;
            if (j >= bits.Count) return null;   // 0의 개수가 홀수면 만들 수 없다
            var inner = j - i - 1;
            if (inner == 0) widths.Append("31");
            else if (inner == 1) widths.Append("33");
            else
            {
                widths.Append("13");
                widths.Append('1', (inner - 2) * 2);
                widths.Append("13");
            }
            i = j + 1;
        }
        return WidthPatternToModules(widths.ToString(), wide: 3);
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

    /// <summary>POSTNET/PLANET. L=긴 막대, S=짧은 막대.</summary>
    /// <param name="addCheck">
    /// 검사숫자를 계산해서 뒤에 붙일지 여부.
    ///
    /// 애니라벨은 붙이지 않고 적어 준 숫자를 그대로 그린다. 검사숫자까지 값에 넣어 두라는 뜻이다.
    /// 45번 칸 POSTNET 값 `1234567890` 이 막대 52개(= 시작 + 10자 + 끝)로 나오는데,
    /// 우리가 붙이면 11자가 되어 POSTNET 이 허용하는 6·10·12자 어디에도 안 맞는다.
    /// 오히려 애니라벨 쪽이 규격에 맞으므로 애니라벨 변환에서는 붙이지 않는다.
    /// 폼텍·아이라벨은 지금까지 하던 대로 붙인다.
    /// </param>
    public static string? EncodePostnetPattern(string digits, bool planet, bool addCheck = true)
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
        if (addCheck)
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

    /// <summary>
    /// Plessey. 한 글자가 16진 네 자리이고, 비트마다 막대 하나 + 공백 하나를 찍는다.
    /// 1은 굵은 막대 + 좁은 공백, 0은 좁은 막대 + 굵은 공백이고 낮은 자리부터 나간다.
    /// 앞에는 시작 비트열 1101 이 붙는다.
    ///
    /// 칸 하나가 늘 5모듈이다(굵은 막대 3 + 공백 2, 좁은 막대 1 + 공백 4).
    /// 「1D barcode 타입.lbl」 23번 칸 애니라벨 원본 그림에서 18:12 와 6:24 픽셀로 쟀다.
    /// 판독기는 칸 안에서 막대가 차지하는 비율(20% 대 60%)로 0과 1을 가르므로 규격 안이다.
    ///
    /// 검사문자(8비트 CRC)는 붙이지 않는다. 같은 칸 원본이 121요소 = 시작 8 + 자료 104 + 정지 9 라
    /// CRC 16요소가 통째로 없다. 우리 기본 경로(ZXing)는 137요소로 CRC 를 붙이는데,
    /// 앞 112요소가 애니라벨과 한 칸도 틀리지 않아 빠진 것이 CRC 뿐임이 확인된다.
    /// </summary>
    public static bool[]? EncodePlesseyAniLabel(string value)
    {
        const string hex = "0123456789ABCDEF";
        var payload = (value ?? "").Trim().ToUpperInvariant();
        if (payload.Length == 0) return null;

        var bits = new List<int>(4 + payload.Length * 4) { 1, 1, 0, 1 };
        foreach (var ch in payload)
        {
            var v = hex.IndexOf(ch);
            if (v < 0) return null;
            for (var b = 0; b < 4; b++) bits.Add(v >> b & 1);
        }

        var modules = new List<bool>(bits.Count * 5 + 25);
        foreach (var bit in bits)
        {
            var bar = bit == 1 ? 3 : 1;
            for (var i = 0; i < bar; i++) modules.Add(true);
            for (var i = 0; i < 5 - bar; i++) modules.Add(false);
        }

        // 정지 패턴. 애니라벨 원본 그림 실측 그대로다(막대 5, 공백 4, 1, 4, 1, 2, 3, 2, 막대 3).
        foreach (var (width, ink) in new[]
                 {
                     (5, true), (4, false), (1, true), (4, false),
                     (1, true), (2, false), (3, true), (2, false), (3, true)
                 })
            for (var i = 0; i < width; i++)
                modules.Add(ink);

        return modules.ToArray();
    }

    /// <summary>Code 32 가 쓰는 32진 글자표. I·O·A·E 처럼 헷갈리는 글자를 뺐다.</summary>
    private const string Code32Alphabet = "0123456789BCDFGHJKLMNPQRSTUVWXYZ";

    /// <summary>
    /// Code 32(이탈리아 의약품 코드). 아홉 자리 십진수를 32진 여섯 글자로 바꿔 Code 39 로 찍는다.
    /// 사람이 읽는 줄에는 막대에 실린 글자가 아니라 `A` + 아홉 자리 숫자를 쓴다.
    ///
    /// 「1D barcode 타입.lbl」 19번 칸에서 확인했다. 값 12345678 → 막대 `*0CSSBG*`, 캡션 `A012345678`.
    /// 0·C·S·S·B·G 를 32진으로 풀면 정확히 12345678 이다. 애니라벨은 검사숫자를 새로 셈하지 않고
    /// 아홉 자리 가운데 끝자리를 이미 붙어 있는 검사숫자로 본다.
    /// </summary>
    public static string? ToCode32Payload(string digits)
    {
        if (digits.Length == 0 || digits.Length > 9) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;
        if (!long.TryParse(digits, out var n) || n < 0) return null;

        var buf = new char[6];
        for (var i = 5; i >= 0; i--)
        {
            buf[i] = Code32Alphabet[(int)(n % 32)];
            n /= 32;
        }
        return n == 0 ? new string(buf) : null;
    }

    /// <summary>
    /// UPU S10 검사숫자. 여덟 자리에 가중치 8·6·4·2·3·5·9·7 을 곱해 더하고 11 로 나눈다.
    /// 나머지를 11 에서 뺀 값이 검사숫자이며, 10 이면 0, 11 이면 5 로 바꾼다(UPU S10 규격).
    /// 값이 S10 꼴(영문 2 + 숫자 8 + 검사 1 + 영문 2)이 아니면 그대로 돌려준다.
    /// </summary>
    public static string WithUpuS10Check(string value)
    {
        var v = (value ?? "").Trim().ToUpperInvariant();
        if (v.Length != 13) return v;
        if (!char.IsAsciiLetter(v[0]) || !char.IsAsciiLetter(v[1])) return v;
        if (!char.IsAsciiLetter(v[11]) || !char.IsAsciiLetter(v[12])) return v;
        for (var i = 2; i <= 10; i++)
            if (!char.IsAsciiDigit(v[i])) return v;

        ReadOnlySpan<int> weights = [8, 6, 4, 2, 3, 5, 9, 7];
        var sum = 0;
        for (var i = 0; i < 8; i++) sum += (v[i + 2] - '0') * weights[i];
        var check = 11 - sum % 11;
        if (check == 10) check = 0;
        else if (check == 11) check = 5;
        return string.Concat(v.AsSpan(0, 10), ((char)('0' + check)).ToString(), v.AsSpan(11));
    }

    /// <summary>
    /// Pharmacode One-track(Laetus). 3~131070 을 2~16개 막대의 이진수로 적는다.
    /// 오른쪽 끝이 최하위 자리이고, 좁은 막대가 1, 굵은 막대가 2를 뜻한다.
    ///
    /// 굵기는 Laetus PHARMACODE Guide 표준 치수를 모듈로 옮긴 것이다.
    /// a1(좁은 막대) 0.5mm, b1(굵은 막대) 1.5mm, c1(막대 사이) 1.0mm 이므로
    /// 0.5mm 를 1모듈로 잡으면 좁은 막대 1, 굵은 막대 3, 사이 2가 된다.
    /// 「1D barcode 타입.lbl」 26번 칸 애니라벨 원본 그림도 6px : 18px : 12px 로 같다.
    /// </summary>
    public static bool[]? EncodePharmaOne(string digits)
    {
        if (digits.Length == 0 || !long.TryParse(digits, out var n) || n is < 3 or > 131070)
            return null;

        var bars = new List<int>(16);
        while (n > 0)
        {
            if (n % 2 == 0)
            {
                bars.Add(3);
                n = (n - 2) / 2;
            }
            else
            {
                bars.Add(1);
                n = (n - 1) / 2;
            }
        }
        bars.Reverse();

        var bits = new List<bool>(bars.Count * 5);
        for (var i = 0; i < bars.Count; i++)
        {
            if (i > 0)
            {
                bits.Add(false);
                bits.Add(false);
            }
            for (var k = 0; k < bars[i]; k++) bits.Add(true);
        }
        return bits.ToArray();
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

    /// <param name="guardWide">
    /// 시작·정지 무늬에만 쓰는 굵기. 0이면 글자와 같다.
    /// 애니라벨 신형 Matrix 2/5는 글자 굵은 요소가 2X인데 시작·정지 막대만 3X다.
    /// </param>
    private static bool[]? Widths(
        string start, string[] table, string digits, string stop, int wide, int guardWide = 0)
    {
        var bits = new List<bool>(start.Length + digits.Length * table[0].Length * wide + stop.Length);
        var black = true;
        void Add(string pattern, int w)
        {
            foreach (var ch in pattern)
            {
                var n = ch == '1' ? 1 : w;
                for (var i = 0; i < n; i++) bits.Add(black);
                black = !black;
            }
        }

        Add(start, guardWide > 0 ? guardWide : wide);
        foreach (var ch in digits)
            Add(table[ch - '0'], wide);
        Add(stop, guardWide > 0 ? guardWide : wide);
        return bits.ToArray();
    }

    /// <summary>
    /// 2/5 Invert. 막대는 항상 좁고 Industrial 막대 굵기가 간격이 된다.
    /// </summary>
    private static bool[]? EncodeInvert(string digits, int wide)
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
        return WidthPatternToModules(swapped.ToString(), wide);
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
