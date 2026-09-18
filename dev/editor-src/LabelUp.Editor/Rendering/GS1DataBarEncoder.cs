// 표와 부호화 절차는 libzint(BSD-3-Clause, Copyright (C) 2008-2026 Robin Stuart)의
// backend/rss.c·rss.h·general_field.c를 ISO/IEC 24724 조항에 맞춰 옮긴 것이다.
// 부속서 B의 combins·getRSSwidths는 BSI가 규격 구현 목적의 자유 사용을 허락한 부분이다.
// GPL·LGPL 계열 구현물은 상용 비공개 제품이라 참고할 수 없어 손대지 않았다.

using System.Globalization;
using System.Text;
using ZXing.Common;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// GS1 DataBar 옴니디렉셔널(RSS-14)·리미티드(RSS Limited)·익스팬디드(RSS Expanded). ISO/IEC 24724.
/// ZXing.Net은 이 셋을 읽기만 하고 쓰지는 못해서 규격 §5.3·§5.4·§7.2를 그대로 옮겼다.
/// RSS-14와 리미티드는 요소 46개를 이어 붙인 한 줄짜리고, 폭은 각각 96·74모듈로 고정이다.
/// 익스팬디드만 자료량에 따라 심볼 문자 4~22칸으로 늘어나 폭이 102~543모듈 사이에서 변한다.
/// 「GS1 DataBar」 실측: RSS-14 95×33, 리미티드 73×10, 익스팬디드 149×34 — 잉크 경계로 잘라서
/// 양쪽 가드의 여백 모듈이 빠진 값이다(RSS-14·리미티드는 왼쪽 하나, 익스팬디드는 양쪽 하나씩).
/// </summary>
internal static class GS1DataBarEncoder
{
    /// <summary>
    /// 부속서 B의 combins(n, r). 리미티드의 19모듈·7요소가 상한이라 n은 17, r은 5를 넘지 않는다.
    /// </summary>
    private static readonly int[][] Combinations =
    [
        [1, 1, 1, 1, 1, 1],
        [1, 1, 1, 1, 1, 1],
        [1, 2, 1, 1, 1, 1],
        [1, 3, 3, 1, 1, 1],
        [1, 4, 6, 4, 1, 1],
        [1, 5, 10, 10, 5, 1],
        [1, 6, 15, 20, 15, 6],
        [1, 7, 21, 35, 35, 21],
        [1, 8, 28, 56, 70, 56],
        [1, 9, 36, 84, 126, 126],
        [1, 10, 45, 120, 210, 252],
        [1, 11, 55, 165, 330, 462],
        [1, 12, 66, 220, 495, 792],
        [1, 13, 78, 286, 715, 1287],
        [1, 14, 91, 364, 1001, 2002],
        [1, 15, 105, 455, 1365, 3003],
        [1, 16, 120, 560, 1820, 4368],
        [1, 17, 136, 680, 2380, 6188]
    ];

    /// <summary>표 1·2의 Gsum. 앞 다섯은 바깥 문자, 뒤 넷은 안쪽 문자 몫이다.</summary>
    private static readonly int[] OmniGroupSum = [0, 161, 961, 2015, 2715, 0, 336, 1036, 1516];

    /// <summary>표 1·2의 Teven(바깥)·Todd(안쪽). 값을 홀·짝 부분집합으로 가르는 나눗수다.</summary>
    private static readonly int[] OmniGroupDivisor = [1, 10, 34, 70, 126, 4, 20, 48, 81];

    /// <summary>
    /// 묶음별 홀수 부분집합 모듈 수(앞 아홉)와 짝수 부분집합 모듈 수(뒤 아홉).
    /// 한 문자는 바깥 16모듈·안쪽 15모듈이라 짝수 쪽은 그 값에서 홀수 쪽을 뺀 나머지다.
    /// </summary>
    private static readonly int[] OmniModules =
    [
        12, 10, 8, 6, 4, 5, 7, 9, 11,
        4, 6, 8, 10, 12, 10, 8, 6, 4
    ];

    /// <summary>묶음별 홀수 부분집합 요소의 최대 너비. 짝수 쪽은 9에서 뺀 값이다.</summary>
    private static readonly int[] OmniWidest = [8, 6, 4, 3, 1, 2, 4, 6, 8];

    /// <summary>표 4. 찾기 무늬 아홉 가지의 요소 너비 다섯 개(합 15).</summary>
    private static readonly string[] OmniFinderPatterns =
        ["38211", "35511", "33711", "31911", "27411", "25611", "23811", "15711", "13911"];

    /// <summary>표 5. 자료 문자 네 개 × 요소 여덟 개에 곱할 검사 가중치.</summary>
    private static readonly int[][] OmniChecksumWeights =
    [
        [1, 3, 9, 27, 2, 6, 18, 54],
        [4, 12, 36, 29, 8, 24, 72, 58],
        [16, 48, 65, 37, 32, 17, 51, 74],
        [64, 34, 23, 69, 49, 68, 46, 59]
    ];

    /// <summary>표 6의 Gsum.</summary>
    private static readonly int[] LimitedGroupSum = [0, 183064, 820064, 1000776, 1491021, 1979845, 1996939];

    /// <summary>표 6의 Teven.</summary>
    private static readonly int[] LimitedGroupDivisor = [28, 728, 6454, 203, 2408, 1, 16632];

    /// <summary>묶음별 홀수 부분집합 모듈 수. 한 문자가 26모듈이라 짝수 쪽은 26에서 뺀 값이다.</summary>
    private static readonly int[] LimitedModules = [17, 13, 9, 15, 11, 19, 7];

    /// <summary>묶음별 홀수 부분집합 요소의 최대 너비. 짝수 쪽은 9에서 뺀 값이다.</summary>
    private static readonly int[] LimitedWidest = [6, 5, 3, 5, 4, 8, 1];

    /// <summary>표 7. 좌·우 문자 요소 열넷에 곱할 검사 가중치.</summary>
    private static readonly int[][] LimitedChecksumWeights =
    [
        [1, 3, 9, 27, 81, 65, 17, 51, 64, 14, 42, 37, 22, 66],
        [20, 60, 2, 6, 18, 54, 73, 41, 34, 13, 39, 28, 84, 74]
    ];

    /// <summary>
    /// 부속서 C. 검사값(0~88)이 그대로 찾기 무늬 번호가 된다. 요소 열넷은 모두 합이 18모듈이다.
    /// </summary>
    private static readonly string[] LimitedFinderPatterns =
    [
        "11111111113311", "11111111123211", "11111111133111", "11111112113211", "11111112123111",
        "11111113113111", "11111211113211", "11111211123111", "11111212113111", "11111311113111",
        "11121111113211", "11121111123111", "11121112113111", "11121211113111", "11131111113111",
        "12111111113211", "12111111123111", "12111112113111", "12111211113111", "12121111113111",
        "13111111113111", "11111111212311", "11111111222211", "11111111232111", "11111112212211",
        "11111112222111", "11111113212111", "11111211212211", "11111211222111", "11111212212111",
        "11111311212111", "11121111212211", "11121111222111", "11121112212111", "11121211212111",
        "11131111212111", "12111111212211", "12111111222111", "12111112212111", "12111211212111",
        "12121111212111", "13111111212111", "11111111311311", "11111111321211", "11111112311211",
        "11121111311211", "12111111311211", "11111121112311", "11111121122211", "11111121132111",
        "11111122112211", "11121121112211", "11121121122111", "11121122112111", "11121221112111",
        "11131121112111", "12111121112211", "12111121122111", "12121121112111", "11112111112311",
        "11112111122211", "11112111132111", "11112112112211", "11112112122111", "11112211112211",
        "12112111112211", "12112111122111", "12112112112111", "12112211112111", "12122111112111",
        "13112111112111", "11211111112311", "11211111122211", "11211111132111", "11211112112211",
        "11211112122111", "11211113112111", "11211211112211", "11211211122111", "11221111112211",
        "21111111122211", "21111111132111", "21111112112211", "21111112122111", "21111113112111",
        "21111211122111", "21111212112111", "21121111122111", "21111111221211"
    ];

    /// <summary>표 8의 Gsum. RSS-14와 달리 바깥·안쪽 구분이 없어 모든 문자가 묶음 다섯 개를 함께 쓴다.</summary>
    private static readonly int[] ExpandedGroupSum = [0, 348, 1388, 2948, 3988];

    /// <summary>표 8의 Teven.</summary>
    private static readonly int[] ExpandedGroupDivisor = [4, 20, 52, 104, 204];

    /// <summary>묶음별 홀수 부분집합 모듈 수. 한 문자가 17모듈이라 짝수 쪽은 17에서 뺀 값이다.</summary>
    private static readonly int[] ExpandedModules = [12, 10, 8, 6, 4];

    /// <summary>묶음별 홀수 부분집합 요소의 최대 너비. 짝수 쪽은 9에서 뺀 값이다.</summary>
    private static readonly int[] ExpandedWidest = [7, 5, 4, 3, 1];

    /// <summary>표 14. 검사 가중치 스물세 줄. 어느 줄을 쓰는지는 표 17이 자료 문자 수로 정해 준다.</summary>
    private static readonly int[][] ExpandedChecksumWeights =
    [
        [1, 3, 9, 27, 81, 32, 96, 77],
        [20, 60, 180, 118, 143, 7, 21, 63],
        [189, 145, 13, 39, 117, 140, 209, 205],
        [193, 157, 49, 147, 19, 57, 171, 91],
        [62, 186, 136, 197, 169, 85, 44, 132],
        [185, 133, 188, 142, 4, 12, 36, 108],
        [113, 128, 173, 97, 80, 29, 87, 50],
        [150, 28, 84, 41, 123, 158, 52, 156],
        [46, 138, 203, 187, 139, 206, 196, 166],
        [76, 17, 51, 153, 37, 111, 122, 155],
        [43, 129, 176, 106, 107, 110, 119, 146],
        [16, 48, 144, 10, 30, 90, 59, 177],
        [109, 116, 137, 200, 178, 112, 125, 164],
        [70, 210, 208, 202, 184, 130, 179, 115],
        [134, 191, 151, 31, 93, 68, 204, 190],
        [148, 22, 66, 198, 172, 94, 71, 2],
        [6, 18, 54, 162, 64, 192, 154, 40],
        [120, 149, 25, 75, 14, 42, 126, 167],
        [79, 26, 78, 23, 69, 207, 199, 175],
        [103, 98, 83, 38, 114, 131, 182, 124],
        [161, 61, 183, 127, 170, 88, 53, 159],
        [55, 165, 73, 8, 24, 72, 5, 15],
        [45, 135, 194, 160, 58, 174, 100, 89]
    ];

    /// <summary>표 15. 찾기 무늬 열두 가지의 요소 너비 다섯 개(합 15). 홀수 번호는 좌우를 뒤집은 짝이다.</summary>
    private static readonly string[] ExpandedFinderPatterns =
        ["18411", "11481", "36411", "11463", "34611", "11643", "32811", "11823", "26511", "11562", "22911", "11922"];

    /// <summary>표 16. 심볼 문자 수에 따라 찾기 무늬를 고르는 순서. 칸 수는 (심볼 문자 수 + 1) / 2다.</summary>
    private static readonly int[][] ExpandedFinderSequence =
    [
        [1, 2],
        [1, 4, 3],
        [1, 6, 3, 8],
        [1, 10, 3, 8, 5],
        [1, 10, 3, 8, 7, 12],
        [1, 10, 3, 8, 9, 12, 11],
        [1, 2, 3, 4, 5, 6, 7, 8],
        [1, 2, 3, 4, 5, 6, 7, 10, 9],
        [1, 2, 3, 4, 5, 6, 7, 10, 11, 12],
        [1, 2, 3, 4, 5, 8, 7, 10, 9, 12, 11]
    ];

    /// <summary>표 17. 자료 문자마다 표 14의 몇 번째 가중치 줄을 쓸지 자료 문자 수별로 적어 둔 것이다.</summary>
    private static readonly int[][] ExpandedWeightRows =
    [
        [0, 1, 2],
        [0, 5, 6, 3, 4],
        [0, 9, 10, 3, 4, 13, 14],
        [0, 17, 18, 3, 4, 13, 14, 7, 8],
        [0, 17, 18, 3, 4, 13, 14, 11, 12, 21, 22],
        [0, 17, 18, 3, 4, 13, 14, 15, 16, 21, 22, 19, 20],
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 17, 18, 15, 16],
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 17, 18, 19, 20, 21, 22],
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 13, 14, 11, 12, 17, 18, 15, 16, 21, 22, 19, 20]
    ];

    /// <summary>자료 문자를 좌·우 쌍으로 가르는 나눗수(§5.3.2).</summary>
    private const long OmniPairDivisor = 4537077;

    /// <summary>쌍 하나를 자료 문자 둘로 가르는 나눗수. 한 문자의 값 범위가 0~1596이다.</summary>
    private const int OmniCharacterDivisor = 1597;

    /// <summary>리미티드의 좌·우 문자 나눗수(§5.4.2).</summary>
    private const long LimitedPairDivisor = 2013571;

    /// <summary>리미티드가 담을 수 있는 최댓값. 13자리로 폈을 때 첫 자리가 0이나 1이어야 한다는 뜻이다.</summary>
    private const long LimitedMaxValue = 1999999999999L;

    private const int OmniSymbolWidth = 96;

    private const int LimitedSymbolWidth = 74;

    /// <summary>FNC1. 길이가 정해지지 않은 AI를 끊는 구분자로, 자료열에는 GS(0x1D)로 들어 있다.</summary>
    private const char Fnc1 = '\u001d';

    /// <summary>익스팬디드가 담을 수 있는 자료 비트 상한. 자료 문자 21개 × 12비트다(§7.2.2).</summary>
    private const int MaxExpandedDataBits = 252;

    /// <summary>괄호를 벗긴 자료열의 길이 상한(§4.2 d) 2)).</summary>
    private const int MaxExpandedSourceLength = 77;

    /// <summary>영숫자 모드가 담는 구두점. 부호값은 58부터 순서대로 붙는다(§7.2.5.5.2).</summary>
    private const string AlphanumericPunctuation = "*,-./";

    /// <summary>ISO/IEC 646 모드가 담는 구두점. 부호값은 232부터 붙고 빈칸도 낀다(§7.2.5.5.3).</summary>
    private const string IsoIecPunctuation = "!\"%&'()*+,-./:;<=>?_ ";

    /// <summary>범용 자료 압축 영역의 문자 갈래. FNC1은 숫자로 센다(§7.2.5.5).</summary>
    private enum GeneralFieldMode
    {
        Invalid,
        Numeric,
        Alphanumeric,
        IsoIec
    }

    /// <summary>RSS-14(옴니디렉셔널) 선형. 96모듈 폭.</summary>
    public static BitMatrix? EncodeRss14(string value, int barHeight = 33)
    {
        if (barHeight < 1) return null;
        var gtin = NormalizeGtin(value);
        if (gtin < 0) return null;

        try
        {
            var leftPair = (int)(gtin / OmniPairDivisor);
            var rightPair = (int)(gtin % OmniPairDivisor);
            int[] characters =
            [
                leftPair / OmniCharacterDivisor, leftPair % OmniCharacterDivisor,
                rightPair / OmniCharacterDivisor, rightPair % OmniCharacterDivisor
            ];

            var elements = new int[4][];
            for (var i = 0; i < 4; i++)
            {
                // 1·3번 문자는 바깥(16모듈), 2·4번은 안쪽(15모듈)이다. 쓰는 표도 좁은 요소 규칙도 서로 반대다.
                var outside = (i & 1) == 0;
                var group = OmniGroup(characters[i], outside);
                var offset = characters[i] - OmniGroupSum[group];
                var quotient = offset / OmniGroupDivisor[group];
                var remainder = offset % OmniGroupDivisor[group];
                elements[i] = Interleave(
                    outside ? quotient : remainder,
                    outside ? remainder : quotient,
                    OmniModules[group], OmniModules[group + 9],
                    4, OmniWidest[group], !outside);
            }

            var checksum = 0;
            for (var i = 0; i < 4; i++)
            {
                for (var j = 0; j < 8; j++)
                    checksum += OmniChecksumWeights[i][j] * elements[i][j];
            }
            checksum %= 79;
            // 표 4의 찾기 무늬는 아홉 가지뿐이라 9×9 조합에서 8·72번 자리는 비워 둔다.
            if (checksum >= 8) checksum++;
            if (checksum >= 72) checksum++;
            var leftFinder = checksum / 9;
            var rightFinder = checksum % 9;

            var widths = new int[46];
            widths[0] = 1;                                      // 좌측 가드 여백
            widths[1] = 1;                                      // 좌측 가드 막대
            widths[44] = 1;                                     // 우측 가드 여백
            widths[45] = 1;                                     // 우측 가드 막대
            for (var i = 0; i < 8; i++)
            {
                widths[i + 2] = elements[0][i];
                widths[i + 15] = elements[1][7 - i];            // 2번 문자는 뒤집어 놓는다
                widths[i + 23] = elements[3][i];
                widths[i + 36] = elements[2][7 - i];            // 3번 문자도 뒤집어 놓는다
            }
            for (var i = 0; i < 5; i++)
            {
                widths[i + 10] = OmniFinderPatterns[leftFinder][i] - '0';
                widths[i + 31] = OmniFinderPatterns[rightFinder][4 - i] - '0';
            }
            return Render(widths, OmniSymbolWidth, barHeight);
        }
        catch (Exception)
        {
            // 라벨 미리보기 중에 터지면 편집기가 통째로 멎는다. 못 그리면 조용히 비운다.
            return null;
        }
    }

    /// <summary>RSS Limited. 74모듈 폭.</summary>
    public static BitMatrix? EncodeRssLimited(string value, int barHeight = 10)
    {
        if (barHeight < 1) return null;
        var gtin = NormalizeGtin(value);
        if (gtin is < 0 or > LimitedMaxValue) return null;

        try
        {
            int[] pairs = [(int)(gtin / LimitedPairDivisor), (int)(gtin % LimitedPairDivisor)];
            var elements = new int[2][];
            for (var i = 0; i < 2; i++)
            {
                var group = LimitedGroup(pairs[i]);
                var offset = pairs[i] - LimitedGroupSum[group];
                elements[i] = Interleave(
                    offset / LimitedGroupDivisor[group],
                    offset % LimitedGroupDivisor[group],
                    LimitedModules[group], 26 - LimitedModules[group],
                    7, LimitedWidest[group], false);
            }

            var checksum = 0;
            for (var i = 0; i < 14; i++)
            {
                checksum += LimitedChecksumWeights[0][i] * elements[0][i];
                checksum += LimitedChecksumWeights[1][i] * elements[1][i];
            }
            checksum %= 89;

            var finder = LimitedFinderPatterns[checksum];
            var widths = new int[46];
            widths[0] = 1;                                      // 좌측 가드 여백
            widths[1] = 1;                                      // 좌측 가드 막대
            widths[44] = 1;                                     // 우측 가드 여백
            widths[45] = 1;                                     // 우측 가드 막대
            for (var i = 0; i < 14; i++)
            {
                widths[i + 2] = elements[0][i];
                widths[i + 16] = finder[i] - '0';
                widths[i + 30] = elements[1][i];
            }
            // §6.2가 요구하는 오른쪽 5X 빈 영역은 심볼 폭에 넣지 않는다. 배치는 부르는 쪽이 정한다.
            return Render(widths, LimitedSymbolWidth, barHeight);
        }
        catch (Exception)
        {
            return null;
        }
    }

    /// <summary>
    /// RSS Expanded. 자료량에 따라 늘어난다. 쌓기 변형이 아닌 한 줄 심볼이라 행이 아니라
    /// 심볼 문자가 4~22칸으로 늘고, 그만큼 폭이 102~543모듈 사이에서 변한다.
    /// </summary>
    public static BitMatrix? EncodeRssExpanded(string value, int barHeight = 34)
    {
        if (barHeight < 1) return null;
        var source = ReduceApplicationIdentifiers(value);
        if (source is null) return null;

        try
        {
            // 문자마다 자료 8비트에 걸쇠 5비트가 붙는 최악을 잡고, 방식 필드와 채움 비트 몫을 더 얹었다.
            var bits = new bool[13 * source.Length + 296];
            var bitCount = 0;
            // 합성 심볼 연결 플래그(§7.2.5.2). 2차원 짝이 없는 단독 선형이라 늘 0이다.
            bits[bitCount++] = false;
            if (!BuildExpandedBits(source, bits, ref bitCount, out var symbolCharacters)) return null;

            // 자료 문자마다 12비트씩 떼어 요소 너비로 편다. 값은 큰 자리가 먼저 온다(§7.2.5.1).
            var dataCharacters = symbolCharacters - 1;
            var characterWidths = new int[dataCharacters][];
            for (var i = 0; i < dataCharacters; i++)
            {
                var characterValue = 0;
                for (var j = 0; j < 12; j++)
                {
                    if (bits[i * 12 + j]) characterValue |= 0x800 >> j;
                }
                characterWidths[i] = ExpandedElements(characterValue);
            }

            // §7.2.6. 요소 너비에 표 14의 가중치를 곱한 합의 211 나머지에, 심볼 문자 수를 실어 보낸다.
            var checksum = 0;
            var weightRows = ExpandedWeightRows[(dataCharacters - 2) / 2];
            for (var i = 0; i < dataCharacters; i++)
            {
                var weights = ExpandedChecksumWeights[weightRows[i]];
                for (var j = 0; j < 8; j++)
                    checksum += characterWidths[i][j] * weights[j];
            }
            var checkWidths = ExpandedElements(211 * (symbolCharacters - 4) + checksum % 211);

            var codeblocks = (symbolCharacters + 1) / 2;
            var widths = new int[codeblocks * 5 + symbolCharacters * 8 + 4];
            var sequence = ExpandedFinderSequence[(symbolCharacters - 1) / 2 - 1];
            for (var i = 0; i < codeblocks; i++)
            {
                var pattern = ExpandedFinderPatterns[sequence[i] - 1];
                for (var j = 0; j < 5; j++)
                    widths[21 * i + j + 10] = pattern[j] - '0';
            }
            for (var i = 0; i < 8; i++)
                widths[i + 2] = checkWidths[i];                 // 검사 문자는 늘 첫 칸 왼쪽 절반이다
            for (var i = 0; i < dataCharacters; i++)
            {
                // 짝수 번째 자료 문자는 찾기 무늬 왼쪽에 뒤집어, 홀수 번째는 오른쪽에 바로 놓는다(§7.2.3).
                var offset = (i & 1) == 0 ? i / 2 * 21 + 15 : (i - 1) / 2 * 21 + 23;
                for (var j = 0; j < 8; j++)
                    widths[offset + j] = characterWidths[i][(i & 1) == 0 ? 7 - j : j];
            }
            // 좌측 가드는 여백·막대 한 모듈씩, 우측 가드는 남은 두 요소를 한 모듈씩 채운다.
            widths[0] = 1;
            widths[1] = 1;
            widths[^2] = 1;
            widths[^1] = 1;
            // 가드 4모듈 + 심볼 문자 17모듈 + 찾기 무늬 15모듈. 요소 너비의 총합과 같다.
            return Render(widths, 4 + symbolCharacters * 17 + codeblocks * 15, barHeight);
        }
        catch (Exception)
        {
            return null;
        }
    }

    /// <summary>
    /// 13자리 GTIN 본문으로 다듬는다. 14자리로 들어오면 마지막 자리를 검사부호로 확인한 뒤 떼어 내고,
    /// 짧게 들어온 값은 앞을 0으로 채운 셈으로 본다. 숫자가 아니거나 검사부호가 틀리면 -1.
    /// </summary>
    private static long NormalizeGtin(string value)
    {
        var digits = value?.Trim();
        if (string.IsNullOrEmpty(digits) || digits.Length > 14) return -1;
        foreach (var c in digits)
        {
            if (!char.IsAsciiDigit(c)) return -1;
        }
        if (digits.Length == 14)
        {
            if (CheckDigit(digits.AsSpan(0, 13)) != digits[13]) return -1;
            digits = digits[..13];
        }
        return long.Parse(digits, CultureInfo.InvariantCulture);
    }

    /// <summary>GS1 모듈로-10 검사부호. 13자리 본문의 첫 자리부터 3·1 가중치가 번갈아 붙는다.</summary>
    private static char CheckDigit(ReadOnlySpan<char> body)
    {
        var sum = 0;
        for (var i = 0; i < body.Length; i++)
            sum += (i % 2 == 0 ? 3 : 1) * (body[i] - '0');
        return (char)('0' + (10 - sum % 10) % 10);
    }

    /// <summary>
    /// 표 1·2에서 값이 속한 묶음. 바깥 문자는 0~4, 안쪽 문자는 5~8을 쓴다.
    /// </summary>
    private static int OmniGroup(int value, bool outside)
    {
        var end = outside ? 4 : 8;
        var i = outside ? 0 : 5;
        for (; i < end; i++)
        {
            if (value < OmniGroupSum[i + 1]) return i;
        }
        return i;
    }

    /// <summary>표 6에서 값이 속한 묶음. 큰 쪽부터 훑어 처음 걸리는 칸이 답이다.</summary>
    private static int LimitedGroup(int value)
    {
        for (var i = 6; i > 0; i--)
        {
            if (value >= LimitedGroupSum[i]) return i;
        }
        return 0;
    }

    /// <summary>
    /// 「(01)…(3103)…」처럼 괄호로 적힌 AI 표기를 심볼에 실을 자료열로 되돌린다. 괄호는 사람이 읽기 위한
    /// 표기일 뿐 자료가 아니라 떼어 내고, 길이가 정해지지 않은 AI 뒤에만 FNC1을 끼운다.
    /// 「예정 길이」 AI는 앞 두 자리가 00~04·11~20·31~36·41인 것들이다(GS1 일반 규격 그림 7-6).
    /// 괄호가 아예 없으면 이미 벗겨진 값으로 보고 그대로 쓴다. 형태가 어긋나면 null.
    /// </summary>
    private static string? ReduceApplicationIdentifiers(string value)
    {
        var source = value?.Trim();
        if (string.IsNullOrEmpty(source)) return null;
        if (!source.Contains('(')) return source;

        var reduced = new StringBuilder(source.Length);
        var predefinedLength = true;                             // 첫 AI 앞에는 구분자가 붙지 않는다
        var index = 0;
        while (index < source.Length)
        {
            if (source[index] != '(') return null;
            var close = source.IndexOf(')', index + 1);
            if (close < 0) return null;
            var ai = source.AsSpan(index + 1, close - index - 1);
            if (ai.Length is < 2 or > 4) return null;
            foreach (var c in ai)
            {
                if (!char.IsAsciiDigit(c)) return null;
            }
            var next = source.IndexOf('(', close + 1);
            if (next < 0) next = source.Length;
            if (next == close + 1) return null;                 // 자료가 없는 AI
            if (!predefinedLength) reduced.Append(Fnc1);
            reduced.Append(ai).Append(source, close + 1, next - close - 1);
            var prefix = (ai[0] - '0') * 10 + (ai[1] - '0');
            predefinedLength = prefix <= 4 || prefix is >= 11 and <= 20 or >= 31 and <= 36 or 41;
            index = next;
        }
        return reduced.Length > 0 ? reduced.ToString() : null;
    }

    /// <summary>
    /// §7.2.5의 부호화 절차. 방식 필드·압축 자료 영역·범용 자료 압축 영역을 차례로 쌓고 남는 자리를 메운다.
    /// 자료 문자 수가 정해져야 채움 비트 수가 나오는 앞뒤 얽힌 순서라 규격 순서를 그대로 지켰다.
    /// </summary>
    private static bool BuildExpandedBits(string source, bool[] bits, ref int bitCount, out int symbolCharacters)
    {
        symbolCharacters = 0;
        var length = source.Length;
        if (length > MaxExpandedSourceLength) return false;

        // 표 10. 가변 길이 심볼 비트 필드(XX)는 자리만 잡아 두고 문자 수가 나온 뒤에 덮어쓴다.
        var method = SelectExpandedMethod(source);
        int readPosition;
        switch (method)
        {
            case 1:
                AppendBits(bits, ref bitCount, 4, 3);           // 「1XX」
                readPosition = 16;
                break;
            case 2:
                AppendBits(bits, ref bitCount, 0, 4);           // 「00XX」
                readPosition = 0;
                break;
            case 3:
            case 4:
                AppendBits(bits, ref bitCount, 4 + (method - 3), 4);    // 「0100」·「0101」
                readPosition = 26;
                break;
            case 5:
                AppendBits(bits, ref bitCount, 0x30, 7);        // 「01100XX」
                readPosition = 20;
                break;
            case 6:
                AppendBits(bits, ref bitCount, 0x34, 7);        // 「01101XX」
                readPosition = 23;
                break;
            default:
                AppendBits(bits, ref bitCount, 56 + (method - 7), 7);   // 「0111000」~「0111111」
                readPosition = length;
                break;
        }

        // 압축 자료 영역은 숫자만 담을 수 있다. 방식을 고른 뒤라도 여기서 걸리면 실을 방법이 없다.
        for (var i = 0; i < readPosition; i++)
        {
            if (!char.IsAsciiDigit(source[i]) && source[i] != Fnc1) return false;
        }

        if (method == 1)
        {
            // §7.2.5.4.1. 「01」을 뗀 지시자 한 자리와 열두 자리를 싣는다. 검사부호는 복호할 때 되살린다.
            AppendBits(bits, ref bitCount, source[2] - '0', 4);
            for (var i = 3; i < 15; i += 3)
                AppendBits(bits, ref bitCount, ParseDigits(source, i, 3), 10);
        }
        else if (method != 2)
        {
            // 방식 3 이상은 지시자가 9로 고정이라 「019」를 떼고 열두 자리만 열 비트씩 싣는다.
            for (var i = 3; i < 15; i += 3)
                AppendBits(bits, ref bitCount, ParseDigits(source, i, 3), 10);
        }

        if (method is 3 or 4)
        {
            // §7.2.5.4.2·§7.2.5.4.3. (3203)은 0.001파운드 단위라 10000을 더해 (3202)와 구분한다.
            var weight = ParseDigits(source, 20, 6);
            AppendBits(bits, ref bitCount, method == 4 && source[19] == '3' ? weight + 10000 : weight, 15);
        }
        else if (method is 5 or 6)
        {
            // §7.2.5.4.5·§7.2.5.4.6. (392x)·(393x)의 x는 값의 소수점 자리라 두 비트면 든다.
            AppendBits(bits, ref bitCount, source[19] - '0', 2);
            if (method == 6)
                AppendBits(bits, ref bitCount, ParseDigits(source, 20, 3), 10);     // ISO 4217 통화 부호
        }
        else if (method >= 7)
        {
            // §7.2.5.4.4. 소수점 자리 x를 무게 앞에 붙여 여섯 자리로 만든다. 무게 첫 자리는 늘 0이라 버린다.
            AppendBits(bits, ref bitCount, (source[19] - '0') * 100000 + ParseDigits(source, 21, 5), 20);
            // 날짜 AI가 없는 경우를 뜻하는 값이 38400이다.
            AppendBits(bits, ref bitCount, length == 34 ? ExpandedDate(source, 28) : 38400, 16);
        }

        var mode = GeneralFieldMode.Numeric;
        var lastDigit = '\0';
        if (readPosition < length
            && !EncodeGeneralField(source[readPosition..], bits, ref bitCount, ref mode, ref lastDigit))
        {
            return false;
        }

        // §7.2.5.5.4. 자료 비트를 12비트씩 끊고 검사 문자 한 칸을 더한다. 최소 네 칸이다(§7.2.2).
        symbolCharacters = Math.Max(4, (bitCount + 11) / 12 + 1);
        var remainder = 12 * (symbolCharacters - 1) - bitCount;
        if (lastDigit != '\0')
        {
            // §7.2.5.5.1 c). 남는 자리가 4~6비트면 그 안에 접어 넣고, 아니면 FNC1과 짝지어 일곱 비트로 싣는다.
            if (remainder is >= 4 and <= 6)
                AppendBits(bits, ref bitCount, lastDigit - '0' + 1, 4);
            else
                AppendBits(bits, ref bitCount, (lastDigit - '0') * 11 + 10 + 8, 7);
            symbolCharacters = Math.Max(4, (bitCount + 11) / 12 + 1);
            remainder = 12 * (symbolCharacters - 1) - bitCount;
        }
        if (bitCount > MaxExpandedDataBits) return false;

        // 숫자 모드로 끝났으면 영숫자 걸쇠 「0000」을 앞세운 뒤 「00100」을 되풀이해 남는 자리를 메운다.
        if (mode == GeneralFieldMode.Numeric)
        {
            AppendBits(bits, ref bitCount, 0, 4);
            remainder -= 4;
        }
        for (; remainder > 0; remainder -= 5)
            AppendBits(bits, ref bitCount, 4, 5);
        // 채움 단위가 5비트라 마지막 덩이는 문자 경계를 넘길 수 있다. 넘긴 몫은 심볼에 들어가지 않는다.
        bitCount = 12 * (symbolCharacters - 1);

        if (method is 1 or 2 or 5 or 6)
        {
            // §7.2.5.3. 방식 필드 뒤 두 자리로 문자 수의 홀짝과 14칸 초과를 알린다.
            var position = method switch { 1 => 2, 2 => 3, _ => 6 };
            bits[position] = (symbolCharacters & 1) != 0;
            bits[position + 1] = symbolCharacters > 14;
        }
        return true;
    }

    /// <summary>
    /// 표 10에서 쓸 부호화 방식. (01)로 시작하지 않으면 2, 알아볼 AI 짝이 없으면 1이다.
    /// 지시자가 9이고 뒤에 무게·값·날짜 AI가 규격 길이대로 딱 이어 붙을 때만 압축이 센 3~14로 내려간다.
    /// </summary>
    private static int SelectExpandedMethod(string source)
    {
        var length = source.Length;
        if (length < 16 || source[0] != '0' || source[1] != '1') return 2;
        if (length < 20 || source[2] != '9' || source[16] != '3') return 1;

        if (length >= 26 && source[17] == '1' && source[18] == '0')
        {
            // (01)+(310x) 킬로그램. 방식 7~14의 무게 상한이 99999다(§7.2.5.4.4).
            var weight = ParseDigits(source, 20, 6);
            if (weight is < 0 or > 99999) return 1;
            if (length == 26) return source[19] == '3' && weight <= 32767 ? 3 : 7;
            if (length == 34 && source[26] == '1' && source[27] is '1' or '3' or '5' or '7'
                && ExpandedDate(source, 28) >= 0)
            {
                return 6 + (source[27] - '0');                  // (11)·(13)·(15)·(17)이 각각 7·9·11·13
            }
            return 1;
        }
        if (length >= 26 && source[17] == '2' && source[18] == '0')
        {
            // (01)+(320x) 파운드. (3202)는 0.01, (3203)은 0.001 단위라 15비트에 드는 상한이 다르다.
            var weight = ParseDigits(source, 20, 6);
            if (weight is < 0 or > 99999) return 1;
            if (length == 26)
                return (source[19] == '2' && weight <= 9999) || (source[19] == '3' && weight <= 22767) ? 4 : 8;
            if (length == 34 && source[26] == '1' && source[27] is '1' or '3' or '5' or '7'
                && ExpandedDate(source, 28) >= 0)
            {
                return 7 + (source[27] - '0');                  // (11)·(13)·(15)·(17)이 각각 8·10·12·14
            }
            return 1;
        }
        if (source[17] == '9' && source[19] is >= '0' and <= '3')
        {
            if (source[18] == '2') return 5;                                                    // (01)+(392x)
            if (length >= 23 && source[18] == '3' && ParseDigits(source, 20, 3) >= 0) return 6; // (01)+(393x)
        }
        return 1;
    }

    /// <summary>문자값 하나를 표 8로 갈라 요소 너비 여덟 개로 편다. 익스팬디드는 늘 좁은 요소를 요구한다.</summary>
    private static int[] ExpandedElements(int characterValue)
    {
        var group = ExpandedGroup(characterValue);
        var offset = characterValue - ExpandedGroupSum[group];
        return Interleave(
            offset / ExpandedGroupDivisor[group],
            offset % ExpandedGroupDivisor[group],
            ExpandedModules[group], 17 - ExpandedModules[group],
            4, ExpandedWidest[group], true);
    }

    /// <summary>표 8에서 값이 속한 묶음.</summary>
    private static int ExpandedGroup(int value)
    {
        for (var i = 0; i < 4; i++)
        {
            if (value < ExpandedGroupSum[i + 1]) return i;
        }
        return 4;
    }

    /// <summary>
    /// YYMMDD를 §7.2.5.4.4의 날짜 값으로 바꾼다. 달은 0이 될 수 없지만 날은 0이 「그 달 말일」을
    /// 뜻하므로 허용한다(GS1 일반 규격 3.4.2~3.4.7). 날짜로 읽을 수 없으면 -1.
    /// </summary>
    private static int ExpandedDate(string source, int position)
    {
        var year = ParseDigits(source, position, 2);
        var month = ParseDigits(source, position + 2, 2);
        var day = ParseDigits(source, position + 4, 2);
        if (year < 0 || month is <= 0 or > 12 || day is < 0 or > 31) return -1;
        return year * 384 + (month - 1) * 32 + day;
    }

    /// <summary>자리 수만큼 잘라 십진수로 읽는다. 길이를 넘거나 숫자가 아니면 -1.</summary>
    private static int ParseDigits(string source, int start, int count)
    {
        if (start < 0 || start + count > source.Length) return -1;
        var value = 0;
        for (var i = 0; i < count; i++)
        {
            if (!char.IsAsciiDigit(source[start + i])) return -1;
            value = value * 10 + (source[start + i] - '0');
        }
        return value;
    }

    /// <summary>값을 지정한 비트 수만큼 큰 자리부터 쌓는다.</summary>
    private static void AppendBits(bool[] bits, ref int bitCount, int value, int count)
    {
        for (var i = count - 1; i >= 0; i--)
            bits[bitCount++] = ((value >> i) & 1) != 0;
    }

    /// <summary>
    /// §7.2.5.5.1~§7.2.5.5.3의 범용 자료 압축 영역. 숫자·영숫자·ISO/IEC 646 세 모드를 걸쇠로 오가며
    /// 숫자는 두 자리 7비트, 영숫자는 5·6비트, 646은 5·7·8비트로 싣는다. CSET 82 밖 문자가 오면 false.
    /// 끝에 숫자 한 자리만 남으면 채움 비트 수를 봐야 정해지므로 <paramref name="lastDigit"/>로 넘긴다.
    /// </summary>
    private static bool EncodeGeneralField(string field, bool[] bits, ref int bitCount,
        ref GeneralFieldMode mode, ref char lastDigit)
    {
        for (var i = 0; i < field.Length;)
        {
            var type = GeneralFieldTypeAt(field, i);
            if (type == GeneralFieldMode.Invalid) return false;
            switch (mode)
            {
                case GeneralFieldMode.Numeric when i < field.Length - 1:
                    if (type != GeneralFieldMode.Numeric
                        || GeneralFieldTypeAt(field, i + 1) != GeneralFieldMode.Numeric)
                    {
                        AppendBits(bits, ref bitCount, 0, 4);           // 영숫자 걸쇠 「0000」
                        mode = GeneralFieldMode.Alphanumeric;
                    }
                    else
                    {
                        var first = field[i] == Fnc1 ? 10 : field[i] - '0';
                        var second = field[i + 1] == Fnc1 ? 10 : field[i + 1] - '0';
                        AppendBits(bits, ref bitCount, 11 * first + second + 8, 7);
                        i += 2;
                    }
                    break;

                case GeneralFieldMode.Numeric:
                    if (type != GeneralFieldMode.Numeric)
                    {
                        AppendBits(bits, ref bitCount, 0, 4);
                        mode = GeneralFieldMode.Alphanumeric;
                    }
                    else
                    {
                        lastDigit = field[i];
                        i++;
                    }
                    break;

                case GeneralFieldMode.Alphanumeric:
                    if (field[i] == Fnc1)
                    {
                        AppendBits(bits, ref bitCount, 15, 5);          // 숫자 걸쇠 「01111」
                        mode = GeneralFieldMode.Numeric;
                        i++;
                    }
                    else if (type == GeneralFieldMode.IsoIec)
                    {
                        AppendBits(bits, ref bitCount, 4, 5);           // ISO/IEC 646 걸쇠 「00100」
                        mode = GeneralFieldMode.IsoIec;
                    }
                    else if (GeneralFieldNext(field, i, 6, GeneralFieldMode.Numeric, GeneralFieldMode.Invalid)
                             || GeneralFieldNextTerminate(field, i, 4, 5, GeneralFieldMode.Numeric))
                    {
                        // 숫자가 여섯 자리 이어지거나 넷 이상으로 끝나면 숫자 모드가 짧다(§7.2.5.5.2 c·d).
                        AppendBits(bits, ref bitCount, 0, 3);           // 숫자 걸쇠 「000」
                        mode = GeneralFieldMode.Numeric;
                    }
                    else if (char.IsAsciiDigit(field[i]))
                    {
                        AppendBits(bits, ref bitCount, field[i] - 43, 5);
                        i++;
                    }
                    else if (char.IsAsciiLetterUpper(field[i]))
                    {
                        AppendBits(bits, ref bitCount, field[i] - 33, 6);
                        i++;
                    }
                    else
                    {
                        AppendBits(bits, ref bitCount, AlphanumericPunctuation.IndexOf(field[i]) + 58, 6);
                        i++;
                    }
                    break;

                case GeneralFieldMode.IsoIec:
                    if (field[i] == Fnc1)
                    {
                        AppendBits(bits, ref bitCount, 15, 5);
                        mode = GeneralFieldMode.Numeric;
                        i++;
                        break;
                    }
                    // 646 전용 문자가 열 자리 안에 없을 때만 더 짧은 모드로 갈아탄다(§7.2.5.5.3 b·c).
                    var isoIecFree = GeneralFieldNextNone(field, i, 10, GeneralFieldMode.IsoIec);
                    if (isoIecFree
                        && GeneralFieldNext(field, i, 4, GeneralFieldMode.Numeric, GeneralFieldMode.Invalid))
                    {
                        AppendBits(bits, ref bitCount, 0, 3);
                        mode = GeneralFieldMode.Numeric;
                    }
                    else if (isoIecFree
                             && GeneralFieldNext(field, i, 5, GeneralFieldMode.Alphanumeric,
                                 GeneralFieldMode.Numeric))
                    {
                        AppendBits(bits, ref bitCount, 4, 5);
                        mode = GeneralFieldMode.Alphanumeric;
                    }
                    else if (char.IsAsciiDigit(field[i]))
                    {
                        AppendBits(bits, ref bitCount, field[i] - 43, 5);
                        i++;
                    }
                    else if (char.IsAsciiLetterUpper(field[i]))
                    {
                        AppendBits(bits, ref bitCount, field[i] - 1, 7);
                        i++;
                    }
                    else if (char.IsAsciiLetterLower(field[i]))
                    {
                        AppendBits(bits, ref bitCount, field[i] - 7, 7);
                        i++;
                    }
                    else
                    {
                        AppendBits(bits, ref bitCount, IsoIecPunctuation.IndexOf(field[i]) + 232, 8);
                        i++;
                    }
                    break;

                default:
                    return false;
            }
        }
        return true;
    }

    /// <summary>자리 하나의 문자 갈래. 숫자·영숫자에 이미 든 문자는 위쪽 갈래로 먼저 걸린다.</summary>
    private static GeneralFieldMode GeneralFieldTypeAt(string field, int index)
    {
        var c = field[index];
        if (c == Fnc1 || char.IsAsciiDigit(c)) return GeneralFieldMode.Numeric;
        if (char.IsAsciiLetterUpper(c) || AlphanumericPunctuation.Contains(c)) return GeneralFieldMode.Alphanumeric;
        if (char.IsAsciiLetterLower(c) || IsoIecPunctuation.Contains(c)) return GeneralFieldMode.IsoIec;
        return GeneralFieldMode.Invalid;
    }

    /// <summary>
    /// 이 자리부터 <paramref name="count"/>자리가 모두 <paramref name="type"/>이거나, 주어졌다면
    /// <paramref name="alternate"/>인지. 자료가 모자라면 거짓이다.
    /// </summary>
    private static bool GeneralFieldNext(string field, int index, int count, GeneralFieldMode type,
        GeneralFieldMode alternate)
    {
        if (index + count > field.Length) return false;
        for (; index < field.Length && count > 0; index++, count--)
        {
            var actual = GeneralFieldTypeAt(field, index);
            if (actual != type && (alternate == GeneralFieldMode.Invalid || actual != alternate)) return false;
        }
        return count == 0;
    }

    /// <summary>
    /// 이 자리부터 끝까지가 모두 주어진 갈래이고, 남은 자리 수가 <paramref name="count"/>~
    /// <paramref name="maxCount"/>인지. 자료가 그 안에서 끝나야 걸쇠를 바꿔 이득이 난다.
    /// </summary>
    private static bool GeneralFieldNextTerminate(string field, int index, int count, int maxCount,
        GeneralFieldMode type)
    {
        if (index + maxCount < field.Length) return false;
        for (; index < field.Length; index++, count--)
        {
            if (GeneralFieldTypeAt(field, index) != type) return false;
        }
        return count <= 0;
    }

    /// <summary>이 자리부터 <paramref name="count"/>자리(또는 끝까지)에 주어진 갈래가 없는지.</summary>
    private static bool GeneralFieldNextNone(string field, int index, int count, GeneralFieldMode type)
    {
        for (; index < field.Length && count > 0; index++, count--)
        {
            if (GeneralFieldTypeAt(field, index) == type) return false;
        }
        return count == 0 || index == field.Length;
    }

    /// <summary>
    /// 홀수·짝수 부분집합의 요소 너비를 따로 구해 번갈아 꿴다. 짝수 쪽은 최대 너비가 9에서 뒤집히고,
    /// 「1모듈짜리 요소가 적어도 하나는 있어야 한다」는 제약도 홀·짝이 서로 반대다(부속서 B).
    /// </summary>
    private static int[] Interleave(int oddValue, int evenValue, int oddModules, int evenModules,
        int elements, int maxWidth, bool noNarrow)
    {
        var odd = ElementWidths(oddValue, oddModules, elements, maxWidth, noNarrow);
        var even = ElementWidths(evenValue, evenModules, elements, 9 - maxWidth, !noNarrow);
        var merged = new int[elements * 2];
        for (var i = 0; i < elements; i++)
        {
            merged[i * 2] = odd[i];
            merged[i * 2 + 1] = even[i];
        }
        return merged;
    }

    /// <summary>
    /// 부속서 B의 getRSSwidths. 값이 작은 순서대로 요소 너비 조합을 늘어놓았다고 보고, 앞에서부터
    /// 「첫 요소를 n모듈로 잡았을 때 남는 조합 수」만큼 값을 깎아 내려가며 자리를 찾는다.
    /// </summary>
    private static int[] ElementWidths(int value, int modules, int elements, int maxWidth, bool noNarrow)
    {
        var widths = new int[elements];
        var narrowMask = 0;
        var remaining = modules;
        var index = 0;
        for (; index < elements - 1; index++)
        {
            var elementWidth = 1;
            narrowMask |= 1 << index;
            int subValue;
            while (true)
            {
                subValue = Combinations[remaining - elementWidth - 1][elements - index - 2];
                // 앞 요소가 모두 2모듈 이상이면, 남은 자리에서도 1모듈이 안 나오는 조합은 셈에서 뺀다.
                if (noNarrow && narrowMask == 0
                    && remaining - elementWidth - (elements - index - 1) >= elements - index - 1)
                {
                    subValue -= Combinations[remaining - elementWidth - (elements - index)][elements - index - 2];
                }
                if (elements - index - 1 > 1)
                {
                    var lessValue = 0;
                    for (var wide = remaining - elementWidth - (elements - index - 2); wide > maxWidth; wide--)
                        lessValue += Combinations[remaining - elementWidth - wide - 1][elements - index - 3];
                    subValue -= lessValue * (elements - 1 - index);
                }
                else if (remaining - elementWidth > maxWidth)
                {
                    subValue--;
                }
                value -= subValue;
                if (value < 0) break;
                elementWidth++;
                narrowMask &= ~(1 << index);
            }
            value += subValue;
            remaining -= elementWidth;
            widths[index] = elementWidth;
        }
        widths[index] = remaining;                              // 마지막 요소는 남은 모듈을 전부 가져간다
        return widths;
    }

    /// <summary>
    /// 요소 너비를 모듈로 편다. 0번 요소는 왼쪽 가드 여백이라 흰색에서 시작해 흑백이 번갈아 간다.
    /// </summary>
    private static BitMatrix Render(int[] widths, int symbolWidth, int barHeight)
    {
        var matrix = new BitMatrix(symbolWidth, barHeight);
        var x = 0;
        var dark = false;
        foreach (var run in widths)
        {
            if (dark)
            {
                for (var i = 0; i < run; i++)
                {
                    for (var y = 0; y < barHeight; y++)
                        matrix[x + i, y] = true;
                }
            }
            x += run;
            dark = !dark;
        }
        return matrix;
    }
}
