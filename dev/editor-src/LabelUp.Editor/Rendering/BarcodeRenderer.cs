using LabelUp.Editor.Models;
using LabelUp.Editor.Services;
using SkiaSharp;
using ZXing;
using ZXing.Aztec;
using ZXing.Common;
using ZXing.Datamatrix;
using ZXing.Datamatrix.Encoder;
using ZXing.PDF417;
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
        if (TryDrawEan13(canvas, obj, value, format, alpha))
            return;
        if (TryDrawIata25(canvas, obj, value, alpha))
            return;
        if (TryDrawCode39(canvas, obj, value, alpha))
            return;

        var isMatrix = format is BarcodeFormat.QR_CODE or BarcodeFormat.DATA_MATRIX or BarcodeFormat.PDF_417
            or BarcodeFormat.AZTEC or BarcodeFormat.RSS_14 or BarcodeFormat.RSS_EXPANDED;
        var textH = HriBand(obj, obj.BarcodeShowText && !isMatrix);
        var barH = Math.Max(1f, obj.Height - textH);

        var pxW = Math.Clamp((int)(obj.Width * 24), 32, isMatrix ? 600 : 800);
        var pxH = isMatrix ? Math.Clamp((int)(barH * 24), 32, 600) : 2;
        var matrix = EncodeBest(obj.BarcodeFormat, format, value, pxW, pxH, obj.QrEcc);
        if (matrix is null || matrix.Width < 1 || matrix.Height < 1)
        {
            DrawPlaceholder(canvas, obj, "바코드 오류", alpha);
            return;
        }

        FillBarcodeBackground(canvas, obj, alpha);

        using var paint = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Fill, alpha),
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };

        var digits = DigitsOnly(value);
        var ean13 = textH > 0 && IsEan13Family(obj.BarcodeFormat) && digits.Length is 12 or 13;
        var ean8 = textH > 0 && IsEan8Family(obj.BarcodeFormat) && digits.Length is 7 or 8;
        var upcA = textH > 0 && IsUpcA(obj.BarcodeFormat) && digits.Length is 11 or 12;
        var retail = ean13 || ean8 || upcA;
        var ean13Digits = ean13 ? EnsureEan13(digits) : "";
        var ean8Digits = ean8 ? EnsureEan8(digits) : "";
        var upcDigits = upcA ? EnsureUpcA(digits) : "";

        using var hriFont = CreateHriFont(obj, textH > 0 ? HriFontMm(obj, textH) : 1f);
        var leftPad = 0f;
        if (ean13 && ean13Digits.Length == 13)
            leftPad = hriFont.MeasureText(ean13Digits[0].ToString()) * 1.15f;
        else if (upcA && upcDigits.Length == 12)
            leftPad = hriFont.MeasureText(upcDigits[0].ToString()) * 1.15f;
        var showGuards = retail && textH > 0 && obj.BarcodeShowStartEnd;

        if (!isMatrix)
        {
            var barAreaW = Math.Max(1f, obj.Width - leftPad);
            var cellW = barAreaW / matrix.Width;
            var start = FirstBlack(matrix);
            var modules = ean8 ? 67 : 95;
            var scale = retail ? RetailModuleScale(matrix, start, modules) : 1f;
            for (var x = 0; x < matrix.Width; x++)
            {
                if (!matrix[x, 0]) continue;
                var guard = showGuards && IsRetailGuard(x, start, scale, ean8);
                var h = guard ? barH + textH * 0.92f : barH;
                canvas.DrawRect(leftPad + x * cellW, 0, cellW + 0.02f, h, paint);
            }

            if (textH > 0 && ean13 && ean13Digits.Length == 13)
            {
                DrawEan13Hri(canvas, obj, ean13Digits, leftPad, cellW, start, scale, barH, textH, hriFont, paint.Color, alpha, showGuards);
                return;
            }
            if (textH > 0 && ean8 && ean8Digits.Length == 8)
            {
                DrawEan8Hri(canvas, obj, ean8Digits, leftPad, cellW, start, scale, barH, textH, hriFont, paint.Color, alpha, showGuards);
                return;
            }
            if (textH > 0 && upcA && upcDigits.Length == 12)
            {
                DrawUpcAHri(canvas, obj, upcDigits, leftPad, cellW, start, scale, barH, textH, hriFont, paint.Color, alpha, showGuards);
                return;
            }
        }
        else
        {
            var rows = matrix.Height;
            var cols = matrix.Width;
            var cellW = obj.Width / cols;
            var cellH = barH / rows;
            var light = SKColors.Transparent;
            if (!obj.BackgroundTransparent
                && !string.IsNullOrWhiteSpace(obj.BackgroundFill)
                && obj.BackgroundFill is not "transparent" and not "none")
                light = ColorUtil.Parse(obj.BackgroundFill, alpha);
            using var lightPaint = new SKPaint { Color = light, IsAntialias = false, Style = SKPaintStyle.Fill };
            for (var y = 0; y < rows; y++)
            {
                for (var x = 0; x < cols; x++)
                {
                    // ZXing true = 어두운 모듈. Fill=점(QR색), BackgroundFill=바탕.
                    var on = matrix[x, y];
                    if (!on && light.Alpha == 0) continue;
                    canvas.DrawRect(x * cellW, y * cellH, cellW + 0.02f, cellH + 0.02f, on ? paint : lightPaint);
                }
            }
        }

        if (textH > 0)
        {
            using var tp = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true };
            using var font = CreateHriFont(obj, HriFontMm(obj, textH));
            var shown = obj.BarcodeShowStartEnd ? $"*{value}*" : value;
            var tw = font.MeasureText(shown);
            var tx = Math.Max(0, (obj.Width - tw) / 2f);
            DrawHriText(canvas, obj, shown, tx, barH + textH * 0.82f, SKTextAlign.Left, font, tp);
        }
    }

    private static bool IsEan13Family(string? format)
    {
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        return id is "EAN_13" or "EAN13" or "JAN_13" or "JAN13" or "ISBN" or "ISSN" or "ISMN" or "OPC";
    }

    private static bool IsEan8Family(string? format)
    {
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        return id is "EAN_8" or "EAN8" or "JAN_8" or "JAN8";
    }

    private static bool IsUpcA(string? format)
    {
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        return id is "UPC_A" or "UPCA";
    }

    private static int FirstBlack(BitMatrix matrix)
    {
        for (var x = 0; x < matrix.Width; x++)
        {
            if (matrix[x, 0]) return x;
        }
        return 0;
    }

    /// <summary>ZXing 1D는 Width로 모듈을 확대한다. 첫 검은 막대~마지막 검은 막대 = 전체 모듈.</summary>
    private static float RetailModuleScale(BitMatrix matrix, int start, int totalModules)
    {
        var last = start;
        for (var x = matrix.Width - 1; x >= start; x--)
        {
            if (matrix[x, 0])
            {
                last = x;
                break;
            }
        }
        var used = Math.Max(1, last - start + 1);
        return used / (float)Math.Max(1, totalModules);
    }

    /// <summary>EAN-13/UPC-A 95모듈, EAN-8 67모듈 기준 가드.</summary>
    private static bool IsRetailGuard(int x, int start, float scale, bool ean8)
    {
        var i = (x - start) / Math.Max(0.5f, scale);
        if (ean8)
            return i is >= 0 and < 3 or >= 31 and < 36 or >= 64 and < 67;
        return i is >= 0 and < 3 or >= 45 and < 50 or >= 92 and < 95;
    }

    private static void DrawEan13Hri(
        SKCanvas canvas, DesignObject obj, string digits, float leftPad, float cellW, int start, float scale,
        float barH, float textH, SKFont font, SKColor color, byte alpha, bool boxed)
    {
        using var tp = new SKPaint { Color = new SKColor(color.Red, color.Green, color.Blue, alpha), IsAntialias = true };
        var y = barH + textH * 0.82f;
        DrawHriText(canvas, obj, digits[0].ToString(), Math.Max(0, leftPad * 0.08f), y, SKTextAlign.Left, font, tp);
        DrawHriGroup(canvas, obj, digits.Substring(1, 6), leftPad, cellW, start, scale, 3, 45, y, font, tp, boxed);
        DrawHriGroup(canvas, obj, digits.Substring(7, 6), leftPad, cellW, start, scale, 50, 92, y, font, tp, boxed);
    }

    private static void DrawEan8Hri(
        SKCanvas canvas, DesignObject obj, string digits, float leftPad, float cellW, int start, float scale,
        float barH, float textH, SKFont font, SKColor color, byte alpha, bool boxed)
    {
        using var tp = new SKPaint { Color = new SKColor(color.Red, color.Green, color.Blue, alpha), IsAntialias = true };
        var y = barH + textH * 0.82f;
        DrawHriGroup(canvas, obj, digits[..4], leftPad, cellW, start, scale, 3, 31, y, font, tp, boxed);
        DrawHriGroup(canvas, obj, digits[4..], leftPad, cellW, start, scale, 36, 64, y, font, tp, boxed);
    }

    private static void DrawUpcAHri(
        SKCanvas canvas, DesignObject obj, string digits, float leftPad, float cellW, int start, float scale,
        float barH, float textH, SKFont font, SKColor color, byte alpha, bool boxed)
    {
        using var tp = new SKPaint { Color = new SKColor(color.Red, color.Green, color.Blue, alpha), IsAntialias = true };
        var y = barH + textH * 0.82f;
        DrawHriText(canvas, obj, digits[0].ToString(), Math.Max(0, leftPad * 0.08f), y, SKTextAlign.Left, font, tp);
        DrawHriGroup(canvas, obj, digits.Substring(1, 5), leftPad, cellW, start, scale, 3, 45, y, font, tp, boxed);
        DrawHriGroup(canvas, obj, digits.Substring(6, 5), leftPad, cellW, start, scale, 50, 92, y, font, tp, boxed);
    }

    private static void DrawHriGroup(
        SKCanvas canvas, DesignObject obj, string text, float leftPad, float cellW, int start, float scale,
        int moduleFrom, int moduleTo, float y, SKFont font, SKPaint tp, bool boxed)
    {
        var inset = boxed ? scale * 0.9f : 0f;
        var x0 = leftPad + (start + moduleFrom * scale + inset) * cellW;
        var x1 = leftPad + (start + moduleTo * scale - inset) * cellW;
        var maxW = Math.Max(1f, x1 - x0);
        var tw = font.MeasureText(text);
        var x = tw <= maxW ? x0 + (maxW - tw) / 2f : x0;
        DrawHriText(canvas, obj, text, x, y, SKTextAlign.Left, font, tp);
    }

    private static void DrawHriText(
        SKCanvas canvas, DesignObject obj, string text, float x, float y,
        SKTextAlign align, SKFont font, SKPaint paint)
    {
        canvas.DrawText(text, x, y, align, font, paint);
        if (!obj.Underline && !obj.Strikeout) return;
        var tw = font.MeasureText(text);
        var left = align switch
        {
            SKTextAlign.Right => x - tw,
            SKTextAlign.Center => x - tw / 2f,
            _ => x
        };
        using var lp = new SKPaint
        {
            Color = paint.Color,
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = Math.Max(0.12f, font.Size * 0.06f)
        };
        if (obj.Underline)
            canvas.DrawLine(left, y + font.Size * 0.12f, left + tw, y + font.Size * 0.12f, lp);
        if (obj.Strikeout)
            canvas.DrawLine(left, y - font.Size * 0.35f, left + tw, y - font.Size * 0.35f, lp);
    }

    private static string EnsureEan13(string digits)
    {
        if (digits.Length >= 13) return digits[..13];
        return digits.Length == 12 ? digits + EanChecksum(digits) : digits;
    }

    private static string EnsureEan8(string digits)
    {
        if (digits.Length >= 8) return digits[..8];
        return digits.Length == 7 ? digits + EanChecksum(digits) : digits;
    }

    private static string EnsureUpcA(string digits)
    {
        if (digits.Length >= 12) return digits[..12];
        return digits.Length == 11 ? digits + EanChecksum(digits) : digits;
    }

    private static char EanChecksum(string payload)
    {
        var sum = 0;
        for (var i = 0; i < payload.Length; i++)
        {
            var n = payload[i] - '0';
            var fromRight = payload.Length - i;
            sum += fromRight % 2 == 0 ? n : n * 3;
        }
        return (char)('0' + (10 - sum % 10) % 10);
    }

    private static float HriBand(DesignObject obj, bool show, float line = 1.28f)
    {
        if (!show) return 0f;
        var fs = obj.FontSize > 0.5f ? obj.FontSize : 2.4f;
        return Math.Min(obj.Height * 0.48f, fs * line);
    }

    private static float HriFontMm(DesignObject obj, float band)
    {
        var fs = obj.FontSize > 0.5f ? obj.FontSize : 2.4f;
        return Math.Min(fs, Math.Max(1.2f, band * 0.88f));
    }

    private static SKFont CreateHriFont(DesignObject obj, float sizeMm)
    {
        var font = new SKFont(DocumentRenderer.ResolveTypeface(obj.FontFamily, obj.Bold, obj.Italic), sizeMm);
        font.ScaleX = DocumentRenderer.HriWidthScale(obj.FontFamily);
        font.SkewX = obj.Italic && !DocumentRenderer.HasItalicFace(obj.FontFamily) ? -0.25f : 0f;
        return font;
    }

    private static void FillBarcodeBackground(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        if (obj.BackgroundTransparent) return;
        if (string.IsNullOrWhiteSpace(obj.BackgroundFill) || obj.BackgroundFill is "transparent" or "none")
            return;
        using var bg = new SKPaint
        {
            Color = ColorUtil.Parse(obj.BackgroundFill, alpha),
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };
        canvas.DrawRect(0, 0, obj.Width, obj.Height, bg);
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

    private static bool TryDrawEan13(SKCanvas canvas, DesignObject obj, string value, BarcodeFormat format, byte alpha)
    {
        var digits = DigitsOnly(value);
        var id = (obj.BarcodeFormat ?? "").ToUpperInvariant();
        var bookland = BarcodeCatalog.IsBookland(obj.BarcodeFormat) || BarcodeCatalog.LooksLikeIsbn(value);
        var eanFamily = id is "EAN_13" or "EAN13" or "JAN_13" or "JAN13" or "OPC";
        if (!bookland && !eanFamily && format != BarcodeFormat.EAN_13)
            return false;
        if (bookland)
            digits = ToBooklandEan13(id, digits);

        var encoded = WithEan13Checksum(digits);
        var modules = EncodeEan13Modules(encoded);
        if (modules is null) return false;
        if (encoded != digits)
            EditorLog.Info($"EAN-13 체크 보정 {digits} → {encoded}");

        var textH = HriBand(obj, obj.BarcodeShowText, bookland ? 1.35f : 1.28f);
        var barH = Math.Max(1f, obj.Height - textH);
        const int quiet = 7;
        var total = modules.Length + quiet * 2;
        var cellW = obj.Width / total;
        var barColor = ColorUtil.Parse(obj.Fill, alpha);

        FillBarcodeBackground(canvas, obj, alpha);
        DrawEanModules(canvas, modules, quiet, obj.Width, barH, barColor);

        if (textH > 0)
        {
            if (bookland)
                DrawBooklandHri(canvas, obj, value, barH, textH, barColor, alpha);
            else if (encoded.Length == 13)
            {
                using var font = CreateHriFont(obj, HriFontMm(obj, textH));
                DrawEan13Hri(canvas, obj, encoded, 0, cellW, quiet, 1f, barH, textH, font, barColor, alpha, obj.BarcodeShowStartEnd);
            }
        }
        return true;
    }

    /// <summary>ISBN/ISSN/ISMN은 EAN-13 그룹(9 788956 743169)이 아니라 하이픈 형태.</summary>
    private static void DrawBooklandHri(SKCanvas canvas, DesignObject obj, string value, float barH, float textH, SKColor color, byte alpha)
    {
        var shown = BarcodeCatalog.FormatBookland(obj.BarcodeFormat, value);
        if (string.IsNullOrWhiteSpace(shown)) return;
        var size = HriFontMm(obj, textH);
        using var font = CreateHriFont(obj, size);
        var tw = font.MeasureText(shown);
        if (tw > obj.Width * 0.98f && tw > 0)
            font.Size = size * (obj.Width * 0.98f / tw);
        tw = font.MeasureText(shown);
        var tx = Math.Max(0, (obj.Width - tw) / 2f);
        using var tp = new SKPaint
        {
            Color = new SKColor(color.Red, color.Green, color.Blue, alpha),
            IsAntialias = true
        };
        DrawHriText(canvas, obj, shown, tx, barH + textH * 0.82f, SKTextAlign.Left, font, tp);
    }

    /// <summary>
    /// Code 39 / 폼텍 PZN.
    /// 파일 값은 123456이지만, 막대는 IFA Code 39: `-` + PZN + mod-11 체크.
    /// 123456 → *-1234562*. 기준: IFA Technische Hinweise Code 39, ISO/IEC 16388.
    /// </summary>
    private static bool TryDrawCode39(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("PZN" or "CODE_39" or "CODE39" or "CODE_39_EXT"))
            return false;

        var payload = id is "PZN"
            ? (obj.UsesFormtecBarcodeRules ? ToPznCode39Payload(value) : value.Trim().ToUpperInvariant())
            : value.Trim().ToUpperInvariant();
        if (string.IsNullOrEmpty(payload)) return false;
        var modules = EncodeCode39(payload);
        if (modules is null) return false;

        var textH = HriBand(obj, obj.BarcodeShowText);
        var barH = Math.Max(1f, obj.Height - textH);
        var barColor = ColorUtil.Parse(obj.Fill, alpha);
        FillBarcodeBackground(canvas, obj, alpha);

        DrawEanModules(canvas, modules, quiet: 6, obj.Width, barH, barColor);
        if (textH > 0)
        {
            var shown = obj.BarcodeShowStartEnd ? $"*{payload}*" : payload;
            using var font = CreateHriFont(obj, HriFontMm(obj, textH));
            using var tp = new SKPaint { Color = barColor, IsAntialias = true };
            var tw = font.MeasureText(shown);
            var tx = Math.Max(0, (obj.Width - tw) / 2f);
            DrawHriText(canvas, obj, shown, tx, barH + textH * 0.82f, SKTextAlign.Left, font, tp);
        }
        return true;
    }

    /// <summary>ISO/IEC 16388. 1=wide, 0=narrow, 순서 bar-space-bar-space-bar-space-bar-space-bar.</summary>
    private static readonly Dictionary<char, string> Code39Patterns = new()
    {
        ['0'] = "000110100", ['1'] = "100100001", ['2'] = "001100001", ['3'] = "101100000",
        ['4'] = "000110001", ['5'] = "100110000", ['6'] = "001110000", ['7'] = "000100101",
        ['8'] = "100100100", ['9'] = "001100100", ['A'] = "100001001", ['B'] = "001001001",
        ['C'] = "101001000", ['D'] = "000011001", ['E'] = "100011000", ['F'] = "001011000",
        ['G'] = "000001101", ['H'] = "100001100", ['I'] = "001001100", ['J'] = "000011100",
        ['K'] = "100000011", ['L'] = "001000011", ['M'] = "101000010", ['N'] = "000010011",
        ['O'] = "100010010", ['P'] = "001010010", ['Q'] = "000000111", ['R'] = "100000110",
        ['S'] = "001000110", ['T'] = "000010110", ['U'] = "110000001", ['V'] = "011000001",
        ['W'] = "111000000", ['X'] = "010010001", ['Y'] = "110010000", ['Z'] = "011010000",
        ['-'] = "010000101", ['.'] = "110000100", [' '] = "011000100", ['$'] = "010101000",
        ['/'] = "010100010", ['+'] = "010001010", ['%'] = "000101010", ['*'] = "010010100"
    };

    private static bool[]? EncodeCode39(string payload)
    {
        if (payload.Length == 0) return null;
        foreach (var ch in payload)
            if (!Code39Patterns.ContainsKey(ch)) return null;

        var bits = new List<bool>(24 + payload.Length * 13);
        void AppendChar(char ch)
        {
            var p = Code39Patterns[ch];
            for (var i = 0; i < 9; i++)
            {
                var wide = p[i] == '1';
                var n = wide ? 2 : 1;
                var black = i % 2 == 0;
                for (var k = 0; k < n; k++) bits.Add(black);
            }
        }

        AppendChar('*');
        foreach (var ch in payload)
        {
            bits.Add(false);
            AppendChar(ch);
        }
        bits.Add(false);
        AppendChar('*');
        return bits.ToArray();
    }

    /// <summary>
    /// IFA PZN → Code 39 페이로드. 하이픈은 ISO/IEC 15418 식별자, 마지막은 mod-11 체크.
    /// PZN-7(6자리): 가중 2..7. PZN-8(7자리): 가중 1..7. 나머지 10이면 무효.
    /// </summary>
    private static string? ToPznCode39Payload(string value)
    {
        var digits = DigitsOnly(value);
        if (digits.Length is < 1 or > 8) return null;

        string data;
        var firstWeight = 2;
        if (digits.Length <= 6)
        {
            data = digits;
        }
        else if (digits.Length == 7)
        {
            var pzn7 = PznCheckDigit(digits[..6], 2);
            if (pzn7 >= 0 && pzn7 == digits[6] - '0')
                data = digits[..6];
            else
            {
                data = digits;
                firstWeight = 1;
            }
        }
        else
        {
            data = digits[..7];
            firstWeight = 1;
        }

        var check = PznCheckDigit(data, firstWeight);
        if (check < 0) return null;
        return $"-{data}{check}";
    }

    private static int PznCheckDigit(string data, int firstWeight)
    {
        var sum = 0;
        for (var i = 0; i < data.Length; i++)
            sum += (data[i] - '0') * (firstWeight + i);
        var rem = sum % 11;
        return rem == 10 ? -1 : rem;
    }

    /// <summary>
    /// IATA 2 of 5. 이산형: 막대만 굵기 변화, 간격은 항상 좁음. Interleaved(ITF)와 다름.
    /// 시작 1010, 숫자 5막대(2 wide), 종료 11101.
    /// </summary>
    private static bool TryDrawIata25(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("I25_IATA" or "IATA"))
            return false;
        var digits = DigitsOnly(value);
        var modules = EncodeIata25(digits);
        if (modules is null) return false;

        var textH = HriBand(obj, obj.BarcodeShowText);
        var barH = Math.Max(1f, obj.Height - textH);
        var barColor = ColorUtil.Parse(obj.Fill, alpha);
        FillBarcodeBackground(canvas, obj, alpha);

        DrawEanModules(canvas, modules, quiet: 4, obj.Width, barH, barColor);
        if (textH > 0)
        {
            using var font = CreateHriFont(obj, HriFontMm(obj, textH));
            using var tp = new SKPaint { Color = barColor, IsAntialias = true };
            var tw = font.MeasureText(digits);
            var tx = Math.Max(0, (obj.Width - tw) / 2f);
            DrawHriText(canvas, obj, digits, tx, barH + textH * 0.82f, SKTextAlign.Left, font, tp);
        }
        return true;
    }

    private static readonly string[] TwoOf5Bars =
    [
        "NNWWN", "WNNNW", "NWNNW", "WWNNN", "NNWNW",
        "WNWNN", "NWWNN", "NNNWW", "WNNWN", "NWNWN"
    ];

    private static bool[]? EncodeIata25(string digits)
    {
        if (digits.Length == 0) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;

        var bits = new List<bool>(8 + digits.Length * 14);
        void Run(int n, bool black)
        {
            for (var i = 0; i < n; i++) bits.Add(black);
        }

        Run(1, true);
        Run(1, false);
        Run(1, true);
        Run(1, false);
        foreach (var ch in digits)
        {
            var pat = TwoOf5Bars[ch - '0'];
            for (var i = 0; i < 5; i++)
            {
                Run(pat[i] == 'W' ? 2 : 1, true);
                Run(1, false);
            }
        }
        Run(2, true);
        Run(1, false);
        Run(1, true);
        return bits.ToArray();
    }

    private static string ToBooklandEan13(string id, string digits)
    {
        if (digits.Length >= 13) return digits[..13];
        if (digits.Length == 12) return digits;
        if (id is "ISSN" && digits.Length >= 7)
            return ("977" + digits.PadRight(9, '0'))[..12];
        if (id is "ISMN" && digits.Length is 9 or 10)
            return "9790" + (digits.Length == 10 ? digits[..8] : digits.PadLeft(8, '0'));
        if (digits.Length == 10)
            return "978" + digits[..9];
        return digits;
    }

    private static BarcodeFormat ResolveFormat(DesignObject obj)
        => BarcodeCatalog.Find(obj.BarcodeFormat)?.Zxing ?? ParseFormat(obj.BarcodeFormat);

    private static BarcodeFormat ParseFormat(string? raw)
    {
        var key = (raw ?? "").Replace("-", "_").ToUpperInvariant();
        if (key.Contains("DATAMATRIX") || key.Contains("DATA_MATRIX"))
            return BarcodeFormat.DATA_MATRIX;
        if (key.Contains("PDF_417") || key.Contains("PDF417"))
            return BarcodeFormat.PDF_417;
        if (key.Contains("AZTEC"))
            return BarcodeFormat.AZTEC;
        if (key is "QR" or "QR_CODE" || key.Contains("QRCODE") || key.Contains("QR_CODE"))
            return BarcodeFormat.QR_CODE;
        return key switch
        {
            "CODE_39" or "CODE39" or "CODE_39_EXT" => BarcodeFormat.CODE_39,
            "CODE_93" or "CODE93" or "CODE_93_EXT" => BarcodeFormat.CODE_93,
            "EAN_13" or "EAN13" or "JAN_13" or "ISBN" or "ISSN" or "ISMN" => BarcodeFormat.EAN_13,
            "EAN_5" or "EAN_2" => BarcodeFormat.CODE_128,
            "EAN_8" or "EAN8" or "JAN_8" => BarcodeFormat.EAN_8,
            "UPC_A" or "UPCA" => BarcodeFormat.UPC_A,
            "UPC_E" or "UPCE" or "UPC_E0" or "UPC_E1" => BarcodeFormat.UPC_E,
            "ITF" or "ITF_14" or "ITF_6" or "ITF_16" or "I25_INDUSTRIAL" or "I25_MATRIX"
                or "I25_DATALOGIC" or "I25_INVERT" or "COOP25" or "LEITCODE" or "IDENTCODE" => BarcodeFormat.ITF,
            "CODABAR" or "ABC_CODABAR" => BarcodeFormat.CODABAR,
            "MSI" => BarcodeFormat.MSI,
            "PLESSEY" => BarcodeFormat.PLESSEY,
            "PHARMA_1" or "PHARMA_2" => BarcodeFormat.PHARMA_CODE,
            "RSS_14" => BarcodeFormat.RSS_14,
            "RSS_EXPANDED" => BarcodeFormat.RSS_EXPANDED,
            "ONECODE" => BarcodeFormat.IMB,
            _ => objTypeFallback(key)
        };

        static BarcodeFormat objTypeFallback(string k)
            => k.Contains("QR") ? BarcodeFormat.QR_CODE : BarcodeFormat.CODE_128;
    }

    private static BitMatrix? EncodeBest(string? catalogId, BarcodeFormat format, string value, int pxW, int pxH, string? ecc)
    {
        foreach (var (fmt, payload) in EncodeCandidates(catalogId, format, value))
        {
            var matrix = TryEncode(fmt, payload, pxW, pxH, ecc);
            if (matrix is { Width: > 0, Height: > 0 })
                return matrix;
        }
        if (IsMatrixFormat(format) || format is BarcodeFormat.EAN_13 or BarcodeFormat.EAN_8 or BarcodeFormat.UPC_A)
            return null;
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
            var ean = id is "ISBN" && digits.Length == 10 ? "978" + digits[..9] : digits;
            if (ean.Length is 12 or 13)
                yield return (BarcodeFormat.EAN_13, ean);
            else if (ean.Length > 13)
                yield return (BarcodeFormat.EAN_13, ean[..13]);
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

        if (id is "I25_IATA" or "IATA")
            yield break;

        if (format == BarcodeFormat.ITF)
        {
            var itf = digits.Length % 2 == 1 ? "0" + digits : digits;
            if (itf.Length >= 2)
                yield return (BarcodeFormat.ITF, itf);
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (id is "PZN")
            yield break;

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

        if (IsMatrixFormat(format))
        {
            yield return (format, value);
            yield break;
        }

        yield return (format, value);
        if (format != BarcodeFormat.CODE_128)
            yield return (BarcodeFormat.CODE_128, value);
    }

    private static bool IsMatrixFormat(BarcodeFormat format)
        => format is BarcodeFormat.QR_CODE or BarcodeFormat.DATA_MATRIX
            or BarcodeFormat.PDF_417 or BarcodeFormat.AZTEC;

    private static BitMatrix? TryEncode(BarcodeFormat format, string value, int pxW, int pxH, string? ecc)
    {
        if (format == BarcodeFormat.EAN_13)
        {
            var modules = EncodeEan13Modules(DigitsOnly(value));
            if (modules is not null) return ModulesToMatrix(modules);
        }

        try
        {
            EncodingOptions options = format switch
            {
                BarcodeFormat.QR_CODE => new QrCodeEncodingOptions
                {
                    Width = 0,
                    Height = 0,
                    Margin = 0,
                    CharacterSet = "UTF-8",
                    ErrorCorrection = ParseEcc(ecc)
                },
                BarcodeFormat.DATA_MATRIX => new DatamatrixEncodingOptions
                {
                    Width = 0,
                    Height = 0,
                    Margin = 0,
                    PureBarcode = true,
                    SymbolShape = SymbolShapeHint.FORCE_SQUARE
                },
                BarcodeFormat.PDF_417 => new PDF417EncodingOptions
                {
                    Width = 0,
                    Height = 0,
                    Margin = 0,
                    PureBarcode = true
                },
                BarcodeFormat.AZTEC => new AztecEncodingOptions
                {
                    Width = 0,
                    Height = 0,
                    Margin = 0,
                    PureBarcode = true
                },
                _ => new EncodingOptions { Width = pxW, Height = format is BarcodeFormat.QR_CODE ? pxH : 2, Margin = 0, PureBarcode = true }
            };
            return new BarcodeWriterGeneric { Format = format, Options = options }.Encode(value);
        }
        catch
        {
            return null;
        }
    }

    /// <summary>
    /// 폼텍 ISBN은 값은 하이픈 그대로 두고, 막대는 EAN-13 체크디짓을 다시 계산한다.
    /// 이 파일 978-89-5674-316-9 → 막대 9788956743165. 저장된 9로 그리면 스캐너가 6788276743169로 읽는다.
    /// </summary>
    private static string WithEan13Checksum(string digits)
    {
        if (digits.Length == 12)
            return digits + EanChecksum(digits);
        if (digits.Length >= 13)
            return digits[..12] + EanChecksum(digits[..12]);
        return digits;
    }

    private static void DrawEanModules(SKCanvas canvas, bool[] modules, int quiet, float width, float barH, SKColor color)
    {
        var total = modules.Length + quiet * 2;
        var cellW = width / total;
        using var paint = new SKPaint
        {
            Color = color,
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };
        var i = 0;
        var x = quiet * cellW;
        while (i < modules.Length)
        {
            if (!modules[i])
            {
                x += cellW;
                i++;
                continue;
            }
            var n = 1;
            while (i + n < modules.Length && modules[i + n]) n++;
            canvas.DrawRect(x, 0, n * cellW, barH, paint);
            x += n * cellW;
            i += n;
        }
    }

    private static bool[]? EncodeEan13Modules(string digits)
    {
        digits = WithEan13Checksum(digits);
        if (digits.Length != 13) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;

        var first = digits[0] - '0';
        var leftParity = first switch
        {
            0 => "LLLLLL",
            1 => "LLGLGG",
            2 => "LLGGLG",
            3 => "LLGGGL",
            4 => "LGLLGG",
            5 => "LGGLLG",
            6 => "LGGGLL",
            7 => "LGLGLG",
            8 => "LGLGGL",
            _ => "LGGLGL"
        };

        var bits = new bool[95];
        var i = 0;
        WriteModules(bits, ref i, "101");
        for (var d = 0; d < 6; d++)
            WriteModules(bits, ref i, Ean7(digits[d + 1] - '0', leftParity[d]));
        WriteModules(bits, ref i, "01010");
        for (var d = 0; d < 6; d++)
            WriteModules(bits, ref i, Ean7(digits[d + 7] - '0', 'R'));
        WriteModules(bits, ref i, "101");
        return bits;
    }

    private static string Ean7(int digit, char set) => (set, digit) switch
    {
        ('L', 0) => "0001101",
        ('L', 1) => "0011001",
        ('L', 2) => "0010011",
        ('L', 3) => "0111101",
        ('L', 4) => "0100011",
        ('L', 5) => "0110001",
        ('L', 6) => "0101111",
        ('L', 7) => "0111011",
        ('L', 8) => "0110111",
        ('L', 9) => "0001011",
        ('G', 0) => "0100111",
        ('G', 1) => "0110011",
        ('G', 2) => "0011011",
        ('G', 3) => "0100001",
        ('G', 4) => "0011101",
        ('G', 5) => "0111001",
        ('G', 6) => "0000101",
        ('G', 7) => "0010001",
        ('G', 8) => "0001001",
        ('G', 9) => "0010111",
        (_, 0) => "1110010",
        (_, 1) => "1100110",
        (_, 2) => "1101100",
        (_, 3) => "1000010",
        (_, 4) => "1011100",
        (_, 5) => "1001110",
        (_, 6) => "1010000",
        (_, 7) => "1000100",
        (_, 8) => "1001000",
        _ => "1110100"
    };

    private static void WriteModules(bool[] bits, ref int i, string pattern)
    {
        foreach (var ch in pattern)
            bits[i++] = ch == '1';
    }

    private static BitMatrix ModulesToMatrix(bool[] modules)
    {
        var matrix = new BitMatrix(modules.Length, 1);
        for (var x = 0; x < modules.Length; x++)
        {
            if (modules[x]) matrix[x, 0] = true;
        }
        return matrix;
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
