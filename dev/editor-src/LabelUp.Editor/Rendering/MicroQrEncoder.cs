using System.Globalization;
using System.Text;
using ZXing.Common;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// Micro QR Code (ISO/IEC 18004). ZXing은 일반 QR만 그려서 M1~M4 심볼은 직접 만들어야 한다.
/// 표·마스크·벌점은 규격을 그대로 따랐고, 자동 선택은 애니라벨과 같은 답이 나오도록
/// 「가장 작은 판형 → 그 판형에서 들어가는 가장 높은 ECC」 순서로 고른다.
/// 「Micro QR Code」 실측: M4·ECC M·마스크 2.
/// </summary>
internal static class MicroQrEncoder
{
    /// <summary>
    /// 심볼 번호(형식 정보 상위 3비트)별 판형·ECC·용량. 규격 Table 9·13의 조합 순서 그대로다.
    /// 0=M1, 1=M2-L, 2=M2-M, 3=M3-L, 4=M3-M, 5=M4-L, 6=M4-M, 7=M4-Q
    /// </summary>
    private static readonly int[] SymVersion = [1, 2, 2, 3, 3, 4, 4, 4];

    /// <summary>ECC 등급(0=L, 1=M, 2=Q). M1은 등급 구분이 없어 -1.</summary>
    private static readonly int[] SymEcc = [-1, 0, 1, 0, 1, 0, 1, 2];

    /// <summary>자료 비트 수. M1·M3은 마지막 부호어가 4비트라 8의 배수가 아니다.</summary>
    private static readonly int[] SymDataBits = [20, 40, 32, 84, 68, 128, 112, 80];

    private static readonly int[] SymDataCodewords = [3, 5, 4, 11, 9, 16, 14, 10];

    private static readonly int[] SymEccCodewords = [2, 5, 6, 6, 8, 8, 10, 14];

    /// <summary>판형별 모드 지시자 길이. M1은 숫자만 담아서 지시자가 아예 없다.</summary>
    private static readonly int[] ModeBits = [0, 1, 2, 3];

    /// <summary>판형별 종료 부호 길이.</summary>
    private static readonly int[] TerminatorBits = [3, 5, 7, 9];

    /// <summary>[판형-1][모드]별 글자 수 지시자 길이. -1은 그 판형에서 못 쓰는 모드다.</summary>
    private static readonly int[][] CountBits =
    [
        [3, -1, -1],
        [4, 3, -1],
        [5, 4, 4],
        [6, 5, 5]
    ];

    /// <summary>숫자 모드에서 3자리 묶음 뒤에 남는 자릿수(0·1·2개)를 담는 비트 수.</summary>
    private static readonly int[] NumericTailBits = [0, 4, 7];

    private const int ModeNumeric = 0;
    private const int ModeAlnum = 1;
    private const int ModeByte = 2;

    private const string AlnumChars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:";

    private const int EccAuto = -1;
    private const int EccInvalid = -2;

    /// <summary>값을 Micro QR 심볼로 만든다. 못 만들면 null.</summary>
    /// <param name="wantVersion">M1~M4를 1~4로 지정. 0이면 자동.</param>
    /// <param name="wantEcc">"L"/"M"/"Q". 비어 있으면 자동.</param>
    /// <param name="wantMask">0~3. 음수면 규격의 벌점 규칙대로 고른다.</param>
    public static BitMatrix? Encode(string value, int wantVersion = 0, string? wantEcc = null, int wantMask = -1)
    {
        if (string.IsNullOrEmpty(value)) return null;
        if (wantVersion is < 0 or > 4 || wantMask > 3) return null;

        var ecc = ParseEcc(wantEcc);
        if (ecc == EccInvalid) return null;

        var mode = PickMode(value);
        if (mode < 0) return null;

        var sym = PickSymbol(value, mode, wantVersion, ecc);
        return sym < 0 ? null : Build(sym, BuildBitStream(value, mode, sym), wantMask);
    }

    private static int ParseEcc(string? want) => want?.Trim().ToUpperInvariant() switch
    {
        null or "" => EccAuto,
        "L" => 0,
        "M" => 1,
        "Q" => 2,
        _ => EccInvalid
    };

    /// <summary>
    /// 담을 수 있는 가장 좁은 모드. 한자 모드는 Shift-JIS 인코딩이 WASM 기본 제공에 없어서
    /// 쓰지 않는다 — 같은 값은 바이트 모드로도 들어간다.
    /// </summary>
    private static int PickMode(string value)
    {
        if (value.All(char.IsAsciiDigit)) return ModeNumeric;
        if (value.All(c => AlnumChars.Contains(c, StringComparison.Ordinal))) return ModeAlnum;
        return value.All(c => c <= 0xFF) ? ModeByte : -1;
    }

    /// <summary>
    /// 판형은 작은 것부터, 같은 판형 안에서는 높은 ECC부터 본다. M4-L(128비트)에도 들어가는
    /// 자료라도 애니라벨은 M4-M(112비트)에 들어가면 M을 쓴다.
    /// </summary>
    private static int PickSymbol(string value, int mode, int wantVersion, int wantEcc)
    {
        for (var version = 1; version <= 4; version++)
        {
            if (wantVersion > 0 && version != wantVersion) continue;
            var need = PayloadBits(value, mode, version);
            if (need < 0) continue;
            for (var sym = 7; sym >= 0; sym--)
            {
                if (SymVersion[sym] != version) continue;
                if (wantEcc != EccAuto && SymEcc[sym] != wantEcc) continue;
                if (need <= SymDataBits[sym]) return sym;
            }
        }
        return -1;
    }

    /// <summary>모드 지시자 + 글자 수 지시자 + 자료 비트. 그 판형에서 못 담으면 -1.</summary>
    private static int PayloadBits(string value, int mode, int version)
    {
        var countBits = CountBits[version - 1][mode];
        if (countBits < 0) return -1;
        if (value.Length >= 1 << countBits) return -1;

        var body = mode switch
        {
            ModeNumeric => value.Length / 3 * 10 + NumericTailBits[value.Length % 3],
            ModeAlnum => value.Length / 2 * 11 + value.Length % 2 * 6,
            _ => value.Length * 8
        };
        return ModeBits[version - 1] + countBits + body;
    }

    private static List<bool> BuildBitStream(string value, int mode, int sym)
    {
        var version = SymVersion[sym];
        var capacity = SymDataBits[sym];
        var bits = new List<bool>(capacity);

        Append(bits, mode, ModeBits[version - 1]);
        Append(bits, value.Length, CountBits[version - 1][mode]);
        switch (mode)
        {
            case ModeNumeric:
                for (var i = 0; i < value.Length; i += 3)
                {
                    var len = Math.Min(3, value.Length - i);
                    var chunk = int.Parse(value.AsSpan(i, len), CultureInfo.InvariantCulture);
                    Append(bits, chunk, len == 3 ? 10 : NumericTailBits[len]);
                }
                break;
            case ModeAlnum:
                for (var i = 0; i < value.Length; i += 2)
                {
                    var head = AlnumChars.IndexOf(value[i], StringComparison.Ordinal);
                    if (i + 1 < value.Length)
                        Append(bits, head * 45 + AlnumChars.IndexOf(value[i + 1], StringComparison.Ordinal), 11);
                    else
                        Append(bits, head, 6);
                }
                break;
            default:
                foreach (var b in Encoding.Latin1.GetBytes(value))
                    Append(bits, b, 8);
                break;
        }

        // 종료 부호는 남은 자리만큼만 넣는다. 자료가 용량을 꽉 채우면 아예 생략한다.
        Append(bits, 0, Math.Min(TerminatorBits[version - 1], capacity - bits.Count));
        while (bits.Count < capacity && bits.Count % 8 != 0)
            bits.Add(false);
        for (var pad = 0; capacity - bits.Count >= 8; pad++)
            Append(bits, (pad & 1) == 0 ? 0xEC : 0x11, 8);
        // M1·M3의 마지막 4비트 부호어에는 메움 값 대신 0000이 들어간다.
        Append(bits, 0, capacity - bits.Count);
        return bits;
    }

    private static void Append(List<bool> bits, int value, int length)
    {
        for (var b = length - 1; b >= 0; b--)
            bits.Add((value >> b & 1) != 0);
    }

    private static BitMatrix Build(int sym, List<bool> bits, int wantMask)
    {
        var size = 9 + SymVersion[sym] * 2;
        var stream = new List<bool>(bits);
        foreach (var word in ReedSolomon(ToCodewords(sym, bits), SymEccCodewords[sym]))
            Append(stream, word, 8);

        var modules = new bool[size, size];
        var reserved = new bool[size, size];
        DrawFunctionPatterns(modules, reserved, size);
        PlaceStream(modules, reserved, size, stream);

        var mask = wantMask >= 0 ? wantMask : PickMask(modules, reserved, size, sym);
        ApplyMask(modules, reserved, size, mask);
        PlaceFormat(modules, sym, mask);

        var matrix = new BitMatrix(size, size);
        for (var y = 0; y < size; y++)
        {
            for (var x = 0; x < size; x++)
            {
                if (modules[x, y])
                    matrix[x, y] = true;
            }
        }
        return matrix;
    }

    /// <summary>M1·M3의 마지막 부호어는 4비트뿐이다. RS 계산에서는 하위 4비트로 담는다.</summary>
    private static int[] ToCodewords(int sym, List<bool> bits)
    {
        var words = new int[SymDataCodewords[sym]];
        for (var i = 0; i < words.Length; i++)
        {
            var length = Math.Min(8, bits.Count - i * 8);
            for (var b = 0; b < length; b++)
                words[i] = words[i] << 1 | (bits[i * 8 + b] ? 1 : 0);
        }
        return words;
    }

    /// <summary>
    /// 좌상단 9×9(찾기 무늬·분리 띠·형식 정보)와 0행·0열 타이밍 무늬가 기능 모듈 전부다.
    /// Micro QR에는 정렬 무늬도, 고정 검은 모듈도 없다.
    /// </summary>
    private static void DrawFunctionPatterns(bool[,] modules, bool[,] reserved, int size)
    {
        for (var y = 0; y < 9; y++)
        {
            for (var x = 0; x < 9; x++)
                reserved[x, y] = true;
        }
        for (var y = 0; y < 7; y++)
        {
            for (var x = 0; x < 7; x++)
                modules[x, y] = x is 0 or 6 || y is 0 or 6 || (x is >= 2 and <= 4 && y is >= 2 and <= 4);
        }
        for (var i = 9; i < size; i++)
        {
            modules[i, 0] = i % 2 == 0;
            modules[0, i] = i % 2 == 0;
            reserved[i, 0] = true;
            reserved[0, i] = true;
        }
        // 타이밍 무늬는 분리 띠 바로 옆 (8,0)·(0,8)에서 검게 시작한다.
        modules[8, 0] = true;
        modules[0, 8] = true;
    }

    /// <summary>오른쪽 아래에서 두 칸 폭으로 위·아래를 번갈아 훑는다. 0행·0열은 타이밍이라 건너뛴다.</summary>
    private static void PlaceStream(bool[,] modules, bool[,] reserved, int size, List<bool> stream)
    {
        var index = 0;
        var upward = true;
        for (var right = size - 1; right >= 1; right -= 2)
        {
            for (var step = 0; step < size - 1; step++)
            {
                var y = upward ? size - 1 - step : 1 + step;
                for (var c = 0; c < 2; c++)
                {
                    var x = right - c;
                    if (x < 1 || reserved[x, y]) continue;
                    if (index < stream.Count && stream[index])
                        modules[x, y] = true;
                    index++;
                }
            }
            upward = !upward;
        }
    }

    private static void ApplyMask(bool[,] modules, bool[,] reserved, int size, int mask)
    {
        for (var y = 0; y < size; y++)
        {
            for (var x = 0; x < size; x++)
            {
                if (!reserved[x, y] && MaskBit(mask, y, x))
                    modules[x, y] ^= true;
            }
        }
    }

    /// <summary>Micro QR은 마스크가 넷뿐이다(일반 QR의 1·4·6·7과 같은 식).</summary>
    private static bool MaskBit(int mask, int i, int j) => mask switch
    {
        0 => i % 2 == 0,
        1 => (i / 2 + j / 3) % 2 == 0,
        2 => (i * j % 2 + i * j % 3) % 2 == 0,
        _ => ((i + j) % 2 + i * j % 3) % 2 == 0
    };

    /// <summary>
    /// 일반 QR의 벌점과 달리 오른쪽·아래 변의 검은 모듈 수만 센다(규격 7.8.3.2).
    /// 점수가 가장 높은 마스크가 뽑힌다.
    /// </summary>
    private static int PickMask(bool[,] modules, bool[,] reserved, int size, int sym)
    {
        var best = 0;
        var bestScore = -1;
        for (var mask = 0; mask < 4; mask++)
        {
            var trial = (bool[,])modules.Clone();
            ApplyMask(trial, reserved, size, mask);
            PlaceFormat(trial, sym, mask);

            var right = 0;
            var bottom = 0;
            for (var i = 1; i < size; i++)
            {
                if (trial[size - 1, i]) right++;
                if (trial[i, size - 1]) bottom++;
            }
            var score = right <= bottom ? right * 16 + bottom : bottom * 16 + right;
            if (score > bestScore)
            {
                bestScore = score;
                best = mask;
            }
        }
        return best;
    }

    /// <summary>형식 정보 15비트는 0번(최하위)부터 8열을 1행→8행으로 내려가고, 이어서 8행을 7열→1열로 간다.</summary>
    private static void PlaceFormat(bool[,] modules, int sym, int mask)
    {
        var code = FormatInfo(sym, mask);
        for (var i = 0; i < 15; i++)
        {
            var bit = (code >> i & 1) != 0;
            if (i < 8)
                modules[8, i + 1] = bit;
            else
                modules[15 - i, 8] = bit;
        }
    }

    /// <summary>심볼 번호 3비트 + 마스크 2비트에 BCH(15,5) 나머지를 붙이고 0x4445로 뒤집는다.</summary>
    private static int FormatInfo(int sym, int mask)
    {
        var data = sym << 2 | mask;
        var poly = data << 10;
        for (var i = 4; i >= 0; i--)
        {
            if ((poly >> i + 10 & 1) != 0)
                poly ^= 0x537 << i;
        }
        return (data << 10 | poly & 0x3FF) ^ 0x4445;
    }

    /// <summary>GF(256), 원시 다항식 0x11D. 일반 QR과 같다.</summary>
    private static int[] ReedSolomon(int[] data, int count)
    {
        var generator = Generator(count);
        var remainder = new int[count];
        foreach (var word in data)
        {
            var factor = word ^ remainder[0];
            Array.Copy(remainder, 1, remainder, 0, count - 1);
            remainder[count - 1] = 0;
            if (factor == 0) continue;
            for (var j = 0; j < count; j++)
                remainder[j] ^= Mul(generator[j + 1], factor);
        }
        return remainder;
    }

    /// <summary>(x - α⁰)…(x - α^(count-1))을 차수 내림 순으로 돌려준다.</summary>
    private static int[] Generator(int count)
    {
        var g = new int[count + 1];
        g[0] = 1;
        var root = 1;
        for (var i = 0; i < count; i++)
        {
            for (var k = i + 1; k >= 1; k--)
                g[k] ^= Mul(root, g[k - 1]);
            root = Mul(root, 2);
        }
        return g;
    }

    private static int Mul(int a, int b)
    {
        var product = 0;
        for (var i = 0; i < 8; i++)
        {
            if ((b >> i & 1) != 0)
                product ^= a << i;
        }
        for (var i = 14; i >= 8; i--)
        {
            if ((product >> i & 1) != 0)
                product ^= 0x11D << i - 8;
        }
        return product & 0xFF;
    }
}
