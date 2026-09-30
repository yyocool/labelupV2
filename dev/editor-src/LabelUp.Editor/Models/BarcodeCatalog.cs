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
        new("CODE_39", "Code 39", "대문자·숫자·-.$/+% 공백, 권장 1~80자", 1, 80, BarcodeFormat.CODE_39),
        new("CODE_39_EXT", "Code 39 Extended", "영문 대소문자·숫자·기호, 권장 1~80자", 1, 80, BarcodeFormat.CODE_39),
        new("CODE_93", "Code 93", "대문자·숫자·기호, 권장 1~47자", 1, 47, BarcodeFormat.CODE_93),
        new("CODE_93_EXT", "Code 93 Extended", "영문 대소문자·숫자·기호, 권장 1~47자", 1, 47, BarcodeFormat.CODE_93),
        new("CODE_11", "Code 11", "숫자와 하이픈, 권장 1~20자", 1, 20, null),
        new("CODABAR", "Codabar", "숫자와 A–D 시작/종료, -$/.:+ , 권장 1~20자", 1, 20, BarcodeFormat.CODABAR),
        new("ABC_CODABAR", "ABC Codabar", "Codabar과 동일 문자 집합, 권장 1~20자", 1, 20, BarcodeFormat.CODABAR),
        new("EAN_13", "EAN-13", "숫자만, 최소 12자 · 최대 13자 (체크디지트 포함)", 12, 13, BarcodeFormat.EAN_13),
        new("EAN_8", "EAN-8", "숫자만, 최소 7자 · 최대 8자", 7, 8, BarcodeFormat.EAN_8),
        new("EAN_5", "EAN-5", "숫자만, 5자", 5, 5, null),
        new("EAN_2", "EAN-2", "숫자만, 2자", 2, 2, null),
        new("JAN_13", "JAN-13", "숫자만, 최소 12자 · 최대 13자", 12, 13, BarcodeFormat.EAN_13),
        new("JAN_8", "JAN-8", "숫자만, 최소 7자 · 최대 8자", 7, 8, BarcodeFormat.EAN_8),
        new("ISBN", "ISBN / Bookland", "EAN-13 막대 + 하이픈 숫자. 예: 978-89-5674-316-9", 10, 13, BarcodeFormat.EAN_13),
        new("ISSN", "ISSN", "EAN-13 막대 + 하이픈 숫자. 예: 1227-116000", 8, 13, BarcodeFormat.EAN_13),
        new("ISMN", "ISMN", "EAN-13 막대 + 하이픈 숫자. 예: 80-7226-102-9", 10, 13, BarcodeFormat.EAN_13),
        new("UPC_A", "UPC-A", "숫자만, 최소 11자 · 최대 12자", 11, 12, BarcodeFormat.UPC_A),
        new("UPC_E", "UPC-E", "숫자만, 최소 6자 · 최대 8자", 6, 8, BarcodeFormat.UPC_E),
        new("UPC_E0", "UPC-E0", "숫자만, 6~8자", 6, 8, BarcodeFormat.UPC_E),
        new("UPC_E1", "UPC-E1", "숫자만, 6~8자", 6, 8, BarcodeFormat.UPC_E),
        new("ITF", "ITF / Interleaved 2 of 5", "숫자만, 짝수 자리(2~30자)", 2, 30, BarcodeFormat.ITF),
        new("ITF_6", "ITF-6", "숫자만, 6자", 6, 6, BarcodeFormat.ITF),
        new("ITF_14", "ITF-14 / EAN-14", "숫자만, 최소 13자 · 최대 14자", 13, 14, BarcodeFormat.ITF),
        new("ITF_16", "ITF-16", "숫자만, 16자", 16, 16, BarcodeFormat.ITF),
        new("I25_INDUSTRIAL", "Code 25 Industrial", "숫자만, 권장 1~20자", 1, 20, null),
        new("I25_MATRIX", "Code 25 Matrix", "숫자만, 권장 1~20자", 1, 20, null),
        new("I25_DATALOGIC", "Code 25 Datalogic", "숫자만, 권장 1~20자", 1, 20, null),
        new("I25_IATA", "IATA 2 of 5", "숫자만, 권장 1~20자", 1, 20, null),
        new("I25_INVERT", "Code 25 Invert", "숫자만, 권장 1~20자", 1, 20, null),
        new("COOP25", "Coop 2 of 5", "숫자만, 권장 1~20자", 1, 20, null),
        new("MSI", "MSI / Plessey", "숫자만, 권장 1~20자", 1, 20, BarcodeFormat.MSI),
        new("PLESSEY", "Plessey", "숫자·A–F, 권장 1~16자", 1, 16, BarcodeFormat.PLESSEY),
        new("PZN", "PZN", "Code 39. 입력 숫자에 `-`와 mod-11 체크를 붙임. 예: 123456 → -1234562", 6, 8, BarcodeFormat.CODE_39),
        new("CODE_32", "Code 32 (Italian Pharmacode)", "숫자만, 8자", 8, 8, BarcodeFormat.CODE_39),
        new("PHARMA_1", "Pharmacode One-track", "숫자만, 1~6자", 1, 6, BarcodeFormat.PHARMA_CODE),
        new("PHARMA_2", "Pharmacode Two-track", "숫자만, 1~8자", 1, 8, BarcodeFormat.PHARMA_CODE),
        new("POSTNET", "POSTNET", "숫자만, 5 / 9 / 11자", 5, 11, null),
        new("PLANET", "PLANET", "숫자만, 11 또는 13자", 11, 13, null),
        new("RM4SCC", "RM4SCC", "영문·숫자, 권장 1~20자", 1, 20, BarcodeFormat.CODE_128),
        new("KIX", "KIX / Kix4s", "영문·숫자, 권장 1~20자", 1, 20, BarcodeFormat.CODE_128),
        new("JAPAN_POST", "Japan Post", "숫자·하이픈·영문, 권장 7~20자", 7, 20, null),
        new("ONECODE", "USPS OneCode / IMB", "숫자만, 20 / 25 / 29 / 31자", 20, 31, null),
        new("AUSPOST", "Australia Post", "FCC 2자리 + 배달구역 8자리 (+ 고객정보 최대 13자)", 10, 23, null),
        new("PATCH_CODE", "Patch Code (코닥)", "1 · 2 · 3 · 4 · 6 · T 중 하나. 문서 구분용 표시라 자료를 싣지 않는다", 1, 1, null),
        new("LEITCODE", "Leitcode", "숫자만, 13자", 13, 13, BarcodeFormat.ITF),
        new("IDENTCODE", "Identcode", "숫자만, 11자", 11, 11, BarcodeFormat.ITF),
        new("FIM", "FIM", "A/B/C/D 또는 1~4", 1, 4, null),
        new("TELEPEN", "Telepen", "ASCII 전체, 권장 1~30자", 1, 30, null),
        new("UPU", "UPU", "영문·숫자, 예: EE123456781CN (13자)", 13, 13, BarcodeFormat.CODE_128),
        new("KOREAN_POST", "Korean PostCode", "한국 우체국 우편번호. 숫자 5자리(체크 자동)", 1, 6, BarcodeFormat.CODE_128),
        new("OPC", "OPC / Optical Product", "숫자 9자리. ITF 막대 + Luhn 체크 자동", 8, 14, BarcodeFormat.ITF),
        // 막대 폭이 모두 같고 사이 간격만 달라지는 계열. 애니라벨 신형 0x25~0x27 이 쓴다.
        new("CHANNEL_CODE", "Channel Code", "숫자만, 1~7자. 값 0~7742862", 1, 7, null),
        new("BC309", "BC309", "숫자만, 권장 1~20자", 1, 20, null),
        new("BC412", "BC412 (SEMI T1-95)", "숫자·대문자(O 제외), 권장 1~18자", 1, 18, null),
        new("FLATTERMARKEN", "Flattermarken", "숫자만, 권장 1~128자. 제책용 접지 표시라 스캐너로 읽는 코드가 아니다", 1, 128, null),
        new("CPC_BINARY", "CPC Binary (캐나다우정)", "캐나다 우편번호 여섯 글자 (예: L3B4T9)", 6, 6, null),
        new("POSTBAR", "POST Bar (캐나다우정 4상태)", "형식문자 1 + 우편번호 6 + 주소코드 4 = 11자 (예: BK1A4S21234)", 11, 11, null),
        // 공개 규격이 없어 애니라벨 0x28 표본 세 개로 역산했다(문서 10.5절).
        // 이름 그대로 「시계막대 + 다섯 자리 중 셋」이라 숫자만 담는다.
        new("CLOCKED_35", "Clocked-35", "숫자만. 글자마다 시계막대 하나와 다섯 자리 중 세 자리 막대", 1, 64, null),
        new("NUMLY", "Numly / ESN", "19자리 전자일련번호. Code 39 막대 + ESN 캡션", 19, 19, BarcodeFormat.CODE_39),
        new("RSS_14", "GS1 DataBar (RSS-14)", "숫자만, 14자", 14, 14, BarcodeFormat.RSS_14),
        // RSS 계열은 ZXing에 인코더가 없어 GS1DataBarEncoder가 그린다.
        // Zxing 값은 2D 격자 경로를 타게 하려고 남겨 둔다.
        new("RSS_LIMITED", "GS1 DataBar Limited", "숫자만 14자. 첫 자리는 0 또는 1", 14, 14, BarcodeFormat.RSS_14),
        new("RSS_EXPANDED", "GS1 DataBar Expanded", "숫자·AI, 권장 1~74자", 1, 74, BarcodeFormat.RSS_EXPANDED)
    ];

    public static readonly BarcodeSpec[] TwoD =
    [
        new("QR_CODE", "QR Code", "텍스트·URL 등, 권장 1~1000자", 1, 2000, BarcodeFormat.QR_CODE, true),
        new("DATA_MATRIX", "Data Matrix", "영문·숫자, 권장 1~500자", 1, 1556, BarcodeFormat.DATA_MATRIX, true),
        new("PDF_417", "PDF417", "텍스트, 권장 1~1000자", 1, 1800, BarcodeFormat.PDF_417, true),
        new("PDF_417_TRUNC", "PDF417 Truncated", "텍스트, 권장 1~1000자", 1, 1800, BarcodeFormat.PDF_417, true),
        new("MICRO_PDF417", "Micro PDF417", "텍스트, 권장 1~150자", 1, 150, BarcodeFormat.PDF_417, true),
        new("AZTEC", "Aztec", "텍스트, 권장 1~300자", 1, 300, BarcodeFormat.AZTEC, true),
        // 아래 셋은 ZXing에 인코더가 없어 전용 인코더가 그린다.
        // Zxing 값은 2D 격자 경로를 타게 하려고 모양이 비슷한 것으로 남겨 둔다.
        new("MICRO_QR", "Micro QR Code", "숫자·영문, 권장 1~35자", 1, 35, BarcodeFormat.QR_CODE, true),
        new("CODE_16K", "Code 16K", "영문·숫자·기호, 권장 1~77자", 1, 77, BarcodeFormat.PDF_417, true),
        // 한신코드는 GB 18030 한자 모드를 뺀 라틴1 범위만 그린다.
        new("HANXIN", "Han Xin Code", "라틴1 텍스트, 권장 1~500자", 1, 3264, BarcodeFormat.QR_CODE, true),
        new("GRID_MATRIX", "Grid Matrix", "영문·숫자·GB 2312, 권장 1~500자", 1, 1313, BarcodeFormat.QR_CODE, true),
        // MaxiCode는 정사각 모듈이 아니라 정육각형 벌집이라 2D 격자 경로에 태울 수 없다.
        // BarcodeRenderer.TryDrawMaxiCode가 끝까지 그리므로 Zxing 값을 비워 둔다.
        new("MAXICODE", "MaxiCode", "라틴1 텍스트, 권장 1~77자", 1, 93, null, true),
        // 규격서(GB/T 27767-2011)를 구하지 못해 인코더가 없다. 모양이 닮은 다른 심볼로
        // 바꿔치기하면 읽히는 값이 달라지므로, 대체하지 않고 미지원임을 드러낸다.
        new("COMPACT_MATRIX", "Compact Matrix (미지원)", "GB/T 27767 규격 미확보로 그리지 못한다", 1, 4096, null, true)
    ];

    /// <summary>
    /// 새로 만들 때 고를 수 있는 심볼로지. 공개 규격이 있고 그 규격대로 인코딩하는 것,
    /// 그중에서도 소매·산업·문헌·의약·우편처럼 실제로 쓰이는 계열만 둔다.
    ///
    /// 여기 없는 타입도 <see cref="OneD"/>·<see cref="TwoD"/>에 그대로 남아 있고 렌더러도 그린다.
    /// 타사 파일을 변환해 들어온 개체가 그 타입이면 그 개체의 목록에만 나타난다.
    /// 빠진 이유는 넷이다.
    ///   1) 공개 규격이 없어 타사 표본으로 역산: Clocked-35, BC309, CPC Binary, POST Bar
    ///   2) 규격과 다르게 찍음: BC412(SEMI T1-95 mod-35 검사문자를 타사에 맞춰 뺐다)
    ///   3) 자료를 싣지 않는 표시류: Flattermarken(제책 접지), Patch Code(문서 구분)
    ///   4) 규격 미확보: Compact Matrix(GB/T 27767)
    /// 나머지(JAN·UPC-E0/E1·ITF-6/16·EAN-2/5·Code 25 변형·ABC Codabar·OPC·Numly·
    /// Channel Code·Code 16K·Han Xin·Grid Matrix·PDF417 Truncated)는 그릴 수는 있으나
    /// 이미 있는 타입과 겹치거나 쓰임이 특정 타사 파일에 한정돼 목록에서 뺐다.
    /// </summary>
    private static readonly HashSet<string> SelectableIds = new(StringComparer.Ordinal)
    {
        // 소매·유통(GS1)
        "CODE_128", "EAN_128", "EAN_13", "EAN_8", "UPC_A", "UPC_E", "ITF", "ITF_14",
        "RSS_14", "RSS_LIMITED", "RSS_EXPANDED",
        // 일반 산업
        "CODE_39", "CODE_39_EXT", "CODE_93", "CODE_93_EXT", "CODE_11", "CODABAR",
        "MSI", "PLESSEY", "TELEPEN",
        // 문헌
        "ISBN", "ISSN", "ISMN",
        // 의약
        "PZN", "CODE_32", "PHARMA_1", "PHARMA_2",
        // 우편·물류
        "POSTNET", "PLANET", "ONECODE", "RM4SCC", "KIX", "JAPAN_POST", "AUSPOST",
        "KOREAN_POST", "LEITCODE", "IDENTCODE", "UPU", "FIM",
        // 2D
        "QR_CODE", "MICRO_QR", "DATA_MATRIX", "PDF_417", "MICRO_PDF417", "AZTEC", "MAXICODE"
    };

    /// <summary>`CODE-128`처럼 하이픈으로 온 이름도 카탈로그 아이디로 맞춘다.</summary>
    private static string Normalize(string? id)
        => (id ?? "").Trim().Replace('-', '_').ToUpperInvariant();

    /// <summary>새로 만들 때 고를 수 있는 타입인지. 아니면 타사 변환 개체에서만 나온다.</summary>
    public static bool IsSelectable(string? id)
        => SelectableIds.Contains(Normalize(id));

    /// <summary>
    /// 목록에 낼 1D 심볼로지. <paramref name="current"/>가 변환으로 들어온 타입이면
    /// 그 항목도 함께 낸다. 목록에 없으면 선택이 첫 항목으로 튀어 값이 다른 막대가 되기 때문이다.
    /// </summary>
    public static IReadOnlyList<BarcodeSpec> SelectableOneD(string? current = null)
        => Selectable(OneD, current);

    /// <summary>목록에 낼 2D 심볼로지. 규칙은 <see cref="SelectableOneD"/>와 같다.</summary>
    public static IReadOnlyList<BarcodeSpec> SelectableTwoD(string? current = null)
        => Selectable(TwoD, current);

    private static IReadOnlyList<BarcodeSpec> Selectable(BarcodeSpec[] all, string? current)
    {
        var keep = Normalize(current);
        return all
            .Where(s => SelectableIds.Contains(s.Id) || s.Id == keep)
            .ToList();
    }

    /// <summary>목록에 쓰는 이름. 변환으로만 들어오는 타입은 그렇다고 밝힌다.</summary>
    public static string OptionLabel(BarcodeSpec spec)
        => SelectableIds.Contains(spec.Id) ? spec.Label : $"{spec.Label} · 변환 전용";

    public static BarcodeSpec? Find(string? id)
    {
        var key = Normalize(id);
        return key.Length == 0
            ? null
            : OneD.Concat(TwoD).FirstOrDefault(s => s.Id == key);
    }

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

    public static string Digits(string? raw)
        => new((raw ?? "").Where(char.IsAsciiDigit).ToArray());

    /// <summary>ISBN/ISSN/ISMN은 EAN-13 막대에 하이픈 캡션을 쓰는 Bookland 형태.</summary>
    public static bool IsBookland(string? format)
    {
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        return id is "ISBN" or "ISSN" or "ISMN";
    }

    public static bool IsNumly(string? format)
    {
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        return id is "NUMLY" or "ESBN" or "ESN" or "NUMLY_NUMBER";
    }

    /// <summary>
    /// 가드 막대를 쓰는 심볼로지. 소매(EAN·UPC)는 시작·가운데·끝 가드가 캡션 칸까지 내려오고,
    /// Codabar·Code 93은 시작·정지 자리의 막대가 같은 식으로 길어진다(애니라벨 실측).
    /// Code 128·Code 39·ITF 등에는 가드 막대라는 개념이 없어 렌더러가 항목을 보지 않는다.
    /// </summary>
    public static bool UsesGuardBars(string? format)
    {
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        return id is "EAN_13" or "EAN13" or "EAN_8" or "EAN8"
            or "JAN_13" or "JAN13" or "JAN_8" or "JAN8"
            or "ISBN" or "ISSN" or "ISMN"
            or "UPC_A" or "UPCA" or "UPC_E" or "UPC_E0" or "UPC_E1"
            or "CODABAR" or "ABC_CODABAR" or "CODE_93" or "CODE93" or "CODE_93_EXT";
    }

    /// <summary>
    /// 사람이 읽는 글에 시작·정지 문자 `*`를 보일 수 있는 심볼로지.
    /// ISO/IEC 16388 4.3.3은 Code 39의 시작·정지 문자를 보통 `*`로 나타낸다고 하며, 표시 여부는 선택이다.
    /// GS1 캐리어(EAN·UPC·GS1-128·ITF-14)의 HRI에는 시작·정지 문자를 넣지 않는다.
    /// PZN·Code 32·UPU는 캡션 모양이 따로 정해져 있어 이 항목을 보지 않는다.
    /// </summary>
    public static bool UsesStartStopCaption(string? format)
    {
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        return id is "CODE_39" or "CODE39" or "CODE_39_EXT";
    }

    /// <summary>
    /// 베어러(막대를 두르는 테두리)를 쓰는 심볼로지. ITF 계열에서 시작·끝 표시는 `*`가 아니라 테두리로 나타난다.
    /// </summary>
    public static bool UsesBearerBars(string? format)
    {
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        return id is "ITF" or "ITF_6" or "ITF_14" or "ITF_16" or "LEITCODE" or "IDENTCODE";
    }

    /// <summary>하이픈이 있는 978/979만 ISBN(Bookland). 숫자만 있는 978은 일반 EAN-13이다.</summary>
    public static bool LooksLikeIsbn(string? raw)
    {
        if (raw?.Contains('-') != true) return false;
        var digits = Digits(raw);
        return digits.Length is 12 or 13
            && (digits.StartsWith("978") || digits.StartsWith("979"));
    }

    public static string FormatIsbn(string? raw) => FormatBookland("ISBN", raw);

    /// <summary>폼텍이 저장한 하이픈을 그대로 쓴다. 없으면 ISBN-13만 기본 그룹한다.</summary>
    public static string FormatBookland(string? format, string? raw)
    {
        if (!string.IsNullOrWhiteSpace(raw) && raw.Contains('-') && raw.Any(char.IsAsciiDigit))
            return raw.Trim();
        var d = Digits(raw);
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is "ISBN" or "" && d.Length == 10)
            return $"{d[0]}-{d[1..5]}-{d[5..9]}-{d[9]}";
        if (id is "ISBN" or "" && d.Length == 13 && (d.StartsWith("978") || d.StartsWith("979")))
            return $"{d[..3]}-{d[3..5]}-{d[5..9]}-{d[9..12]}-{d[12]}";
        return raw?.Trim() ?? "";
    }

    /// <summary>아이라벨 ISBNAutoCaption. 9자리 123456789 → ISBN 1-2345678-9-X.</summary>
    public static string FormatILabelIsbnCaption(string? raw)
    {
        var d = Digits(raw);
        if (d.Length == 13 && (d.StartsWith("978") || d.StartsWith("979")))
            return "ISBN " + FormatBookland("ISBN", d);
        if (d.Length >= 9)
        {
            var body = d[..9];
            var check = d.Length >= 10 ? d[9].ToString() : Isbn10Check(body);
            return $"ISBN {body[0]}-{body[1..8]}-{body[8]}-{check}";
        }
        if (d.Length > 0)
            return "ISBN " + d;
        return "ISBN";
    }

    /// <summary>아이라벨 Numly. 19자리 1234567890123456789 → ESN 12345-678901-234567-89.</summary>
    public static string FormatILabelNumlyCaption(string? raw)
    {
        var d = Digits(raw);
        if (d.Length < 19)
            d = d.PadLeft(19, '0');
        if (d.Length > 19)
            d = d[..19];
        return $"ESN {d[..5]}-{d[5..11]}-{d[11..17]}-{d[17..]}";
    }

    private static string Isbn10Check(string nine)
    {
        var sum = 0;
        for (var i = 0; i < 9; i++)
            sum += (nine[i] - '0') * (10 - i);
        var n = (11 - sum % 11) % 11;
        return n == 10 ? "X" : n.ToString();
    }
}
