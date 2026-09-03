using LabelUp.Editor.Models;
using SkiaSharp;
using ZXing;
using ZXing.Common;
using ZXing.QrCode;

namespace LabelUp.Editor.Rendering;

public static class BarcodeRenderer
{
    public static void Draw(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        try
        {
            DrawCore(canvas, obj, value, alpha);
        }
        catch
        {
            DrawPlaceholder(canvas, obj, "바코드 오류", alpha);
        }
    }

    private static void DrawCore(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        if (string.IsNullOrWhiteSpace(value))
        {
            DrawPlaceholder(canvas, obj, "값 없음", alpha);
            return;
        }

        var format = ResolveFormat(obj);
        var isMatrix = format is BarcodeFormat.QR_CODE or BarcodeFormat.DATA_MATRIX or BarcodeFormat.PDF_417
            or BarcodeFormat.AZTEC or BarcodeFormat.RSS_14 or BarcodeFormat.RSS_EXPANDED;
        var textH = obj.BarcodeShowText && !isMatrix
            ? Math.Min(obj.Height * 0.22f, obj.FontSize > 0 ? obj.FontSize : 2.4f)
            : 0f;
        var barH = Math.Max(1f, obj.Height - textH);

        var pxW = Math.Clamp((int)(obj.Width * 24), 32, isMatrix ? 600 : 800);
        var pxH = isMatrix ? Math.Clamp((int)(barH * 24), 32, 600) : 2;
        var matrix = EncodeBest(obj.BarcodeFormat, format, value, pxW, pxH, obj.QrEcc);
        if (matrix is null || matrix.Width < 1 || matrix.Height < 1)
        {
            DrawPlaceholder(canvas, obj, "바코드 오류", alpha);
            return;
        }

        if (!obj.BackgroundTransparent
            && !string.IsNullOrWhiteSpace(obj.BackgroundFill)
            && obj.BackgroundFill is not "transparent" and not "none")
        {
            using var bg = new SKPaint
            {
                Color = ColorUtil.Parse(obj.BackgroundFill, alpha),
                IsAntialias = false,
                Style = SKPaintStyle.Fill
            };
            canvas.DrawRect(0, 0, obj.Width, barH, bg);
        }

        using var paint = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Fill, alpha),
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };

        if (!isMatrix)
        {
            var cellW = obj.Width / matrix.Width;
            for (var x = 0; x < matrix.Width; x++)
            {
                if (!matrix[x, 0]) continue;
                canvas.DrawRect(x * cellW, 0, cellW + 0.02f, barH, paint);
            }
        }
        else
        {
            var rows = Math.Min(matrix.Height, 256);
            var cols = Math.Min(matrix.Width, 256);
            var cellW = obj.Width / cols;
            var cellH = barH / rows;
            for (var y = 0; y < rows; y++)
            {
                for (var x = 0; x < cols; x++)
                {
                    if (!matrix[x, y]) continue;
                    canvas.DrawRect(x * cellW, y * cellH, cellW + 0.02f, cellH + 0.02f, paint);
                }
            }
        }

        if (textH > 0)
        {
            using var tp = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true };
            using var font = new SKFont(DocumentRenderer.ResolveTypeface(false), textH * 0.85f);
            var shown = obj.BarcodeShowStartEnd ? $"*{value}*" : value;
            var tw = font.MeasureText(shown);
            var tx = Math.Max(0, (obj.Width - tw) / 2f);
            canvas.DrawText(shown, tx, barH + textH * 0.82f, SKTextAlign.Left, font, tp);
        }
    }

    private static void DrawPlaceholder(SKCanvas canvas, DesignObject obj, string msg, byte alpha)
    {
        using var fill = new SKPaint { Color = new SKColor(0xF3, 0xE8, 0xEC, alpha), IsAntialias = true };
        canvas.DrawRect(0, 0, obj.Width, obj.Height, fill);
        using var stroke = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Stroke, alpha),
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = 0.25f
        };
        canvas.DrawRect(0.1f, 0.1f, obj.Width - 0.2f, obj.Height - 0.2f, stroke);
        using var tp = new SKPaint { Color = ColorUtil.Parse("#6B6560", alpha), IsAntialias = true };
        using var font = new SKFont(DocumentRenderer.ResolveTypeface(false), Math.Min(3.2f, obj.Height * 0.3f));
        canvas.DrawText(msg, obj.Width / 2f, obj.Height / 2f + 1f, SKTextAlign.Center, font, tp);
    }

    private static BarcodeFormat ResolveFormat(DesignObject obj)
    {
        if (obj.Type == ObjectType.Qr)
            return BarcodeCatalog.Find(obj.BarcodeFormat)?.Zxing ?? BarcodeFormat.QR_CODE;
        return BarcodeCatalog.Find(obj.BarcodeFormat)?.Zxing ?? ParseFormat(obj.BarcodeFormat);
    }

    private static BarcodeFormat ParseFormat(string? raw)
    {
        return (raw ?? "").Replace("-", "_").ToUpperInvariant() switch
        {
            "CODE_39" or "CODE39" or "CODE_39_EXT" => BarcodeFormat.CODE_39,
            "CODE_93" or "CODE93" or "CODE_93_EXT" => BarcodeFormat.CODE_93,
            "EAN_13" or "EAN13" or "JAN_13" or "ISBN" or "ISSN" or "ISMN" => BarcodeFormat.EAN_13,
            "EAN_5" or "EAN_2" => BarcodeFormat.CODE_128,
            "EAN_8" or "EAN8" or "JAN_8" => BarcodeFormat.EAN_8,
            "UPC_A" or "UPCA" => BarcodeFormat.UPC_A,
            "UPC_E" or "UPCE" or "UPC_E0" or "UPC_E1" => BarcodeFormat.UPC_E,
            "ITF" or "ITF_14" or "ITF_6" or "ITF_16" or "I25_INDUSTRIAL" or "I25_MATRIX"
                or "I25_DATALOGIC" or "I25_IATA" or "I25_INVERT" or "COOP25" or "LEITCODE" or "IDENTCODE" => BarcodeFormat.ITF,
            "CODABAR" or "ABC_CODABAR" => BarcodeFormat.CODABAR,
            "MSI" => BarcodeFormat.MSI,
            "PLESSEY" => BarcodeFormat.PLESSEY,
            "QR" or "QR_CODE" => BarcodeFormat.QR_CODE,
            "DATA_MATRIX" or "DATAMATRIX" => BarcodeFormat.DATA_MATRIX,
            "PDF_417" or "PDF417" or "PDF_417_TRUNC" or "MICRO_PDF417" => BarcodeFormat.PDF_417,
            "AZTEC" => BarcodeFormat.AZTEC,
            "PHARMA_1" or "PHARMA_2" => BarcodeFormat.PHARMA_CODE,
            "RSS_14" => BarcodeFormat.RSS_14,
            "RSS_EXPANDED" => BarcodeFormat.RSS_EXPANDED,
            "ONECODE" => BarcodeFormat.IMB,
            _ => BarcodeFormat.CODE_128
        };
    }

    private static BitMatrix? EncodeBest(string? catalogId, BarcodeFormat format, string value, int pxW, int pxH, string? ecc)
    {
        foreach (var (fmt, payload) in EncodeCandidates(catalogId, format, value))
        {
            var matrix = TryEncode(fmt, payload, pxW, pxH, ecc);
            if (matrix is { Width: > 0, Height: > 0 })
                return matrix;
        }
        return TryEncode(BarcodeFormat.CODE_128, value, pxW, 2, ecc);
    }

    private static IEnumerable<(BarcodeFormat Format, string Value)> EncodeCandidates(string? catalogId, BarcodeFormat format, string value)
    {
        var id = (catalogId ?? "").ToUpperInvariant();
        var digits = DigitsOnly(value);

        if (id is "EAN_2" or "EAN_5" or "FIM" or "PATCH_CODE" or "FLATTERMARKEN"
            or "CHANNEL_CODE" or "BC309" or "BC412" or "CLOCKED_35" or "ONECODE"
            or "POSTNET" or "PLANET" or "KIX" or "JAPAN_POST" or "RM4SCC" or "UPU"
            or "TELEPEN")
        {
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.CODABAR)
        {
            yield return (BarcodeFormat.CODABAR, EnsureCodabar(value));
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.EAN_13)
        {
            if (digits.Length is 12 or 13)
                yield return (BarcodeFormat.EAN_13, digits.Length == 13 ? digits : digits);
            else if (digits.Length >= 12)
                yield return (BarcodeFormat.EAN_13, digits[..13]);
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.EAN_8)
        {
            if (digits.Length is 7 or 8)
                yield return (BarcodeFormat.EAN_8, digits);
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.UPC_A)
        {
            if (digits.Length is 11 or 12)
                yield return (BarcodeFormat.UPC_A, digits);
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.UPC_E)
        {
            if (digits.Length is >= 6 and <= 8)
                yield return (BarcodeFormat.UPC_E, digits);
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.ITF)
        {
            var itf = digits.Length % 2 == 1 ? "0" + digits : digits;
            if (itf.Length >= 2)
                yield return (BarcodeFormat.ITF, itf);
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.CODE_39)
        {
            yield return (BarcodeFormat.CODE_39, value.ToUpperInvariant());
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.CODE_93)
        {
            yield return (BarcodeFormat.CODE_93, value.ToUpperInvariant());
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.PLESSEY)
        {
            yield return (BarcodeFormat.PLESSEY, value.ToUpperInvariant());
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.MSI)
        {
            if (digits.Length > 0)
                yield return (BarcodeFormat.MSI, digits);
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.PHARMA_CODE)
        {
            if (digits.Length > 0)
                yield return (BarcodeFormat.PHARMA_CODE, digits);
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.IMB)
        {
            if (digits.Length is 20 or 25 or 29 or 31)
                yield return (BarcodeFormat.IMB, digits);
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.RSS_14 && digits.Length > 0)
        {
            yield return (BarcodeFormat.RSS_14, digits.PadLeft(14, '0'));
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        yield return (format, value);
        if (format != BarcodeFormat.CODE_128)
            yield return (BarcodeFormat.CODE_128, value);
    }

    private static BitMatrix? TryEncode(BarcodeFormat format, string value, int pxW, int pxH, string? ecc)
    {
        try
        {
            EncodingOptions options = format switch
            {
                BarcodeFormat.QR_CODE => new QrCodeEncodingOptions
                {
                    Width = pxW,
                    Height = pxH,
                    Margin = 0,
                    CharacterSet = "UTF-8",
                    ErrorCorrection = ParseEcc(ecc)
                },
                BarcodeFormat.DATA_MATRIX or BarcodeFormat.PDF_417 or BarcodeFormat.AZTEC
                    => new EncodingOptions { Width = pxW, Height = pxH, Margin = 0, PureBarcode = true },
                _ => new EncodingOptions { Width = pxW, Height = format is BarcodeFormat.QR_CODE ? pxH : 2, Margin = 0, PureBarcode = true }
            };
            return new BarcodeWriterGeneric { Format = format, Options = options }.Encode(value);
        }
        catch
        {
            return null;
        }
    }

    private static string DigitsOnly(string value)
    {
        var sb = new System.Text.StringBuilder(value.Length);
        foreach (var ch in value)
        {
            if (char.IsAsciiDigit(ch))
                sb.Append(ch);
        }
        return sb.ToString();
    }

    private static string EnsureCodabar(string value)
    {
        var s = value.Trim();
        if (s.Length == 0) return "A0A";
        static bool Guard(char c) => c is 'A' or 'B' or 'C' or 'D' or 'a' or 'b' or 'c' or 'd';
        if (s.Length >= 2 && Guard(s[0]) && Guard(s[^1]))
            return s.ToUpperInvariant();
        return "A" + s + "A";
    }

    private static ZXing.QrCode.Internal.ErrorCorrectionLevel ParseEcc(string? raw) => (raw ?? "M").ToUpperInvariant() switch
    {
        "L" => ZXing.QrCode.Internal.ErrorCorrectionLevel.L,
        "Q" => ZXing.QrCode.Internal.ErrorCorrectionLevel.Q,
        "H" => ZXing.QrCode.Internal.ErrorCorrectionLevel.H,
        _ => ZXing.QrCode.Internal.ErrorCorrectionLevel.M
    };
}
