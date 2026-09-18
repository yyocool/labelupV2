using System.Text;
using ZXing.Common;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// Grid Matrix (AIMD014 Rev. 1.63 / 중국 국가표준 2D 심볼). ZXing.Net은 읽기도 쓰기도 못해서
/// libzint gridmtx.c·reedsol.c 절차를 그대로 옮겼다 — libzint는 BSD-3-Clause라 상용 배포를 막는 조항이 없다.
/// 자료 부호어는 7비트고, 두 개씩 묶여 6×6 매크로모듈 하나에 14비트로 들어간다. 판형 n은 매크로모듈을
/// (2n+1)² 체스판으로 깔아 한 변이 6+12n모듈이 되고, 검은 칸의 테두리가 타이밍 무늬 노릇을 한다.
/// 「QR코드 타입.lbl」의 Grid Matrix 칸 일곱 개를 되읽어 보면 판형·ECC 등급(애니라벨은 늘 5)·메움
/// 부호어·오류정정·배치·층 번호가 규격과 모두 같아서, 애니라벨의 비트 흐름을 그대로 넣으면 한 모듈도
/// 어긋나지 않는다. 다만 기호 값 두 군데가 규격을 벗어난다 — 혼합 모드 빈칸(12·21번 칸)과 바이트 블록
/// 길이 지시자(22번 칸)다. 가져오기 경로는 원본을 그대로 되살려야 하므로 aniLabelQuirks를 켤 때만 그
/// 두 결함을 따라 적고, 편집기에서 새로 만드는 바코드는 규격대로 찍는다.
///
/// 모드 선택은 표본 스물세 칸 가운데 12번(「Grid Matrix」)·20번(「GRID matrix」) 두 칸이 갈렸었다.
/// 20번은 두 길이 똑같이 69비트인 완전한 동점이라 비용이 아니라 동점 처리 방향의 문제였고, 아래 전환
/// 고리에서 등호 하나를 빼 맞췄다 — 규격을 벗어나지 않아 조건 없이 쓴다. 12번은 동점이 아니었다.
/// 애니라벨이 79비트짜리 길을 두고 80비트 길을 골랐다. 비용 모형 손잡이 19만 가지를 훑어 네 갈래가
/// 남았고, 그 넷을 가르려고 짧은 표본 여섯 칸(33~38번)을 더 받아 갈랐다. 「Ga」·「Gabcdefg」가
/// 첫 구간 최소 길이를, 「11」이 혼합 시작값을, 「1AAAA」가 혼합 나가는 전환값을 떨어뜨렸고,
/// 대문자↔소문자 전환값만 남았다. 값의 구간은 「aA」가 위를 막아 5⅓~7비트이고 그 안에서 표 9에
/// 실제로 적힌 숫자는 7뿐이다. <see cref="AniLabelSwitchCosts"/>를 보라. 규격이 5비트라고 못
/// 박은 자리라 이것만은 aniLabelQuirks 뒤에 둔다.
/// </summary>
internal static class GridMatrixEncoder
{
    /// <summary>표 11의 권장 부호어 수. 자료가 이 안에 들면 그 판형을 자동으로 고른다.</summary>
    private static readonly int[] RecommendCws =
        [9, 30, 59, 114, 170, 237, 315, 405, 506, 618, 741, 875, 1021];

    /// <summary>표 11의 판형별 자료 부호어 상한. 가장 낮은 ECC 등급까지 내렸을 때의 값이다.</summary>
    private static readonly int[] MaxCws =
        [11, 40, 89, 146, 218, 305, 405, 521, 650, 794, 953, 1125, 1313];

    /// <summary>
    /// [판형-1][등급-1]별 자료 부호어 수. 등급 1~5가 각각 자료 비중 90·80·70·60·50%다.
    /// 판형 1의 1등급은 규격에 없어 0으로 막아 둔다.
    /// </summary>
    private static readonly int[][] DataCws =
    [
        [0, 15, 13, 11, 9],
        [45, 40, 35, 30, 25],
        [89, 79, 69, 59, 49],
        [146, 130, 114, 98, 81],
        [218, 194, 170, 146, 121],
        [305, 271, 237, 203, 169],
        [405, 360, 315, 270, 225],
        [521, 463, 405, 347, 289],
        [650, 578, 506, 434, 361],
        [794, 706, 618, 530, 441],
        [953, 847, 741, 635, 529],
        [1125, 1000, 875, 750, 625],
        [1313, 1167, 1021, 875, 729]
    ];

    /// <summary>표 A.1의 N1. 긴 쪽 블록의 부호어 수고, 짧은 쪽 블록은 여기서 하나가 빠진다.</summary>
    internal static readonly int[] BlockSize1 =
        [18, 50, 98, 81, 121, 113, 113, 116, 121, 126, 118, 125, 122];

    /// <summary>표 A.1의 B1. N1짜리 블록 수.</summary>
    internal static readonly int[] BlockCount1 = [1, 1, 1, 2, 2, 2, 2, 3, 2, 7, 5, 10, 6];

    /// <summary>표 A.1의 B2. N1−1짜리 블록 수.</summary>
    internal static readonly int[] BlockCount2 = [0, 0, 0, 0, 0, 1, 2, 2, 4, 0, 4, 0, 6];

    /// <summary>표 A.1의 E1. 앞쪽 B3개 블록에 붙는 오류정정 부호어 수.</summary>
    internal static readonly int[][] BlockEcc1 =
    [
        [0, 3, 5, 7, 9],
        [5, 10, 15, 20, 25],
        [9, 19, 29, 39, 49],
        [8, 16, 24, 32, 41],
        [12, 24, 36, 48, 61],
        [11, 23, 34, 45, 57],
        [12, 23, 34, 45, 57],
        [12, 23, 35, 47, 58],
        [12, 24, 36, 48, 61],
        [13, 26, 38, 51, 63],
        [12, 24, 36, 47, 59],
        [13, 25, 38, 50, 63],
        [13, 25, 37, 49, 61]
    ];

    /// <summary>표 A.1의 B3. E1을 쓰는 블록의 개수.</summary>
    internal static readonly int[][] BlockCount3 =
    [
        [0, 1, 1, 1, 1],
        [1, 1, 1, 1, 1],
        [1, 1, 1, 1, 1],
        [2, 2, 2, 2, 1],
        [2, 2, 2, 2, 1],
        [3, 1, 2, 3, 1],
        [1, 2, 3, 4, 1],
        [2, 5, 3, 1, 4],
        [6, 6, 6, 6, 1],
        [4, 1, 5, 2, 7],
        [6, 4, 2, 9, 7],
        [5, 10, 5, 10, 5],
        [1, 3, 5, 7, 9]
    ];

    /// <summary>표 A.1의 E2. B3 다음 블록들에 붙는 오류정정 부호어 수.</summary>
    internal static readonly int[][] BlockEcc2 =
    [
        [0, 0, 0, 0, 0],
        [0, 0, 0, 0, 0],
        [0, 0, 0, 0, 0],
        [0, 0, 0, 0, 40],
        [0, 0, 0, 0, 60],
        [0, 22, 33, 0, 56],
        [11, 22, 33, 0, 56],
        [11, 0, 34, 46, 57],
        [0, 0, 0, 0, 60],
        [12, 25, 37, 50, 0],
        [11, 23, 35, 0, 58],
        [12, 0, 37, 0, 62],
        [12, 24, 36, 48, 60]
    ];

    /// <summary>
    /// 표 9의 형태 전환 부호. [지금 모드][갈 모드-1]이고, 0행은 아직 아무 모드도 켜지 않은 처음,
    /// 7행은 자료 끝 표시다.
    /// </summary>
    private static readonly int[][] ModeSwitch =
    [
        [1, 2, 3, 4, 5, 7],
        [0, 8161, 8162, 8163, 8164, 8165],
        [1019, 0, 1020, 1021, 1022, 1023],
        [28, 29, 0, 30, 124, 126],
        [28, 29, 30, 0, 124, 126],
        [1009, 1010, 1011, 1012, 0, 1015],
        [1, 2, 3, 4, 5, 0],
        [8160, 1018, 27, 27, 1008, 0]
    ];

    /// <summary>위 부호의 비트 길이.</summary>
    private static readonly int[][] ModeSwitchBits =
    [
        [4, 4, 4, 4, 4, 4],
        [0, 13, 13, 13, 13, 13],
        [10, 0, 10, 10, 10, 10],
        [5, 5, 0, 5, 7, 7],
        [5, 5, 5, 0, 7, 7],
        [10, 10, 10, 10, 0, 10],
        [4, 4, 4, 4, 4, 0],
        [13, 10, 5, 5, 10, 4]
    ];

    private const int ModeChinese = 1;
    private const int ModeNumeral = 2;
    private const int ModeLower = 3;
    private const int ModeUpper = 4;
    private const int ModeMixed = 5;
    private const int ModeByte = 6;

    /// <summary>모드가 아니라 자료 끝 표시를 꺼내는 자리다.</summary>
    private const int ModeEnd = 7;

    private const int ModeCount = 6;

    // 비용·역추적 표의 열 번호. 모드 번호에서 하나를 뺀 값이다.
    private const int IdxHan = ModeChinese - 1;
    private const int IdxNum = ModeNumeral - 1;
    private const int IdxLower = ModeLower - 1;
    private const int IdxUpper = ModeUpper - 1;
    private const int IdxMixed = ModeMixed - 1;
    private const int IdxByte = ModeByte - 1;

    /// <summary>비용을 2·3으로 나눠도 정수로 남게 비트 수에 곱하는 배수.</summary>
    private const int CostMult = 6;

    /// <summary>모드를 처음 켜는 값. 숫자는 메움 값 2비트, 바이트는 길이 지시자 9비트를 미리 문다.</summary>
    private static readonly int[] HeadCosts =
    [
        4 * CostMult, (4 + 2) * CostMult, 4 * CostMult, 4 * CostMult, 4 * CostMult, (4 + 9) * CostMult
    ];

    /// <summary>표 9의 전환 비용. [지금 모드 열][갈 모드 열]이다.</summary>
    private static readonly int[][] SwitchCosts =
    [
        [0, (13 + 2) * CostMult, 13 * CostMult, 13 * CostMult, 13 * CostMult, (13 + 9) * CostMult],
        [10 * CostMult, 0, 10 * CostMult, 10 * CostMult, 10 * CostMult, (10 + 9) * CostMult],
        [5 * CostMult, (5 + 2) * CostMult, 0, 5 * CostMult, 7 * CostMult, (7 + 9) * CostMult],
        [5 * CostMult, (5 + 2) * CostMult, 5 * CostMult, 0, 7 * CostMult, (7 + 9) * CostMult],
        [10 * CostMult, (10 + 2) * CostMult, 10 * CostMult, 10 * CostMult, 0, (10 + 9) * CostMult],
        [4 * CostMult, (4 + 2) * CostMult, 4 * CostMult, 4 * CostMult, 4 * CostMult, 0]
    ];

    /// <summary>
    /// 애니라벨이 쓰는 전환 비용표. 대문자↔소문자 칸만 5비트가 아니라 7비트다.
    ///
    /// 표 9에서 대·소문자 줄의 지시자는 두 무리로 나뉜다 — 한자·숫자·상대 대소문자·끝표시는 5비트짜리
    /// 27~30번이고, 혼합·시프트·바이트는 7비트짜리 124~126번이다. 대↔소는 30번이라 5비트가 맞는데,
    /// 애니라벨의 비용표는 이 칸을 옆의 7비트 무리 값으로 물고 있다. 값을 꺼내 쓸 때는 제대로 30번을
    /// 5비트로 내보내므로, 비용을 재는 표에서만 어긋난 것이다.
    ///
    /// 표본 스물세 칸을 전부 맞히는 값의 구간은 5⅓~7비트이고, 그 안에서 표 9에 실제로 적힌 숫자는
    /// 7뿐이다. 나머지 후보(첫 구간 최소 길이·혼합 시작값·혼합 나가는 전환)는 이 구간을 가르려고
    /// 받은 33·34·35·36·38번 칸이 각각 떨어뜨렸다.
    ///
    /// 규격은 5비트라고 못 박았고 7비트로 재면 규격보다 긴 비트열이 나온다 — 무작위 20만 개로 재니
    /// 226개가 길어지고 그 가운데 8개는 판형까지 커졌다. 그래서 새로 만드는 바코드는 건드리지 않고
    /// 가져오기 경로에서만 쓴다.
    /// </summary>
    private static readonly int[][] AniLabelSwitchCosts =
    [
        SwitchCosts[IdxHan],
        SwitchCosts[IdxNum],
        [5 * CostMult, (5 + 2) * CostMult, 0, 7 * CostMult, 7 * CostMult, (7 + 9) * CostMult],
        [5 * CostMult, (5 + 2) * CostMult, 7 * CostMult, 0, 7 * CostMult, (7 + 9) * CostMult],
        SwitchCosts[IdxMixed],
        SwitchCosts[IdxByte]
    ];

    /// <summary>표 9의 자료 끝 표시 비용.</summary>
    private static readonly int[] EodCosts =
    [
        13 * CostMult, 10 * CostMult, 5 * CostMult, 5 * CostMult, 10 * CostMult, 4 * CostMult
    ];

    /// <summary>숫자 모드가 숫자 사이에 함께 담을 수 있는 글자(6.3.2). 줄바꿈 쌍은 따로 처리한다.</summary>
    private const string NumeralNonDigits = " +-.,";

    private const int MaxVersion = 13;

    private const int MaxEcc = 5;

    /// <summary>자료 부호어 1313개 × 7비트가 비트 흐름의 한도다.</summary>
    private const int MaxBits = 1313 * 7;

    /// <summary>한도를 넘는 순간을 잡아내려면 바이트 모드가 한 번에 넘겨 쓰는 46비트만큼 여유가 있어야 한다.</summary>
    private const int BinaryCapacity = MaxBits + 49;

    private const int MaxDataCws = 1313;

    /// <summary>판형 13의 매크로모듈 27×27 × 부호어 2개.</summary>
    private const int MaxTotalCws = 1458;

    private const int GfPoly = 0x89;
    private const int GfSize = 128;
    private const int GfOrder = 127;

    /// <summary>부속서 A가 지정한 GF(128), 원시 다항식 0x89(x⁷+x³+1).</summary>
    private static readonly int[] GfLog = new int[GfSize];

    /// <summary>지수를 두 벌 깔아 두어 곱셈에서 나머지 연산을 없앤다.</summary>
    private static readonly int[] GfExp = new int[GfSize * 2];

    /// <summary>
    /// GB 2312(코드페이지 936). 옮길 수 없는 글자는 예외로 튕겨 내야 해서 대체 문자를 끈다.
    /// 제공자는 Program.cs가 이미 등록하지만, 그것만 믿으면 호출 순서에 매이므로 여기서도 한 번 건다.
    /// </summary>
    private static readonly Lazy<Encoding?> Gb2312 = new(() =>
    {
        try
        {
            Encoding.RegisterProvider(CodePagesEncodingProvider.Instance);
            return Encoding.GetEncoding(936, EncoderFallback.ExceptionFallback, DecoderFallback.ExceptionFallback);
        }
        catch (Exception)
        {
            return null;
        }
    });

    static GridMatrixEncoder()
    {
        for (int p = 1, v = 0; v < GfOrder; v++)
        {
            GfExp[v] = p;
            GfExp[GfOrder + v] = p;
            GfLog[p] = v;
            p <<= 1;
            if ((p & GfSize) != 0) p ^= GfPoly;
        }
    }

    /// <summary>버전·오류정정 등급을 지정하지 않으면 데이터에 맞는 최소 조합을 고른다.</summary>
    /// <param name="wantVersion">1~13. 0이면 자동.</param>
    /// <param name="wantEcc">
    /// 1~5(자료 비중 90·80·70·60·50%). 0이면 들어가는 가장 높은 등급. 표 11의 권장 최솟값보다 낮게
    /// 요구하면 그 값으로 올린다.
    /// </param>
    /// <param name="aniLabelQuirks">
    /// 애니라벨 가져오기 전용. 켜면 규격을 벗어난 애니라벨의 기호 값 두 가지를 일부러 그대로 재현한다:
    /// 혼합 모드 빈칸을 62가 아닌 26으로 적고(규격 해독기는 그 자리를 'Q'로 읽는다), 바이트 블록 길이
    /// 지시자에 「바이트 수 − 1」이 아닌 바이트 수를 적는다(규격 해독기는 블록 뒤로 쓰레기 한 바이트를
    /// 더 집어 간다). 비트 길이는 둘 다 그대로라 판형·ECC 등급 선택에는 영향이 없다.
    /// 편집기에서 새로 만드는 바코드는 규격대로 찍어야 하니 기본값은 끈 상태다.
    /// </param>
    public static BitMatrix? Encode(string value, int wantVersion = 0, int wantEcc = 0,
                                    bool aniLabelQuirks = false)
    {
        if (string.IsNullOrEmpty(value)) return null;
        if (wantVersion is < 0 or > MaxVersion || wantEcc is < 0 or > MaxEcc) return null;

        try
        {
            var points = ToCodePoints(value);
            if (points.Length == 0) return null;

            var binary = new bool[BinaryCapacity];
            var bitLength = EncodeBits(points, DefineModes(points, aniLabelQuirks), binary, aniLabelQuirks);
            if (bitLength < 0) return null;

            var dataCws = bitLength / 7;
            var version = PickVersion(dataCws, wantVersion);
            if (version < 0) return null;

            var ecc = PickEcc(dataCws, version, wantEcc);
            if (ecc < 0) return null;

            return BuildGrid(AddEcc(binary, dataCws, version, ecc), version, ecc);
        }
        catch (Exception)
        {
            // 라벨 미리보기 중에 터지면 편집기가 통째로 멎는다. 못 그리면 조용히 비운다.
            return null;
        }
    }

    /// <summary>
    /// ASCII는 그대로 두고 나머지는 GB 2312 두 바이트로 바꾼다. GB 2312에 없는 글자는 UTF-8
    /// 바이트로 풀어 바이트 모드에 싣는다 — 6.3.7의 바이트 모드는 코드페이지를 따지지 않는 날 바이트
    /// 통로라 한글을 실어도 규격을 벗어나지 않는다. 애니라벨도 한글을 UTF-8 바이트 모드로 담는다.
    /// </summary>
    private static int[] ToCodePoints(string value)
    {
        if (value.All(char.IsAscii))
        {
            var ascii = new int[value.Length];
            for (var i = 0; i < value.Length; i++)
                ascii[i] = value[i];
            return ascii;
        }

        return TryGb2312Points(value) ?? Utf8Points(value);
    }

    /// <summary>
    /// GBK가 덧붙인 구역은 6.3.1.2의 자릿수 계산(구역 0xA1~0xA9·0xB0~0xF7)에 들어갈 자리가 없어
    /// 한자 모드로 못 보낸다. 한 글자라도 걸리면 전체를 포기해 바이트 모드로 넘긴다.
    /// </summary>
    private static int[]? TryGb2312Points(string value)
    {
        var gb = Gb2312.Value;
        if (gb is null) return null;

        byte[] bytes;
        try
        {
            bytes = gb.GetBytes(value);
        }
        catch (EncoderFallbackException)
        {
            return null;
        }

        var points = new int[bytes.Length];
        var count = 0;
        for (var i = 0; i < bytes.Length; i++)
        {
            if (bytes[i] <= 0x7F)
            {
                points[count++] = bytes[i];
                continue;
            }
            if (i + 1 >= bytes.Length) return null;
            int lead = bytes[i];
            int trail = bytes[i + 1];
            if (lead is not (>= 0xA1 and <= 0xA9) and not (>= 0xB0 and <= 0xF7)) return null;
            if (trail is < 0xA1 or > 0xFE) return null;
            points[count++] = lead << 8 | trail;
            i++;
        }
        return points[..count];
    }

    /// <summary>
    /// UTF-8 바이트를 한 개씩 낱개 부호점으로 깐다. ASCII 구간은 0x7F 아래로 남아 대·소문자·숫자
    /// 모드가 그대로 집어 갈 수 있고, 0x80 위의 바이트만 바이트 모드로 흘러간다.
    /// </summary>
    private static int[] Utf8Points(string value)
    {
        var bytes = Encoding.UTF8.GetBytes(value);
        var points = new int[bytes.Length];
        for (var i = 0; i < bytes.Length; i++)
            points[i] = bytes[i];
        return points;
    }

    /// <summary>
    /// 표 11의 권장 부호어 수에 드는 가장 작은 판형. 판형을 지정받았으면 자료가 들어가는지만 본다.
    /// </summary>
    private static int PickVersion(int dataCws, int wantVersion)
    {
        if (wantVersion >= 1)
            return dataCws <= MaxCws[wantVersion - 1] ? wantVersion : -1;

        var version = MaxVersion;
        for (var i = MaxVersion - 1; i > 0; i--)
        {
            if (RecommendCws[i - 1] >= dataCws) version = i;
        }
        return dataCws <= MaxCws[version - 1] ? version : -1;
    }

    /// <summary>
    /// 애니라벨 실측은 자료 비중이 가장 낮은 5등급이라, 가장 높은 등급에서 시작해 자료가 들어갈 때까지
    /// 내린다. 판형은 이미 표 11의 권장 부호어 수로 정해 두었으므로 등급을 높게 잡아도 심볼이 커지지
    /// 않고, 내려가도 그 판형의 권장 등급 아래로는 떨어지지 않는다.
    /// </summary>
    private static int PickEcc(int dataCws, int version, int wantEcc)
    {
        // 권장 최솟값보다 낮게 요구받으면 조용히 올린다. 읽을 수 없을 만큼 약한 심볼은 만들지 않는다.
        var minRecommended = version == 1 ? 4 : version == 2 ? 2 : 1;
        var ecc = wantEcc >= 1 ? Math.Max(wantEcc, minRecommended) : MaxEcc;

        var floor = version > 1 ? 1 : 2;                    // 판형 1의 1등급은 규격에 없다
        while (ecc > floor && dataCws > DataCws[version - 1][ecc - 1])
            ecc--;
        return dataCws <= DataCws[version - 1][ecc - 1] ? ecc : -1;
    }

    /// <summary>
    /// 글자마다 어느 모드로 담을지 정한다. 모드별 누적 비용을 훑어 내려가는 동적 계획법이고,
    /// 전환 비용까지 합쳐 최소가 되는 길을 뒤에서부터 되짚어 뽑는다.
    /// </summary>
    /// <param name="aniLabelQuirks">
    /// 켜면 대문자↔소문자 전환을 규격의 5비트가 아니라 애니라벨이 물고 있는 7비트로 재서, 가져온
    /// 바코드의 모드 배치를 그대로 되살린다. <see cref="AniLabelSwitchCosts"/>를 보라.
    /// </param>
    private static int[] DefineModes(int[] points, bool aniLabelQuirks)
    {
        var switchCosts = aniLabelQuirks ? AniLabelSwitchCosts : SwitchCosts;
        var length = points.Length;
        var charModes = new int[length, ModeCount];
        var prevCosts = (int[])HeadCosts.Clone();
        var curCosts = new int[ModeCount];
        var numeralEnd = 0;
        var numeralCost = 0;
        var byteCount = 0;

        for (var i = 0; i < length; i++)
        {
            Array.Clear(curCosts);

            var doubleByte = points[i] > 0xFF;
            var space = !doubleByte && points[i] == ' ';
            var digit = false;
            var lower = false;
            var upper = false;
            var control = false;
            var doubleDigit = false;
            var eol = false;
            if (!doubleByte && !space)
            {
                digit = IsDigit(points[i]);
                if (!digit)
                {
                    lower = IsLower(points[i]);
                    upper = IsUpper(points[i]);
                    if (!lower && !upper)
                    {
                        control = points[i] < 0x7F;         // DEL은 표 7에 자리가 없다
                        eol = i + 1 < length && points[i] == 13 && points[i + 1] == 10;
                    }
                }
                else if (i + 1 < length)
                {
                    doubleDigit = IsDigit(points[i + 1]);
                }
            }

            // 한자 모드는 GB 2312 짝과 ASCII를 담고, 숫자 두 글자와 줄바꿈 쌍은 한 부호에 묶어 절반
            // 값이다. UTF-8로 풀린 0x80~0xFF 낱개 바이트는 6.3.1.2의 「7777 + 값」 자리가 규격에
            // 없는 구간으로 넘어가므로 한자 모드에서 뺀다 — 이 바이트는 바이트 모드로만 나간다.
            if (doubleByte || points[i] < 0x80)
            {
                curCosts[IdxHan] = prevCosts[IdxHan] + (doubleDigit || eol ? 39 : 78);
                charModes[i, IdxHan] = ModeChinese;
            }

            if (byteCount == 512 || (doubleByte && byteCount == 511))
            {
                // 6.3.7의 블록 한도가 512바이트다. 넘치면 길이 지시자를 새로 깔아야 한다.
                curCosts[IdxByte] = HeadCosts[IdxByte];
                if (doubleByte && byteCount == 511)
                {
                    curCosts[IdxByte] += 8 * CostMult;
                    doubleByte = false;                     // 두 바이트가 블록 경계에서 갈라진다
                }
                byteCount = 0;
            }
            curCosts[IdxByte] += prevCosts[IdxByte] + (doubleByte ? 96 : 48);
            charModes[i, IdxByte] = ModeByte;
            byteCount += doubleByte ? 2 : 1;

            if (InNumeral(points, i, ref numeralEnd, ref numeralCost))
            {
                curCosts[IdxNum] = prevCosts[IdxNum] + numeralCost;
                charModes[i, IdxNum] = ModeNumeral;
            }

            if (control)
            {
                // 시프트로 넣으면 지시자까지 얹혀 대·소문자는 7+6비트, 혼합은 10+6비트가 된다.
                curCosts[IdxLower] = prevCosts[IdxLower] + (7 + 6) * CostMult;
                charModes[i, IdxLower] = ModeLower;
                curCosts[IdxUpper] = prevCosts[IdxUpper] + (7 + 6) * CostMult;
                charModes[i, IdxUpper] = ModeUpper;
                curCosts[IdxMixed] = prevCosts[IdxMixed] + (10 + 6) * CostMult;
                charModes[i, IdxMixed] = ModeMixed;
            }
            else
            {
                if (lower || space)
                {
                    curCosts[IdxLower] = prevCosts[IdxLower] + 5 * CostMult;
                    charModes[i, IdxLower] = ModeLower;
                }
                if (upper || space)
                {
                    curCosts[IdxUpper] = prevCosts[IdxUpper] + 5 * CostMult;
                    charModes[i, IdxUpper] = ModeUpper;
                }
                if (digit || lower || upper || space)
                {
                    curCosts[IdxMixed] = prevCosts[IdxMixed] + 6 * CostMult;
                    charModes[i, IdxMixed] = ModeMixed;
                }
            }

            if (i + 1 == length)
            {
                for (var j = 0; j < ModeCount; j++)
                {
                    if (charModes[i, j] != 0) curCosts[j] += EodCosts[j];
                }
            }

            // 여기서 모드를 갈아 새 구간을 여는 쪽이 싸면 그쪽으로 갈아탄다.
            // 값이 같을 때 어느 쪽을 남길지는 비용이 정해 주지 않는다. 「이 글자를 j로 담는다(전환이
            // 앞에서 이미 일어났다)」와 「이 글자를 k로 담고 뒤에서 j로 간다」가 같은 값이면, 부등호에
            // 등호를 넣는 쪽이 앞 것을, 빼는 쪽이 뒤 것을 남긴다 — 곧 경계 글자가 앞뒤 어느 구간에
            // 붙느냐만 갈린다. 20번 칸 「GRID matrix」가 이것 하나로 갈렸다: 두 길 다 69비트인데
            // 애니라벨은 사이의 빈칸을 앞의 대문자 구간에 남겼고(UPR×5 LWR×6), 등호를 빼면 같아진다.
            // 무작위 20만 개로 재 보니 이 쪽이 심볼을 키우는 경우는 한 건도 없고 오히려 세 건이
            // 작아지므로, 가져오기 전용이 아니라 새로 만드는 바코드에도 그대로 쓴다.
            for (var j = 0; j < ModeCount; j++)
            {
                for (var k = 0; k < ModeCount; k++)
                {
                    if (j == k || charModes[i, k] == 0) continue;
                    var newCost = curCosts[k] + switchCosts[k][j];
                    if (charModes[i, j] != 0 && newCost > curCosts[j]) continue;
                    curCosts[j] = newCost;
                    charModes[i, j] = k + 1;
                }
            }

            Array.Copy(curCosts, prevCosts, ModeCount);
        }

        // 여기 등호는 반대로 남겨 둔다. 값이 같으면 번호가 앞선 모드가 이기고, 그래서 대·소문자가
        // 혼합보다 먼저 뽑힌다. 12번 칸에서 「전부 혼합」과 「혼합 뒤 소문자」가 같은 값일 때
        // 애니라벨이 고른 쪽도 소문자로 끝나는 쪽이라, 이 방향이 실측과 맞는다.
        var minCost = prevCosts[0];
        var mode = ModeChinese;
        for (var i = 1; i < ModeCount; i++)
        {
            if (prevCosts[i] >= minCost) continue;
            minCost = prevCosts[i];
            mode = i + 1;
        }

        var modes = new int[length];
        for (var i = length - 1; i >= 0; i--)
        {
            mode = charModes[i, mode - 1];
            modes[i] = mode;
        }
        return modes;
    }

    /// <summary>
    /// 이 자리에서 숫자 모드를 열 수 있는지 본다. 숫자 세 글자를 열 비트에 묶는 모드라 숫자가 하나는
    /// 있어야 하고, 묶음 하나에 숫자 아닌 글자는 한 개까지만 끼울 수 있다(6.3.2) — "2.2.0"이 그래서 걸린다.
    /// 열 수 있으면 묶음이 끝나는 자리와 글자 하나당 평균 비용을 함께 돌려준다.
    /// </summary>
    private static bool InNumeral(int[] points, int position, ref int numeralEnd, ref int numeralCost)
    {
        if (position < numeralEnd) return true;

        var length = points.Length;
        var digits = 0;
        var nonDigit = 0;
        var nonDigitPosition = 0;
        var i = position;
        for (; i < length && i < position + 4 && digits < 3; i++)
        {
            if (IsDigit(points[i]))
            {
                digits++;
                continue;
            }
            if (points[i] <= 0xFF && NumeralNonDigits.Contains((char)points[i], StringComparison.Ordinal))
            {
                if (nonDigit != 0)
                {
                    numeralEnd = 0;
                    return false;
                }
                nonDigit = 1;
                nonDigitPosition = i;
                continue;
            }
            if (i + 1 < length && points[i] == 13 && points[i + 1] == 10)
            {
                if (nonDigit != 0)
                {
                    numeralEnd = 0;
                    return false;
                }
                i++;
                nonDigit = 2;                               // 줄바꿈 쌍은 두 글자를 먹는다
                nonDigitPosition = i;
                continue;
            }
            break;
        }

        if (digits == 0)
        {
            numeralEnd = 0;
            return false;
        }
        if (nonDigit != 0 && nonDigitPosition == i - 1)
            nonDigit = 0;                                   // 숫자 아닌 글자가 묶음 끝에 오면 담을 자리가 없다

        numeralEnd = position + digits + nonDigit;
        // 묶음 값 10비트에 끼움 글자 10비트가 더 붙는다. 글자 수로 나눠 CostMult를 곱한 값이다.
        numeralCost = digits switch
        {
            3 => nonDigit == 2 ? 24 : nonDigit == 1 ? 30 : 20,
            2 => nonDigit == 2 ? 30 : nonDigit == 1 ? 40 : 30,
            _ => nonDigit == 2 ? 40 : 60
        };
        return true;
    }

    /// <summary>비트 흐름을 짠다. 한도를 넘으면 -1.</summary>
    private static int EncodeBits(int[] points, int[] modes, bool[] binary, bool aniLabelQuirks)
    {
        var length = points.Length;
        var numbuf = new char[3];
        var sp = 0;
        var bp = 0;
        var currentMode = 0;
        var numeralCount = 0;
        var numeralPadPosition = 0;
        var byteCountPosition = 0;
        var byteCount = 0;
        int glyph;

        // 6.3.7의 블록 길이 지시자는 「바이트 수 − 1」이다 — 9비트로 1~512바이트를 가리킨다.
        // 애니라벨은 바이트 수를 그대로 적는다(22번 칸 「grid !@#$」 실측: 네 바이트 블록에 4를 적었다).
        // 그래서 규격대로 읽는 해독기는 블록 뒤로 쓰레기 한 바이트를 더 집어 간다. 가져오기 충실도
        // 때문에 그 결함을 일부러 따라 적는다.
        int BlockLength(int count) => aniLabelQuirks ? count : count - 1;

        do
        {
            var nextMode = modes[sp];
            if (nextMode != currentMode)
            {
                // 모드를 떠나기 전에, 앞서 비워 둔 자리에 그 구간의 마무리 값을 적어 넣는다.
                if (currentMode == ModeByte)
                {
                    WriteAt(binary, BlockLength(byteCount), 9, byteCountPosition);
                    byteCount = 0;
                }
                else if (currentMode == ModeNumeral && numeralCount != 0)
                {
                    WriteAt(binary, 3 - numeralCount, 2, numeralPadPosition);
                }
                bp = Write(binary, ModeSwitch[currentMode][nextMode - 1],
                    ModeSwitchBits[currentMode][nextMode - 1], bp);
                if (bp > MaxBits) return -1;
            }
            var lastMode = currentMode;
            currentMode = nextMode;

            switch (currentMode)
            {
                case ModeChinese:
                    if (points[sp] > 0xFF)
                    {
                        // 구역·자리 번호를 이어 붙인 값. 0xAA~0xAF는 GB 2312에 없어 아홉 구역을 건너뛴다.
                        var lead = points[sp] >> 8;
                        var trail = points[sp] & 0xFF;
                        glyph = lead <= 0xA9
                            ? 0x60 * (lead - 0xA1) + (trail - 0xA0)
                            : 0x60 * (lead - 0xB0 + 9) + (trail - 0xA0);
                    }
                    else if (sp + 1 < length && points[sp] == 13 && points[sp + 1] == 10)
                    {
                        glyph = 7776;
                        sp++;
                    }
                    else if (sp + 1 < length && IsDigit(points[sp]) && IsDigit(points[sp + 1]))
                    {
                        glyph = 8033 + 10 * (points[sp] - '0') + (points[sp + 1] - '0');
                        sp++;
                    }
                    else
                    {
                        glyph = 7777 + points[sp];
                    }
                    bp = Write(binary, glyph, 13, bp);
                    sp++;
                    break;

                case ModeNumeral:
                    if (lastMode != currentMode)
                    {
                        numeralPadPosition = bp;            // 마지막 묶음의 빈 자릿수를 적을 2비트
                        bp = Write(binary, 0, 2, bp);
                    }
                    numeralCount = 0;
                    numbuf[0] = numbuf[1] = numbuf[2] = '0';
                    var punt = 0;
                    var puntPosition = -1;
                    do
                    {
                        if (IsDigit(points[sp]))
                        {
                            numbuf[numeralCount++] = (char)points[sp];
                        }
                        else if (points[sp] <= 0xFF
                                 && NumeralNonDigits.Contains((char)points[sp], StringComparison.Ordinal))
                        {
                            if (puntPosition != -1) break;
                            punt = points[sp];
                            puntPosition = numeralCount;
                        }
                        else if (sp + 1 < length && points[sp] == 13 && points[sp + 1] == 10)
                        {
                            if (puntPosition != -1) break;
                            punt = points[sp++];
                            puntPosition = numeralCount;
                        }
                        else
                        {
                            break;
                        }
                        sp++;
                    } while (numeralCount < 3 && sp < length && modes[sp] == ModeNumeral);

                    if (puntPosition != -1)
                    {
                        // 표 6의 1000~1023. 끼움 글자 종류와 그 글자가 묶음 안에서 앉는 자리를 함께 담는다.
                        glyph = (punt == 13
                            ? 15
                            : NumeralNonDigits.IndexOf((char)punt, StringComparison.Ordinal) * 3) + puntPosition;
                        bp = Write(binary, glyph + 1000, 10, bp);
                    }
                    glyph = 100 * (numbuf[0] - '0') + 10 * (numbuf[1] - '0') + (numbuf[2] - '0');
                    bp = Write(binary, glyph, 10, bp);
                    break;

                case ModeByte:
                    if (lastMode != currentMode)
                    {
                        byteCountPosition = bp;             // 6.3.7의 블록 길이 지시자 9비트
                        bp = Write(binary, 0, 9, bp);
                    }
                    glyph = points[sp];
                    if (byteCount == 512 || (glyph > 0xFF && byteCount == 511))
                    {
                        if (glyph > 0xFF && byteCount == 511)
                        {
                            bp = Write(binary, glyph >> 8, 8, bp);
                            glyph &= 0xFF;
                            byteCount++;
                        }
                        WriteAt(binary, BlockLength(byteCount), 9, byteCountPosition);
                        bp = Write(binary, 7, 4, bp);       // 바이트 모드를 한 번 더 켜서 블록을 새로 연다
                        byteCountPosition = bp;
                        bp = Write(binary, 0, 9, bp);
                        byteCount = 0;
                    }
                    bp = Write(binary, glyph, glyph > 0xFF ? 16 : 8, bp);
                    sp++;
                    byteCount += glyph > 0xFF ? 2 : 1;
                    break;

                case ModeMixed:
                    if (points[sp] == ' ' || IsDigit(points[sp]) || IsUpper(points[sp]) || IsLower(points[sp]))
                    {
                        // 혼합 집합은 숫자 0~9·대문자 10~35·소문자 36~61에 빈칸이 62다. 애니라벨은 여기에
                        // 대·소문자 집합의 빈칸 값 26을 그대로 쓴다(12·21번 칸 실측). 26은 혼합 집합에서
                        // 'Q' 자리라, 규격대로 읽는 해독기는 빈칸을 'Q'로 읽는다. 21번 칸은 모드 열과 비트
                        // 수가 우리와 똑같은데도 이 값 하나 때문에 90모듈이 어긋났다 — 가져오기 충실도
                        // 때문에 그 결함을 일부러 따라 적는다.
                        glyph = points[sp] == ' '
                            ? (aniLabelQuirks ? 26 : 62)
                            : points[sp] - (IsDigit(points[sp]) ? '0' : IsUpper(points[sp]) ? 'A' - 10 : 'a' - 36);
                        bp = Write(binary, glyph, 6, bp);
                    }
                    else
                    {
                        bp = Write(binary, 1014, 10, bp);
                        bp = WriteShiftChar(binary, bp, points[sp]);
                    }
                    sp++;
                    break;

                case ModeUpper:
                    if (points[sp] == ' ' || IsUpper(points[sp]))
                    {
                        bp = Write(binary, points[sp] == ' ' ? 26 : points[sp] - 'A', 5, bp);
                    }
                    else
                    {
                        bp = Write(binary, 125, 7, bp);
                        bp = WriteShiftChar(binary, bp, points[sp]);
                    }
                    sp++;
                    break;

                default:
                    if (points[sp] == ' ' || IsLower(points[sp]))
                    {
                        bp = Write(binary, points[sp] == ' ' ? 26 : points[sp] - 'a', 5, bp);
                    }
                    else
                    {
                        bp = Write(binary, 125, 7, bp);
                        bp = WriteShiftChar(binary, bp, points[sp]);
                    }
                    sp++;
                    break;
            }

            if (bp > MaxBits) return -1;
        } while (sp < length);

        if (currentMode == ModeByte)
            WriteAt(binary, BlockLength(byteCount), 9, byteCountPosition);
        else if (currentMode == ModeNumeral && numeralCount != 0)
            WriteAt(binary, 3 - numeralCount, 2, numeralPadPosition);

        bp = Write(binary, ModeSwitch[ModeEnd][currentMode - 1], ModeSwitchBits[ModeEnd][currentMode - 1], bp);
        if (bp > MaxBits) return -1;

        // 부호어가 7비트라 비트 흐름 길이도 7의 배수로 맞춘다.
        var padding = 7 - bp % 7;
        return padding % 7 == 0 ? bp : Write(binary, 0, padding, bp);
    }

    /// <summary>
    /// 표 7. 알파벳·숫자·빈칸이 아닌 ASCII를 시프트 값 여섯 비트에 접는다. 제어문자 32자에 이어
    /// 33~47·58~64·91~96·123~126이 빈틈없이 붙어 예순넷이 예순네 칸을 꽉 채우므로, DEL은 자리가 없다.
    /// libzint는 91~96에서만 46을 빼서 '.'(45)·':'(47) 같은 앞 구간 값과 겹치는 값을 내보낸다.
    /// 나머지 세 구간(−1·−11·−63)은 모두 이 빈틈없는 배치와 맞아떨어지므로 37을 빼는 쪽이 옳다.
    /// </summary>
    private static int WriteShiftChar(bool[] binary, int bp, int c)
    {
        var glyph = c switch
        {
            < ' ' => c,
            < '0' => c - 1,
            < 'A' => c - 11,
            < 'a' => c - 37,
            _ => c - 63
        };
        return Write(binary, glyph, 6, bp);
    }

    private static int Write(bool[] binary, int value, int length, int position)
    {
        WriteAt(binary, value, length, position);
        return position + length;
    }

    private static void WriteAt(bool[] binary, int value, int length, int position)
    {
        for (var i = 0; i < length; i++)
            binary[position + i] = (value >> (length - 1 - i) & 1) != 0;
    }

    /// <summary>
    /// 7비트 부호어로 자른 뒤 블록마다 오류정정 부호어를 붙이고, 블록을 한 칸씩 번갈아 꿴다(부속서 A).
    /// </summary>
    private static byte[] AddEcc(bool[] binary, int dataCws, int version, int ecc)
    {
        var data = new byte[MaxDataCws];
        for (var i = 0; i < dataCws; i++)
        {
            var word = 0;
            for (var j = 0; j < 7; j++)
                word |= (binary[i * 7 + j] ? 1 : 0) << (6 - j);
            data[i] = (byte)word;
        }

        // 6.7의 메움 부호어. 자료 바로 뒤 한 칸은 0으로 비우고, 그다음부터 홀수 자리에만 0x7E가 든다.
        var totalData = DataCws[version - 1][ecc - 1];
        for (var i = dataCws + 1; i < totalData; i++)
        {
            if ((i & 1) != 0) data[i] = 0x7E;
        }

        var size1 = BlockSize1[version - 1];
        var count1 = BlockCount1[version - 1];
        var count3 = BlockCount3[version - 1][ecc - 1];
        var ecc1 = BlockEcc1[version - 1][ecc - 1];
        var ecc2 = BlockEcc2[version - 1][ecc - 1];
        var blocks = count1 + BlockCount2[version - 1];

        var cws = new byte[MaxTotalCws];
        var read = 0;
        for (var i = 0; i < blocks; i++)
        {
            var blockSize = i < count1 ? size1 : size1 - 1;
            var eccSize = i < count3 ? ecc1 : ecc2;
            var dataSize = blockSize - eccSize;
            var block = new byte[blockSize];
            Array.Copy(data, read, block, 0, dataSize);
            read += dataSize;
            ReedSolomon(block, dataSize, eccSize);
            for (var j = 0; j < blockSize; j++)
                cws[blocks * j + i] = block[j];
        }
        return cws;
    }

    /// <summary>
    /// 생성 다항식 (x+α¹)…(x+α^n)으로 나눈 나머지를 뒤집어 block 뒤에 붙인다.
    /// 계수가 0인 항은 로그를 취할 수 없어 곱셈에서 빼고 자리만 밀어 준다.
    /// </summary>
    internal static void ReedSolomon(byte[] block, int dataSize, int eccSize)
    {
        if (eccSize == 0) return;

        var poly = new int[eccSize + 1];
        poly[0] = 1;
        for (var i = 1; i <= eccSize; i++)
        {
            poly[i] = 1;
            for (var k = i - 1; k > 0; k--)
            {
                if (poly[k] != 0) poly[k] = GfExp[GfLog[poly[k]] + i];
                poly[k] ^= poly[k - 1];
            }
            poly[0] = GfExp[GfLog[poly[0]] + i];
        }

        var residue = new int[eccSize];
        for (var i = 0; i < dataSize; i++)
        {
            var m = residue[eccSize - 1] ^ block[i];
            if (m == 0)
            {
                Array.Copy(residue, 0, residue, 1, eccSize - 1);
                residue[0] = 0;
                continue;
            }
            var logM = GfLog[m];
            for (var k = eccSize - 1; k > 0; k--)
                residue[k] = poly[k] != 0 ? residue[k - 1] ^ GfExp[logM + GfLog[poly[k]]] : residue[k - 1];
            residue[0] = GfExp[logM + GfLog[poly[0]]];
        }

        for (var i = 0; i < eccSize; i++)
            block[dataSize + i] = (byte)residue[eccSize - 1 - i];
    }

    private static BitMatrix BuildGrid(byte[] cws, int version, int ecc)
    {
        var size = 6 + version * 12;
        var perDim = 1 + version * 2;
        var grid = new bool[size, size];

        for (var row = 0; row < perDim; row++)
        {
            for (var col = 0; col < perDim; col++)
            {
                var index = MacromoduleIndex(perDim, col, row);
                PlaceMacromodule(grid, col, row, cws[index + 1] << 7 | cws[index]);
            }
        }
        PlaceLayerIds(grid, version, ecc);

        // 6.9의 매크로모듈 테두리. 체스판의 검은 칸에만 두르고 흰 칸은 비워, 그 경계가 타이밍 무늬가 된다.
        for (var col = 0; col < perDim; col++)
        {
            var dark = (col & 1) == 0;
            for (var row = 0; row < perDim; row++)
            {
                if (dark)
                {
                    var left = col * 6;
                    var top = row * 6;
                    for (var i = 0; i < 5; i++)
                    {
                        grid[left + i, top] = true;
                        grid[left + i, top + 5] = true;
                        grid[left, top + i] = true;
                        grid[left + 5, top + i] = true;
                    }
                    grid[left + 5, top + 5] = true;
                }
                dark = !dark;
            }
        }

        var matrix = new BitMatrix(size, size);
        for (var y = 0; y < size; y++)
        {
            for (var x = 0; x < size; x++)
            {
                if (grid[x, y]) matrix[x, y] = true;
            }
        }
        return matrix;
    }

    /// <summary>
    /// 매크로모듈 안쪽 4×4에 부호어 두 개(14비트)를 높은 자리부터 채운다. 첫 줄 왼쪽 두 칸은
    /// 층 번호가 쓰는 자리라 자료는 첫 줄 오른쪽 두 칸에서 시작해 아래로 내려간다.
    /// </summary>
    private static void PlaceMacromodule(bool[,] grid, int col, int row, int cw)
    {
        var left = col * 6 + 1;
        var top = row * 6 + 1;
        if ((cw & 0x2000) != 0) grid[left + 2, top] = true;
        if ((cw & 0x1000) != 0) grid[left + 3, top] = true;
        for (var r = 1; r < 4; r++)
        {
            for (var c = 0; c < 4; c++)
            {
                if ((cw >> (15 - r * 4 - c) & 1) != 0) grid[left + c, top + r] = true;
            }
        }
    }

    /// <summary>
    /// 부호어가 들어갈 매크로모듈 번호. 가장 안쪽 고리의 0번에서 시작해 바깥 고리로 번호가 커지고,
    /// 한 고리 안에서는 오른쪽 아래에서 시계 반대 방향으로 돈다. 한 칸에 부호어가 둘이라 두 배로 돌려준다.
    /// </summary>
    internal static int MacromoduleIndex(int perDim, int col, int row)
    {
        // 바깥에서 몇 번째 고리인가. 네 변까지의 거리 중 가장 짧은 것이다.
        var ring = col < row
            ? col < perDim - row - 1 ? col : perDim - row - 1
            : row < perDim - col - 1 ? row : perDim - col - 1;
        var span = ((perDim - 1) / 2 - ring) * 2 + 1;       // 그 고리의 한 변 매크로모듈 수
        var last = span * span - 1;                          // 고리 안에서 가장 큰 번호
        int index;
        if (col == ring)
            index = last - row + ring;
        else if (row == ring)
            index = last - (span - 1) * 4 + (col - ring);
        else if (col == perDim - ring - 1)
            index = last - (span - 1) * 3 + (row - ring);
        else
            index = last - (span - 1) - (col - ring);
        return index << 1;
    }

    /// <summary>
    /// 층 번호 두 비트를 매크로모듈 첫 줄 왼쪽 두 칸에 박는다. 읽는 쪽은 고리마다 도는 이 번호로
    /// 판형과 ECC 등급을 알아낸다(6.10). 1등급만 번호가 거꾸로 돈다.
    /// </summary>
    private static void PlaceLayerIds(bool[,] grid, int version, int ecc)
    {
        var perDim = 1 + version * 2;
        var layerIds = new int[version + 1];
        for (var i = 0; i <= version; i++)
            layerIds[i] = ecc == 1 ? 3 - (i & 0x03) : (i + 5 - ecc) & 0x03;

        var ids = new int[perDim, perDim];
        var start = perDim / 2;
        var stop = perDim / 2;
        for (var layer = 0; layer <= version; layer++)
        {
            // 가운데에서 바깥으로 한 고리씩 넓혀 가며 네 변을 같은 번호로 덮는다.
            for (var i = start; i <= stop; i++)
            {
                ids[start, i] = layerIds[layer];
                ids[i, start] = layerIds[layer];
                ids[perDim - start - 1, i] = layerIds[layer];
                ids[i, perDim - start - 1] = layerIds[layer];
            }
            start--;
            stop++;
        }

        for (var row = 0; row < perDim; row++)
        {
            for (var col = 0; col < perDim; col++)
            {
                var id = ids[row, col];
                if ((id & 0x02) != 0) grid[col * 6 + 1, row * 6 + 1] = true;
                if ((id & 0x01) != 0) grid[col * 6 + 2, row * 6 + 1] = true;
            }
        }
    }

    private static bool IsDigit(int c) => c is >= '0' and <= '9';

    private static bool IsLower(int c) => c is >= 'a' and <= 'z';

    private static bool IsUpper(int c) => c is >= 'A' and <= 'Z';
}
