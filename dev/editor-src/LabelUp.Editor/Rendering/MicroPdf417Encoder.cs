using System.Globalization;
using System.Numerics;
using System.Text;
using ZXing.Common;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// Micro PDF417 (ISO/IEC 24728). ZXing은 표준 PDF417만 그려서 아이라벨 Micro와 모양이 달라진다.
/// 표·ECC는 Okapi/Zint와 같고, 코드워드 패턴은 ISO Annex A(ZXing 표)를 쓴다.
/// </summary>
internal static class MicroPdf417Encoder
{
    private static readonly int[] AutoSize =
    [
        4, 6, 7, 8, 8, 10, 10, 12, 12, 13, 14, 16, 18, 18, 19, 20, 24, 24, 24, 29, 30, 33, 34, 37, 39, 46, 54, 58, 70, 72, 82, 90, 108, 126,
        1, 14, 2, 7, 24, 3, 15, 25, 4, 8, 16, 5, 17, 26, 9, 6, 10, 18, 27, 11, 28, 12, 19, 13, 29, 20, 30, 21, 22, 31, 23, 32, 33, 34
    ];

    private static readonly int[] Variants =
    [
        1, 1, 1, 1, 1, 1, 2, 2, 2, 2, 2, 2, 2, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4,
        11, 14, 17, 20, 24, 28, 8, 11, 14, 17, 20, 23, 26, 6, 8, 10, 12, 15, 20, 26, 32, 38, 44, 4, 6, 8, 10, 12, 15, 20, 26, 32, 38, 44,
        7, 7, 7, 8, 8, 8, 8, 9, 9, 10, 11, 13, 15, 12, 14, 16, 18, 21, 26, 32, 38, 44, 50, 8, 12, 14, 16, 18, 21, 26, 32, 38, 44, 50,
        0, 0, 0, 7, 7, 7, 7, 15, 15, 24, 34, 57, 84, 45, 70, 99, 115, 133, 154, 180, 212, 250, 294, 7, 45, 70, 99, 115, 133, 154, 180, 212, 250, 294
    ];

    private static readonly int[] RapTable =
    [
        1, 8, 36, 19, 9, 25, 1, 1, 8, 36, 19, 9, 27, 1, 7, 15, 25, 37, 1, 1, 21, 15, 1, 47, 1, 7, 15, 25, 37, 1, 1, 21, 15, 1,
        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 7, 15, 25, 37, 17, 9, 29, 31, 25, 19, 1, 7, 15, 25, 37, 17, 9, 29, 31, 25,
        9, 8, 36, 19, 17, 33, 1, 9, 8, 36, 19, 17, 35, 1, 7, 15, 25, 37, 33, 17, 37, 47, 49, 43, 1, 7, 15, 25, 37, 33, 17, 37, 47, 49,
        0, 3, 6, 0, 6, 0, 0, 0, 3, 6, 0, 6, 6, 0, 0, 6, 0, 0, 0, 0, 6, 6, 0, 3, 0, 0, 6, 0, 0, 0, 0, 6, 6, 0
    ];

    private static readonly string[] RapLr =
    [
        "", "221311", "311311", "312211", "222211", "213211", "214111", "223111",
        "313111", "322111", "412111", "421111", "331111", "241111", "232111", "231211", "321211",
        "411211", "411121", "411112", "321112", "312112", "311212", "311221", "311131", "311122",
        "311113", "221113", "221122", "221131", "221221", "222121", "312121", "321121", "231121",
        "231112", "222112", "213112", "212212", "212221", "212131", "212122", "212113", "211213",
        "211123", "211132", "211141", "211231", "211222", "211312", "211321", "211411", "212311"
    ];

    private static readonly string[] RapC =
    [
        "", "112231", "121231", "122131", "131131", "131221", "132121", "141121",
        "141211", "142111", "133111", "132211", "131311", "122311", "123211", "124111", "115111",
        "114211", "114121", "123121", "123112", "122212", "122221", "121321", "121411", "112411",
        "113311", "113221", "113212", "113122", "122122", "131122", "131113", "122113", "113113",
        "112213", "112222", "112312", "112321", "111421", "111331", "111322", "111232", "111223",
        "111133", "111124", "111214", "112114", "121114", "121123", "121132", "112132", "112141"
    ];

    private static readonly int[] Coeffs =
    [
        76, 925, 537, 597, 784, 691, 437,
        237, 308, 436, 284, 646, 653, 428, 379,
        567, 527, 622, 257, 289, 362, 501, 441, 205,
        377, 457, 64, 244, 826, 841, 818, 691, 266, 612,
        462, 45, 565, 708, 825, 213, 15, 68, 327, 602, 904,
        597, 864, 757, 201, 646, 684, 347, 127, 388, 7, 69, 851,
        764, 713, 342, 384, 606, 583, 322, 592, 678, 204, 184, 394, 692,
        669, 677, 154, 187, 241, 286, 274, 354, 478, 915, 691, 833, 105, 215,
        460, 829, 476, 109, 904, 664, 230, 5, 80, 74, 550, 575, 147, 868, 642,
        274, 562, 232, 755, 599, 524, 801, 132, 295, 116, 442, 428, 295, 42, 176, 65,
        279, 577, 315, 624, 37, 855, 275, 739, 120, 297, 312, 202, 560, 321, 233, 756, 760, 573,
        108, 519, 781, 534, 129, 425, 681, 553, 422, 716, 763, 693, 624, 610, 310, 691, 347, 165, 193, 259, 568,
        443, 284, 887, 544, 788, 93, 477, 760, 331, 608, 269, 121, 159, 830, 446, 893, 699, 245, 441, 454, 325, 858, 131, 847, 764, 169,
        361, 575, 922, 525, 176, 586, 640, 321, 536, 742, 677, 742, 687, 284, 193, 517, 273, 494, 263, 147, 593, 800, 571, 320, 803, 133, 231, 390, 685, 330, 63, 410,
        234, 228, 438, 848, 133, 703, 529, 721, 788, 322, 280, 159, 738, 586, 388, 684, 445, 680, 245, 595, 614, 233, 812, 32, 284, 658, 745, 229, 95, 689, 920, 771, 554, 289, 231, 125, 117, 518,
        476, 36, 659, 848, 678, 64, 764, 840, 157, 915, 470, 876, 109, 25, 632, 405, 417, 436, 714, 60, 376, 97, 413, 706, 446, 21, 3, 773, 569, 267, 272, 213, 31, 560, 231, 758, 103, 271, 572, 436, 339, 730, 82, 285,
        923, 797, 576, 875, 156, 706, 63, 81, 257, 874, 411, 416, 778, 50, 205, 303, 188, 535, 909, 155, 637, 230, 534, 96, 575, 102, 264, 233, 919, 593, 865, 26, 579, 623, 766, 146, 10, 739, 246, 127, 71, 244, 211, 477, 920, 876, 427, 820, 718, 435
    ];

    /// <summary>
    /// ISO/IEC 24728 AutoSize. 아이라벨도 상자 모양과 무관하게 이 규칙을 쓴다.
    /// `12345` 실측: 1열×11행 = 38×11 모듈.
    /// </summary>
    public static BitMatrix? Encode(string value)
    {
        if (string.IsNullOrEmpty(value)) return null;
        var bytes = Encoding.Latin1.GetBytes(value);
        var compact = Compact(bytes);
        if (compact.Count == 0 || compact.Count > 126) return null;

        var variant = PickVariant(compact.Count);
        return variant < 0 ? null : Build(compact, variant);
    }

    private static BitMatrix? Build(IReadOnlyList<int> compact, int variant)
    {
        var columns = Variants[variant];
        var rows = Variants[variant + 34];
        var eccCount = Variants[variant + 68];
        var eccOffset = Variants[variant + 102];
        var dataSlots = columns * rows - eccCount;
        var data = new List<int>(compact);
        while (data.Count < dataSlots)
            data.Add(900);

        var ecc = ReedSolomon(data, eccCount, eccOffset);
        data.AddRange(ecc);

        var leftRap = RapTable[variant];
        var centreRap = RapTable[variant + 34];
        var rightRap = RapTable[variant + 68];
        var cluster = RapTable[variant + 102] / 3;
        var rowsBits = new List<bool[]>(rows);

        // 한 줄은 RAP(10) + 코드워드(17씩) + RAP(10) + 정지 막대(1)다. 사이에 여분 모듈은 없다.
        // 열 수별 폭: 1열 38, 2열 55, 3열 82, 4열 99 모듈. 3·4열은 가운데 RAP이 둘째 코드워드 뒤에 온다.
        for (var r = 0; r < rows; r++)
        {
            var bits = new List<bool>(99);
            AppendRuns(bits, RapLr[leftRap], startBlack: true);
            AppendCodeword(bits, cluster, data[r * columns]);
            if (columns >= 2)
                AppendCodeword(bits, cluster, data[r * columns + 1]);
            if (columns >= 3)
            {
                AppendRuns(bits, RapC[centreRap], startBlack: true);
                AppendCodeword(bits, cluster, data[r * columns + 2]);
            }
            if (columns >= 4)
                AppendCodeword(bits, cluster, data[r * columns + 3]);
            AppendRuns(bits, RapLr[rightRap], startBlack: true);
            bits.Add(true);
            rowsBits.Add(bits.ToArray());

            leftRap = leftRap == 52 ? 1 : leftRap + 1;
            centreRap = centreRap == 0 ? 0 : centreRap == 52 ? 1 : centreRap + 1;
            rightRap = rightRap == 52 ? 1 : rightRap + 1;
            cluster = cluster == 2 ? 0 : cluster + 1;
        }

        var width = rowsBits[0].Length;
        var matrix = new BitMatrix(width, rows);
        for (var y = 0; y < rows; y++)
        {
            var row = rowsBits[y];
            for (var x = 0; x < width && x < row.Length; x++)
            {
                if (row[x])
                    matrix[x, y] = true;
            }
        }
        return matrix;
    }

    private static int PickVariant(int codeWordCount)
    {
        for (var i = 0; i < 34; i++)
        {
            if (codeWordCount <= AutoSize[i])
                return AutoSize[i + 34] - 1;
        }
        return -1;
    }

    private static List<int> ReedSolomon(List<int> data, int k, int offset)
    {
        var corr = new int[k];
        foreach (var cw in data)
        {
            var total = (cw + corr[k - 1]) % 929;
            for (var j = k - 1; j >= 0; j--)
            {
                var term = 929 - total * Coeffs[offset + j] % 929;
                corr[j] = j == 0 ? term % 929 : (corr[j - 1] + term) % 929;
            }
        }
        var ecc = new List<int>(k);
        for (var i = k - 1; i >= 0; i--)
            ecc.Add(corr[i] == 0 ? 0 : 929 - corr[i]);
        return ecc;
    }

    /// <summary>
    /// 숫자 압축(902 잠금)은 자릿수 13개 이상 이어질 때만 이득이라 규격 인코딩 절차가 그때만 쓴다.
    /// 그보다 짧으면 텍스트 압축 Mixed 부모드로 넣는다. 아이라벨도 같다 —
    /// `12345` 실측 코드워드는 841·63·125로, 28(Mixed 잠금)+1,2,3,4,5를 두 개씩 묶은 값이다.
    /// </summary>
    private const int NumericRunMin = 13;

    /// <summary>텍스트 압축 부모드. 값 26은 어디서나 빈칸이다.</summary>
    private const string Alpha = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    private const string Lower = "abcdefghijklmnopqrstuvwxyz";
    private const string Mixed = "0123456789&\r\t,:#-.$/+%*=^";
    private const string Punct = ";<>@[\\]_`~!\r\t,:\n-.$/\"|*()?{}'";

    private const int SubAlpha = 0;
    private const int SubLower = 1;
    private const int SubMixed = 2;

    private static List<int> Compact(byte[] data)
    {
        var words = new List<int>();
        var chain = new List<int>();
        var sub = SubAlpha;
        var i = 0;
        while (i < data.Length)
        {
            var run = 0;
            while (i + run < data.Length && IsDigit(data[i + run]))
                run++;
            if (run >= NumericRunMin)
            {
                FlushText(words, chain);
                sub = SubAlpha;
                var take = Math.Min(run, 44);
                AppendNumeric(words, data, i, take);
                i += take;
                continue;
            }

            var bytes = 0;
            while (i + bytes < data.Length && !Encodable(data[i + bytes]))
                bytes++;
            if (bytes > 0)
            {
                FlushText(words, chain);
                sub = SubAlpha;
                AppendBytes(words, data, i, bytes);
                i += bytes;
                continue;
            }

            AppendTextChar(chain, ref sub, data[i]);
            i++;
        }
        FlushText(words, chain);
        return words;
    }

    private static bool IsDigit(byte b) => b is >= (byte)'0' and <= (byte)'9';

    private static bool Encodable(byte b)
        => ValueIn(SubAlpha, b) >= 0 || ValueIn(SubLower, b) >= 0
           || ValueIn(SubMixed, b) >= 0 || ValueIn(3, b) >= 0;

    /// <summary>부모드 안에서의 값. 없으면 -1.</summary>
    private static int ValueIn(int sub, byte b)
    {
        var ch = (char)b;
        if (ch == ' ')
            return sub == 3 ? -1 : 26;
        var table = sub switch
        {
            SubAlpha => Alpha,
            SubLower => Lower,
            SubMixed => Mixed,
            _ => Punct
        };
        return table.IndexOf(ch, StringComparison.Ordinal);
    }

    private static void AppendTextChar(List<int> chain, ref int sub, byte b)
    {
        var here = ValueIn(sub, b);
        if (here >= 0)
        {
            chain.Add(here);
            return;
        }

        foreach (var target in (int[])[SubAlpha, SubLower, SubMixed])
        {
            var v = ValueIn(target, b);
            if (v < 0) continue;
            // Lower에서 Alpha로 가는 잠금은 없다. Mixed를 거쳐야 한다.
            if (target == SubAlpha)
                chain.AddRange(sub == SubLower ? (int[])[28, 28] : [28]);
            else
                chain.Add(target == SubLower ? 27 : 28);
            sub = target;
            chain.Add(v);
            return;
        }

        // 구두점은 한 글자만 넘어가는 시프트(29)로 넣는다. 부모드는 그대로 남는다.
        chain.Add(29);
        chain.Add(ValueIn(3, b));
    }

    private static void FlushText(List<int> words, List<int> chain)
    {
        if (chain.Count == 0) return;
        if (words.Count > 0)
            words.Add(900);                         // 텍스트 압축으로 되돌아온다
        if ((chain.Count & 1) == 1)
            chain.Add(29);                          // 남는 반 칸은 구두점 시프트로 채운다
        for (var i = 0; i < chain.Count; i += 2)
            words.Add(30 * chain[i] + chain[i + 1]);
        chain.Clear();
    }

    /// <summary>바이트 압축. 6개씩 묶어 5코드워드로, 남는 건 한 바이트당 한 코드워드.</summary>
    private static void AppendBytes(List<int> words, byte[] data, int start, int length)
    {
        words.Add(length % 6 == 0 ? 924 : 901);
        var i = 0;
        while (length - i >= 6)
        {
            var total = BigInteger.Zero;
            for (var k = 0; k < 6; k++)
                total = total * 256 + data[start + i + k];
            var block = new int[5];
            for (var k = 4; k >= 0; k--)
            {
                block[k] = (int)(total % 900);
                total /= 900;
            }
            words.AddRange(block);
            i += 6;
        }
        for (; i < length; i++)
            words.Add(data[start + i]);
    }

    private static void AppendNumeric(List<int> words, byte[] data, int start, int length)
    {
        words.Add(902);
        var sb = new StringBuilder(length + 1);
        sb.Append('1');
        for (var i = 0; i < length; i++)
            sb.Append((char)data[start + i]);
        var tVal = BigInteger.Parse(sb.ToString(), CultureInfo.InvariantCulture);
        var stack = new List<int>();
        do
        {
            stack.Add((int)(tVal % 900));
            tVal /= 900;
        } while (tVal > 0);
        for (var i = stack.Count - 1; i >= 0; i--)
            words.Add(stack[i]);
    }

    private static void AppendCodeword(List<bool> bits, int cluster, int codeword)
    {
        var table = Pdf417CodewordTable.Clusters[cluster];
        if ((uint)codeword >= (uint)table.Length) return;
        AppendPattern(bits, table[codeword], 17);
    }

    private static void AppendPattern(List<bool> bits, int pattern, int len)
    {
        var map = 1 << (len - 1);
        for (var i = 0; i < len; i++)
        {
            bits.Add((pattern & map) != 0);
            map >>= 1;
        }
    }

    private static void AppendRuns(List<bool> bits, string runs, bool startBlack)
    {
        if (string.IsNullOrEmpty(runs)) return;
        var black = startBlack;
        foreach (var ch in runs)
        {
            var n = ch - '0';
            for (var i = 0; i < n; i++)
                bits.Add(black);
            black = !black;
        }
    }
}
