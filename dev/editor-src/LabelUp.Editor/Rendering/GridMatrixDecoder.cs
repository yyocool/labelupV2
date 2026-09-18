using System.Globalization;
using System.Text;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// 찍힌 Grid Matrix 모듈 행렬을 거꾸로 풀어 값 후보를 뽑는다. 매크로모듈 배치와 블록 엮기는
/// GridMatrixEncoder의 BuildGrid·AddEcc를 뒤집은 것이고, 비트 흐름 해석은 AIMD014 표 6~9(모드
/// 지시자와 집합별 기호 폭)를 그대로 따른다. 근거는 규격 표와 libzint(BSD-3-Clause)뿐이다.
///
/// 애니라벨은 ASCII를 벗어난 바코드 값을 .lbl에 '?'로 깎아 적는다. 「QR코드 타입.lbl」 32번 칸은
/// 값 바이트가 3f3f3f인데 심볼 안에는 EC8381 ED9288 EBAA85(UTF-8 「상품명」)이 들어 있고, 압축을
/// 푼 2.29MB를 UTF-8·CP949·UTF-16LE·UTF-16BE·GB18030으로 훑어도 원문은 어디에도 없다. 그러니
/// 값은 함께 저장된 그림에서만 되살릴 수 있다.
///
/// 여기서 나온 값은 그것만으로는 믿을 수 없다. 쓰는 쪽(AniLabelValueRecovery)이 우리 인코더로
/// 다시 찍어 원본 행렬과 한 모듈도 다르지 않을 때만 갈아 끼우므로, 이 해독기는 갈래가 갈리는
/// 자리마다 후보를 하나씩 더 내놓고 판정은 넘긴다.
/// </summary>
internal static class GridMatrixDecoder
{
    private const int ModeChinese = 1;
    private const int ModeNumeral = 2;
    private const int ModeLower = 3;
    private const int ModeUpper = 4;
    private const int ModeMixed = 5;
    private const int ModeByte = 6;

    private const int MaxVersion = 13;

    private const int MaxEcc = 5;

    /// <summary>한 변이 6 + 12×판형이다(AIMD014 표 10).</summary>
    private const int MinSide = 18;

    /// <summary>6.3.7의 바이트 블록 한도.</summary>
    private const int MaxBlockBytes = 512;

    /// <summary>숫자 모드가 숫자 사이에 끼워 넣을 수 있는 글자(표 6). 여섯째 자리는 줄바꿈 쌍이다.</summary>
    private const string NumeralNonDigits = " +-.,";

    /// <summary>
    /// 애니라벨은 바이트 모드에 UTF-8 날 바이트를 ECI 없이 싣는다(32번 칸 블록 EC8381 ED9288
    /// EBAA85가 「상품명」으로만 읽히고 CP949·CP936·GB18030·CP932로는 아예 못 읽는다).
    /// 어긋난 바이트열을 글자로 뭉개면 왕복 검사를 통과할 수 없으니 예외로 튕겨 낸다.
    /// </summary>
    private static readonly UTF8Encoding Utf8Strict = new(false, true);

    /// <summary>GB 2312(코드페이지 936). 한자 모드 부호를 두 바이트로 되돌려 읽는 데만 쓴다.</summary>
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

    /// <summary>
    /// 모듈 행렬에서 값 후보를 뽑는다. 행렬은 [행, 열]이고 true가 검은 모듈이다.
    /// 오류정정 등급과 바이트 블록 길이 해석이 갈리는 자리마다 후보가 하나씩 늘어난다.
    /// </summary>
    public static IReadOnlyList<string> ReadCandidates(bool[,] modules)
    {
        var candidates = new List<string>();
        try
        {
            var side = modules.GetLength(0);
            if (modules.GetLength(1) != side || side < MinSide || (side - 6) % 12 != 0) return candidates;
            var version = (side - 6) / 12;
            if (version > MaxVersion) return candidates;

            var perDim = 1 + version * 2;
            var codewords = new int[2 * perDim * perDim];
            var layerIds = new int[perDim * perDim];
            ReadMacromodules(modules, version, codewords, layerIds);

            foreach (var ecc in EccOrder(codewords, layerIds, version))
            {
                if (!TryDeinterleave(codewords, version, ecc, out var data, out _)) continue;
                var bits = ToBits(data);
                // 규격은 바이트 블록 길이를 「바이트 수 − 1」로 적고 애니라벨은 바이트 수를 그대로
                // 적는다(22·32번 칸 실측). 두 해석을 다 내놓고 판정은 왕복 검사에 맡긴다.
                foreach (var plainByteCount in new[] { false, true })
                {
                    var value = ParseStream(bits, plainByteCount);
                    if (value is { Length: > 0 } && !candidates.Contains(value, StringComparer.Ordinal))
                        candidates.Add(value);
                }
            }
        }
        catch (Exception)
        {
            // 가져오기 중에 터지면 파일 하나가 통째로 안 열린다. 못 읽으면 후보 없이 돌아간다.
            return candidates;
        }
        return candidates;
    }

    /// <summary>
    /// 매크로모듈 안쪽 4×4를 BuildGrid가 넣은 차례 그대로 되읽어 부호어와 층 번호를 꺼낸다.
    /// 첫 줄 왼쪽 두 칸은 층 번호가 쓰는 자리다(6.10).
    /// </summary>
    private static void ReadMacromodules(bool[,] modules, int version, int[] codewords, int[] layerIds)
    {
        var perDim = 1 + version * 2;
        for (var row = 0; row < perDim; row++)
        {
            for (var col = 0; col < perDim; col++)
            {
                var left = col * 6 + 1;
                var top = row * 6 + 1;

                layerIds[row * perDim + col] =
                    (modules[top, left] ? 2 : 0) | (modules[top, left + 1] ? 1 : 0);

                var cw = 0;
                if (modules[top, left + 2]) cw |= 0x2000;
                if (modules[top, left + 3]) cw |= 0x1000;
                for (var r = 1; r < 4; r++)
                {
                    for (var c = 0; c < 4; c++)
                    {
                        if (modules[top + r, left + c]) cw |= 1 << (15 - r * 4 - c);
                    }
                }

                var index = GridMatrixEncoder.MacromoduleIndex(perDim, col, row);
                codewords[index] = cw & 0x7F;
                codewords[index + 1] = cw >> 7 & 0x7F;
            }
        }
    }

    /// <summary>
    /// 오류정정 등급을 시험할 순서. 층 번호판이 맞아떨어지는 등급을 먼저 보고, 그다음으로
    /// 오류정정 부호어 검산을 통과하는 등급을 높은 쪽부터 본다. 생성 다항식이 서로를 나누므로
    /// 낮은 등급은 언제나 함께 통과한다 — 그래서 높은 쪽이 실제 등급이다.
    /// </summary>
    private static List<int> EccOrder(int[] codewords, int[] layerIds, int version)
    {
        var order = new List<int>();
        for (var ecc = MaxEcc; ecc >= 1; ecc--)
        {
            if (version == 1 && ecc == 1) continue;          // 판형 1의 1등급은 규격에 없다
            if (LayerIdsMatch(layerIds, version, ecc)) order.Add(ecc);
        }
        for (var ecc = MaxEcc; ecc >= 1; ecc--)
        {
            if (version == 1 && ecc == 1 || order.Contains(ecc)) continue;
            if (TryDeinterleave(codewords, version, ecc, out _, out var eccValid) && eccValid)
                order.Add(ecc);
        }
        return order;
    }

    /// <summary>
    /// 층 번호는 가운데가 0층이고 1등급만 거꾸로 돈다. layerIds[0]은 1등급이면 3, 아니면
    /// (5−등급)&amp;3이라 3이 나오는 자리에서 1·2등급이 겹친다. 그래서 등급마다 번호판을 통째로
    /// 만들어 맞춰 본다 — PlaceLayerIds를 뒤집은 것이다.
    /// </summary>
    private static bool LayerIdsMatch(int[] observed, int version, int ecc)
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
                if (ids[row, col] != observed[row * perDim + col]) return false;
            }
        }
        return true;
    }

    /// <summary>
    /// 블록을 풀어 자료 부분만 이어 붙인다(부속서 A). 오류정정 부호어가 우리 계산과 같은지도
    /// 함께 돌려주어, 등급을 잘못 짚은 해석을 뒤로 밀 수 있게 한다.
    /// </summary>
    private static bool TryDeinterleave(int[] codewords, int version, int ecc, out byte[] data, out bool eccValid)
    {
        data = [];
        eccValid = false;

        var size1 = GridMatrixEncoder.BlockSize1[version - 1];
        var count1 = GridMatrixEncoder.BlockCount1[version - 1];
        var count3 = GridMatrixEncoder.BlockCount3[version - 1][ecc - 1];
        var ecc1 = GridMatrixEncoder.BlockEcc1[version - 1][ecc - 1];
        var ecc2 = GridMatrixEncoder.BlockEcc2[version - 1][ecc - 1];
        var blocks = count1 + GridMatrixEncoder.BlockCount2[version - 1];
        if (blocks < 1 || size1 < 1) return false;

        var collected = new List<byte>();
        var valid = true;
        for (var i = 0; i < blocks; i++)
        {
            var blockSize = i < count1 ? size1 : size1 - 1;
            var eccSize = i < count3 ? ecc1 : ecc2;
            var dataSize = blockSize - eccSize;
            if (dataSize < 1 || blocks * (blockSize - 1) + i >= codewords.Length) return false;

            var block = new byte[blockSize];
            for (var j = 0; j < blockSize; j++)
                block[j] = (byte)codewords[blocks * j + i];

            var check = new byte[blockSize];
            Array.Copy(block, check, dataSize);
            GridMatrixEncoder.ReedSolomon(check, dataSize, eccSize);
            if (!check.AsSpan().SequenceEqual(block)) valid = false;

            collected.AddRange(block.AsSpan(0, dataSize));
        }

        data = collected.ToArray();
        eccValid = valid;
        return data.Length > 0;
    }

    /// <summary>자료 부호어는 7비트다. 메움 부호어까지 그대로 펴서 읽을 자리를 넉넉히 둔다.</summary>
    private static bool[] ToBits(byte[] data)
    {
        var bits = new bool[data.Length * 7];
        for (var i = 0; i < data.Length; i++)
        {
            for (var b = 0; b < 7; b++)
                bits[i * 7 + b] = (data[i] >> (6 - b) & 1) != 0;
        }
        return bits;
    }

    /// <summary>
    /// 비트 흐름을 표 9의 모드 지시자와 집합별 기호 폭대로 읽어 값을 되살린다. 자료 끝 표시까지
    /// 온전히 읽히지 않으면 후보로 내놓지 않는다 — 어긋난 해석을 값으로 쓰면 안 된다.
    /// </summary>
    /// <param name="plainByteCount">
    /// 켜면 바이트 블록 길이 칸을 바이트 수 그대로 읽는다. 규격은 「바이트 수 − 1」이다.
    /// </param>
    private static string? ParseStream(bool[] bits, bool plainByteCount)
    {
        var available = bits.Length;
        var bp = 0;
        var text = new StringBuilder();
        var block = new List<byte>();
        var complete = false;

        bool CanRead(int n) => bp + n <= available;

        int Read(int n)
        {
            var v = 0;
            for (var i = 0; i < n; i++)
                v = v << 1 | (bits[bp + i] ? 1 : 0);
            bp += n;
            return v;
        }

        bool FlushBlock()
        {
            if (block.Count == 0) return true;
            try
            {
                text.Append(Utf8Strict.GetString(block.ToArray()));
            }
            catch (DecoderFallbackException)
            {
                return false;
            }
            block.Clear();
            return true;
        }

        void AppendPunt(int punt) => text.Append(punt == 5 ? "\r\n" : NumeralNonDigits[punt]);

        if (!CanRead(4)) return null;
        var mode = Read(4) switch
        {
            1 => ModeChinese, 2 => ModeNumeral, 3 => ModeLower,
            4 => ModeUpper, 5 => ModeMixed, 7 => ModeByte, _ => 0
        };
        if (mode == 0) return null;

        while (!complete)
        {
            if (mode == ModeChinese)
            {
                if (!CanRead(13)) return null;
                var g = Read(13);
                if (g == 8160) { complete = true; break; }
                if (g is >= 8161 and <= 8165)
                {
                    mode = g switch
                    {
                        8161 => ModeNumeral, 8162 => ModeLower, 8163 => ModeUpper,
                        8164 => ModeMixed, _ => ModeByte
                    };
                    continue;
                }
                if (g == 7776)
                {
                    text.Append("\r\n");
                    continue;
                }
                if (g is >= 7777 and <= 8032)
                {
                    text.Append((char)(g - 7777));
                    continue;
                }
                if (g is >= 8033 and <= 8132)
                {
                    var two = g - 8033;
                    text.Append((char)('0' + two / 10)).Append((char)('0' + two % 10));
                    continue;
                }
                if (g is < 1 or > 7775) return null;

                // 구역·자리 번호를 GB 2312 두 바이트로 되돌린다. 0xAA~0xAF는 규격에 없어 건너뛴다.
                var zone = g / 0x60;
                var cell = g % 0x60;
                var han = DecodeGb(zone <= 8 ? 0xA1 + zone : 0xB0 + zone - 9, 0xA0 + cell);
                if (han is null) return null;
                text.Append(han);
                continue;
            }

            if (mode == ModeNumeral)
            {
                if (!CanRead(2)) return null;
                var pad = Read(2);
                if (pad > 2) return null;

                var groups = new List<(int Punt, int PuntAt, int Value)>();
                var next = 0;
                while (true)
                {
                    if (!CanRead(10)) return null;
                    var g = Read(10);
                    if (g == 1018) break;
                    if (g is >= 1019 and <= 1023)
                    {
                        next = g switch
                        {
                            1019 => ModeChinese, 1020 => ModeLower, 1021 => ModeUpper,
                            1022 => ModeMixed, _ => ModeByte
                        };
                        break;
                    }
                    if (g >= 1000)
                    {
                        // 표 6의 1000~1017. 끼움 글자 종류와 그 글자가 묶음 안에서 앉는 자리다.
                        var basis = g - 1000;
                        if (!CanRead(10)) return null;
                        var value = Read(10);
                        if (value > 999) return null;
                        groups.Add((basis / 3, basis % 3, value));
                        continue;
                    }
                    groups.Add((-1, 0, g));
                }

                if (groups.Count == 0) return null;
                // 메움 값은 마지막 묶음에만 걸린다. 앞 묶음은 언제나 세 자리다.
                for (var i = 0; i < groups.Count; i++)
                {
                    var (punt, puntAt, value) = groups[i];
                    var count = i == groups.Count - 1 ? 3 - pad : 3;
                    var digits = value.ToString("D3", CultureInfo.InvariantCulture);
                    for (var d = 0; d < count; d++)
                    {
                        if (punt >= 0 && d == puntAt) AppendPunt(punt);
                        text.Append(digits[d]);
                    }
                    if (punt >= 0 && puntAt >= count) AppendPunt(punt);
                }

                if (next == 0) { complete = true; break; }
                mode = next;
                continue;
            }

            if (mode is ModeLower or ModeUpper)
            {
                if (!CanRead(5)) return null;
                var v = Read(5);
                if (v < 26)
                {
                    text.Append((char)((mode == ModeLower ? 'a' : 'A') + v));
                    continue;
                }
                if (v == 26)
                {
                    text.Append(' ');
                    continue;
                }
                if (v == 27) { complete = true; break; }
                if (v is 28 or 29 or 30)
                {
                    mode = v switch
                    {
                        28 => ModeChinese,
                        29 => ModeNumeral,
                        _ => mode == ModeLower ? ModeUpper : ModeLower
                    };
                    continue;
                }

                // 31은 일곱 비트 지시자(124~127)의 앞머리다.
                if (!CanRead(2)) return null;
                var w = v << 2 | Read(2);
                if (w == 125)
                {
                    if (!CanRead(6)) return null;
                    text.Append(ShiftChar(Read(6)));
                    continue;
                }
                if (w is 124 or 126)
                {
                    mode = w == 124 ? ModeMixed : ModeByte;
                    continue;
                }
                return null;
            }

            if (mode == ModeMixed)
            {
                if (!CanRead(6)) return null;
                var v = Read(6);
                if (v < 63)
                {
                    // 혼합 집합은 숫자 0~9·대문자 10~35·소문자 36~61에 빈칸이 62다. 애니라벨은 빈칸에
                    // 대·소문자 집합의 26을 그대로 써서(12·21번 칸 실측) 그 자리가 'Q'로 읽힌다.
                    // 규격대로만 읽고, 그렇게 읽은 값이 맞는지는 왕복 검사가 가린다.
                    text.Append(v switch
                    {
                        < 10 => (char)('0' + v),
                        < 36 => (char)('A' + v - 10),
                        < 62 => (char)('a' + v - 36),
                        _ => ' '
                    });
                    continue;
                }

                if (!CanRead(4)) return null;
                var w = v << 4 | Read(4);
                if (w == 1008) { complete = true; break; }
                if (w == 1014)
                {
                    if (!CanRead(6)) return null;
                    text.Append(ShiftChar(Read(6)));
                    continue;
                }
                if (w is >= 1009 and <= 1012 or 1015)
                {
                    mode = w switch
                    {
                        1009 => ModeChinese, 1010 => ModeNumeral, 1011 => ModeLower,
                        1012 => ModeUpper, _ => ModeByte
                    };
                    continue;
                }
                return null;
            }

            // 바이트 모드. 블록 길이를 먼저 읽고, 블록이 끝나면 네 비트 지시자가 뒤따른다(6.3.7).
            if (!CanRead(9)) return null;
            var blockLen = Read(9) + (plainByteCount ? 0 : 1);
            if (blockLen is < 1 or > MaxBlockBytes) return null;
            if (!CanRead(blockLen * 8)) return null;
            for (var i = 0; i < blockLen; i++)
                block.Add((byte)Read(8));

            if (!CanRead(4)) return null;
            var tail = Read(4);
            // 512바이트를 넘겨 블록을 새로 여는 경우다. UTF-8 한 글자가 블록 경계에서 갈릴 수 있어
            // 바이트는 모아 두고 모드를 떠날 때 한꺼번에 푼다.
            if (tail == 7) continue;
            if (!FlushBlock()) return null;
            if (tail == 0) { complete = true; break; }
            if (tail is >= ModeChinese and <= ModeMixed)
            {
                mode = tail;
                continue;
            }
            return null;
        }

        if (!complete || !FlushBlock()) return null;
        return text.ToString();
    }

    /// <summary>
    /// 표 7의 시프트 집합. 제어문자 32자에 33~47·58~64·91~96·123~126이 빈틈없이 붙어 예순네
    /// 칸을 꽉 채운다. WriteShiftChar를 뒤집은 것이다.
    /// </summary>
    private static char ShiftChar(int glyph) => (char)(glyph switch
    {
        < 32 => glyph,
        < 47 => glyph + 1,
        < 54 => glyph + 11,
        < 60 => glyph + 37,
        _ => glyph + 63
    });

    private static string? DecodeGb(int lead, int trail)
    {
        var gb = Gb2312.Value;
        if (gb is null) return null;
        try
        {
            return gb.GetString([(byte)lead, (byte)trail]);
        }
        catch (DecoderFallbackException)
        {
            return null;
        }
    }
}
