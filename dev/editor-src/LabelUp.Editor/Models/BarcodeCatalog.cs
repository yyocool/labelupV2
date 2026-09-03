using ZXing;

namespace LabelUp.Editor.Models;

/// <summary>폼텍·애니라벨·아이라벨에서 확인된 바코드/2D 심볼로지 목록과 값 제한.</summary>
public sealed record BarcodeSpec(
    string Id,
    string Label,
    string Hint,
    int? MinLen,
    int? MaxLen,
    BarcodeFormat? Zxing,
    bool Is2d = false);

public static class BarcodeCatalog
{
    public static readonly BarcodeSpec[] OneD =
    [
        new("CODE_128", "Code 128", "영문·숫자·기호, 권장 1~48자", 1, 80, BarcodeFormat.CODE_128),
        new("EAN_128", "EAN-128 / GS1-128", "GS1 AI 포함 문자열, 권장 1~48자", 1, 80, BarcodeFormat.CODE_128),
        new("CODE_39", "Code 39", "대문자·숫자·-.$/+% 공백, 권장 1~43자", 1, 43, BarcodeFormat.CODE_39),
        new("CODE_39_EXT", "Code 39 Extended", "영문 대소문자·숫자·기호, 권장 1~43자", 1, 43, BarcodeFormat.CODE_39),
        new("CODE_93", "Code 93", "대문자·숫자·기호, 권장 1~47자", 1, 47, BarcodeFormat.CODE_93),
        new("CODE_93_EXT", "Code 93 Extended", "영문 대소문자·숫자·기호, 권장 1~47자", 1, 47, BarcodeFormat.CODE_93),
        new("CODE_11", "Code 11", "숫자와 하이픈, 권장 1~20자", 1, 20, BarcodeFormat.CODE_39),
        new("CODABAR", "Codabar", "숫자와 A–D 시작/종료, -$/.:+ , 권장 1~20자", 1, 20, BarcodeFormat.CODABAR),
        new("ABC_CODABAR", "ABC Codabar", "Codabar과 동일 문자 집합, 권장 1~20자", 1, 20, BarcodeFormat.CODABAR),
        new("EAN_13", "EAN-13", "숫자만, 최소 12자 · 최대 13자 (체크디지트 포함)", 12, 13, BarcodeFormat.EAN_13),
        new("EAN_8", "EAN-8", "숫자만, 최소 7자 · 최대 8자", 7, 8, BarcodeFormat.EAN_8),
        new("EAN_5", "EAN-5", "숫자만, 5자", 5, 5, BarcodeFormat.CODE_128),
        new("EAN_2", "EAN-2", "숫자만, 2자", 2, 2, BarcodeFormat.CODE_128),
        new("JAN_13", "JAN-13", "숫자만, 최소 12자 · 최대 13자", 12, 13, BarcodeFormat.EAN_13),
        new("JAN_8", "JAN-8", "숫자만, 최소 7자 · 최대 8자", 7, 8, BarcodeFormat.EAN_8),
        new("ISBN", "ISBN / Bookland", "숫자만, 10자 또는 13자", 10, 13, BarcodeFormat.EAN_13),
        new("ISSN", "ISSN", "숫자만, 권장 8~13자", 8, 13, BarcodeFormat.EAN_13),
        new("ISMN", "ISMN", "숫자만, 권장 10~13자", 10, 13, BarcodeFormat.EAN_13),
        new("UPC_A", "UPC-A", "숫자만, 최소 11자 · 최대 12자", 11, 12, BarcodeFormat.UPC_A),
        new("UPC_E", "UPC-E", "숫자만, 최소 6자 · 최대 8자", 6, 8, BarcodeFormat.UPC_E),
        new("UPC_E0", "UPC-E0", "숫자만, 6~8자", 6, 8, BarcodeFormat.UPC_E),
        new("UPC_E1", "UPC-E1", "숫자만, 6~8자", 6, 8, BarcodeFormat.UPC_E),
        new("ITF", "ITF / Interleaved 2 of 5", "숫자만, 짝수 자리(2~30자)", 2, 30, BarcodeFormat.ITF),
        new("ITF_6", "ITF-6", "숫자만, 6자", 6, 6, BarcodeFormat.ITF),
        new("ITF_14", "ITF-14 / EAN-14", "숫자만, 최소 13자 · 최대 14자", 13, 14, BarcodeFormat.ITF),
        new("ITF_16", "ITF-16", "숫자만, 16자", 16, 16, BarcodeFormat.ITF),
        new("I25_INDUSTRIAL", "Code 25 Industrial", "숫자만, 권장 1~20자", 1, 20, BarcodeFormat.ITF),
        new("I25_MATRIX", "Code 25 Matrix", "숫자만, 권장 1~20자", 1, 20, BarcodeFormat.ITF),
        new("I25_DATALOGIC", "Code 25 Datalogic", "숫자만, 권장 1~20자", 1, 20, BarcodeFormat.ITF),
        new("I25_IATA", "IATA 2 of 5", "숫자만, 권장 1~20자", 1, 20, BarcodeFormat.ITF),
        new("I25_INVERT", "Code 25 Invert", "숫자만, 권장 1~20자", 1, 20, BarcodeFormat.ITF),
        new("COOP25", "Coop 2 of 5", "숫자만, 권장 1~20자", 1, 20, BarcodeFormat.ITF),
        new("MSI", "MSI / Plessey", "숫자만, 권장 1~20자", 1, 20, BarcodeFormat.MSI),
        new("PLESSEY", "Plessey", "숫자·A–F, 권장 1~16자", 1, 16, BarcodeFormat.PLESSEY),
        new("PZN", "PZN", "숫자만, 6~8자", 6, 8, BarcodeFormat.CODE_39),
        new("CODE_32", "Code 32 (Italian Pharmacode)", "숫자만, 8자", 8, 8, BarcodeFormat.CODE_39),
        new("PHARMA_1", "Pharmacode One-track", "숫자만, 1~6자", 1, 6, BarcodeFormat.PHARMA_CODE),
        new("PHARMA_2", "Pharmacode Two-track", "숫자만, 1~8자", 1, 8, BarcodeFormat.PHARMA_CODE),
        new("POSTNET", "POSTNET", "숫자만, 5 / 9 / 11자", 5, 11, BarcodeFormat.CODE_128),
        new("PLANET", "PLANET", "숫자만, 11 또는 13자", 11, 13, BarcodeFormat.CODE_128),
        new("RM4SCC", "RM4SCC", "영문·숫자, 권장 1~20자", 1, 20, BarcodeFormat.CODE_128),
        new("KIX", "KIX / Kix4s", "영문·숫자, 권장 1~20자", 1, 20, BarcodeFormat.CODE_128),
        new("JAPAN_POST", "Japan Post", "숫자·하이픈·영문, 권장 7~20자", 7, 20, BarcodeFormat.CODE_128),
        new("ONECODE", "USPS OneCode / IMB", "숫자만, 20 / 25 / 29 / 31자", 20, 31, BarcodeFormat.IMB),
        new("LEITCODE", "Leitcode", "숫자만, 13자", 13, 13, BarcodeFormat.ITF),
        new("IDENTCODE", "Identcode", "숫자만, 11자", 11, 11, BarcodeFormat.ITF),
        new("FIM", "FIM", "숫자만, 1~4자", 1, 4, BarcodeFormat.CODE_128),
        new("TELEPEN", "Telepen", "영문·숫자, 권장 1~30자", 1, 30, BarcodeFormat.CODE_128),
        new("UPU", "UPU", "영문·숫자, 예: EE123456781CN (13자)", 13, 13, BarcodeFormat.CODE_128),
        new("KOREAN_POST", "Korean PostCode", "숫자만, 권장 6~10자", 6, 10, BarcodeFormat.CODE_128),
        new("OPC", "OPC / Optical Product", "숫자만, 권장 8~14자", 8, 14, BarcodeFormat.EAN_13),
        new("RSS_14", "GS1 DataBar (RSS-14)", "숫자만, 14자", 14, 14, BarcodeFormat.RSS_14),
        new("RSS_EXPANDED", "GS1 DataBar Expanded", "숫자·AI, 권장 1~74자", 1, 74, BarcodeFormat.RSS_EXPANDED)
    ];

    public static readonly BarcodeSpec[] TwoD =
    [
        new("QR_CODE", "QR Code", "텍스트·URL 등, 권장 1~1000자", 1, 2000, BarcodeFormat.QR_CODE, true),
        new("DATA_MATRIX", "Data Matrix", "영문·숫자, 권장 1~500자", 1, 1556, BarcodeFormat.DATA_MATRIX, true),
        new("PDF_417", "PDF417", "텍스트, 권장 1~1000자", 1, 1800, BarcodeFormat.PDF_417, true),
        new("PDF_417_TRUNC", "PDF417 Truncated", "텍스트, 권장 1~1000자", 1, 1800, BarcodeFormat.PDF_417, true),
        new("MICRO_PDF417", "Micro PDF417", "텍스트, 권장 1~150자", 1, 150, BarcodeFormat.PDF_417, true),
        new("AZTEC", "Aztec", "텍스트, 권장 1~300자", 1, 300, BarcodeFormat.AZTEC, true)
    ];

    public static BarcodeSpec? Find(string? id)
        => OneD.Concat(TwoD).FirstOrDefault(s => string.Equals(s.Id, id, StringComparison.OrdinalIgnoreCase));

    public static string HintFor(string? id)
    {
        var spec = Find(id);
        if (spec is null) return "값 길이 제한은 심볼로지에 따라 다릅니다.";
        return spec.Hint;
    }

    public static string ValueFieldLabel(string? id)
    {
        var spec = Find(id);
        if (spec is null) return "값";
        return $"값 (ex) {spec.Label}은 {spec.Hint}";
    }
}
