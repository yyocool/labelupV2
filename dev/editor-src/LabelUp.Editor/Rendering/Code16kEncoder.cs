using System.Text;
using ZXing.Common;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// Code 16K (BS EN 12323 / ISO/IEC 12323). ZXing에는 아예 없는 규격이라 Zint code16k.c 절차를 그대로 옮겼다.
/// 자료 압축은 Code 128과 같고, 그 문자들을 한 줄에 다섯씩 쌓아 올린다.
/// 「Code 16K」 실측: 3행 = 81×28 모듈.
/// </summary>
internal static class Code16kEncoder
{
    /// <summary>
    /// Code 128 문자 107개의 요소 너비(막대·여백 여섯 개, 합 11). 106번은 Code 128에서는 정지 무늬지만
    /// EN 12323에서는 3중 시프트라 6요소짜리로 쓴다.
    /// </summary>
    private static readonly string[] SymbolPatterns =
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

    /// <summary>EN 12323 표 3·4. 행 시작·정지 무늬의 요소 너비(네 개, 합 7).</summary>
    private static readonly string[] RowEdgePatterns = ["3211", "2221", "2122", "1411", "1132", "1231", "1114", "3112"];

    /// <summary>EN 12323 표 5. 읽는 쪽은 이 두 무늬 조합만 보고 몇 번째 행인지 안다.</summary>
    private static readonly int[] RowStartIndexes = [0, 1, 2, 3, 4, 5, 6, 7, 0, 1, 2, 3, 4, 5, 6, 7];

    private static readonly int[] RowStopIndexes = [0, 1, 2, 3, 4, 5, 6, 7, 4, 5, 6, 7, 0, 1, 2, 3];

    private const int CharsPerRow = 5;
    private const int MaxRows = 16;

    /// <summary>검사 문자 두 개를 뺀 자료 문자 한도(16행 × 5 − 2).</summary>
    private const int MaxSymbolChars = MaxRows * CharsPerRow - 2;

    /// <summary>시작 7 + 안내 막대 1 + 문자 55 + 정지 7.</summary>
    private const int SymbolWidth = 70;

    /// <summary>EN 12323 4.3.6은 좌우 10X를 요구하지만, 애니라벨은 왼쪽만 10X를 그리고 오른쪽은 1X만 남긴다.</summary>
    private const int LeftQuietZone = 10;

    private const int RightQuietZone = 1;

    /// <summary>행 높이. 규격 최소는 8X이고 애니라벨 실측도 8X다.</summary>
    private const int RowHeight = 8;

    private const int PadValue = 103;
    private const int LatchToA = 101;
    private const int LatchToB = 100;
    private const int LatchToC = 99;
    private const int ShiftValue = 98;
    private const int CheckModulus = 107;

    // 문자마다 어느 부호 집합에 넣을지 표시하는 표식. 소문자는 한 글자만 넘어가는 시프트다.
    private const char SetA = 'A';
    private const char SetB = 'B';
    private const char SetC = 'C';
    private const char ShiftToA = 'a';
    private const char ShiftToB = 'b';

    /// <summary>A·B·C 어디든 들어가는 숫자.</summary>
    private const char AnySet = '9';

    /// <summary>A·B 둘 다 되는 글자.</summary>
    private const char SetAorB = 'Z';

    /// <summary>값을 Code 16K 심볼로 만든다. 못 만들면 null.</summary>
    public static BitMatrix? Encode(string value)
    {
        if (string.IsNullOrEmpty(value)) return null;
        try
        {
            var data = Encoding.Latin1.GetBytes(value);
            var sets = ResolveSets(data);
            var mode = PickMode(data, sets);
            var values = EncodeValues(data, sets, mode);
            return values is null ? null : Build(values, mode);
        }
        catch (Exception)
        {
            // 라벨 미리보기 중에 터지면 편집기가 통째로 멎는다. 못 그리면 조용히 비운다.
            return null;
        }
    }

    private static BitMatrix? Build(List<int> values, int mode)
    {
        var pads = CharsPerRow - (values.Count + 2) % CharsPerRow;
        if (pads == CharsPerRow) pads = 0;
        // 규격 최소는 2행이라, 자료가 짧아도 문자 여덟 개는 채운다.
        if (values.Count + pads < (2 * CharsPerRow) - 2)
            pads += (2 * CharsPerRow) - 2 - (values.Count + pads);
        if (values.Count + pads > MaxSymbolChars) return null;

        for (var i = 0; i < pads; i++)
            values.Add(PadValue);

        var rows = (values.Count + 2) / CharsPerRow;
        values[0] = 7 * (rows - 2) + mode;                  // 4.3.4.2 모드 문자: 행 수와 시작 부호 집합을 함께 알린다
        AppendCheckValues(values);

        var width = LeftQuietZone + SymbolWidth + RightQuietZone;
        var height = rows * (RowHeight + 1) + 1;            // 행마다 분리 막대 하나 + 위아래 묶음 막대
        var matrix = new BitMatrix(width, height);
        for (var x = 0; x < width; x++)
        {
            matrix[x, 0] = true;                            // 위 묶음 막대
            matrix[x, height - 1] = true;                   // 아래 묶음 막대
        }

        for (var row = 0; row < rows; row++)
        {
            var modules = BuildRowModules(values, row);
            var top = 1 + row * (RowHeight + 1);
            for (var x = 0; x < SymbolWidth; x++)
            {
                if (!modules[x]) continue;
                for (var y = top; y < top + RowHeight; y++)
                    matrix[LeftQuietZone + x, y] = true;
            }
            if (row == rows - 1) continue;
            for (var x = 0; x < SymbolWidth; x++)
                matrix[LeftQuietZone + x, top + RowHeight] = true;
        }
        return matrix;
    }

    /// <summary>
    /// 한 행의 요소 너비를 이어 붙여 모듈로 편다. 시작 무늬 뒤의 안내 막대(1X) 때문에 요소 짝이 한 칸 밀려서,
    /// 문자 무늬는 막대가 아니라 여백부터 그려진다 — Code 128과 흑백이 반대인 이유다.
    /// </summary>
    private static bool[] BuildRowModules(List<int> values, int row)
    {
        var elements = new StringBuilder(39);
        elements.Append(RowEdgePatterns[RowStartIndexes[row]]);
        elements.Append('1');
        for (var i = 0; i < CharsPerRow; i++)
            elements.Append(SymbolPatterns[values[row * CharsPerRow + i]]);
        elements.Append(RowEdgePatterns[RowStopIndexes[row]]);

        var modules = new bool[SymbolWidth];
        var x = 0;
        var dark = true;
        for (var i = 0; i < elements.Length; i++)
        {
            for (var run = elements[i] - '0'; run > 0; run--)
                modules[x++] = dark;
            dark = !dark;
        }
        return modules;
    }

    /// <summary>4.3.5의 검사 문자 둘. 둘째 합계에는 첫째 검사값이 마지막 항으로 한 번 더 들어간다.</summary>
    private static void AppendCheckValues(List<int> values)
    {
        var firstSum = 0;
        var secondSum = 0;
        for (var i = 0; i < values.Count; i++)
        {
            firstSum += (i + 2) * values[i];
            secondSum += (i + 1) * values[i];
        }
        var first = firstSum % CheckModulus;
        values.Add(first);
        values.Add((secondSum + first * values.Count) % CheckModulus);
    }

    /// <summary>글자 하나가 어느 집합에 들어갈 수 있는지 가린다(ISO/IEC 15417 부속서 E).</summary>
    private static char Classify(byte c) => c switch
    {
        <= 31 => ShiftToA,
        >= 48 and <= 57 => AnySet,
        <= 95 => SetAorB,
        <= 127 => ShiftToB,
        <= 159 => ShiftToA,
        <= 223 => SetAorB,
        _ => ShiftToB
    };

    /// <summary>글자별 부호 집합을 정한다. 덩어리로 묶어 다듬은 뒤 글자 단위로 편다.</summary>
    private static char[] ResolveSets(byte[] data)
    {
        var blocks = new List<(int Length, char Set)>();
        var index = 0;
        while (index < data.Length)
        {
            var kind = Classify(data[index]);
            var start = index;
            while (index < data.Length && Classify(data[index]) == kind)
                index++;
            blocks.Add((index - start, kind));
        }

        Smooth(blocks);
        Merge(blocks);

        var sets = new char[data.Length];
        var write = 0;
        foreach (var (length, set) in blocks)
        {
            for (var i = 0; i < length; i++)
                sets[write++] = set;
        }
        FixOddModeCRuns(sets);
        return sets;
    }

    /// <summary>
    /// 덩어리마다 실제로 쓸 집합을 고른다. 되도록 잠금을 적게 쓰되, 한 글자짜리 예외는 시프트로 남긴다.
    /// A·B 중 어느 쪽으로 잠글지는 뒤에 나올 시프트를 미리 보고 정한다.
    /// </summary>
    private static void Smooth(List<(int Length, char Set)> blocks)
    {
        var nextShift = '\0';
        var nextShiftIndex = 0;
        for (var i = 0; i < blocks.Count; i++)
        {
            var (length, current) = blocks[i];
            if (i == nextShiftIndex)
            {
                nextShift = '\0';
                for (var j = i + 1; j < blocks.Count; j++)
                {
                    if (blocks[j].Set is not (ShiftToA or ShiftToB)) continue;
                    nextShift = blocks[j].Set;
                    nextShiftIndex = j;
                    break;
                }
            }

            if (current == AnySet)
            {
                // 숫자 두 글자가 전부이거나 넉 자 이상 이어질 때만 C가 이득이다.
                current = (blocks.Count == 1 && length == 2) || length >= 4 ? SetC : SetAorB;
            }

            if (i == 0)
            {
                // 첫 덩어리는 기댈 앞 상태가 없으니 반드시 잠근다.
                current = current switch
                {
                    SetAorB => nextShift == ShiftToA ? SetA : SetB,
                    ShiftToA => SetA,
                    ShiftToB => SetB,
                    _ => current
                };
            }
            else
            {
                var last = blocks[i - 1].Set;
                current = current switch
                {
                    SetAorB when last is SetA or ShiftToB => SetA,
                    SetAorB when last is SetB or ShiftToA => SetB,
                    SetAorB => nextShift == ShiftToA ? SetA : SetB,
                    ShiftToA when length > 1 || last is SetA or ShiftToB or SetC => SetA,
                    ShiftToB when length > 1 || last is SetB or ShiftToA or SetC => SetB,
                    _ => current
                };
            }
            blocks[i] = (length, current);
        }
    }

    private static void Merge(List<(int Length, char Set)> blocks)
    {
        for (var i = blocks.Count - 1; i > 0; i--)
        {
            if (blocks[i].Set != blocks[i - 1].Set) continue;
            blocks[i - 1] = (blocks[i - 1].Length + blocks[i].Length, blocks[i - 1].Set);
            blocks.RemoveAt(i);
        }
    }

    /// <summary>
    /// C는 두 글자를 한 문자로 묶으므로 홀수로 남으면 안 된다. 남는 한 글자는 B로 돌리고,
    /// 세 글자 이하로 짧아진 덩어리는 아예 통째로 B가 낫다.
    /// </summary>
    private static void FixOddModeCRuns(char[] sets)
    {
        var run = 0;
        var haveOther = false;
        int i;
        for (i = 0; i < sets.Length; i++)
        {
            if (sets[i] == SetC)
            {
                run++;
                continue;
            }
            haveOther = true;
            if ((run & 1) == 1)
                sets[i > run ? i - run : i - 1] = SetB;
            run = 0;
        }
        if ((run & 1) == 1)
        {
            if (i > run && haveOther)
            {
                sets[i - run] = SetB;
                if (run < 4)
                {
                    for (var j = i - run + 1; j < i; j++)
                        sets[j] = SetB;
                }
            }
            else
            {
                sets[i - 1] = SetB;
            }
        }

        // 홀수 정리를 거치며 외톨이가 된 C는 잠금 값이 아까우니 이웃 집합에 붙인다.
        for (i = 1; i < sets.Length - 1; i++)
        {
            if (sets[i] == SetC && sets[i - 1] != SetC && sets[i + 1] != SetC)
                sets[i] = sets[i + 1];
        }
        if (sets.Length > 1 && sets[^1] == SetC && sets[^2] != SetC)
            sets[^1] = sets[^2];
    }

    /// <summary>
    /// 모드 문자에 실을 시작 부호 집합 번호(4.3.4.2 표 2). 5·6번은 B로 시작한 뒤 바로 C로 넘어가는 경우로,
    /// 이때는 C 잠금 문자를 따로 넣지 않으므로 한 문자를 아낀다.
    /// </summary>
    private static int PickMode(byte[] data, char[] sets)
    {
        if (sets.Length > 1 && sets[0] == SetB && sets[1] == SetC)
            return data[0] >= 128 ? 6 : 5;
        if (sets.Length > 2 && sets[0] == SetB && sets[1] == SetB && sets[2] == SetC
            && data[0] < 128 && data[1] < 128)
            return 6;
        return sets[0] - SetA;
    }

    private static List<int>? EncodeValues(byte[] data, char[] sets, int mode)
    {
        var values = new List<int> { 0 };                   // 모드 문자 자리. 행 수가 정해져야 채울 수 있다
        var currentSet = sets[0];
        var read = 0;
        while (read < data.Length)
        {
            if (read != 0 && sets[read] != currentSet)
            {
                switch (sets[read])
                {
                    case SetA:
                        values.Add(LatchToA);
                        currentSet = SetA;
                        break;
                    case SetB:
                        values.Add(LatchToB);
                        currentSet = SetB;
                        break;
                    case SetC:
                        if (!(read == 1 && mode >= 5) && !(read == 2 && mode == 6))
                            values.Add(LatchToC);
                        currentSet = SetC;
                        break;
                }
            }

            if (data[read] >= 128 && currentSet is SetA or SetB)
                values.Add(currentSet == SetA ? LatchToA : LatchToB);   // FNC4: 확장 ASCII 한 글자

            if (sets[read] is ShiftToA or ShiftToB)
                values.Add(ShiftValue);

            switch (sets[read])
            {
                case SetA:
                case ShiftToA:
                    values.Add(ValueInSetA(data[read]));
                    read++;
                    break;
                case SetC:
                    if (read + 1 >= data.Length) return null;
                    values.Add(10 * (data[read] - '0') + (data[read + 1] - '0'));
                    read += 2;
                    break;
                default:
                    values.Add(ValueInSetB(data[read]));
                    read++;
                    break;
            }

            if (values.Count > MaxSymbolChars) return null;
        }
        return values;
    }

    private static int ValueInSetA(byte c) => c >= 128
        ? c < 160 ? c - 64 : c - 160
        : c < 32 ? c + 64 : c - 32;

    private static int ValueInSetB(byte c) => c >= 160 ? c - 160 : c - 32;
}
