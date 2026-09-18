using System.Globalization;
using LabelUp.Editor.Models;
using LabelUp.Editor.Rendering;

namespace LabelUp.Editor.Services;

/// <summary>
/// 애니라벨 1D/2D 바코드. md_anylabel 타입·데이터·모양 분석 확정값.
/// </summary>
internal static class AniLabelBarcodes
{
    public const uint Marker = 0x00002711;

    public static bool TryRead1D(byte[] data, int payloadStart, int payloadEnd, out uint type, out string value, out string format)
    {
        type = 0;
        value = "";
        format = "CODE_128";
        var marker = ExternalImportService.FindU32(data, payloadStart, Math.Min(payloadEnd, payloadStart + 8192), Marker);
        if (marker < 0) return false;
        if (marker + 0x1D + 1 > payloadEnd) return false;
        type = BitConverter.ToUInt32(data, marker + 0x15);
        var len = BitConverter.ToInt32(data, marker + 0x19);
        if (type > 0x33 || len is < 1 or > 512 || marker + 0x1D + len > payloadEnd) return false;
        var slice = data.AsSpan(marker + 0x1D, len);
        if (!IsPrintableData(slice)) return false;
        value = System.Text.Encoding.Latin1.GetString(slice);
        format = Map1D(type);
        return true;
    }

    /// <summary>구형 0x07 바코드. marker+0x1E uint8 Type, +0x1F DataLength, +0x23 Data.</summary>
    public static bool TryRead1DLegacy(
        byte[] data, int payloadStart, int payloadEnd, out string value, out string format, out byte rawType)
    {
        value = "";
        format = "CODE_128";
        rawType = 0xFF;
        var marker = ExternalImportService.FindU32(data, payloadStart, Math.Min(payloadEnd, payloadStart + 8192), Marker);
        if (marker < 0 || marker + 0x23 + 1 > payloadEnd) return false;
        var type = data[marker + 0x1E];
        var len = BitConverter.ToInt32(data, marker + 0x1F);
        if (len is < 1 or > 512 || marker + 0x23 + len > payloadEnd) return false;
        var slice = data.AsSpan(marker + 0x23, len);
        if (!IsPrintableData(slice)) return false;
        value = System.Text.Encoding.Latin1.GetString(slice);
        format = MapLegacy(type);
        rawType = type;
        return true;
    }

    private static bool IsPrintableData(ReadOnlySpan<byte> slice)
    {
        var printable = 0;
        foreach (var b in slice)
        {
            if (b is >= 32 and <= 126 or >= 160) printable++;
        }
        return printable * 2 >= slice.Length;
    }

    /// <summary>
    /// 0x1B 옵션. data 뒤 u32 막대색, u32 배경색, u8 HRI 표시. 배경 투명 플래그는 없다.
    /// </summary>
    public static void Apply1DStyle(DesignObject obj, byte[] data, int payloadStart, int payloadEnd)
    {
        var marker = ExternalImportService.FindU32(data, payloadStart, Math.Min(payloadEnd, payloadStart + 8192), Marker);
        if (marker < 0) return;
        var len = marker + 0x19 + 4 <= payloadEnd ? BitConverter.ToInt32(data, marker + 0x19) : -1;
        if (len is < 0 or > 512) return;
        var opt = marker + 0x1D + len;
        if (opt + 9 > payloadEnd) return;
        ApplyBarColor(obj, BitConverter.ToUInt32(data, opt));
        ApplyBackColor(obj, BitConverter.ToUInt32(data, opt + 4), transparent: false);
        obj.BarcodeShowText = data[opt + 8] != 0;
        ApplyHriFont(obj, data, opt + 9, payloadEnd);
    }

    /// <summary>
    /// 구형 0x07(PSOFT) 옵션. 「바코드 모양.lbl」 23종 비교로 확정.
    /// data 뒤 u8 HRI 표시, u8 배경 투명, u32 배경색, u32 막대색 순서다.
    /// 0x1B와 달리 색 순서가 배경 → 막대이고, 배경 투명 플래그가 따로 있다.
    /// </summary>
    public static void Apply1DLegacyStyle(DesignObject obj, byte[] data, int payloadStart, int payloadEnd)
    {
        var marker = ExternalImportService.FindU32(data, payloadStart, Math.Min(payloadEnd, payloadStart + 8192), Marker);
        if (marker < 0 || marker + 0x23 > payloadEnd) return;
        var len = BitConverter.ToInt32(data, marker + 0x1F);
        if (len is < 1 or > 512) return;
        var opt = marker + 0x23 + len;
        if (opt + 10 > payloadEnd) return;
        obj.BarcodeShowText = data[opt] != 0;
        ApplyBackColor(obj, BitConverter.ToUInt32(data, opt + 2), data[opt + 1] != 0);
        ApplyBarColor(obj, BitConverter.ToUInt32(data, opt + 6));
        ApplyHriFont(obj, data, opt + 10, payloadEnd);
    }

    /// <summary>
    /// 그려 둔 BMP를 재서 2D 심볼의 모양 매개변수를 객체에 옮긴다.
    /// 값과 심볼로지만으로는 인코더마다 크기·오류정정 수준·마스크가 달라져 모양이 어긋난다.
    /// </summary>
    public static void ApplyBmp2DShape(DesignObject obj, byte[] data, int bmpStart, int bmpEnd)
    {
        if (!TryReadBmpMatrix(data, bmpStart, bmpEnd, out var m)) return;
        var rows = m.GetLength(0);
        var cols = m.GetLength(1);
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();

        switch (id)
        {
            case "QR_CODE":
                ApplyQrShape(obj, m, rows, cols);
                break;
            case "MICRO_QR":
                ApplyMicroQrShape(obj, m, rows, cols);
                break;
            case "HANXIN":
                ApplyHanXinShape(obj, m, rows, cols);
                break;
            case "GRID_MATRIX":
                // 한 변이 6 + 12×판형이다(AIMD014 표 10). 우리 부호화가 한 비트 짧아
                // 판형이 한 단계 작아지는 경우가 있어 실측값으로 못 박는다.
                if (rows == cols && rows >= 18 && (rows - 6) % 12 == 0)
                    obj.QrVersion = (rows - 6) / 12;
                break;
            case "DATA_MATRIX":
                obj.QrKind = $"{cols}x{rows}";
                break;
            case "PDF_417":
            case "MICRO_PDF417":
                ApplyStackedShape(obj, m, rows, cols);
                break;
        }
    }

    /// <summary>
    /// QR은 한 변에서 버전을, 형식정보 15비트에서 오류정정 수준과 마스크를 얻는다.
    /// 세 값이 다 같아야 같은 그림이 나온다(ISO/IEC 18004 §8.9).
    /// </summary>
    private static void ApplyQrShape(DesignObject obj, bool[,] m, int rows, int cols)
    {
        if (rows != cols || rows < 21 || (rows - 17) % 4 != 0) return;
        var version = (rows - 17) / 4;
        if (version is < 1 or > 40) return;
        obj.QrVersion = version;

        // 형식정보 사본 1. f14는 (8,0), f8~f7은 (8,7)·(8,8), f6은 (7,8), f5~f0은 (5,8)~(0,8)이다.
        var format = 0;
        for (var i = 0; i <= 5; i++) format = (format << 1) | (m[8, i] ? 1 : 0);
        format = (format << 1) | (m[8, 7] ? 1 : 0);
        format = (format << 1) | (m[8, 8] ? 1 : 0);
        format = (format << 1) | (m[7, 8] ? 1 : 0);
        for (var i = 5; i >= 0; i--) format = (format << 1) | (m[i, 8] ? 1 : 0);

        var bits = format ^ 0x5412;
        obj.QrEcc = ((bits >> 13) & 0x03) switch
        {
            0b01 => "L",
            0b00 => "M",
            0b11 => "Q",
            _ => "H"
        };
        obj.QrKind = $"MASK:{(bits >> 10) & 0x07}";
    }

    /// <summary>
    /// Micro QR은 한 변에서 M1~M4를, 형식정보 15비트에서 오류정정 수준과 마스크를 얻는다.
    /// 형식정보는 8열을 1행부터 8행까지 내려간 뒤 8행을 7열부터 1열까지 훑은 순서로 놓인다.
    /// </summary>
    private static void ApplyMicroQrShape(DesignObject obj, bool[,] m, int rows, int cols)
    {
        if (rows != cols || rows is < 11 or > 17 || (rows - 9) % 2 != 0) return;

        var code = 0;
        for (var i = 14; i >= 0; i--)
            code = (code << 1) | ((i < 8 ? m[i + 1, 8] : m[8, 15 - i]) ? 1 : 0);

        var symbol = ((code ^ 0x4445) >> 12) & 0x07;
        // 심볼 번호 0=M1, 1=M2-L, 2=M2-M, 3=M3-L, 4=M3-M, 5=M4-L, 6=M4-M, 7=M4-Q.
        var version = new[] { 1, 2, 2, 3, 3, 4, 4, 4 }[symbol];
        if (version != (rows - 9) / 2) return;

        obj.QrVersion = version;
        obj.QrEcc = symbol switch
        {
            1 or 3 or 5 => "L",
            2 or 4 or 6 => "M",
            7 => "Q",
            _ => ""          // M1은 오류정정 등급 구분이 없다
        };
        obj.QrKind = $"MASK:{((code ^ 0x4445) >> 10) & 0x03}";
    }

    /// <summary>
    /// 한신코드 구조 정보 앞 12비트는 판형+20(8비트)·등급-1(2비트)·마스크(2비트)다(GB/T 21049 표 2).
    /// 왼쪽 위 귀퉁이 사본만 읽는다. 8행을 0열부터 훑은 뒤 8열을 8행에서 위로 거슬러 올라간다.
    /// </summary>
    private static void ApplyHanXinShape(DesignObject obj, bool[,] m, int rows, int cols)
    {
        if (rows != cols || rows is < 23 or > 189 || (rows - 21) % 2 != 0) return;

        var head = 0;
        for (var i = 0; i < 8; i++) head = (head << 1) | (m[8, i] ? 1 : 0);
        var version = head - 20;
        if (version != (rows - 21) / 2) return;

        var ecc = (((m[8, 8] ? 1 : 0) << 1) | (m[7, 8] ? 1 : 0)) + 1;
        var mask = ((m[6, 8] ? 1 : 0) << 1) | (m[5, 8] ? 1 : 0);

        obj.QrVersion = version;
        obj.QrEcc = $"L{ecc}";
        obj.QrKind = $"MASK:{mask}";
    }

    /// <summary>
    /// PDF417 계열은 한 행이 몇 모듈인지로 열 수를, 같은 행이 몇 번 반복되는지로 행 수를 얻는다.
    /// 표준 PDF417 한 행은 시작 17 + 왼쪽 17 + 자료 17×열 + 오른쪽 17 + 정지 18 모듈이다.
    /// </summary>
    private static void ApplyStackedShape(DesignObject obj, bool[,] m, int rows, int cols)
    {
        var distinct = 1;
        for (var r = 1; r < rows; r++)
        {
            var same = true;
            for (var c = 0; c < cols && same; c++)
                same = m[r, c] == m[r - 1, c];
            if (!same) distinct++;
        }
        if (distinct is < 1 or > 90) return;

        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is "MICRO_PDF417")
        {
            // Micro PDF417은 열마다 폭이 정해져 있다(1열 38, 2열 55, 3열 82, 4열 99모듈).
            var micro = cols switch { 38 => 1, 55 => 2, 82 => 3, 99 => 4, _ => 0 };
            if (micro > 0)
                obj.QrKind = $"{micro}x{distinct}";
            return;
        }

        if ((cols - 69) % 17 != 0) return;
        var dataCols = (cols - 69) / 17;
        if (dataCols is < 1 or > 30) return;
        obj.QrKind = $"{dataCols}x{distinct}";
    }

    /// <summary>2D는 BMP 뒤 Type·Data 다음에 u32 전경색, u32 배경색. 투명 플래그는 없다.</summary>
    public static void Apply2DStyle(DesignObject obj, byte[] data, int afterBmp, int payloadEnd)
    {
        if (afterBmp + 8 > payloadEnd) return;
        var len = BitConverter.ToInt32(data, afterBmp + 4);
        var opt = afterBmp + 8 + len;
        if (len is < 0 or > 4096 || opt + 8 > payloadEnd) return;
        ApplyBarColor(obj, BitConverter.ToUInt32(data, opt));
        ApplyBackColor(obj, BitConverter.ToUInt32(data, opt + 4), transparent: false);
    }

    /// <summary>
    /// HRI 글꼴 꼬리. u32 이름 길이 + 이름, u8 크기(pt), u32 글자색, u32 스타일.
    /// 크기는 1바이트다. 「1D barcode 모양.lbl」에서 글자색을 빨강으로 바꾸면
    /// 크기 바로 뒤 4바이트가 색으로 바뀌는 것을 확인했다.
    /// </summary>
    private static void ApplyHriFont(DesignObject obj, byte[] data, int pos, int payloadEnd)
    {
        if (pos + 4 > payloadEnd) return;
        var fontLen = BitConverter.ToInt32(data, pos);
        if (fontLen is < 1 or > 64 || pos + 4 + fontLen + 9 > payloadEnd) return;
        var font = ExternalImportService.DecodeAnsi(data.AsSpan(pos + 4, fontLen));
        if (!string.IsNullOrWhiteSpace(font) && !font.Contains('?'))
            obj.FontFamily = font.Trim();
        pos += 4 + fontLen;

        var fontSize = data[pos];
        if (fontSize is >= 6 and <= 96)
            obj.FontSize = Math.Clamp(fontSize * 25.4f / 72f, 1.2f, HriSizeCapMm(obj));

        // HRI 글자색은 막대색과 따로 저장된다. 에디터는 Stroke를 HRI 색으로 쓴다.
        var fontColor = BitConverter.ToUInt32(data, pos + 1);
        if ((fontColor >> 24) == 0)
            obj.Stroke = TColorCss(fontColor);

        var style = BitConverter.ToUInt32(data, pos + 5);
        if (style > 0x0F) return;
        obj.Bold = (style & 0x01) != 0;
        obj.Italic = (style & 0x02) != 0;
        obj.Underline = (style & 0x04) != 0;
        obj.Strikeout = (style & 0x08) != 0;
    }

    /// <summary>
    /// HRI 글자 크기의 위 한계(mm). 바코드 칸 높이의 10%다.
    ///
    /// 애니라벨은 pt를 적어 두지만 그대로 그리지 않는다. 「1D barcode 타입.lbl」은 29칸이 모두
    /// 33.87×19.05mm 상자에 48pt(16.93mm = 상자 높이의 88.9%)라고 적혀 있는데,
    /// 애니라벨 화면의 EAN-13 캡션은 막대 아래 좁은 띠에 들어가 있다.
    /// 적힌 크기를 믿고 12mm로만 잘라 쓰면 글자가 막대를 덮고 상자 좌우로도 넘친다.
    ///
    /// 화면 실측이다. 막대 띠가 178px, 숫자 잉크가 18px, 그림 전체가 205px이었다.
    /// 맑은 고딕·Arial 숫자의 잉크 높이는 em의 0.72라 em은 25px 안팎이고, 상자 높이의 10~12%다.
    /// 애니라벨이 "바코드 칸을 넘지 않는 선에서 최대"로 줄인다는 사용자 확인과도 맞는다.
    ///
    /// 구형 0x07은 여기에 걸리지 않는다. 그쪽 크기는 EMF에 남은 LOGFONT에서 실측으로 가져오고
    /// (ApplyEmfHriFont), 「바코드 타입 전체.lbl」 44칸에서 애니라벨 글자 칸과 오차 4% 안이다.
    /// 그 값은 상자 높이의 10~27.6%로 칸마다 다르므로 10%로 자르면 맞던 것이 틀어진다.
    /// </summary>
    private static float HriSizeCapMm(DesignObject obj)
        => Math.Max(1.2f, Math.Min(12f, obj.Height * 0.10f));

    private const uint EmrSetTextColor = 24;
    private const uint EmrExtCreateFontIndirectW = 82;
    private const uint EmrExtTextOutW = 84;

    /// <summary>
    /// 구형 0x07의 HRI 글꼴. LBL 옵션에 적힌 값은 쓸 수 없다. 크기는 44개 심볼이 전부 16이고,
    /// 이름은 「바코드 모양.lbl」 23칸이 전부 문서 기본 얼굴(`?? ??` = 맑은 고딕)로 같다.
    /// 글꼴을 바탕·Arial로 바꾸거나 굵게·기울임·밑줄·글자색을 켜도 .lbl 바이트는 변하지 않는다.
    /// 실제로 쓰인 얼굴·굵기·기울임·글자색은 함께 저장된 EMF에만 남는다(분석 문서 8.17).
    /// </summary>
    public static void ApplyEmfHriFont(DesignObject obj, byte[] data, int emfStart, int emfLength)
    {
        if (!TryReadEmfHriFont(data, emfStart, emfLength, out var hri)) return;

        var mm = obj.Height * hri.Ratio;
        if (mm is >= 0.3f and <= 12f)
            obj.FontSize = mm;

        var face = hri.Face.Trim();
        if (face.Length >= 2)
            obj.FontFamily = FontCatalog.CanonicalId(face);
        obj.Bold = hri.Weight >= 600;
        obj.Italic = hri.Italic;
        obj.Underline = hri.Underline;
        obj.Strikeout = hri.Strikeout;
        // 에디터는 Stroke를 HRI 글자색으로 쓴다. EMR_SETTEXTCOLOR는 TColor와 같은 BGR 배치다.
        if (hri.TextColor is { } color)
            obj.Stroke = TColorCss(color);
    }

    /// <summary>
    /// 자릿수가 정해진 숫자 심볼의 전체 길이. 체크문자 자리를 포함한다.
    /// </summary>
    private static readonly Dictionary<string, int> FixedDigitLength = new(StringComparer.Ordinal)
    {
        ["EAN_13"] = 13,
        ["EAN_8"] = 8,
        ["UPC_A"] = 12,
        ["LEITCODE"] = 14,
        ["IDENTCODE"] = 12,
        ["ITF_6"] = 6,
        ["ITF_14"] = 14,
        ["ITF_16"] = 16,
        // PZN-7 은 여섯 자리 + 체크 한 자리다. 20번 칸 값 123456 의 캡션이 `PZN-0123456` 이고
        // 막대도 Code 39 `-0123456` 이라 왼쪽을 0으로 채운 뒤 끝자리를 체크로 본다.
        ["PZN"] = 7,
        // Code 32 는 여덟 자리 + 체크 한 자리다. 19번 칸 값 12345678 의 캡션이 `A012345678` 이고
        // 막대의 32진 여섯 글자 `0CSSBG` 를 풀면 12345678 이다.
        ["CODE_32"] = 9
    };

    /// <summary>
    /// 애니라벨 신형 1D는 짧은 값을 오른쪽에 붙이고 왼쪽을 0으로 채운다. 맨 끝은 체크문자 자리다.
    ///
    /// 「1D barcode 타입.lbl」에 애니라벨이 그려 둔 그림의 캡션으로 확인했다.
    ///   EAN-13    123456789012    → 0 123456 789012
    ///   EAN-8     1234567         → 0123 4567
    ///   UPC-A     12345678901     → 0 12345 67890 1
    ///   Leitcode  1234567890123   → 01234567890123
    ///   Identcode 12345678901     → 012345678901
    ///   ITF-16    123456789012345 → 0123456789012345
    /// 값을 그냥 데이터로 보고 체크문자를 뒤에 붙이면 자리가 한 칸 밀려 막대가 통째로 달라진다.
    ///
    /// 체크문자가 맞는지는 애니라벨이 따지지 않는다. UPC-A 예의 끝자리 1은 규격대로면 5다.
    /// 틀린 체크문자를 그대로 찍으면 스캐너가 읽지 못하는 인쇄물이 나오므로, 여기서는 자리만
    /// 맞추고 값이 어긋나면 렌더러가 제 자리에 맞는 체크문자를 넣는다.
    ///
    /// UPC-E 계열(0x1B·0x1C·0x1D)은 이 규칙을 따르지 않는다. 123456을 넣으면 00012345로
    /// 끝자리가 사라지고 체크문자도 규격과 다르다. 규칙을 못 세워 손대지 않는다.
    /// </summary>
    public static string PadFixedLength(string format, string value)
    {
        var v = (value ?? "").Trim();
        if (v.Length == 0 || !FixedDigitLength.TryGetValue(format, out var want)) return v;
        if (v.Length >= want) return v;
        foreach (var ch in v)
            if (!char.IsAsciiDigit(ch)) return v;
        return v.PadLeft(want, '0');
    }

    /// <summary>
    /// 신형 1D(LBL 0x1B)의 모양을 애니라벨이 그려 둔 그림에서 잰다.
    ///
    /// 신형은 구형과 달리 EMF 안에 막대를 직접 그리지 않고, 다 그린 비트맵 한 장을
    /// 상자 크기로 늘려 찍는다(EMR_STRETCHBLT). 그 비트맵이 애니라벨 화면 그대로라
    /// 파일에 적힌 값보다 믿을 만하다. 여기서 두 가지를 가져온다.
    ///
    /// · HRI 글자 칸 높이 — 파일에 적힌 pt(전부 48pt = 상자 높이의 88.9%)는 화면과 무관하다.
    ///   막대 아래 남은 띠를 재서 렌더러의 글자 칸 셈(HriBand)을 거꾸로 풀어 글자 크기를 얻는다.
    /// · 막대를 두르는 사각 테두리 — 「1D barcode 타입.lbl」 52칸 중 13·14·15번
    ///   (ITF-6·ITF-14·ITF-16)에만 있었다. Leitcode·Identcode에는 없다.
    /// </summary>
    public static void ApplyEmfDib1D(DesignObject obj, byte[] data, int emfStart, int emfLength)
    {
        if (!TryMeasureEmfDib(data, emfStart, emfLength, out var shot)) return;

        if (shot.Framed)
            obj.BarcodeShowStartEnd = true;

        // 소매 계열은 띠 대신 숫자 잉크를 직접 재서 쓴다(아래 DigitInk 설명 참고).
        var mm = shot.DigitInk > 0f
            ? obj.Height * shot.DigitInk / HriInkPerEm
            : obj.Height * shot.CaptionBand / BarcodeRenderer.AniLabelHriLine;
        if (mm is >= 0.3f and <= 12f)
            obj.FontSize = mm;
    }

    /// <summary>
    /// HRI 글꼴에서 숫자 잉크 높이 ÷ 글자 크기. 맑은 고딕·Arial 숫자로 0.70 이다.
    /// 글자 칸 배수 1.07(<see cref="BarcodeRenderer.AniLabelHriLine"/>)과 짝을 이루는 값이다.
    /// 신형 1D 칸에서 글자 칸이 그림 높이의 13.3%, 숫자 잉크가 8.7% 였으므로 8.7 ÷ (13.3 ÷ 1.07) = 0.70.
    /// </summary>
    private const float HriInkPerEm = 0.70f;

    /// <summary>
    /// 그려 둔 그림에서 잰 값.
    /// CaptionBand 는 그림 높이 대비 글자 띠 비율, DigitInk 는 글자 잉크 자체의 높이 비율이다.
    /// </summary>
    private readonly record struct EmfDibShot(bool Framed, float CaptionBand, float DigitInk);

    private const uint EmrBitBlt = 76;
    private const uint EmrStretchBlt = 77;
    private const uint EmrSetDiBitsToDevice = 80;
    private const uint EmrStretchDiBits = 81;

    private static bool TryMeasureEmfDib(byte[] data, int start, int length, out EmfDibShot shot)
    {
        shot = default;
        var end = start + length;
        if (start < 0 || length < 88 || end > data.Length) return false;

        var pos = start;
        while (pos + 8 <= end)
        {
            var type = BitConverter.ToUInt32(data, pos);
            var size = (int)BitConverter.ToUInt32(data, pos + 4);
            if (size < 8 || pos + size > end) return false;
            // BITBLT·STRETCHBLT는 +84, SETDIBITSTODEVICE·STRETCHDIBITS는 +48에 DIB 자리가 있다.
            var at = type switch
            {
                EmrBitBlt or EmrStretchBlt => 84,
                EmrSetDiBitsToDevice or EmrStretchDiBits => 48,
                _ => 0
            };
            if (at > 0 && size >= at + 16 && TryMeasureDib(data, pos, at, out shot))
                return true;
            pos += size;
        }
        return false;
    }

    /// <summary>
    /// DIB 한 장을 훑어 막대 영역이 끝나는 줄과 사각 테두리 두께를 찾는다.
    ///
    /// 막대는 그림 위쪽에서 끊기지 않고 내려오는 검은 기둥이다. 줄마다 살아남은 열만 남기면
    /// 마지막까지 살아남은 줄이 막대(또는 테두리) 밑변이다. 그 아래 남은 띠가 HRI 글자 칸이다.
    /// 열을 추려 가며 보므로 아랫줄로 갈수록 볼 점이 빠르게 줄어든다.
    /// </summary>
    private static bool TryMeasureDib(byte[] data, int record, int at, out EmfDibShot shot)
    {
        shot = default;
        var offBmi = (int)BitConverter.ToUInt32(data, record + at);
        var offBits = (int)BitConverter.ToUInt32(data, record + at + 8);
        var cbBits = (int)BitConverter.ToUInt32(data, record + at + 12);
        var bmi = record + offBmi;
        var bits = record + offBits;
        if (offBmi < 40 || offBits <= 0 || cbBits <= 0) return false;
        if (bmi + 40 > data.Length || bits + cbBits > data.Length) return false;

        var w = BitConverter.ToInt32(data, bmi + 4);
        var rawH = BitConverter.ToInt32(data, bmi + 8);
        var bitCount = BitConverter.ToInt16(data, bmi + 14);
        var compression = BitConverter.ToUInt32(data, bmi + 16);
        // 팔레트를 읽어야 하는 4·8비트와 압축 DIB는 다루지 않는다. 표본은 모두 32비트 무압축이다.
        if (compression is not (0 or 3) || bitCount is not (1 or 24 or 32)) return false;
        var h = Math.Abs(rawH);
        if (w is < 8 or > 20000 || h is < 32 or > 20000) return false;

        var stride = (w * bitCount + 31) / 32 * 4;
        if ((long)stride * h > cbBits) return false;
        var topDown = rawH < 0;
        var bpp = bitCount / 8;

        int RowAt(int y) => bits + (topDown ? y : h - 1 - y) * stride;

        bool Ink(int row, int x) => bitCount == 1
            ? (data[row + (x >> 3)] >> (7 - (x & 7)) & 1) == 0
            : data[row + x * bpp] + data[row + x * bpp + 1] + data[row + x * bpp + 2] < 384;

        // 테두리: 맨 윗줄이 가로로 꽉 차 있으면 막대를 두른 사각 테두리다.
        var top = RowAt(0);
        var solid = 0;
        for (var x = 0; x < w; x++)
            if (Ink(top, x)) solid++;
        var framed = solid >= w - 2;

        var alive = new int[w];
        var live0 = 0;
        for (var x = 0; x < w; x++)
            if (Ink(top, x)) alive[live0++] = x;
        if (live0 == 0) return false;

        // 막대가 살아 있는 마지막 줄을 둘로 나눠 센다.
        // bulkBottom 은 막대 대부분이 끝나는 줄, barsBottom 은 마지막 한 개까지 끝나는 줄이다.
        // EAN·UPC 는 가드 막대 예닐곱 개만 글자 칸 아래로 내려오므로 둘이 크게 벌어진다.
        var live = live0;
        var bulkBottom = 1;
        var barsBottom = 1;
        for (var y = 1; y < h && live > 0; y++)
        {
            var row = RowAt(y);
            var kept = 0;
            for (var i = 0; i < live; i++)
                if (Ink(row, alive[i])) alive[kept++] = alive[i];
            if (kept == 0) break;
            live = kept;
            barsBottom = y + 1;
            if (kept * 5 >= live0 * 2) bulkBottom = y + 1;
        }

        var band = (float)(h - barsBottom) / h;
        var ink = 0f;
        // 가드가 그림 바닥까지 내려오면 글자 띠를 잴 수가 없다(28번 칸이 그렇다).
        // 그럴 때는 보통 막대 밑변 아래를 가드 열만 빼고 훑어 숫자 잉크 높이를 직접 잰다.
        //
        // 절반을 기준으로 잡는다. UPC-A 는 가드 말고 양끝 숫자의 막대도 같이 내려와서
        // 28번 칸이 264열 중 78열(29.5%)이나 남는다. 1/4 로 잡으면 이 칸을 놓친다.
        if (barsBottom > bulkBottom && live * 2 < live0)
        {
            var guard = new bool[w];
            for (var i = 0; i < live; i++) guard[alive[i]] = true;

            var first = -1;
            var last = -1;
            for (var y = bulkBottom; y < h; y++)
            {
                var row = RowAt(y);
                var hit = false;
                for (var x = 0; x < w && !hit; x++)
                    if (!guard[x] && Ink(row, x)) hit = true;
                if (!hit) continue;
                if (first < 0) first = y;
                last = y;
            }
            if (first >= 0 && last > first) ink = (float)(last - first + 1) / h;
        }
        else if (barsBottom < h)
        {
            // 가드가 없는 보통 칸. 막대 밑변 아래는 통째로 글자 칸이라 열을 가릴 것이 없다.
            //
            // 띠를 그대로 쓰면 글자가 커진다. 애니라벨은 글자 칸에 남기는 여백이 칸마다 다른데
            // (Clocked-35 표본에서 띠 대비 잉크가 0.65 와 0.56 으로 갈렸다) 띠 배수는 하나뿐이라
            // 여백이 넉넉한 칸일수록 글자를 키워 버린다. 잉크를 직접 재면 여백에 휘둘리지 않는다.
            var first = -1;
            var last = -1;
            for (var y = barsBottom; y < h; y++)
            {
                var row = RowAt(y);
                var hit = false;
                for (var x = 0; x < w && !hit; x++)
                    if (Ink(row, x)) hit = true;
                if (!hit) continue;
                if (first < 0) first = y;
                last = y;
            }
            if (first >= 0 && last > first) ink = (float)(last - first + 1) / h;
        }

        // 캡션을 끈 칸은 막대가 바닥까지 닿는다. 절반을 넘는 띠는 막대를 잘못 잡은 것이다.
        shot = new EmfDibShot(
            framed,
            band is > 0.005f and < 0.48f ? band : 0f,
            ink is > 0.01f and < 0.4f ? ink : 0f);
        return true;
    }

    private readonly record struct EmfHriFont(
        float Ratio, string Face, int Weight, bool Italic, bool Underline, bool Strikeout, uint? TextColor);

    /// <summary>
    /// HRI를 실제로 찍는 데 쓰인 LOGFONT를 EMF에서 찾는다.
    /// 글꼴 기록 다음에 글자를 찍는 기록이 나오면 그 짝이 HRI다.
    /// 글자 수가 0이거나 상자가 빈 기록은 캐시 찌꺼기라 건너뛴다 — HRI를 끈 칸에도 남아 있어서
    /// 그냥 집으면 바코드와 상관없는 UI 글꼴(Segoe UI)을 얼굴로 박게 된다.
    ///
    /// 크기는 EMR_HEADER의 rclFrame·szlDevice·szlMillimeters로 환산한 그림 높이 대비 비율로 넘긴다.
    /// 잴 수 없으면 비율만 0으로 두고 얼굴·굵기는 그대로 살린다.
    /// </summary>
    private static bool TryReadEmfHriFont(byte[] data, int start, int length, out EmfHriFont hri)
    {
        hri = default;
        var end = start + length;
        if (start < 0 || length < 88 || end > data.Length) return false;

        var frameBottom = BitConverter.ToInt32(data, start + 36);
        var deviceCy = BitConverter.ToInt32(data, start + 76);
        var mmCy = BitConverter.ToInt32(data, start + 84);
        var pictureH = frameBottom > 0 && deviceCy > 0 && mmCy > 0
            ? frameBottom / 100.0 * deviceCy / mmCy
            : 0;

        var pos = start;
        var lfHeight = 0;
        var weight = 400;
        var italic = false;
        var underline = false;
        var strikeout = false;
        var face = "";
        uint? textColor = null;
        while (pos + 8 <= end)
        {
            var type = BitConverter.ToUInt32(data, pos);
            var size = (int)BitConverter.ToUInt32(data, pos + 4);
            if (size < 8 || pos + size > end) return false;
            if (type == EmrSetTextColor && pos + 12 <= end)
                textColor = BitConverter.ToUInt32(data, pos + 8);
            else if (type == EmrExtCreateFontIndirectW && pos + 104 <= end)
            {
                lfHeight = BitConverter.ToInt32(data, pos + 12);
                weight = BitConverter.ToInt32(data, pos + 28);
                italic = data[pos + 32] != 0;
                underline = data[pos + 33] != 0;
                strikeout = data[pos + 34] != 0;
                face = ReadLogFontFace(data, pos + 40);
            }
            else if (type == EmrExtTextOutW && pos + 48 <= end)
            {
                var boundsLeft = BitConverter.ToInt32(data, pos + 8);
                var boundsRight = BitConverter.ToInt32(data, pos + 16);
                var chars = BitConverter.ToUInt32(data, pos + 44);
                if (chars == 0 || boundsRight <= boundsLeft) { pos += size; continue; }
                if (lfHeight == 0) return false;

                // lfHeight 양수는 내부 여백을 포함한 칸 높이, 음수는 글자 높이다.
                var em = lfHeight < 0 ? -lfHeight : lfHeight * 0.75;
                var ratio = pictureH is >= 16 and <= 100_000 ? (float)(em / pictureH) : 0f;
                if (ratio is <= 0.02f or >= 0.9f) ratio = 0f;
                hri = new EmfHriFont(ratio, face, weight, italic, underline, strikeout, textColor);
                return true;
            }
            pos += size;
        }
        return false;
    }

    /// <summary>LOGFONTW.lfFaceName은 널로 끝나는 32글자 UTF-16 배열이다.</summary>
    private static string ReadLogFontFace(byte[] data, int pos)
    {
        var chars = new char[32];
        var n = 0;
        for (; n < 32; n++)
        {
            var ch = (char)BitConverter.ToUInt16(data, pos + n * 2);
            if (ch == '\0') break;
            chars[n] = ch;
        }
        return new string(chars, 0, n);
    }

    private const uint EmrPolygon = 3;
    private const uint EmrPolyline = 4;
    private const uint EmrPolygon16 = 86;
    private const uint EmrPolyline16 = 87;
    private const uint EmrPolyPolygon16 = 91;

    /// <summary>
    /// EMF 막대의 밑변을 재서 캡션 칸까지 내려오는 긴 막대(가드)가 있는지 본다.
    ///
    /// 애니라벨은 이 설정을 .lbl 논리 필드에 남기지 않는다. 「바코드 모양.lbl」은 같은 EAN-13을
    /// 옵션만 바꿔 23칸에 늘어놓은 샘플인데, 가드가 없는 1번 칸과 있는 2번 칸은 구획 전체를 맞대도
    /// 설명 글자 두 바이트(`없음`→`있음`) 말고는 완전히 같다. 값·크기·색·글꼴·옵션 바이트가 모두
    /// 동일하고 EMF 그림만 다르다. 그래서 타입 표로 짐작하지 않고 그림을 직접 잰다.
    /// </summary>
    public static bool TryReadEmfGuardBars(byte[] data, int start, int length, out bool guards)
    {
        guards = false;
        var end = start + length;
        if (start < 0 || length < 88 || end > data.Length) return false;

        // 그림 테두리(EMR_HEADER rclBounds)를 기준으로 삼아 논리 단위가 달라져도 견디게 한다.
        var boundsW = BitConverter.ToInt32(data, start + 16) - BitConverter.ToInt32(data, start + 8);
        var boundsH = BitConverter.ToInt32(data, start + 20) - BitConverter.ToInt32(data, start + 12);
        if (boundsW <= 0 || boundsH <= 0) return false;
        var maxBarWidth = Math.Max(4, boundsW * 12 / 100);
        var tolerance = Math.Max(2, boundsH / 100);

        var bars = new List<(int Left, int Right, int Bottom)>();
        var pos = start;
        while (pos + 8 <= end)
        {
            var type = BitConverter.ToUInt32(data, pos);
            var size = (int)BitConverter.ToUInt32(data, pos + 4);
            if (size < 8 || pos + size > end) break;
            if (type is EmrPolygon or EmrPolyline or EmrPolygon16 or EmrPolyline16 or EmrPolyPolygon16
                && pos + 24 <= end)
            {
                var left = BitConverter.ToInt32(data, pos + 8);
                var right = BitConverter.ToInt32(data, pos + 16);
                var bottom = BitConverter.ToInt32(data, pos + 20);
                // 굵은 사각형은 막대가 아니라 배경·테두리다.
                if (right - left is var w && w > 0 && w <= maxBarWidth)
                    bars.Add((left, right, bottom));
            }
            pos += size;
        }
        if (bars.Count < 4) return false;

        // 한 막대를 여러 조각으로 그린 경우가 있어 가로로 겹치는 것끼리 합친다.
        bars.Sort((a, b) => a.Left.CompareTo(b.Left));
        var merged = new List<(int Right, int Bottom)>();
        foreach (var (left, right, bottom) in bars)
        {
            if (merged.Count > 0 && left <= merged[^1].Right)
                merged[^1] = (Math.Max(merged[^1].Right, right), Math.Max(merged[^1].Bottom, bottom));
            else
                merged.Add((right, bottom));
        }
        if (merged.Count < 4) return false;

        var shortest = merged.Min(m => m.Bottom);
        guards = merged.Exists(m => m.Bottom > shortest + tolerance);
        return true;
    }

    private static void ApplyBarColor(DesignObject obj, uint color)
    {
        if ((color >> 24) != 0) return;
        obj.Fill = TColorCss(color);
    }

    private static void ApplyBackColor(DesignObject obj, uint color, bool transparent)
    {
        if (transparent)
        {
            obj.BackgroundFill = "transparent";
            obj.BackgroundTransparent = true;
            return;
        }
        if ((color >> 24) != 0) return;
        obj.BackgroundFill = TColorCss(color);
        obj.BackgroundTransparent = false;
    }

    public static bool TryRead2D(byte[] data, int payloadStart, int payloadEnd, out uint type, out string value, out string format, out int bmpStart, out int afterBmp)
    {
        type = 0;
        value = "";
        format = "QR_CODE";
        bmpStart = payloadStart;
        afterBmp = payloadStart;
        if (!TryBmpRange(data, payloadStart, payloadEnd, out bmpStart, out afterBmp))
            return false;
        if (afterBmp + 8 > payloadEnd) return false;
        type = BitConverter.ToUInt32(data, afterBmp);
        var len = BitConverter.ToInt32(data, afterBmp + 4);
        if (type > 0x0F || len is < 0 or > 4096 || afterBmp + 8 + len > payloadEnd) return false;
        value = System.Text.Encoding.Latin1.GetString(data, afterBmp + 8, len);
        format = Map2D(type);
        return true;
    }

    public static bool TryBmpEnd(byte[] data, int from, int limit, out int end)
        => TryBmpRange(data, from, limit, out _, out end);

    public static bool TryBmpRange(byte[] data, int from, int limit, out int start, out int end)
    {
        start = from;
        end = from;
        var last = Math.Min(limit, from + 4096);
        for (var i = from; i + 6 < last; i++)
        {
            if (data[i] != (byte)'B' || data[i + 1] != (byte)'M') continue;
            var len = BitConverter.ToInt32(data, i + 2);
            if (len is > 54 and < 8_000_000 && i + len <= limit)
            {
                start = i;
                end = i + len;
                return true;
            }
        }
        return false;
    }

    /// <summary>
    /// 신형 2D(0x1C)가 안에 넣어 둔 BMP를 모듈 행렬로 되돌린다.
    ///
    /// 애니라벨은 2D 심볼의 버전·오류정정 수준·마스크·심볼 크기를 .lbl 논리 필드에 남기지 않는다.
    /// 「QR코드 타입.lbl」 2D 객체는 기하 + 표식 + BMP + 타입 + 값 + 전경색 + 배경색이 전부다.
    /// 그래서 가드(8.16)·HRI 글꼴(8.17)과 똑같이 그려 둔 그림에서 값을 되읽는다(8.18).
    /// </summary>
    public static bool TryReadBmpMatrix(byte[] data, int bmpStart, int bmpEnd, out bool[,] modules)
    {
        modules = new bool[0, 0];
        if (bmpStart < 0 || bmpStart + 54 > bmpEnd || bmpEnd > data.Length) return false;

        var bits = BitConverter.ToInt32(data, bmpStart + 10);
        var width = BitConverter.ToInt32(data, bmpStart + 18);
        var height = BitConverter.ToInt32(data, bmpStart + 22);
        var bpp = BitConverter.ToUInt16(data, bmpStart + 28);
        var compression = BitConverter.ToInt32(data, bmpStart + 30);
        if (compression != 0 || bpp is not (1 or 8 or 24 or 32)) return false;
        if (width is < 4 or > 20_000 || height == 0 || Math.Abs(height) > 20_000) return false;

        var bottomUp = height > 0;
        height = Math.Abs(height);
        var stride = (width * bpp + 31) / 32 * 4;
        if (bmpStart + bits + stride * height > bmpEnd) return false;

        var ink = new bool[height, width];
        for (var y = 0; y < height; y++)
        {
            var row = bmpStart + bits + (bottomUp ? height - 1 - y : y) * stride;
            for (var x = 0; x < width; x++)
            {
                ink[y, x] = bpp switch
                {
                    // 1bpp는 표준 팔레트(0=검정), 나머지는 첫 채널 밝기로 본다.
                    1 => ((data[row + (x >> 3)] >> (7 - (x & 7))) & 1) == 0,
                    8 => data[row + x] < 128,
                    24 => data[row + x * 3] < 128,
                    _ => data[row + x * 4] < 128
                };
            }
        }

        return TryShrink(ink, height, width, out modules);
    }

    /// <summary>픽셀 그림에서 여백을 떼고 모듈 한 칸의 픽셀 수를 재서 행렬로 줄인다.</summary>
    private static bool TryShrink(bool[,] ink, int height, int width, out bool[,] modules)
    {
        modules = new bool[0, 0];
        int x0 = width, x1 = -1, y0 = height, y1 = -1;
        for (var y = 0; y < height; y++)
        {
            for (var x = 0; x < width; x++)
            {
                if (!ink[y, x]) continue;
                if (x < x0) x0 = x;
                if (x > x1) x1 = x;
                if (y < y0) y0 = y;
                if (y > y1) y1 = y;
            }
        }
        if (x1 < x0 || y1 < y0) return false;

        // 가로·세로로 같은 색이 이어지는 최소 길이가 모듈 한 칸이다.
        var unit = int.MaxValue;
        for (var y = y0; y <= y1; y++)
            unit = Math.Min(unit, MinRun(ink, y, x0, x1, horizontal: true));
        for (var x = x0; x <= x1; x++)
            unit = Math.Min(unit, MinRun(ink, x, y0, y1, horizontal: false));
        if (unit is < 1 or int.MaxValue) return false;

        var boxW = x1 - x0 + 1;
        var boxH = y1 - y0 + 1;
        var cols = (int)Math.Round((double)boxW / unit);
        var rows = (int)Math.Round((double)boxH / unit);
        if (cols is < 4 or > 800 || rows is < 4 or > 800) return false;

        modules = new bool[rows, cols];
        for (var r = 0; r < rows; r++)
        {
            var yy = Math.Min(y1, y0 + (int)((r + 0.5) * boxH / rows));
            for (var c = 0; c < cols; c++)
            {
                var xx = Math.Min(x1, x0 + (int)((c + 0.5) * boxW / cols));
                modules[r, c] = ink[yy, xx];
            }
        }
        return true;
    }

    private static int MinRun(bool[,] ink, int line, int from, int to, bool horizontal)
    {
        var best = int.MaxValue;
        var run = 0;
        var prev = horizontal ? ink[line, from] : ink[from, line];
        for (var i = from; i <= to; i++)
        {
            var v = horizontal ? ink[line, i] : ink[i, line];
            if (v == prev)
            {
                run++;
                continue;
            }
            best = Math.Min(best, run);
            run = 1;
            prev = v;
        }
        return Math.Min(best, run);
    }

    /// <summary>BMP 뒤 Type + Data + 전경/배경색. 그 다음이 같은 칸의 텍스트 객체다.</summary>
    public static int LogicalEndAfterBmp(byte[] data, int bmpEnd, int limit)
    {
        if (bmpEnd + 8 > limit) return bmpEnd;
        var type = BitConverter.ToUInt32(data, bmpEnd);
        var len = BitConverter.ToInt32(data, bmpEnd + 4);
        if (type > 0x0F || len is < 0 or > 4096 || bmpEnd + 8 + len + 8 > limit)
            return bmpEnd;
        return bmpEnd + 8 + len + 8;
    }

    public static string Map1D(uint type) => type switch
    {
        0x00 => "CODABAR",
        0x01 => "CODE_11",
        0x02 => "I25_INDUSTRIAL",
        0x03 => "I25_INVERT",
        0x04 => "I25_IATA",
        0x05 => "ITF",
        0x06 => "I25_MATRIX",
        0x07 => "I25_DATALOGIC",
        0x08 => "COOP25",
        0x09 => "LEITCODE",
        0x0A => "IDENTCODE",
        0x0B => "ITF_6",
        0x0C => "ITF_14",
        0x0D => "ITF_16",
        0x0E => "CODE_39",
        0x0F => "UPU",
        0x10 => "CODE_39_EXT",
        0x11 => "CODE_32",
        0x12 => "PZN",
        0x13 => "CODE_93",
        0x14 => "CODE_93_EXT",
        0x15 => "PLESSEY",
        0x16 => "MSI",
        0x17 => "TELEPEN",
        0x18 => "PHARMA_1",
        0x19 => "PHARMA_2",
        0x1A => "UPC_A",
        0x1B => "UPC_E",
        0x1C => "UPC_E0",
        0x1D => "UPC_E1",
        0x1E => "EAN_2",
        0x1F => "EAN_5",
        0x20 => "EAN_8",
        0x21 => "EAN_13",
        0x22 => "CODE_128",
        0x23 => "EAN_128",
        // 0x24~0x28 은 「1D barcode 타입.lbl」이 칸마다 심볼 이름을 텍스트로 적어 두어 확정했다.
        // Flattermarken 과 Clocked-35 는 인코더가 없어 이름만 달고 Code 128 로 그린다.
        0x24 => "FLATTERMARKEN",
        0x25 => "CHANNEL_CODE",
        0x26 => "BC309",
        0x27 => "BC412",
        0x28 => "CLOCKED_35",
        0x29 => "FIM",
        0x2A => "PATCH_CODE",
        0x2B => "PLANET",
        0x2C => "POSTNET",
        0x2D => "KIX",
        0x2E => "JAPAN_POST",
        0x2F => "RM4SCC",
        // 49번 칸 값 `5932211324A124B` 를 FCC 59 + 배달구역 32211324 + 고객정보 A124B 로 풀면
        // 52막대 Customer Barcode 2 가 되고 검사심볼 26·23·20·52 까지 원본 그림과 같다.
        0x30 => "AUSPOST",
        0x31 => "CPC_BINARY",
        0x32 => "POSTBAR",
        0x33 => "ONECODE",
        _ => "CODE_128"
    };

    /// <summary>
    /// 신형 2D(0x1C) 타입표. 「QR코드 타입.lbl」이 칸마다 심볼 이름을 텍스트로 적어 두어 확정했다.
    ///
    /// 0x00 Aztec Code / 0x01 Aztec Runes / 0x02 Code 16K / 0x03 Data Matrix(ECC 200) /
    /// 0x04 Data Matrix(ECC 000–140) / 0x05 MaxiCode / 0x06 PDF417 / 0x07 MicroPDF417 /
    /// 0x08 QR Code / 0x09 Micro QR Code / 0x0A Han Xin Code / 0x0B Grid Matrix /
    /// 0x0C Compact Matrix / 0x0D RSS-14 / 0x0E RSS Limited / 0x0F RSS Expanded.
    ///
    /// 인코더가 없는 심볼은 모양이 제일 가까운 것으로 보낸다. 분석 문서 8.18 참고.
    /// 다만 Compact Matrix는 39×18 가로 직사각형이라 어떤 대체품과도 모양이 겹치지 않고,
    /// 대체하면 원본과 다른 값이 읽혀 인쇄물이 잘못 나간다. 그래서 미지원으로 드러낸다.
    /// </summary>
    public static string Map2D(uint type) => type switch
    {
        0x00 => "AZTEC",
        0x01 => "AZTEC",            // Aztec Runes 11×11. 전용 인코더 없음
        0x02 => "CODE_16K",
        0x03 => "DATA_MATRIX",
        0x04 => "DATA_MATRIX",      // ECC 000–140(홀수 크기). 전용 인코더 없음
        0x05 => "MAXICODE",
        0x06 => "PDF_417",
        0x07 => "MICRO_PDF417",
        0x08 => "QR_CODE",
        0x09 => "MICRO_QR",
        0x0A => "HANXIN",
        0x0B => "GRID_MATRIX",
        0x0C => "COMPACT_MATRIX",   // 규격 미확보. 대체하지 않고 미지원으로 드러낸다
        0x0D => "RSS_14",
        0x0E => "RSS_LIMITED",
        0x0F => "RSS_EXPANDED",
        _ => "QR_CODE"
    };

    /// <summary>구형 0x07 PSOFT 1-byte enum. md_anylabel 바코드 타입 전체 분석.</summary>
    private static string MapLegacy(byte type) => type switch
    {
        0x01 => "EAN_8",
        0x02 => "EAN_13",
        0x03 => "CODABAR",
        0x04 => "CODE_39",
        0x05 => "CODE_39_EXT",
        0x06 => "CODE_93",
        0x07 => "CODE_93_EXT",
        0x08 => "CODE_128",
        0x09 => "ABC_CODABAR",
        0x0A => "I25_DATALOGIC",
        0x0B => "ITF",
        0x0C => "I25_MATRIX",
        0x0D => "I25_INDUSTRIAL",
        0x0E => "I25_IATA",
        0x0F => "I25_INVERT",
        0x10 => "ITF",
        0x11 => "ISBN",
        0x12 => "ISSN",
        0x13 => "ISMN",
        0x14 => "UPC_A",
        0x15 => "UPC_E0",
        0x16 => "UPC_E1",
        0x17 => "ITF_14",
        0x19 => "JAN_8",
        0x1A => "JAN_13",
        0x1B => "UPC_E",
        // 높이 변조 우편형. EMF 막대 높이를 표로 되돌려 확정했다(md 8.12).
        0x1C => "POSTNET",
        0x1D => "PLANET",
        0x1E => "RM4SCC",
        0x1F => "RM4SCC",
        0x20 => "KIX",
        0x22 => "RM4SCC",
        // 「바코드 타입 전체.lbl」은 칸마다 심볼 이름을 텍스트로 적어 두었다(md 8.14).
        0x25 => "OPC",
        // 파일에는 UCC/EAN 128이라고 적혀 있으나 EMF 막대 22개에 FNC1이 없다. 평범한 Code 128이다.
        0x26 => "CODE_128",
        0x27 => "COOP25",
        0x28 => "CODE_11",
        0x29 => "PZN",
        0x2A => "PDF_417",
        0x30 => "FIM",
        0x31 => "TELEPEN",
        0x32 => "ONECODE",
        // 41번 칸 텍스트가 `australia post barcode`다. 37막대 4상태를 풀면 FCC 11 + 배달구역 96184209로
        // Standard Customer Barcode가 맞고, Reed-Solomon 검사심볼까지 실측과 같다.
        0x33 => "AUSPOST",
        0x34 => "DATA_MATRIX",
        0x35 => "QR_CODE",
        _ => "CODE_128"
    };

    private static string TColorCss(uint color)
    {
        var r = color & 0xFF;
        var g = (color >> 8) & 0xFF;
        var b = (color >> 16) & 0xFF;
        return string.Create(CultureInfo.InvariantCulture, $"#{r:X2}{g:X2}{b:X2}");
    }
}
