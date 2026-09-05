using System.Globalization;
using System.Text;
using System.Text.RegularExpressions;
using LabelUp.Editor.Models;
using LabelUp.Editor.Vendor;

namespace LabelUp.Editor.Services;

/// <summary>
/// 폼텍 DGF 레코드 길이·본문 매핑. WPF Dgf*RecordParser 와 같은 오프셋을 쓴다.
/// </summary>
internal static class FormtecRecords
{
    private static readonly byte[] PngSig = [0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A];
    private static readonly Regex CustomToken = new(
        @"\{DATE:([1-4])\}|\{TIME:([1-3])\}|\{HEX-SERIALNO\}|\{SERIALNO\}",
        RegexOptions.Compiled | RegexOptions.CultureInvariant);

    internal static bool TryObjectLength(byte[] data, int start, int pageEnd, byte type, out int length)
    {
        length = 0;
        var available = pageEnd - start;
        if (available < 41) return false;

        switch (type)
        {
            case 0x02:
                // 페이지 블록 길이 100은 B8 01(2)를 포함한다. 객체 본문은 98바이트.
                // 100으로 읽으면 같은 페이지의 다음 선이 2바이트씩 밀린다.
                if (available < 79) return false;
                length = Math.Min(98, available);
                return true;
            case 0x07:
            case 0x08:
                return TryBarcode1DLength(data, start, pageEnd, out length);
            case 0x04:
                length = 95;
                return length <= available;
            case 0x05:
                length = 75;
                return length <= available;
            case 0x09 or 0x18:
                return TryImageLength(data, start, available, type, out length);
            case 0x0A:
                return TryWordArtLength(data, start, pageEnd, out length);
            case 0x0B:
                return TryUserDefinedLength(data, start, pageEnd, out length);
            case 0x10:
                return TryBarcode2DLength(data, start, available, out length);
            case 0x16:
                return TryExtendedLength(data, start, pageEnd, out length);
            case 0x00:
                return TryTextLength(data, start, pageEnd, out length);
            case 0x0F:
                return TryTableLength(data, start, pageEnd, out length);
            case 0x0E:
                if (IsGradient(data, start, pageEnd))
                {
                    length = 89;
                    return length <= available;
                }
                return false;
            default:
                return false;
        }
    }

    internal static void Apply(DesignObject obj, byte[] data, int start, int end, byte type)
    {
        end = Math.Min(end, data.Length);
        switch (type)
        {
            case 0x02:
                ApplyLineOrArrow(obj, data, start, end);
                break;
            case 0x04:
            case 0x05:
                ApplyClosedShape(obj, data, start, end);
                break;
            case 0x0E:
                if (IsGradient(data, start, end))
                    ApplyGradient(obj, data, start, end);
                else
                    ApplyClosedShape(obj, data, start, end);
                break;
            case 0x09:
            case 0x18:
                ApplyImage(obj, data, start, end, type == 0x18);
                break;
            case 0x07:
            case 0x08:
                ApplyBarcode1D(obj, data, start, end);
                break;
            case 0x10:
                ApplyBarcode2D(obj, data, start, end);
                break;
            case 0x0A:
                ApplyWordArt(obj, data, start, end);
                break;
            case 0x0B:
                ApplyUserDefined(obj, data, start, end);
                break;
            case 0x16:
                ApplyExtended(obj, data, start, end);
                break;
            case 0x00:
                ApplyPlainText(obj, data, start, end);
                break;
            case 0x0F:
                ApplyTable(obj, data, start, end);
                break;
        }
    }

    internal static string ExpandCustom(DesignObject obj, int labelIndex)
    {
        var raw = obj.Text ?? "";
        if (CustomToken.IsMatch(raw))
            return ExpandTokens(raw, obj, labelIndex);

        return obj.CustomKind switch
        {
            "date" => DateTime.Now.ToString(string.IsNullOrWhiteSpace(obj.CustomFormat) ? "yyyy-MM-dd" : obj.CustomFormat, CultureInfo.InvariantCulture),
            "time" => DateTime.Now.ToString(string.IsNullOrWhiteSpace(obj.CustomFormat) ? "HH:mm" : obj.CustomFormat, CultureInfo.InvariantCulture),
            "serial" => (obj.SerialStart + labelIndex * Math.Max(1, obj.SerialStep))
                .ToString("D" + Math.Clamp(obj.SerialDigits, 1, 12), CultureInfo.InvariantCulture),
            "hexserial" => (obj.SerialStart + labelIndex * Math.Max(1, obj.SerialStep))
                .ToString("X" + Math.Clamp(obj.SerialDigits, 1, 12), CultureInfo.InvariantCulture),
            _ => raw
        };
    }

    private static bool TryImageLength(byte[] data, int start, int available, byte type, out int length)
    {
        length = 0;
        if (available < 0x55) return false;

        if (type == 0x09 && IsBmp(data, start + 0x51))
        {
            var bmpSize = BitConverter.ToUInt32(data, start + 0x53);
            if (bmpSize is < 54 or > 8_000_000) return false;
            length = 0x51 + (int)bmpSize;
            return length <= available;
        }

        var dataLen = BitConverter.ToUInt32(data, start + 0x51);
        if (dataLen is 0 or > 8_000_000) return false;
        length = 85 + (int)dataLen;
        return length <= available;
    }

    private static bool TryWordArtLength(byte[] data, int start, int pageEnd, out int length)
    {
        length = 0;
        var marker = FindWordArtMarker(data, start, pageEnd);
        if (marker < 0) return false;
        if (!TryReadWordArtNF(data, marker, pageEnd, out var n, out var f, out _))
            return false;
        length = 141 + n * 2 + f;
        return length > 0 && start + length <= pageEnd;
    }

    private static bool TryUserDefinedLength(byte[] data, int start, int pageEnd, out int length)
    {
        length = 0;
        var m = start + 0x47;
        if (m + 4 > pageEnd || !Is2711(data, m)) return false;
        var fontLenPos = start + 0x47 + 4 + 24;
        if (fontLenPos + 4 > pageEnd) return false;
        var f = (int)BitConverter.ToUInt32(data, fontLenPos);
        if (f is < 0 or > 200) return false;
        var nPos = fontLenPos + 4 + f + 9;
        if (nPos + 4 > pageEnd) return false;
        var n = (int)BitConverter.ToUInt32(data, nPos);
        if (n is < 0 or > 10_000) return false;
        length = 134 + f + n * 2;
        return length > 0 && length <= pageEnd - start;
    }

    /// <summary>
    /// DGF_바코드_객체_분석_Rev2026-08-05.md + WPF DgfBarcodeRecordParser.
    /// 문서 +0x52/+0x53 은 타입 1바이트 기준과 2바이트 차이.
    /// 확정: Subtype +0x50, TextLength +0x51, Text +0x55. 길이 = Style 직후 + 45.
    /// </summary>
    private static bool TryBarcode1DLength(byte[] data, int start, int pageEnd, out int length)
    {
        length = 0;
        var shift = FieldNameShift(data, start, pageEnd);
        if (!TryReadBarcode1DCore(data, start, pageEnd, shift, out _, out _, out var afterStyle)
            && (shift == 0 || !TryReadBarcode1DCore(data, start, pageEnd, 0, out _, out _, out afterStyle)))
            return false;

        var available = pageEnd - start;
        var pos = afterStyle + 27;
        var hadCaption = false;
        while (pos + 4 <= pageEnd)
        {
            var sl = (int)BitConverter.ToUInt32(data, pos);
            if (sl is < 2 or > 40 || pos + 4 + sl > pageEnd) break;
            if (!LooksLikeCaptionBytes(data.AsSpan(pos + 4, sl))) break;
            pos += 4 + sl;
            hadCaption = true;
        }

        length = hadCaption ? pos + 10 - start : afterStyle + 45 - start;
        return length > 41 && length <= available;
    }

    private static bool TryReadBarcode1DCore(
        byte[] data, int start, int pageEnd, int shift,
        out int textLen, out int fontNameLen, out int afterStyle)
    {
        textLen = 0;
        fontNameLen = 0;
        afterStyle = 0;
        if (pageEnd - start < 0x55 + shift + 14) return false;
        textLen = (int)BitConverter.ToUInt32(data, start + 0x51 + shift);
        if (textLen is < 0 or > 80) return false;
        var textEnd = start + 0x55 + shift + textLen;
        if (textEnd + 14 > pageEnd) return false;
        fontNameLen = (int)BitConverter.ToUInt32(data, textEnd + 10);
        if (fontNameLen is < 0 or > 80) return false;
        afterStyle = textEnd + 14 + fontNameLen + 12;
        return afterStyle + 45 <= pageEnd;
    }

    private static bool LooksLikeCaptionBytes(ReadOnlySpan<byte> bytes)
    {
        if (bytes.Length == 0) return false;
        foreach (var b in bytes)
            if (b < 0x20) return false;
        return true;
    }

    private static bool TryBarcode2DLength(byte[] data, int start, int available, out int length)
    {
        length = 0;
        var shift = FieldNameShift(data, start, start + available);
        var classOff = 0x50 + shift;
        if (!TryReadClassAndText(data, start, available, classOff, out var nameLen, out var charCount, out _))
            return false;
        length = 158 + shift + nameLen + charCount * 2;
        if (length <= available) return true;
        var withoutTrailer = classOff + 4 + nameLen + 4 + charCount * 2;
        if (withoutTrailer <= available)
        {
            length = available;
            return true;
        }
        return false;
    }

    private static bool TryExtendedLength(byte[] data, int start, int pageEnd, out int length)
    {
        length = 0;
        if (pageEnd - start < 0x4F + 47) return false;
        var rtfLen = BitConverter.ToUInt32(data, start + 0x4B);
        if (rtfLen > 2_000_000) return false;
        length = 126 + (int)rtfLen;
        if (start + length > pageEnd) return false;
        var rtf = start + 0x4F;
        return rtf + 5 < data.Length
               && data[rtf] == (byte)'{'
               && data[rtf + 1] == (byte)'\\'
               && data[rtf + 2] == (byte)'r'
               && data[rtf + 3] == (byte)'t'
               && data[rtf + 4] == (byte)'f';
    }

    private static bool TryTableLength(byte[] data, int start, int pageEnd, out int length)
    {
        length = 0;
        if (start + 0x53 > pageEnd) return false;
        var rows = (int)BitConverter.ToUInt32(data, start + 0x4B);
        var cols = (int)BitConverter.ToUInt32(data, start + 0x4F);
        if (rows is < 1 or > 40 || cols is < 1 or > 40) return false;
        length = 0x53 + 16 * (rows + cols);
        return start + length <= pageEnd;
    }

    /// <summary>
    /// 표 0x0F. md_formtec/표_색상_데이터_분석.md
    /// 닫힌 도형과 같이 +0x29 배경, +0x2D 플래그, +0x2E 선색, +0x33 선굵기.
    /// +0x47의 0x2711은 레코드 표식일 뿐 셀 텍스트가 아니다.
    /// +0x4B 행, +0x4F 열. +0x53부터 16바이트×(행+열).
    /// 셀 글은 별도 텍스트 항목이 확인될 때만. 바이너리 인쇄문자는 넣지 않는다.
    /// </summary>
    private static void ApplyTable(DesignObject obj, byte[] data, int start, int end)
    {
        obj.TableCells = [];
        obj.TableRowFills = [];
        obj.TableColFills = [];
        obj.BackgroundTransparent = false;
        obj.BackgroundFill = "#ffffff";
        obj.Stroke = "#000000";
        obj.TableBorderWidth = 0.2f;

        if (start + 0x29 + 4 <= end)
            obj.BackgroundFill = ColorRefCss(BitConverter.ToUInt32(data, start + 0x29));

        if (start + 0x2E + 4 <= end)
            obj.Stroke = ColorRefCss(BitConverter.ToUInt32(data, start + 0x2E));

        if (start + 0x33 + 10 <= end)
        {
            var thick = Extended80.ReadStandard(data.AsSpan(start + 0x33, 10));
            if (thick is > 0 and < 8)
                obj.TableBorderWidth = (float)Math.Clamp(thick, 0.08, 2);
        }

        if (start + 0x53 > end) return;
        var rows = (int)BitConverter.ToUInt32(data, start + 0x4B);
        var cols = (int)BitConverter.ToUInt32(data, start + 0x4F);
        if (rows is < 1 or > 40 || cols is < 1 or > 40) return;
        obj.TableRows = rows;
        obj.TableCols = cols;

        for (var i = 0; i < rows + cols; i++)
        {
            var off = start + 0x53 + i * 16;
            if (off + 16 > end) break;
            var enabled = data[off + 11];
            var color = enabled == 0 ? ColorRefCss(BitConverter.ToUInt32(data, off + 12)) : null;
            if (i < rows)
                obj.TableRowFills.Add(color);
            else
                obj.TableColFills.Add(color);
        }

        obj.EnsureTableSize();
        EditorLog.Info($"폼텍 표: {rows}x{cols} bg={obj.BackgroundFill} stroke={obj.Stroke}");
    }

    /// <summary>
    /// DGZ_텍스트_항목_최종_확정_2026-08-10.md + WPF DgfTextRecordParser.
    /// 117+2N+F 와 48+2N+F 는 표식 R(0x2711) 기준. 객체 앞머리 0x47은 별도.
    /// trailing 69바이트는 다음 객체 좌표이므로, 바코드 등이 이어지면 생략한다.
    /// </summary>
    private static bool TryTextLength(byte[] data, int start, int pageEnd, out int length)
    {
        const int outerHeader = 0x47;
        const int fixedWithTrailing = 117;
        const int fixedWithoutTrailing = 48;
        length = 0;
        var marker = start + outerHeader;
        if (!Is2711(data, marker) || marker + 8 > pageEnd) return false;
        var n = BitConverter.ToUInt32(data, marker + 4);
        if (n > 100_000) return false;
        var p = marker + 8 + n * 2L;
        if (p + 28 > pageEnd) return false;
        if (data[p + 2] > 2 || data[p + 8] > 2) return false;
        var f = BitConverter.ToUInt32(data, (int)p + 24);
        if (f > 200) return false;

        var n2f = (int)n * 2 + (int)f;
        var fullFromR = fixedWithTrailing + n2f;
        var shortFromR = fixedWithoutTrailing + n2f;

        // 본문을 훑지 않는다. Q+11/Q+12(문서 1바이트 차이) 후보만 다음 객체로 본다.
        foreach (var cand in new[] { marker + n2f + 46, marker + n2f + 48 })
        {
            if (cand <= start + 41 || cand + 41 > pageEnd) continue;
            if (!ExternalImportService.TryGeom(data, cand, pageEnd, out var nextType, out _, out _, out _, out _)
                || !IsObjectType(nextType))
                continue;
            length = cand - start;
            return length > 41;
        }

        if (marker + fullFromR <= pageEnd && IsValidTextRecord(data, marker + fullFromR, pageEnd))
        {
            length = outerHeader + fullFromR;
            return length > 41 && start + length <= pageEnd;
        }

        length = outerHeader + shortFromR;
        return length > 41 && start + length <= pageEnd;
    }

    private static bool IsValidTextRecord(byte[] data, int recordStart, int pageEnd)
    {
        if (!Is2711(data, recordStart) || recordStart + 8 > pageEnd) return false;
        var n = BitConverter.ToUInt32(data, recordStart + 4);
        if (n is 0 or > 100_000) return false;
        var p = recordStart + 8 + n * 2L;
        if (p + 28 > pageEnd) return false;
        if (data[p + 2] > 2 || data[p + 8] > 2) return false;
        var f = BitConverter.ToUInt32(data, (int)p + 24);
        return f <= 200;
    }

    private static bool IsObjectType(byte type) => type is
        0x00 or 0x02 or 0x04 or 0x05 or 0x06 or 0x07 or 0x08 or 0x09 or 0x0A or 0x0B
        or 0x0E or 0x0F or 0x10 or 0x16 or 0x18;

    internal static bool IsPlainLine(byte[] data, int start, int end)
        => !IsArrow(data, start, end);

    /// <summary>
    /// 화살표는 +0x57 이 1~4. 일반 선은 0.
    /// +0x4B의 0/1은 화살표 여부가 아니라 대각선 방향이다.
    /// </summary>
    internal static bool IsArrow(byte[] data, int start, int end)
    {
        var style = start + 0x57;
        return style < end && data[style] is >= 1 and <= 4;
    }

    internal static bool TryReadLineKind(byte[] data, int start, int end, out uint kind)
    {
        kind = 0;
        var marker = start + 0x47;
        if (marker + 8 <= end && Is2711(data, marker))
        {
            kind = BitConverter.ToUInt32(data, marker + 4);
            return true;
        }

        var scanTo = Math.Min(end, start + 100) - 8;
        for (var i = start + 0x40; i <= scanTo; i++)
        {
            if (!Is2711(data, i)) continue;
            kind = BitConverter.ToUInt32(data, i + 4);
            return true;
        }
        return false;
    }

    /// <summary>
    /// DGZ_그라데이션_프레임_분석.md. type 0x0E + R(+0x47)=0x2711 + RGB패드 + 방향 0~3 + 정밀도 1~100.
    /// 레코드 길이는 89바이트. 더 길게 잡으면 같은 칸의 텍스트를 삼킨다.
    /// </summary>
    internal static bool IsGradient(byte[] data, int start, int end)
    {
        var marker = start + 0x47;
        if (marker + 14 > end || !Is2711(data, marker)) return false;
        if (data[marker + 7] != 0 || data[marker + 11] != 0) return false;
        if (data[marker + 12] > 3) return false;
        var prec = data[marker + 13];
        return prec is >= 1 and <= 100;
    }

    private static void ApplyGradient(DesignObject obj, byte[] data, int start, int end)
    {
        obj.Type = ObjectType.Gradient;
        obj.BackgroundTransparent = false;
        var marker = start + 0x47;
        obj.Fill = RgbCss(data[marker + 4], data[marker + 5], data[marker + 6]);
        obj.GradientEnd = RgbCss(data[marker + 8], data[marker + 9], data[marker + 10]);
        obj.GradientDirection = data[marker + 12];
        obj.GradientPrecision = data[marker + 13];

        var framed = start + 0x2D < end && data[start + 0x2D] != 0;
        if (framed && start + 0x32 <= end)
            obj.Stroke = ColorRefCss(BitConverter.ToUInt32(data, start + 0x2E));
        else
            obj.Stroke = "transparent";

        obj.StrokeWidth = 0;
        if (framed && start + 0x33 + 10 <= end)
        {
            var thick = Extended80.ReadStandard(data.AsSpan(start + 0x33, 10));
            obj.StrokeWidth = (float)thick;
        }

        EditorLog.Info(
            $"폼텍 그라데이션: {obj.Fill}->{obj.GradientEnd} dir={obj.GradientDirection} prec={obj.GradientPrecision} frame={obj.Stroke} w={obj.StrokeWidth:0.###}");
    }

    /// <summary>
    /// 닫힌 도형(사각/원/둥근사각). geometry 41바이트 다음:
    /// +0x29 COLORREF 채우기, +0x2E COLORREF 선색, +0x33 Extended80 선굵기.
    /// +0x2D는 채우기 투명이 아니다. 「여러가지 사각형」에서 흰 채우기=1, 초록/하늘/보라=0.
    /// 0을 투명으로 보면 아래 3칸 채우기가 빠지고 속성 투명이 켜진다. 채우기는 항상 +0x29.
    /// </summary>
    private static void ApplyClosedShape(DesignObject obj, byte[] data, int start, int end)
    {
        obj.Fill = "transparent";
        obj.Stroke = "#000000";
        obj.StrokeWidth = 0.35f;
        obj.BackgroundTransparent = true;
        byte flag = 0xFF;

        if (start + 0x29 + 4 <= end)
        {
            var raw = BitConverter.ToUInt32(data, start + 0x29);
            if (start + 0x2D < end)
                flag = data[start + 0x2D];
            obj.Fill = ColorRefCss(raw);
            obj.BackgroundFill = obj.Fill;
            obj.BackgroundTransparent = false;
        }

        if (start + 0x32 <= end)
            obj.Stroke = ColorRefCss(BitConverter.ToUInt32(data, start + 0x2E));

        if (start + 0x33 + 10 <= end)
        {
            var thick = Extended80.ReadStandard(data.AsSpan(start + 0x33, 10));
            if (thick is > 0 and < 20)
                obj.StrokeWidth = (float)Math.Clamp(thick, 0.15, 8);
        }

        EditorLog.Info($"폼텍 도형: fill={obj.Fill} flag={flag:X2} stroke={obj.Stroke} w={obj.StrokeWidth:0.###}");
    }

    private static void ApplyLineOrArrow(DesignObject obj, byte[] data, int start, int end)
    {
        obj.BackgroundTransparent = true;
        obj.Stroke = "#000000";
        obj.StrokeWidth = 0.35f;

        if (start + 0x32 <= end)
            obj.Stroke = ColorRefCss(BitConverter.ToUInt32(data, start + 0x2E));

        if (start + 0x33 + 10 <= end)
        {
            var thick = Extended80.ReadStandard(data.AsSpan(start + 0x33, 10));
            if (thick is > 0 and < 20)
                obj.StrokeWidth = (float)Math.Clamp(thick, 0.15, 8);
        }

        obj.Fill = obj.Stroke;
        if (IsPlainLine(data, start, end))
        {
            obj.ShapeKind = ShapeKind.Line;
        }
        else
        {
            obj.ShapeKind = ShapeKind.Arrow;
            obj.ArrowHeads = ArrowHeads.End;
        }
    }

    /// <summary>
    /// 선/화살표 배치. DGF에는 끝점 (x1,y1)-(x2,y2)가 없다.
    /// 첫 4개 ext80은 히트 박스이고, 보이는 선보다 사방 약 5mm(한 축 10mm) 크다.
    /// 납작한 박스는 가로/세로 중앙선, 아니면 대각선.
    /// +0x4B 0=좌상→우하, 1=좌하→우상.
    /// </summary>
    internal static void FitFormtecLine(DesignObject obj, float x, float y, float w, float h, uint kind)
    {
        ShrinkLineHitBox(ref x, ref y, ref w, ref h);
        var aw = MathF.Abs(w);
        var ah = MathF.Abs(h);
        var longSide = MathF.Max(aw, ah);
        var shortSide = MathF.Min(aw, ah);
        var aspect = longSide < 0.01f ? 0 : shortSide / longSide;
        if (aspect < 0.45f)
        {
            FitLineBox(obj, x, y, w, h);
            return;
        }

        if (kind == 1)
            FitDiagonal(obj, x, y + h, w, -h);
        else
            FitDiagonal(obj, x, y, w, h);
    }

    /// <summary>
    /// 가로선 히트 박스 높이가 10mm로 반복된다. 사방 5mm를 빼고 안쪽을 선으로 쓴다.
    /// </summary>
    private static void ShrinkLineHitBox(ref float x, ref float y, ref float w, ref float h)
    {
        const float pad = 5f;
        const float minInner = 0.4f;
        if (MathF.Abs(w) > pad * 2f + minInner)
        {
            x += MathF.CopySign(pad, w);
            w -= MathF.CopySign(pad * 2f, w);
        }
        if (MathF.Abs(h) > pad * 2f + minInner)
        {
            y += MathF.CopySign(pad, h);
            h -= MathF.CopySign(pad * 2f, h);
        }
        else
        {
            y += h / 2f;
            h = 0;
        }
    }

    /// <summary>
    /// 납작한 박스: 긴 변 방향으로 중앙을 지난다.
    /// </summary>
    internal static void FitLineBox(DesignObject obj, float x, float y, float w, float h)
    {
        var thick = Math.Max(obj.StrokeWidth * 6f, 1.2f);
        if (MathF.Abs(w) >= MathF.Abs(h))
        {
            obj.Width = Math.Max(MathF.Abs(w), 0.4f);
            obj.Height = thick;
            obj.X = w >= 0 ? x : x + w;
            obj.Y = y + h / 2f - thick / 2f;
            obj.Rotation = 0;
            return;
        }

        obj.Width = Math.Max(MathF.Abs(h), 0.4f);
        obj.Height = thick;
        obj.X = x + w / 2f - obj.Width / 2f;
        obj.Y = y + h / 2f - thick / 2f;
        obj.Rotation = 90;
    }

    internal static void FitDiagonal(DesignObject obj, float x, float y, float w, float h)
    {
        var len = MathF.Sqrt(w * w + h * h);
        if (len < 0.4f) len = 0.4f;
        var angle = MathF.Atan2(h, w) * (180f / MathF.PI);
        var thick = Math.Max(obj.StrokeWidth * 6f, obj.ShapeKind == ShapeKind.Arrow ? 3.2f : 1.2f);
        obj.Width = len;
        obj.Height = thick;
        obj.X = x + w / 2f - obj.Width / 2f;
        obj.Y = y + h / 2f - obj.Height / 2f;
        obj.Rotation = angle;
    }

    private static void ApplyImage(DesignObject obj, byte[] data, int start, int end, bool clipart)
    {
        if (start + 0x55 > end) return;

        int dataOff;
        int dataLen;
        string mime;

        if (!clipart && IsBmp(data, start + 0x51))
        {
            dataOff = start + 0x51;
            dataLen = (int)BitConverter.ToUInt32(data, start + 0x53);
            mime = "image/bmp";
        }
        else
        {
            dataOff = start + 0x55;
            dataLen = (int)BitConverter.ToUInt32(data, start + 0x51);
            mime = DetectMime(data, dataOff);
        }

        if (dataLen <= 0 || dataOff + dataLen > data.Length) return;
        var bytes = data.AsSpan(dataOff, dataLen).ToArray();
        var enc = System.Diagnostics.Stopwatch.StartNew();
        obj.ImageData = ExternalImportService.ToDataUrl(bytes, mime);
        obj.ImageFit = "contain";
        obj.StrokeWidth = 0;
        obj.BackgroundTransparent = true;

        if (mime == "image/wmf")
        {
            if (FormtecWmfShape.TryConvertClipart(bytes, out var parts) && parts.Count > 0)
            {
                obj.SvgParts = parts;
                obj.Svg = parts[0].D;
                obj.ClipartId = "formtec-wmf";
                obj.Fill = parts.Select(p => p.Fill).FirstOrDefault(f =>
                    !string.IsNullOrWhiteSpace(f) && f is not "transparent" && !f.Equals("#ffffff", StringComparison.OrdinalIgnoreCase))
                    ?? "#2E2A27";
                EditorLog.Info($"폼텍 클립아트 WMF: {dataLen}b paths={parts.Count} encode={enc.ElapsedMilliseconds}ms");
                return;
            }

            EditorLog.Warn($"폼텍 클립아트 WMF 경로 변환 실패 {dataLen}b");
            return;
        }

        if (mime == "application/octet-stream")
        {
            obj.ImageData = null;
            return;
        }

        obj.Svg = null;
        obj.SvgParts = null;
        if (clipart)
            obj.ClipartId = "embedded";
        obj.Fill = "transparent";
        EditorLog.Info($"폼텍 이미지: {mime} {dataLen}b encode={enc.ElapsedMilliseconds}ms");
    }

    private static readonly string[] Barcode1DFormats =
    [
        "EAN_8", "EAN_13", "CODABAR", "CODE_39", "CODE_39_EXT",
        "CODE_93", "CODE_93_EXT", "CODE_128", "ABC_CODABAR",
        "I25_DATALOGIC", "ITF", "I25_MATRIX", "I25_INDUSTRIAL",
        "I25_IATA", "I25_INVERT", "ITF", "ISBN", "ISSN", "ISMN",
        "UPC_A", "UPC_E0", "UPC_E1", "UPC_A", "JAN_8", "JAN_13",
        "MSI", "POSTNET", "OPC", "EAN_128", "COOP25",
        "CODE_11", "PZN"
    ];

    private static void ApplyBarcode1D(DesignObject obj, byte[] data, int start, int end)
    {
        var shift = FieldNameShift(data, start, end);
        if (!TryReadBarcode1DCore(data, start, end, shift, out var textLen, out var fontNameLen, out var afterStyle))
        {
            if (shift == 0 || !TryReadBarcode1DCore(data, start, end, 0, out textLen, out fontNameLen, out afterStyle))
                return;
            shift = 0;
        }

        var subtype = data[start + 0x50 + shift];
        obj.BarcodeFormat = subtype < Barcode1DFormats.Length
            ? Barcode1DFormats[subtype]
            : "CODE_128";
        obj.Type = ObjectType.Barcode;
        obj.BarcodeVendor = "formtec";
        if (start + 0x55 + shift + textLen <= end)
            obj.BarcodeValue = Encoding.ASCII.GetString(data, start + 0x55 + shift, textLen);
        if (subtype == 0x10 || BarcodeCatalog.LooksLikeIsbn(obj.BarcodeValue))
            obj.BarcodeFormat = "ISBN";

        var p = start + 0x55 + shift + textLen;
        if (p + 14 > end) return;
        obj.BarcodeShowText = data[p] != 0;
        obj.BackgroundTransparent = data[p + 1] != 0;
        obj.BackgroundFill = ColorRefCss(BitConverter.ToUInt32(data, p + 2));
        obj.Fill = ColorRefCss(BitConverter.ToUInt32(data, p + 6));
        obj.Stroke = obj.Fill;

        var q = p + 14 + fontNameLen;
        if (q + 4 <= end)
            obj.FontSize = PtToMm(BitConverter.ToUInt32(data, q));
        if (q + 9 <= end)
            ApplyStyleFlags(obj, data[q + 8]);
        if (fontNameLen > 0 && p + 14 + fontNameLen <= end)
            FontCatalog.ApplyImportedFamily(obj, ExternalImportService.DecodeAnsi(data.AsSpan(p + 14, fontNameLen)));
        EditorLog.Info($"폼텍 1D: format={obj.BarcodeFormat} subtype=0x{subtype:X2} value={obj.BarcodeValue} font={obj.FontFamily} {FontCatalog.ToPt(obj.FontSize):0.#}pt show={obj.BarcodeShowText}");

        var pos = afterStyle + 27;
        while (pos + 4 <= end)
        {
            var sl = (int)BitConverter.ToUInt32(data, pos);
            if (sl is < 2 or > 40 || pos + 4 + sl > end) break;
            var slice = data.AsSpan(pos + 4, sl);
            if (!LooksLikeCaptionBytes(slice)) break;
            var caption = ExternalImportService.DecodeAnsi(slice);
            if (caption is "머리말" or "꼬리말")
                obj.BarcodeShowStartEnd = true;
            pos += 4 + sl;
        }

        for (var i = afterStyle; i + 4 <= end; i++)
        {
            var rot = BitConverter.ToInt32(data, i);
            if (rot is 90 or 180 or 270)
            {
                obj.Rotation = rot;
                break;
            }
        }
    }

    private static void ApplyBarcode2D(DesignObject obj, byte[] data, int start, int end)
    {
        var available = end - start;
        var shift = FieldNameShift(data, start, end);
        var classOff = 0x50 + shift;
        if (!TryReadClassAndText(data, start, available, classOff, out var nameLen, out var charCount, out var typeName))
            return;

        obj.BarcodeFormat = MapFormtec2DClass(typeName);
        obj.Type = ExternalImportService.Is2dBarcode(obj.BarcodeFormat) ? ObjectType.Qr : ObjectType.Barcode;
        obj.BarcodeVendor = "formtec";
        obj.BarcodeShowText = false;

        var textOff = start + classOff + 4 + nameLen + 4;
        var textBytes = charCount * 2;
        if (textOff + textBytes <= data.Length)
        {
            var payload = Encoding.Unicode.GetString(data, textOff, textBytes);
            if (payload.StartsWith("URL:", StringComparison.OrdinalIgnoreCase))
                payload = payload[4..];
            obj.BarcodeValue = payload;
            EditorLog.Info($"폼텍 2D: {obj.BarcodeFormat} class={typeName} n={payload.Length}");
            if (payload.StartsWith("MECARD:", StringComparison.OrdinalIgnoreCase)) obj.QrKind = "vcard";
            else if (payload.StartsWith("SMSTO:", StringComparison.OrdinalIgnoreCase)) obj.QrKind = "sms";
            else if (payload.StartsWith("MAILTO:", StringComparison.OrdinalIgnoreCase)) obj.QrKind = "email";
            else if (payload.StartsWith("TEL:", StringComparison.OrdinalIgnoreCase)) obj.QrKind = "phone";
            else if (payload.StartsWith("http", StringComparison.OrdinalIgnoreCase)) obj.QrKind = "url";
        }

        var q = textOff + textBytes;
        if (q + 8 > data.Length) return;

        obj.BackgroundTransparent = data[q] != 0;
        // Q+1..3 배경, Q+5..7 바코드/QR. 기본값(01 FFFFFF / 000000)과
        // 「배경 녹색」단독 파일이 이 순서다.
        obj.BackgroundFill = RgbCss(data[q + 1], data[q + 2], data[q + 3]);
        obj.Fill = RgbCss(data[q + 5], data[q + 6], data[q + 7]);
        obj.Stroke = obj.Fill;
        EditorLog.Info($"폼텍 2D 색: bar={obj.Fill} bg={obj.BackgroundFill} transparent={obj.BackgroundTransparent}");
    }

    private static string MapFormtec2DClass(string typeName)
    {
        if (typeName.Contains("DataMatrix", StringComparison.OrdinalIgnoreCase))
            return "DATA_MATRIX";
        if (typeName.Contains("MicroPDF", StringComparison.OrdinalIgnoreCase))
            return "MICRO_PDF417";
        if (typeName.Contains("PDF417", StringComparison.OrdinalIgnoreCase) || typeName.Contains("PDF_417", StringComparison.OrdinalIgnoreCase))
            return "PDF_417";
        if (typeName.Contains("Aztec", StringComparison.OrdinalIgnoreCase))
            return "AZTEC";
        if (typeName.Contains("QR", StringComparison.OrdinalIgnoreCase))
            return "QR_CODE";
        return typeName switch
        {
            "bcQRCode" => "QR_CODE",
            "bcDataMatrix" => "DATA_MATRIX",
            "bcPDF417" => "PDF_417",
            _ => "QR_CODE"
        };
    }

    private static void ApplyWordArt(DesignObject obj, byte[] data, int start, int end)
    {
        obj.TextMode = TextMode.WordArt;
        var marker = FindWordArtMarker(data, start, end);
        if (marker < 0 || !TryReadWordArtNF(data, marker, end, out var n, out var f, out var text))
            return;

        obj.Text = SanitizeImportedText(text);
        var extra = data[marker + 4];
        obj.WordArtStyle = extra == 0x01 ? WordArtStyle.Circle : WordArtStyle.None;

        var p = marker + 9 + n * 2;
        if (p + 3 <= data.Length)
            obj.TextAlign = AlignH(data[p + 2]);
        if (p + 8 <= data.Length)
            obj.Fill = ColorRefCss(BitConverter.ToUInt32(data, p + 4));

        var fontOff = p + 13;
        if (f > 0 && fontOff + f <= data.Length)
            FontCatalog.ApplyImportedFamily(obj, ExternalImportService.DecodeAnsi(data.AsSpan(fontOff, f)));

        var s = fontOff + f;
        if (s + 19 > data.Length) return;
        obj.FontSize = PtToMm(BitConverter.ToUInt32(data, s));
        var flags = data[s + 8];
        obj.Bold = (flags & 0x01) != 0;
        obj.Italic = (flags & 0x02) != 0;
        var wordAngle = (int)BitConverter.ToUInt32(data, s + 9);
        if (extra != 0x01)
            obj.Rotation = -wordAngle;
        obj.WordArtBend = Math.Clamp(Math.Abs(wordAngle), 8, 80);
        if (s + 18 < data.Length)
            obj.WordArtGuide = data[s + 18] != 0;
    }

    private static void ApplyUserDefined(DesignObject obj, byte[] data, int start, int end)
    {
        obj.TextMode = TextMode.Custom;
        var marker = start + 0x47;
        if (marker + 40 > end || !Is2711(data, marker)) return;

        var p = marker + 4;
        ApplyTextStyleHead(obj, data, p);
        var f = (int)BitConverter.ToUInt32(data, p + 24);
        var fontPos = p + 28;
        if (f < 0 || fontPos + f + 13 > data.Length) return;
        if (f > 0)
            FontCatalog.ApplyImportedFamily(obj, ExternalImportService.DecodeAnsi(data.AsSpan(fontPos, f)));

        var q = fontPos + f;
        obj.FontSize = PtToMm(BitConverter.ToUInt32(data, q));
        obj.Fill = ColorRefCss(BitConverter.ToUInt32(data, q + 4));
        ApplyStyleFlags(obj, data[q + 8]);

        var n = (int)BitConverter.ToUInt32(data, q + 9);
        var textPos = q + 13;
        var textBytes = n * 2;
        if (n < 0 || textPos + textBytes > data.Length) return;
        var raw = Encoding.Unicode.GetString(data, textPos, textBytes);
        obj.Text = SanitizeImportedText(raw);
        obj.TextWrap = "char";

        var paramPos = textPos + textBytes;
        if (paramPos + 16 <= data.Length)
        {
            obj.SerialStart = (int)BitConverter.ToUInt32(data, paramPos);
            obj.SerialStep = (int)Math.Max(1, BitConverter.ToUInt32(data, paramPos + 8));
            obj.SerialDigits = (int)Math.Clamp(BitConverter.ToUInt32(data, paramPos + 12), 1, 12);
        }

        ApplyCustomKind(obj, raw);
    }

    private static void ApplyExtended(DesignObject obj, byte[] data, int start, int end)
    {
        obj.TextMode = TextMode.Extended;
        if (start + 0x4F + 47 > end) return;
        var rtfLen = (int)BitConverter.ToUInt32(data, start + 0x4B);
        var rtfOff = start + 0x4F;
        if (rtfLen < 6 || rtfOff + rtfLen > data.Length) return;
        var rtf = Encoding.ASCII.GetString(data, rtfOff, rtfLen);
        obj.Text = SanitizeImportedText(ExtractRtfPlain(rtf));
        obj.TextWrap = "char";
        if (Regex.IsMatch(rtf, @"\\fs(\d+)"))
        {
            var fs = Regex.Match(rtf, @"\\fs(\d+)");
            if (int.TryParse(fs.Groups[1].Value, out var half))
                obj.FontSize = PtToMm((uint)Math.Max(1, half / 2));
        }
        if (rtf.Contains("\\b") && !rtf.Contains("\\b0")) obj.Bold = true;
        if (rtf.Contains("\\i") && !rtf.Contains("\\i0")) obj.Italic = true;
        var color = Regex.Match(rtf, @"\\red(\d+)\\green(\d+)\\blue(\d+)");
        if (color.Success)
            obj.Fill = RgbCss(byte.Parse(color.Groups[1].Value), byte.Parse(color.Groups[2].Value), byte.Parse(color.Groups[3].Value));
    }

    private static void ApplyPlainText(DesignObject obj, byte[] data, int start, int end)
    {
        var marker = start + 0x47;
        if (!Is2711(data, marker) || marker + 8 > end) return;
        var n = (int)BitConverter.ToUInt32(data, marker + 4);
        if (n is <= 0 or > 100_000) return;
        var textBytes = n * 2;
        if (marker + 8 + textBytes > data.Length) return;
        obj.Text = SanitizeImportedText(Encoding.Unicode.GetString(data, marker + 8, textBytes));
        var p = marker + 8 + textBytes;
        if (p + 28 > data.Length) return;
        ApplyTextStyleHead(obj, data, p);
        var f = (int)BitConverter.ToUInt32(data, p + 24);
        if (f < 0 || p + 28 + f + 8 > data.Length) return;
        if (f > 0)
            FontCatalog.ApplyImportedFamily(obj, ExternalImportService.DecodeAnsi(data.AsSpan(p + 28, f)));
        var q = p + 28 + f;
        obj.FontSize = PtToMm(BitConverter.ToUInt32(data, q));
        if (q + 8 <= data.Length)
            obj.Fill = ColorRefCss(BitConverter.ToUInt32(data, q + 4));
        if (q + 9 <= data.Length)
            ApplyStyleFlags(obj, data[q + 8]);

        // 폼텍 세로 문자열은 전용 세로쓰기가 아니다. 좁은 박스에서 가로 줄바꿈만 한다.
        // 본문에 CR/LF가 없어도 박스 폭에서 자동 줄바꿈. P+9: 0=글자, 1=단어.
        if (p + 9 < data.Length)
            obj.TextWrap = data[p + 9] == 1 ? "word" : "char";
        else
            obj.TextWrap = "char";
    }

    private static void ApplyTextStyleHead(DesignObject obj, byte[] data, int p)
    {
        if (p + 9 > data.Length) return;
        obj.TextAlign = AlignH(data[p + 2]);
        obj.BackgroundTransparent = data[p + 3] != 0;
        obj.BackgroundFill = ColorRefCss(BitConverter.ToUInt32(data, p + 4));
        obj.VerticalAlign = data[p + 8] switch { 0 => "top", 2 => "bottom", _ => "middle" };
        if (p + 24 <= data.Length)
            obj.LineHeight = MapFormtecLineHeight(BitConverter.ToDouble(data, p + 16));
    }

    private static void ApplyStyleFlags(DesignObject obj, byte flags)
    {
        obj.Bold = (flags & 0x01) != 0;
        obj.Italic = (flags & 0x02) != 0;
        obj.Underline = (flags & 0x04) != 0;
        obj.Strikeout = (flags & 0x08) != 0;
    }

    private static void ApplyCustomKind(DesignObject obj, string raw)
    {
        var m = CustomToken.Match(raw);
        if (!m.Success)
        {
            obj.CustomKind = "none";
            return;
        }

        if (m.Groups[1].Success)
        {
            obj.CustomKind = "date";
            obj.CustomFormat = m.Groups[1].Value switch
            {
                "1" => "yy-MM-dd",
                "3" => "yyyy-MM-dd dddd",
                "4" => "dddd, MM, dd, yyyy",
                _ => "yyyy-MM-dd"
            };
        }
        else if (m.Groups[2].Success)
        {
            obj.CustomKind = "time";
            obj.CustomFormat = m.Groups[2].Value switch
            {
                "2" => "tt hh mm",
                "3" => "HH mm ss",
                _ => "HH mm"
            };
        }
        else if (m.Value.Contains("HEX", StringComparison.Ordinal))
            obj.CustomKind = "hexserial";
        else
            obj.CustomKind = "serial";
    }

    private static string ExpandTokens(string raw, DesignObject obj, int labelIndex)
    {
        var clock = DateTime.Now;
        var weekdays = new[] { "일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일" };
        return CustomToken.Replace(raw, m =>
        {
            if (m.Groups[1].Success)
            {
                return int.Parse(m.Groups[1].Value, CultureInfo.InvariantCulture) switch
                {
                    1 => clock.ToString("yy-MM-dd", CultureInfo.InvariantCulture),
                    3 => clock.ToString("yyyy-MM-dd", CultureInfo.InvariantCulture) + " " + weekdays[(int)clock.DayOfWeek],
                    4 => $"{weekdays[(int)clock.DayOfWeek]}, {clock:MM}, {clock:dd}, {clock:yyyy}",
                    _ => clock.ToString("yyyy-MM-dd", CultureInfo.InvariantCulture)
                };
            }

            if (m.Groups[2].Success)
            {
                return int.Parse(m.Groups[2].Value, CultureInfo.InvariantCulture) switch
                {
                    2 => clock.ToString("(tt) hh mm", CultureInfo.GetCultureInfo("en-US")),
                    3 => clock.ToString("HH mm ss", CultureInfo.InvariantCulture),
                    _ => clock.ToString("HH mm", CultureInfo.InvariantCulture)
                };
            }

            var value = obj.SerialStart + labelIndex * Math.Max(1, obj.SerialStep);
            var digits = Math.Clamp(obj.SerialDigits, 1, 12);
            if (m.Value.Contains("HEX", StringComparison.Ordinal))
                return value.ToString($"X{digits}", CultureInfo.InvariantCulture);
            return value.ToString($"D{digits}", CultureInfo.InvariantCulture);
        });
    }

    private static string ExtractRtfPlain(string rtf)
    {
        if (string.IsNullOrEmpty(rtf)) return "";
        var body = Regex.Replace(rtf, @"\{\\fonttbl.*?\}\s*", "", RegexOptions.Singleline);
        body = Regex.Replace(body, @"\{\\colortbl.*?\}\s*", "", RegexOptions.Singleline);
        body = Regex.Replace(body, @"\{\\\*\\[^}]*\}", "", RegexOptions.Singleline);
        var cp949 = Encoding.GetEncoding(949);
        var sb = new StringBuilder();
        var hex = new List<byte>();
        void Flush()
        {
            if (hex.Count == 0) return;
            try { sb.Append(cp949.GetString(hex.ToArray())); } catch { /* ignore */ }
            hex.Clear();
        }

        for (var i = 0; i < body.Length; i++)
        {
            if (body[i] == '\\' && i + 1 < body.Length)
            {
                var next = body[i + 1];
                if (next is '\\' or '{' or '}')
                {
                    Flush();
                    sb.Append(next);
                    i++;
                    continue;
                }

                if (next == '\'' && i + 3 < body.Length
                    && int.TryParse(body.AsSpan(i + 2, 2), NumberStyles.HexNumber, null, out var b))
                {
                    hex.Add((byte)b);
                    i += 3;
                    continue;
                }

                Flush();
                if (next == 'u')
                {
                    var um = Regex.Match(body[i..], @"^\\u(-?\d+)\??");
                    if (um.Success && int.TryParse(um.Groups[1].Value, out var code))
                    {
                        if (code < 0) code += 65536;
                        sb.Append((char)code);
                        i += um.Length - 1;
                        continue;
                    }
                }

                if (i + 4 <= body.Length && body.AsSpan(i, 4).SequenceEqual("\\par")
                    && (i + 4 >= body.Length || !char.IsLetter(body[i + 4])))
                {
                    sb.Append('\n');
                    i += 3;
                    continue;
                }

                i++;
                while (i < body.Length && (char.IsLetter(body[i]) || body[i] == '*')) i++;
                if (i < body.Length && (body[i] == '-' || char.IsDigit(body[i])))
                {
                    if (body[i] == '-') i++;
                    while (i < body.Length && char.IsDigit(body[i])) i++;
                }
                continue;
            }

            Flush();
            if (body[i] is '{' or '}') continue;
            if (body[i] != '\r') sb.Append(body[i]);
        }

        Flush();
        return sb.ToString().Trim();
    }

    private static int FieldNameShift(byte[] data, int start, int limit)
    {
        var marker = start + 0x47;
        if (marker + 9 > limit || !Is2711(data, marker)) return 0;
        var fnLen = (int)BitConverter.ToUInt32(data, marker + 5);
        if (fnLen is < 0 or > 200) return 0;
        var after = 0x47 + 4 + 1 + 4 + fnLen;
        return Math.Max(0, after - (0x47 + 9));
    }

    private static bool TryReadClassAndText(
        byte[] data, int start, int available, int classOff,
        out int nameLen, out int charCount, out string typeName)
    {
        nameLen = 0;
        charCount = 0;
        typeName = "";
        if (classOff + 4 > available) return false;
        var nlen = BitConverter.ToUInt32(data, start + classOff);
        if (nlen is 0 or > 64) return false;
        nameLen = (int)nlen;
        var nameOff = start + classOff + 4;
        if (nameOff + nameLen + 4 > start + available) return false;
        for (var i = 0; i < nameLen; i++)
            if (data[nameOff + i] is < 0x20 or > 0x7E) return false;
        typeName = Encoding.ASCII.GetString(data, nameOff, nameLen);
        if (!typeName.StartsWith("bc", StringComparison.Ordinal)) return false;
        var count = BitConverter.ToUInt32(data, nameOff + nameLen);
        if (count > 100_000) return false;
        charCount = (int)count;
        return true;
    }

    private static int FindWordArtMarker(byte[] data, int start, int limit)
    {
        var end = Math.Min(limit, data.Length) - 13;
        for (var i = start; i <= end; i++)
        {
            if (!Is2711(data, i)) continue;
            var n = BitConverter.ToUInt32(data, i + 5);
            if (n is 0 or >= 100_000) continue;
            var textEnd = i + 9L + n * 2L;
            if (textEnd + 13 <= limit) return i;
        }
        return -1;
    }

    private static bool TryReadWordArtNF(byte[] data, int marker, int limit, out int n, out int f, out string text)
    {
        n = 0; f = 0; text = "";
        if (marker + 9 > limit) return false;
        n = (int)BitConverter.ToUInt32(data, marker + 5);
        if (n is <= 0 or > 100_000) return false;
        var p = marker + 9 + n * 2;
        if (p + 13 > limit || p + 13 > data.Length) return false;
        f = (int)BitConverter.ToUInt32(data, p + 9);
        if (f is <= 0 or > 200 || p + 13 + f + 44 > data.Length) return false;
        text = Encoding.Unicode.GetString(data, marker + 9, n * 2);
        return true;
    }

    private static bool Is2711(byte[] data, int offset)
        => offset + 4 <= data.Length && BitConverter.ToUInt32(data, offset) == 0x00002711;

    private static bool IsBmp(byte[] data, int offset)
        => offset + 2 <= data.Length && data[offset] == (byte)'B' && data[offset + 1] == (byte)'M';

    private static string DetectMime(byte[] data, int offset)
    {
        if (offset + 8 <= data.Length && data.AsSpan(offset, 8).SequenceEqual(PngSig)) return "image/png";
        if (offset + 3 <= data.Length && data[offset] == 0xFF && data[offset + 1] == 0xD8 && data[offset + 2] == 0xFF)
            return "image/jpeg";
        if (IsBmp(data, offset)) return "image/bmp";
        if (offset + 3 <= data.Length && data[offset] == (byte)'G' && data[offset + 1] == (byte)'I' && data[offset + 2] == (byte)'F')
            return "image/gif";
        if (offset + 4 <= data.Length && data[offset] == 0xD7 && data[offset + 1] == 0xCD && data[offset + 2] == 0xC6 && data[offset + 3] == 0x9A)
            return "image/wmf";
        return "application/octet-stream";
    }

    /// <summary>
    /// 폼텍이 문단 앞에 넣는 LRM(U+200E) 등은 화면에 안 그린다.
    /// 글리프가 없어 네모(□)로 보이던 제어문자만 뺀다. 정렬 바이트는 본문이 아니다.
    /// </summary>
    private static string SanitizeImportedText(string text)
    {
        if (string.IsNullOrEmpty(text)) return text;
        var sb = new StringBuilder(text.Length);
        foreach (var rune in text.EnumerateRunes())
        {
            if (IsInvisibleFormat(rune.Value)) continue;
            sb.Append(rune);
        }
        return sb.ToString().TrimEnd('\r', '\n');
    }

    /// <summary>
    /// md_formtec 행간격 표. 저장 double은 배율이 아니라 단계 코드다.
    /// 0=기본, ±1.90625=1칸, ±1.96875=2칸, ±1.984375=3칸.
    /// 1.2+값×0.05로 바꾸면 +3도 1.30이라 화면에 차이가 없다.
    /// </summary>
    private static float MapFormtecLineHeight(double spacing)
    {
        if (Math.Abs(spacing) < 0.01)
            return 1.2f;

        var a = Math.Abs(spacing);
        var step = a < 1.94 ? 1 : a < 1.977 ? 2 : 3;
        return spacing > 0
            ? step switch { 1 => 1.6f, 2 => 2.0f, _ => 2.4f }
            : step switch { 1 => 1.0f, 2 => 0.82f, _ => 0.68f };
    }

    internal static bool IsInvisibleFormat(int code)
    {
        if (code is 0x200B or 0x200C or 0x200D or 0x200E or 0x200F)
            return true;
        if (code is >= 0x202A and <= 0x202E)
            return true;
        if (code is >= 0x2066 and <= 0x2069)
            return true;
        if (code is 0xFEFF or 0x00AD)
            return true;
        return code < 0x20 && code is not (0x09 or 0x0A or 0x0D);
    }

    private static string AlignH(byte v) => v switch { 1 => "right", 2 => "center", _ => "left" };
    private static float PtToMm(uint pt) => Math.Clamp(pt * 0.3528f, 1.4f, 28f);
    private static string RgbCss(byte r, byte g, byte b) => $"#{r:x2}{g:x2}{b:x2}";
    private static string ColorRefCss(uint c)
        => $"#{c & 0xFF:x2}{(c >> 8) & 0xFF:x2}{(c >> 16) & 0xFF:x2}";
}
