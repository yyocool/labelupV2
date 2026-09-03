using System.Globalization;
using LabelUp.Editor.Models;

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
    public static bool TryRead1DLegacy(byte[] data, int payloadStart, int payloadEnd, out string value, out string format)
    {
        value = "";
        format = "CODE_128";
        var marker = ExternalImportService.FindU32(data, payloadStart, Math.Min(payloadEnd, payloadStart + 8192), Marker);
        if (marker < 0 || marker + 0x23 + 1 > payloadEnd) return false;
        var type = data[marker + 0x1E];
        var len = BitConverter.ToInt32(data, marker + 0x1F);
        if (len is < 1 or > 512 || marker + 0x23 + len > payloadEnd) return false;
        var slice = data.AsSpan(marker + 0x23, len);
        if (!IsPrintableData(slice)) return false;
        value = System.Text.Encoding.Latin1.GetString(slice);
        format = MapLegacy(type);
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

    public static void Apply1DStyle(DesignObject obj, byte[] data, int payloadStart, int payloadEnd)
    {
        var marker = ExternalImportService.FindU32(data, payloadStart, Math.Min(payloadEnd, payloadStart + 8192), Marker);
        if (marker < 0) return;
        var len = marker + 0x19 + 4 <= payloadEnd ? BitConverter.ToInt32(data, marker + 0x19) : -1;
        if (len is < 0 or > 512) return;
        var opt = marker + 0x1D + len;
        if (opt + 9 > payloadEnd) return;
        obj.Fill = TColorCss(BitConverter.ToUInt32(data, opt));
        obj.BackgroundFill = TColorCss(BitConverter.ToUInt32(data, opt + 4));
        obj.BackgroundTransparent = false;
        obj.BarcodeShowText = data[opt + 8] != 0;
    }

    public static void Apply2DStyle(DesignObject obj, byte[] data, int afterBmp, int payloadEnd)
    {
        if (afterBmp + 8 > payloadEnd) return;
        var len = BitConverter.ToInt32(data, afterBmp + 4);
        var opt = afterBmp + 8 + len;
        if (len is < 0 or > 4096 || opt + 8 > payloadEnd) return;
        obj.Fill = TColorCss(BitConverter.ToUInt32(data, opt));
        obj.BackgroundFill = TColorCss(BitConverter.ToUInt32(data, opt + 4));
        obj.BackgroundTransparent = false;
    }

    public static bool TryRead2D(byte[] data, int payloadStart, int payloadEnd, out uint type, out string value, out string format, out int afterBmp)
    {
        type = 0;
        value = "";
        format = "QR_CODE";
        afterBmp = payloadStart;
        if (!TryBmpEnd(data, payloadStart, payloadEnd, out afterBmp))
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
    {
        end = from;
        var last = Math.Min(limit, from + 4096);
        for (var i = from; i + 6 < last; i++)
        {
            if (data[i] != (byte)'B' || data[i + 1] != (byte)'M') continue;
            var len = BitConverter.ToInt32(data, i + 2);
            if (len is > 54 and < 8_000_000 && i + len <= limit)
            {
                end = i + len;
                return true;
            }
        }
        return false;
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
        0x29 => "FIM",
        0x2B => "PLANET",
        0x2C => "POSTNET",
        0x2D => "KIX",
        0x2E => "JAPAN_POST",
        0x2F => "RM4SCC",
        0x33 => "ONECODE",
        _ => "CODE_128"
    };

    public static string Map2D(uint type) => type switch
    {
        0x00 or 0x01 => "AZTEC",
        0x03 or 0x04 => "DATA_MATRIX",
        0x06 => "PDF_417",
        0x07 => "MICRO_PDF417",
        0x08 or 0x09 => "QR_CODE",
        0x0D => "RSS_14",
        0x0E => "RSS_14",
        0x0F => "RSS_EXPANDED",
        _ => "QR_CODE"
    };

    private static string MapLegacy(byte type) => type switch
    {
        0x03 => "CODABAR",
        0x0C => "CODE_39",
        0x0E => "CODE_93",
        0x10 => "CODE_128",
        0x18 => "EAN_13",
        0x19 => "EAN_8",
        0x1A => "UPC_A",
        0x1B => "UPC_E",
        0x1C => "ITF",
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
