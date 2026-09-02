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
        if (string.IsNullOrWhiteSpace(value))
        {
            DrawPlaceholder(canvas, obj, "값 없음", alpha);
            return;
        }

            var format = ResolveFormat(obj);
        var textH = obj.BarcodeShowText && format != BarcodeFormat.QR_CODE && format != BarcodeFormat.DATA_MATRIX
            ? Math.Min(obj.Height * 0.22f, obj.FontSize > 0 ? obj.FontSize : 2.4f)
            : 0f;
        var barH = Math.Max(1f, obj.Height - textH);

        BitMatrix? matrix = null;
        try
        {
            var pxW = Math.Clamp((int)(obj.Width * 24), 32, 1200);
            var pxH = Math.Clamp((int)(barH * 24), 32, 1200);
            EncodingOptions options = format switch
            {
                BarcodeFormat.QR_CODE => new QrCodeEncodingOptions
                {
                    Width = pxW,
                    Height = pxH,
                    Margin = 0,
                    CharacterSet = "UTF-8",
                    ErrorCorrection = ParseEcc(obj.QrEcc)
                },
                BarcodeFormat.DATA_MATRIX => new EncodingOptions { Width = pxW, Height = pxH, Margin = 0, PureBarcode = true },
                BarcodeFormat.PDF_417 => new EncodingOptions { Width = pxW, Height = pxH, Margin = 0, PureBarcode = true },
                _ => new EncodingOptions { Width = pxW, Height = pxH, Margin = 0, PureBarcode = true }
            };

            var writer = new BarcodeWriterGeneric { Format = format, Options = options };
            matrix = writer.Encode(value);
        }
        catch
        {
            DrawPlaceholder(canvas, obj, "바코드 오류", alpha);
            return;
        }

        if (matrix is null)
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

        var cellW = obj.Width / matrix.Width;
        var cellH = barH / matrix.Height;
        for (var y = 0; y < matrix.Height; y++)
        {
            for (var x = 0; x < matrix.Width; x++)
            {
                if (!matrix[x, y]) continue;
                canvas.DrawRect(x * cellW, y * cellH, cellW + 0.02f, cellH + 0.02f, paint);
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
            "EAN_13" or "EAN13" or "JAN_13" or "ISBN" or "ISSN" or "ISMN" or "EAN_5" or "EAN_2" => BarcodeFormat.EAN_13,
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

    private static ZXing.QrCode.Internal.ErrorCorrectionLevel ParseEcc(string? raw) => (raw ?? "M").ToUpperInvariant() switch
    {
        "L" => ZXing.QrCode.Internal.ErrorCorrectionLevel.L,
        "Q" => ZXing.QrCode.Internal.ErrorCorrectionLevel.Q,
        "H" => ZXing.QrCode.Internal.ErrorCorrectionLevel.H,
        _ => ZXing.QrCode.Internal.ErrorCorrectionLevel.M
    };
}
