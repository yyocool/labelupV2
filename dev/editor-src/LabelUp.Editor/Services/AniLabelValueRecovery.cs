using LabelUp.Editor.Models;
using LabelUp.Editor.Rendering;
using ZXing.Common;

namespace LabelUp.Editor.Services;

/// <summary>
/// 애니라벨이 깎아 버린 2D 바코드 값을 함께 저장된 BMP에서 되살린다.
///
/// 애니라벨은 .lbl에 값을 적을 때 ASCII 밖 글자를 '?'로 바꿔 버린다 — 「QR코드 타입.lbl」 32번 칸
/// (3쪽 4번 라벨)의 Grid Matrix는 값 바이트가 3f3f3f인데 심볼 안에는 UTF-8 「상품명」이 들어 있고,
/// 압축을 푼 2.29MB를 UTF-8·CP949·UTF-16LE·UTF-16BE·GB18030으로 훑어도 원문은 없다. 한글 바코드는
/// 한국 라벨에서 흔하므로, 그대로 가져오면 그런 바코드가 모두 '???'가 되어 조용히 값을 잃는다.
///
/// 되살린 값을 그냥 믿으면 엉뚱한 심볼을 그리게 되므로 네 단계를 다 통과해야만 갈아 끼운다.
///   (1) 저장된 값에 '?'가 있어 망가진 것이 분명할 때만 손댄다. 깨끗한 값은 건드리지 않는다.
///   (2) BMP에서 뜬 모듈 행렬을 해독기에 넣어 값 후보를 얻는다.
///   (3) 저장값에 살아남은 글자를 그대로 두고 '?' 자리만 후보로 채운다(MergeWithStored).
///   (4) 그렇게 합친 값을 우리 인코더로 다시 찍어 원본 행렬과 한 모듈도 다르지 않은지 본다.
/// (4)를 통과하면 우리가 그릴 그림이 애니라벨의 그림과 완전히 같다는 뜻이고, (3)이 심볼만으로는
/// 가릴 수 없는 한 가지 어긋남을 막는다. 하나라도 걸리면 저장된 값을 그대로 둔다.
///
/// 심볼로지는 Symbologies 표에 「후보 뽑기 + 다시 찍기」 한 쌍을 더하면 늘어난다. 지금은 해독기가
/// 있는 Grid Matrix만 등록되어 있고, 표에 없는 심볼로지는 값에 '?'가 있어도 손대지 않는다.
/// </summary>
internal static class AniLabelValueRecovery
{
    /// <summary>모듈 행렬에서 값 후보를 뽑는다. 행렬은 [행, 열]이고 true가 검은 모듈이다.</summary>
    private delegate IReadOnlyList<string> CandidateReader(bool[,] modules);

    /// <summary>
    /// 후보를 다시 찍는다. 렌더러가 그 객체를 그릴 때와 똑같은 인자로 불러야 왕복 검사가 뜻을 가진다.
    /// </summary>
    private delegate BitMatrix? Reencoder(DesignObject obj, string candidate);

    private readonly record struct Symbology(CandidateReader ReadCandidates, Reencoder Reencode);

    /// <summary>BarcodeRenderer가 쓰는 것과 같은 심볼로지 이름을 쓴다.</summary>
    private static readonly Dictionary<string, Symbology> Symbologies = new(StringComparer.Ordinal)
    {
        ["GRID_MATRIX"] = new(GridMatrixDecoder.ReadCandidates, ReencodeGridMatrix),
        ["GRIDMATRIX"] = new(GridMatrixDecoder.ReadCandidates, ReencodeGridMatrix)
    };

    /// <summary>
    /// 값이 망가진 2D 바코드 객체의 값을 BMP에서 되살려 갈아 끼운다.
    /// 되살릴 수 없거나 왕복 검사를 통과하지 못하면 객체를 그대로 둔다.
    /// </summary>
    public static void Apply(DesignObject obj, byte[] data, int bmpStart, int bmpEnd)
    {
        var stored = obj.BarcodeValue ?? "";
        if (!stored.Contains('?', StringComparison.Ordinal)) return;

        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (!Symbologies.TryGetValue(id, out var symbology)) return;
        if (!AniLabelBarcodes.TryReadBmpMatrix(data, bmpStart, bmpEnd, out var modules)) return;

        foreach (var candidate in symbology.ReadCandidates(modules))
        {
            if (candidate.Length != stored.Length)
            {
                EditorLog.Warn(
                    $"애니라벨 2D 값 복구 기각: {id} 후보 \"{candidate}\"는 글자 수가 " +
                    $"저장값 \"{stored}\"와 다르다({candidate.Length} ≠ {stored.Length}) — 저장값 유지");
                continue;
            }

            var merged = MergeWithStored(stored, candidate);
            if (string.Equals(merged, stored, StringComparison.Ordinal)) continue;

            var diff = Difference(symbology.Reencode(obj, merged), modules);
            if (diff != 0)
            {
                EditorLog.Warn(
                    $"애니라벨 2D 값 복구 기각: {id} 후보 \"{merged}\" 다시 찍으니 " +
                    $"{(diff < 0 ? "크기가 다르다" : $"{diff}모듈 어긋난다")} — 저장값 \"{stored}\" 유지");
                continue;
            }

            EditorLog.Info(
                $"애니라벨 2D 값 복구: {id} \"{stored}\" → \"{merged}\" (다시 찍어 모듈 차이 0)");
            obj.BarcodeValue = merged;
            return;
        }
    }

    /// <summary>
    /// 살아남은 저장값 글자와 심볼에서 되읽은 글자를 자리마다 합친다. 애니라벨은 값을 ANSI로 옮길 때
    /// 못 옮기는 글자 자리에 '?' 한 자를 남기므로 글자 수가 그대로고 ASCII 글자는 살아 있다
    /// (32번 칸: 「상품명」 세 글자 → 3f3f3f 세 바이트). 그러니 '?' 자리만 심볼에서 채우면 된다.
    ///
    /// 합치지 않고 되읽은 값을 그대로 쓰면 안 된다. 애니라벨이 혼합 모드 빈칸에 'Q'와 똑같은 기호
    /// 값 26을 쓰기 때문에(12·21번 칸 실측) 빈칸과 'Q'는 심볼에서 구별되지 않는다 — 왕복 검사로도
    /// 가릴 수 없다. 우리 인코더로 「상품 1234 A」를 애니라벨 결함까지 재현해 찍고 되읽어 보면
    /// 「상품Q1234QA」가 나오는데, 그 값을 다시 찍으면 모듈이 한 칸도 안 틀린다. 저장값에 빈칸이
    /// 그대로 남아 있으니 그 자리는 저장값을 믿는다.
    ///
    /// '?' 자리에는 이 어긋남이 닿지 않는다 — 빈칸과 'Q'는 둘 다 ASCII라 '?'로 깎이지 않기 때문이고,
    /// 바이트 모드로 실린 UTF-8 글자는 심볼에서 한 가지로만 읽힌다. 합친 값은 다시 찍어 원본 행렬과
    /// 맞대 보므로, 통과하면 우리가 그리는 그림이 애니라벨의 그림과 완전히 같다.
    /// </summary>
    private static string MergeWithStored(string stored, string candidate)
    {
        var merged = stored.ToCharArray();
        for (var i = 0; i < merged.Length; i++)
        {
            if (merged[i] == '?') merged[i] = candidate[i];
        }
        return new string(merged);
    }

    /// <summary>
    /// BarcodeRenderer가 애니라벨 Grid Matrix를 그릴 때와 같은 인자다. 판형만 BMP 실측값을 넘기고,
    /// 오류정정 등급은 자동에 맡기고, 기호 값 결함 재현(혼합 모드 빈칸 26·바이트 블록 길이)을 켠다.
    /// 가져오기 경로는 애니라벨 바코드에 BarcodeVendor를 반드시 남기므로 결함 재현이 늘 켜진다.
    /// </summary>
    private static BitMatrix? ReencodeGridMatrix(DesignObject obj, string candidate)
    {
        var version = obj.QrVersion is > 0 and <= 13 ? obj.QrVersion : 0;
        var quirks = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel;
        return GridMatrixEncoder.Encode(candidate, version, 0, quirks);
    }

    /// <summary>다시 찍은 심볼과 원본 행렬이 다른 모듈 수. 크기가 다르거나 못 찍었으면 -1.</summary>
    private static int Difference(BitMatrix? replay, bool[,] modules)
    {
        if (replay is null) return -1;
        var rows = modules.GetLength(0);
        var cols = modules.GetLength(1);
        if (replay.Height != rows || replay.Width != cols) return -1;

        var diff = 0;
        for (var y = 0; y < rows; y++)
        {
            for (var x = 0; x < cols; x++)
            {
                if (replay[x, y] != modules[y, x]) diff++;
            }
        }
        return diff;
    }
}
