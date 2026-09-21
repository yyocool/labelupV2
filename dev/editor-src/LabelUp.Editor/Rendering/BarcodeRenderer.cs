using System.Globalization;
using LabelUp.Editor.Models;
using LabelUp.Editor.Services;
using SkiaSharp;
using ZXing;
using ZXing.Aztec;
using ZXing.Common;
using ZXing.Datamatrix;
using ZXing.Datamatrix.Encoder;
using ZXing.PDF417;
using ZXing.PDF417.Internal;
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
        canvas.ClipRect(new SKRect(0, 0, obj.Width, obj.Height));
        if (string.IsNullOrWhiteSpace(value)
            && string.Equals(obj.BarcodeFormat, "KOREAN_POST", StringComparison.OrdinalIgnoreCase))
            value = DigitsOnly(obj.Text ?? "");
        if (string.IsNullOrWhiteSpace(value))
        {
            if (obj.DataBound) return;
            DrawPlaceholder(canvas, obj, "값 없음", alpha);
            return;
        }

        // 인코더가 없는 심볼은 여기서 끊는다. ParseFormat이 모르는 이름을 Code 128로 흘려보내
        // 원본과 전혀 다른 값이 읽히는 막대가 인쇄되기 때문이다.
        if (IsUnsupportedSymbology(obj))
        {
            DrawPlaceholder(canvas, obj, UnsupportedNotice(obj), alpha);
            return;
        }

        // USPS IMB 는 추적 20자리 + 배달구역 0·5·9·11자리라야 만들어진다.
        // 애니라벨은 값이 모자라면 제 샘플 추적번호를 대신 찍지만(52번 칸) 그건 남의 우편번호를
        // 인쇄물에 싣는 것이라 따라 하지 않는다. Code 128 로 떨어뜨려도 전혀 다른 심볼이 되므로
        // 그리지 않고 무엇이 모자란지 알려 준다.
        if (ImbShortage(obj, value) is { } shortage)
        {
            DrawPlaceholder(canvas, obj, shortage, alpha);
            return;
        }

        var format = ResolveFormat(obj);
        if (TryDrawEan13(canvas, obj, value, format, alpha))
            return;
        if (TryDrawEan8(canvas, obj, value, format, alpha))
            return;
        if (TryDrawEanAddon(canvas, obj, value, alpha))
            return;
        if (TryDrawDiscrete25(canvas, obj, value, alpha))
            return;
        if (TryDrawCode11(canvas, obj, value, alpha))
            return;
        if (TryDrawPatchCode(canvas, obj, value, alpha))
            return;
        if (TryDrawPostnet(canvas, obj, value, alpha))
            return;
        if (TryDrawFourState(canvas, obj, value, alpha))
            return;
        if (TryDrawFim(canvas, obj, value, alpha))
            return;
        if (TryDrawTelepen(canvas, obj, value, alpha))
            return;
        if (TryDrawPharmaTwo(canvas, obj, value, alpha))
            return;
        if (TryDrawPharmaOne(canvas, obj, value, alpha))
            return;
        if (TryDrawPlessey(canvas, obj, value, alpha))
            return;
        if (TryDrawSingleWidth(canvas, obj, value, alpha))
            return;
        if (TryDrawCodabar(canvas, obj, value, alpha))
            return;
        if (TryDrawCode39(canvas, obj, value, alpha))
            return;
        if (TryDrawCode93(canvas, obj, value, alpha))
            return;
        if (TryDrawCode128(canvas, obj, value, alpha))
            return;
        if (TryDrawUpcE(canvas, obj, value, alpha))
            return;
        if (TryDrawItf(canvas, obj, value, alpha))
            return;
        if (TryDrawOpc(canvas, obj, value, alpha))
            return;
        if (TryDrawNumly(canvas, obj, value, alpha))
            return;
        if (TryDrawMsi(canvas, obj, value, alpha))
            return;
        if (TryDrawKoreanPost(canvas, obj, value, alpha))
            return;
        if (TryDrawMaxiCode(canvas, obj, value, alpha))
            return;

        var isMatrix = format is BarcodeFormat.QR_CODE or BarcodeFormat.DATA_MATRIX or BarcodeFormat.PDF_417
            or BarcodeFormat.AZTEC or BarcodeFormat.RSS_14 or BarcodeFormat.RSS_EXPANDED;
        var textH = HriBand(obj, obj.BarcodeShowText && !isMatrix);
        var barH = Math.Max(1f, obj.Height - textH);

        var pxW = Math.Clamp((int)(obj.Width * 24), 32, isMatrix ? 600 : 800);
        var pxH = isMatrix ? Math.Clamp((int)(barH * 24), 32, 600) : 2;
        var matrix = EncodeBest(obj, format, value, pxW, pxH);
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
        // EAN/UPC 가드(시작·가운데·끝)는 숫자 표시와 같이 길어진다. Code 39의 * 표시와 무관하다.
        var showGuards = retail && textH > 0 && GuardBarsOn(obj);

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
                DrawEan13Hri(canvas, obj, ean13Digits, leftPad, cellW, start, scale, barH, textH, hriFont, HriColor(obj, alpha), alpha, showGuards);
                return;
            }
            if (textH > 0 && ean8 && ean8Digits.Length == 8)
            {
                DrawEan8Hri(canvas, obj, ean8Digits, leftPad, cellW, start, scale, barH, textH, hriFont, HriColor(obj, alpha), alpha, showGuards);
                return;
            }
            if (textH > 0 && upcA && upcDigits.Length == 12)
            {
                DrawUpcAHri(canvas, obj, upcDigits, leftPad, cellW, start, scale, barH, textH, hriFont, HriColor(obj, alpha), alpha, showGuards);
                return;
            }
        }
        else
        {
            var rows = matrix.Height;
            var cols = matrix.Width;
            var cellW = obj.Width / cols;
            var cellH = barH / rows;
            var ox = 0f;
            var oy = 0f;
            // 아이라벨: 모듈 피치는 26×26이 상자에 맞는 크기(실측). ASCII Auto만 상자를 채운다.
            // 폼텍은 상자 전체에 늘린다. 정렬은 왼쪽 위.
            if (format == BarcodeFormat.DATA_MATRIX
                && Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.ILabel)
            {
                var fit = Math.Min(obj.Width / cols, barH / rows);
                var pitch = Math.Min(obj.Width, barH) / 26f;
                var asciiAuto = (obj.QrEcc ?? "").Equals("ASCII", StringComparison.OrdinalIgnoreCase)
                    && (obj.QrKind ?? "").Equals("AUTO", StringComparison.OrdinalIgnoreCase);
                var cell = asciiAuto ? fit : Math.Min(fit, pitch);
                cellW = cellH = cell;
                ox = 0;
                oy = 0;
            }
            else if (format == BarcodeFormat.QR_CODE)
            {
                // QR은 상자가 직사각형이어도 정사각형으로 그린다. 정렬은 왼쪽 위.
                cellW = cellH = Math.Min(obj.Width / cols, barH / rows);
                ox = 0;
                oy = 0;
            }
            // 아이라벨 PDF417·Micro는 가로를 상자에 맞추고 행을 높이에 고르게 늘린다(실측).
            // 상자 높이는 ILabelImporter가 0.5in으로 키워 둔다.
            var light = SKColors.Transparent;
            if (HasOpaqueBackground(obj))
                light = ColorUtil.Parse(obj.BackgroundFill, alpha);
            using var lightPaint = new SKPaint { Color = light, IsAntialias = false, Style = SKPaintStyle.Fill };
            for (var y = 0; y < rows; y++)
            {
                for (var x = 0; x < cols; x++)
                {
                    // ZXing true = 어두운 모듈. Fill=점(QR색), BackgroundFill=바탕.
                    var on = matrix[x, y];
                    if (!on && light.Alpha == 0) continue;
                    canvas.DrawRect(ox + x * cellW, oy + y * cellH, cellW + 0.02f, cellH + 0.02f, on ? paint : lightPaint);
                }
            }

            if (format == BarcodeFormat.QR_CODE)
                DrawQrLogo(canvas, obj, ox, oy, cols * cellW, rows * cellH, alpha);
        }

        if (textH > 0 && DocumentRenderer.FontsReady)
        {
            using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
            using var font = CreateHriFont(obj, HriFontMm(obj, textH));
            // 시작·정지 문자 `*`는 Code 39 전용이고 그쪽은 TryDrawCode39가 맡는다.
            // 여기까지 오는 1D는 UPC-A·GS1 DataBar 같은 GS1 캐리어라, HRI에 시작·정지 문자를 넣지 않는다.
            DrawCenteredHri(canvas, obj, value, barH, textH, font, tp, obj.Width);
        }
    }

    /// <summary>
    /// QR 가운데 로고. 아이라벨은 크기를 파일에 남기지 않는다(Factors 행의 Image* 칸이 전부 0).
    /// 실측으로 정했다. 스크린샷 석 장(6페이지 8·10·14번)에서 로고의 색 있는 부분이 심볼 한 변의
    /// 36.3·36.4·36.9%를 차지했고, 같은 그림을 30% 정사각형에 넣은 우리 쪽은 27.6~27.8%였다.
    /// 그림 좌우 흰 여백(약 7.5%)을 감안하면 아이라벨의 상자는 한 변의 40%다.
    /// </summary>
    private const float QrLogoFraction = 0.4f;

    private static void DrawQrLogo(
        SKCanvas canvas, DesignObject obj, float ox, float oy, float w, float h, byte alpha)
    {
        if (string.IsNullOrEmpty(obj.QrLogoData)) return;
        var bmp = DocumentRenderer.GetBitmap(obj, obj.QrLogoData);
        if (bmp is null || bmp.Width < 1 || bmp.Height < 1) return;

        var side = Math.Min(w, h) * QrLogoFraction;
        var scale = Math.Min(side / bmp.Width, side / bmp.Height);
        var dw = bmp.Width * scale;
        var dh = bmp.Height * scale;
        var dest = new SKRect(
            ox + (w - dw) / 2f, oy + (h - dh) / 2f,
            ox + (w + dw) / 2f, oy + (h + dh) / 2f);

        using var paint = new SKPaint { IsAntialias = true, Color = SKColors.White.WithAlpha(alpha) };
        using var image = SKImage.FromBitmap(bmp);
        canvas.DrawImage(image, new SKRect(0, 0, bmp.Width, bmp.Height), dest,
            new SKSamplingOptions(SKFilterMode.Linear, SKMipmapMode.Linear), paint);
    }

    /// <summary>
    /// 애니라벨 소매 계열 가드. 구형(LBL 0x07) EMF에서 막대가 캡션 칸까지 내려오는 것은
    /// 기본 EAN-13(0x02) 하나뿐이고, ISBN·ISSN·ISMN·UPC-A·UPC-E·JAN-8·JAN-13은 밑변이 모두 같다.
    /// 기본 EAN-8(0x01)은 표본이 없어 같은 기본형인 EAN-13과 함께 가드를 남긴다.
    /// 신형(LBL 0x1B)은 0x1A~0x21 전부 가드가 있어 해당하지 않는다.
    /// </summary>
    private static bool AniLabelFlatGuards(DesignObject obj)
    {
        if (!Barcode1DEncoders.IsAniLabelLegacy(obj)) return false;
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        return id is not ("EAN_13" or "EAN13" or "EAN_8" or "EAN8");
    }

    /// <summary>
    /// 「가드 자동」이 이 항목에서 길게로 풀리는지. 출처별 실측 규칙이다.
    /// 소매(EAN·UPC)는 규격대로 늘 길게가 기본이고, 애니라벨 구형만 실측대로 평평해진다.
    /// Codabar·Code 93의 긴 막대는 애니라벨 구형에서만 관찰된 특성이라 그때만 기본으로 켠다.
    /// 속성바가 자동의 뜻을 같이 보여주므로 공개해 둔다.
    /// </summary>
    public static bool AutoGuardBarsOn(DesignObject obj)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        var aniLegacy = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel
            && Barcode1DEncoders.IsAniLabelLegacy(obj);
        return id switch
        {
            "CODE_93" or "CODE93" or "CODE_93_EXT" => aniLegacy,
            "CODABAR" => aniLegacy,
            // 구형 8번 칸 ABC Codabar(0x09)는 EMF 막대 24개가 모두 같은 높이다.
            "ABC_CODABAR" => false,
            _ => !AniLabelFlatGuards(obj)
        };
    }

    /// <summary>
    /// 시작·가운데·끝 막대를 늘릴지 정한다. `BarcodeGuardBars`가 on/off면 그 값을 쓰고,
    /// auto(기본)면 <see cref="AutoGuardBarsOn"/>의 출처별 실측 규칙을 따른다.
    /// 늘어나는 자리와 길이는 심볼 구조가 정하므로 여기서 다루지 않는다.
    /// </summary>
    private static bool GuardBarsOn(DesignObject obj)
        => (obj.BarcodeGuardBars ?? "").Trim() switch
        {
            DesignObject.GuardBarsOn => true,
            DesignObject.GuardBarsOff => false,
            _ => AutoGuardBarsOn(obj)
        };

    private static bool IsEan13Family(string? format)
    {
        var id = (format ?? "").Replace("-", "_").ToUpperInvariant();
        return id is "EAN_13" or "EAN13" or "JAN_13" or "JAN13" or "ISBN" or "ISSN" or "ISMN";
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
        using var hri = FitRetailHriFont(font,
            (digits[0].ToString(), RetailQuietWidth(leftPad, cellW, start)),
            (digits.Substring(1, 6), RetailSlotWidth(leftPad, cellW, start, scale, 3, 47, boxed)),
            (digits.Substring(7, 6), RetailSlotWidth(leftPad, cellW, start, scale, 48, 95, boxed)));
        var y = barH + textH * 0.82f;
        var lead = digits[0].ToString();
        var quietW = RetailQuietWidth(leftPad, cellW, start);
        var leadX = Math.Max(0, (quietW - hri.MeasureText(lead)) * (leftPad > 0.25f ? 0.5f : 0.12f));
        DrawHriText(canvas, obj, lead, leadX, y, SKTextAlign.Left, hri, tp);
        DrawHriGroup(canvas, obj, digits.Substring(1, 6), leftPad, cellW, start, scale, 3, 47, y, hri, tp, boxed);
        DrawHriGroup(canvas, obj, digits.Substring(7, 6), leftPad, cellW, start, scale, 48, 95, y, hri, tp, boxed);
    }

    private static void DrawEan8Hri(
        SKCanvas canvas, DesignObject obj, string digits, float leftPad, float cellW, int start, float scale,
        float barH, float textH, SKFont font, SKColor color, byte alpha, bool boxed)
    {
        using var tp = new SKPaint { Color = new SKColor(color.Red, color.Green, color.Blue, alpha), IsAntialias = true };
        using var hri = FitRetailHriFont(font,
            (digits[..4], RetailSlotWidth(leftPad, cellW, start, scale, 3, 33, boxed)),
            (digits[4..], RetailSlotWidth(leftPad, cellW, start, scale, 34, 67, boxed)));
        var y = barH + textH * 0.82f;
        DrawHriGroup(canvas, obj, digits[..4], leftPad, cellW, start, scale, 3, 33, y, hri, tp, boxed);
        DrawHriGroup(canvas, obj, digits[4..], leftPad, cellW, start, scale, 34, 67, y, hri, tp, boxed);
    }

    private static void DrawUpcAHri(
        SKCanvas canvas, DesignObject obj, string digits, float leftPad, float cellW, int start, float scale,
        float barH, float textH, SKFont font, SKColor color, byte alpha, bool boxed)
    {
        // UPC-A는 12자리를 1 + 5 + 5 + 체크 1로 나눈다. 첫 자리와 체크 자리는 막대 바깥 여백에 쓴다.
        using var tp = new SKPaint { Color = new SKColor(color.Red, color.Green, color.Blue, alpha), IsAntialias = true };
        var tailX = leftPad + (start + 95 * scale) * cellW;
        var tailW = Math.Max(0.4f, obj.Width - tailX);
        using var hri = FitRetailHriFont(font,
            (digits[0].ToString(), RetailQuietWidth(leftPad, cellW, start)),
            (digits.Substring(1, 5), RetailSlotWidth(leftPad, cellW, start, scale, 3, 47, boxed)),
            (digits.Substring(6, 5), RetailSlotWidth(leftPad, cellW, start, scale, 48, 92, boxed)),
            (digits[11].ToString(), tailW));
        var y = barH + textH * 0.82f;
        var lead = digits[0].ToString();
        var quietW = RetailQuietWidth(leftPad, cellW, start);
        var leadX = Math.Max(0, (quietW - hri.MeasureText(lead)) * (leftPad > 0.25f ? 0.5f : 0.12f));
        DrawHriText(canvas, obj, lead, leadX, y, SKTextAlign.Left, hri, tp);
        DrawHriGroup(canvas, obj, digits.Substring(1, 5), leftPad, cellW, start, scale, 3, 47, y, hri, tp, boxed);
        DrawHriGroup(canvas, obj, digits.Substring(6, 5), leftPad, cellW, start, scale, 48, 92, y, hri, tp, boxed);

        var tail = digits[11].ToString();
        var tailTextX = tailX + Math.Max(0, (tailW - hri.MeasureText(tail)) * 0.5f);
        DrawHriText(canvas, obj, tail, tailTextX, y, SKTextAlign.Left, hri, tp);
    }

    /// <summary>
    /// EAN/UPC는 16pt를 유지하고 가로만 조절한다.
    /// 일반 HRI의 1.07 확장은 빼고, 그룹이 겹칠 때만 같은 비율로 줄인다.
    /// </summary>
    private static SKFont FitRetailHriFont(SKFont font, params (string Text, float MaxW)[] slots)
    {
        var hri = DocumentRenderer.MakeTextFont(font.Typeface, font.Size);
        hri.ScaleX = font.ScaleX / HriLetterScale;
        hri.SkewX = font.SkewX;
        var factor = 1f;
        foreach (var (text, maxW) in slots)
        {
            if (string.IsNullOrEmpty(text) || maxW < 0.2f) continue;
            var tw = hri.MeasureText(text);
            if (tw > maxW && tw > 0.01f)
                factor = Math.Min(factor, maxW / tw);
        }
        if (factor < 0.999f)
            hri.ScaleX *= Math.Max(0.45f, factor);
        return hri;
    }

    private static float RetailSlotWidth(
        float leftPad, float cellW, int start, float scale, int moduleFrom, int moduleTo, bool boxed)
    {
        var inset = boxed ? scale * 0.9f : 0f;
        var x0 = leftPad + (start + moduleFrom * scale + inset) * cellW;
        var x1 = leftPad + (start + moduleTo * scale - inset) * cellW;
        return Math.Max(0.4f, x1 - x0);
    }

    private static float RetailQuietWidth(float leftPad, float cellW, int start)
        => leftPad > 0.25f ? leftPad : Math.Max(0.4f, start * cellW * 0.9f);

    private static float HriOverflowScale(SKFont font, string text, float maxW)
    {
        if (string.IsNullOrEmpty(text) || maxW < 0.2f) return 1f;
        var tw = font.MeasureText(text);
        if (tw <= maxW || tw < 0.01f) return 1f;
        return maxW / tw;
    }

    /// <summary>바코드 숫자 자간. 1이면 글꼴 그대로.</summary>
    private const float HriLetterScale = 1.07f;

    /// <summary>저장 글꼴 크기는 유지하고, 넘치면 자간만 줄여 폭 안에 전부 넣는다.</summary>
    private static void FitHriToWidth(SKFont font, string text, float maxW)
    {
        if (string.IsNullOrEmpty(text) || maxW < 0.2f) return;
        for (var i = 0; i < 4; i++)
        {
            var tw = font.MeasureText(text);
            if (tw <= maxW || tw < 0.01f) return;
            font.ScaleX *= Math.Max(0.08f, maxW / tw);
        }
    }

    private static void DrawCenteredHri(
        SKCanvas canvas, DesignObject obj, string text, float barH, float textH,
        SKFont font, SKPaint tp, float width, float x0 = 0)
    {
        var maxW = Math.Max(0.4f, width);
        FitHriToWidth(font, text, maxW * 0.98f);
        var tw = font.MeasureText(text);
        var tx = x0 + Math.Max(0, (maxW - tw) / 2f);
        canvas.Save();
        canvas.ClipRect(new SKRect(x0, 0, x0 + maxW, obj.Height + 0.5f));
        DrawHriText(canvas, obj, text, tx, barH + textH * 0.82f, SKTextAlign.Left, font, tp);
        canvas.Restore();
    }

    private static void DrawHriGroup(
        SKCanvas canvas, DesignObject obj, string text, float leftPad, float cellW, int start, float scale,
        int moduleFrom, int moduleTo, float y, SKFont font, SKPaint tp, bool boxed)
    {
        var maxW = RetailSlotWidth(leftPad, cellW, start, scale, moduleFrom, moduleTo, boxed);
        var inset = boxed ? scale * 0.9f : 0f;
        var x0 = leftPad + (start + moduleFrom * scale + inset) * cellW;
        var tw = font.MeasureText(text);
        var x = x0 + (maxW - tw) / 2f;
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

    /// <summary>
    /// UPC-A 12자리. 끝자리는 늘 앞 11자리에서 다시 셈한다.
    ///
    /// 애니라벨은 체크숫자를 따지지 않는다. 「1D barcode 타입.lbl」 28번 칸은 값 12345678901 을
    /// 012345678901 로 왼쪽 채움한 뒤 끝자리 1 을 그대로 찍는데, 규격대로면 5다.
    /// 그대로 두면 ZXing 이 인코딩을 거절해 Code 128 로 떨어지고, 심볼로지도 값도 달라진 인쇄물이 나온다.
    /// 여기서 고쳐 주면 캡션과 막대가 같은 값을 말하고 스캐너도 읽는다. EAN-13(WithEan13Checksum)과 같은 처리다.
    /// </summary>
    private static string EnsureUpcA(string digits)
    {
        if (digits.Length >= 12) return digits[..11] + EanChecksum(digits[..11]);
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

    /// <summary>HRI 글자 칸 높이 ÷ 글자 크기.</summary>
    private const float HriLine = 1.28f;

    /// <summary>
    /// 애니라벨 신형의 HRI 글자 칸 배수. 가져오기에서 거꾸로 글자 크기를 셈할 때도 쓴다.
    ///
    /// 「1D barcode 타입.lbl」에 든 애니라벨 원본 그림에서 글자 칸은 그림 높이의 13.3%,
    /// 숫자 잉크는 8.7%였다. 우리 HRI 글꼴은 숫자 잉크가 글자 크기의 0.697이므로
    /// 글자 칸을 13.3%로 두면서 잉크도 8.7%로 맞추려면 배수가 13.3 ÷ (8.7 ÷ 0.697) = 1.07이다.
    /// 기본값 1.28로 두면 막대 높이는 맞지만 글자가 애니라벨보다 16% 작게 나온다.
    /// </summary>
    public const float AniLabelHriLine = 1.07f;

    /// <summary>애니라벨 신형 엔진(LBL 0x1B). 구형 PSOFT(0x07)와 막대 배치가 여러 군데 다르다.</summary>
    private static bool IsAniLabelModern(DesignObject obj)
        => Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel
           && !Barcode1DEncoders.IsAniLabelLegacy(obj);

    /// <param name="line">0이면 심볼을 만든 회사에 맞는 기본 배수를 쓴다.</param>
    private static float HriBand(DesignObject obj, bool show, float line = 0f)
    {
        if (!show) return 0f;
        if (line <= 0f)
            line = IsAniLabelModern(obj) ? AniLabelHriLine : HriLine;
        var fs = obj.FontSize > 0.5f ? obj.FontSize : 2.4f;
        return Math.Min(obj.Height * 0.48f, fs * line);
    }

    private static float HriFontMm(DesignObject obj, float band)
    {
        var fs = obj.FontSize > 0.5f ? obj.FontSize : 2.4f;
        // 기본 글자 칸은 글자 크기의 1.28배라 0.88을 곱해도 글자 크기가 이긴다.
        // 애니라벨 신형은 칸이 1.07배뿐이라 같은 셈을 하면 글자가 6% 작아진다. 칸에서 그대로 푼다.
        var fit = IsAniLabelModern(obj)
            ? band / AniLabelHriLine
            : Math.Max(1.2f, band * 0.88f);
        return Math.Min(fs, fit);
    }

    private static SKFont CreateHriFont(DesignObject obj, float sizeMm)
    {
        var family = obj.FontFamily;
        var face = DocumentRenderer.ResolveTypeface(family, obj.Bold, obj.Italic);
        if (!string.IsNullOrWhiteSpace(family)
            && (string.Equals(face.FamilyName, "Pretendard", StringComparison.OrdinalIgnoreCase)
                || string.Equals(face.FamilyName, "sans-serif", StringComparison.OrdinalIgnoreCase)))
        {
            var style = SKFontStyle.Normal;
            if (obj.Bold && obj.Italic) style = SKFontStyle.BoldItalic;
            else if (obj.Bold) style = SKFontStyle.Bold;
            else if (obj.Italic) style = SKFontStyle.Italic;
            var sys = SKTypeface.FromFamilyName(family, style);
            if (sys is not null
                && sys.FamilyName.Contains(family, StringComparison.OrdinalIgnoreCase))
                face = sys;
        }
        var font = DocumentRenderer.MakeTextFont(face, sizeMm);
        font.ScaleX = DocumentRenderer.HriWidthScale(family) * HriLetterScale;
        font.SkewX = obj.Italic && !DocumentRenderer.HasItalicFace(family) ? -0.25f : 0f;
        return font;
    }

    /// <summary>내용 글자색. 폼텍은 막대색과 별개(기본 검정).</summary>
    private static SKColor HriColor(DesignObject obj, byte alpha)
    {
        var stroke = obj.Stroke;
        var fill = obj.Fill;
        if (string.Equals(obj.BarcodeVendor, "formtec", StringComparison.OrdinalIgnoreCase)
            && (string.IsNullOrWhiteSpace(stroke) || stroke is "transparent" or "none"
                || string.Equals(stroke, fill, StringComparison.OrdinalIgnoreCase)))
            return new SKColor(0, 0, 0, alpha);
        if (!string.IsNullOrWhiteSpace(stroke) && stroke is not "transparent" and not "none")
            return ColorUtil.Parse(stroke, alpha);
        return ColorUtil.Parse(fill, alpha);
    }

    private static bool HasOpaqueBackground(DesignObject obj)
        => !obj.BackgroundTransparent
            && !string.IsNullOrWhiteSpace(obj.BackgroundFill)
            && obj.BackgroundFill is not "transparent" and not "none";

    private static void FillBarcodeBackground(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        if (!HasOpaqueBackground(obj)) return;
        using var bg = new SKPaint
        {
            Color = ColorUtil.Parse(obj.BackgroundFill, alpha),
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };
        canvas.DrawRect(0, 0, obj.Width, obj.Height, bg);
    }

    /// <summary>
    /// 그릴 수 없는 심볼로지. Compact Matrix는 중국 국가표준 GB/T 27767-2011을 구하지 못했다.
    /// 부속서의 생성다항식 81개와 부호어 분할 규칙 없이는 표본만으로 복원할 수 없고,
    /// 공개 구현도 없다. 애니라벨 39×18 심볼과 닮은 대체품이 없으므로 그리지 않는다.
    /// </summary>
    private static bool IsUnsupportedSymbology(DesignObject obj)
        => (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant() is "COMPACT_MATRIX";

    private static string UnsupportedNotice(DesignObject obj)
        => BarcodeCatalog.Find(obj.BarcodeFormat)?.Label ?? "미지원 심볼";

    /// <summary>
    /// OneCode(USPS IMB) 값이 규격 자릿수에 못 미치면 무엇이 모자란지 한 줄로 돌려준다.
    /// 맞으면 null. 추적 20자리는 반드시 있어야 하고 뒤 배달구역은 0·5·9·11자리만 된다.
    /// </summary>
    private static string? ImbShortage(DesignObject obj, string value)
    {
        if ((obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant() is not "ONECODE")
            return null;
        var n = DigitsOnly(value).Length;
        if (n < 20) return $"OneCode: 추적 20자리 필요 (지금 {n}자리)";
        return n is 20 or 25 or 29 or 31 ? null : $"OneCode: 배달구역은 0·5·9·11자리 (지금 {n - 20}자리)";
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
        if (!DocumentRenderer.FontsReady) return;
        using var tp = new SKPaint { Color = ColorUtil.Parse("#6B6560", alpha), IsAntialias = true };
        using var font = new SKFont(DocumentRenderer.ResolveTypeface(false), Math.Min(3.2f, obj.Height * 0.3f));
        // 「OneCode: 추적 20자리 필요」처럼 긴 알림도 상자를 넘지 않게 줄여 앉힌다.
        var width = font.MeasureText(msg);
        var room = obj.Width * 0.92f;
        if (width > room)
            font.Size *= room / width;
        canvas.DrawText(msg, obj.Width / 2f, obj.Height / 2f + font.Size * 0.35f, SKTextAlign.Center, font, tp);
    }

    private static bool TryDrawEan13(SKCanvas canvas, DesignObject obj, string value, BarcodeFormat format, byte alpha)
    {
        var digits = DigitsOnly(value);
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        var bookland = BarcodeCatalog.IsBookland(obj.BarcodeFormat) || BarcodeCatalog.LooksLikeIsbn(value);
        var eanFamily = id is "EAN_13" or "EAN13" or "JAN_13" or "JAN13";
        if (!bookland && !eanFamily && format != BarcodeFormat.EAN_13)
            return false;
        if (bookland)
            digits = ToBooklandEan13(id, digits);
        else if (id is "JAN_13" or "JAN13"
                 && Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.ILabel)
            digits = ToILabelJan13(digits);

        var encoded = WithEan13Checksum(digits);
        var modules = EncodeEan13Modules(encoded);
        if (modules is null) return false;
        if (encoded != digits)
            EditorLog.Info($"EAN-13 체크 보정 {digits} → {encoded}");

        // 폼텍 ISBN(하이픈)만 가운데 캡션. 아이라벨 Bookland 123456789 → 9 781234 567897.
        // 애니라벨은 저장값에 하이픈이 있어도 캡션을 EAN-13 묶음으로 쓴다.
        //「바코드 타입 전체.lbl」16~18번 칸 80-7226-102-9 →
        // ISBN 9 788072 261024, ISSN 9 778072 261025, ISMN 9 798072 261023.
        var hyphenHri = bookland && value.Contains('-')
            && Barcode1DEncoders.VendorOf(obj) != BarcodeVendorKind.AniLabel;
        var showText = obj.BarcodeShowText;
        var extraCaption = showText && DocumentRenderer.FontsReady
            && obj.BarcodeIsbnCaption && id is "ISBN"
            ? BarcodeCatalog.FormatILabelIsbnCaption(value)
            : "";
        var extraH = extraCaption.Length > 0 ? HriBand(obj, true) : 0f;
        var eanHri = showText && encoded.Length == 13 && !hyphenHri;
        var textH = HriBand(obj, showText, bookland ? 1.35f : 0f);
        var barH = Math.Max(1f, obj.Height - textH - extraH);
        // 애니라벨 신형은 오른쪽 여백을 두지 않는다. 「1D barcode 타입.lbl」1번 칸 원본 그림에서
        // 612px 중 왼쪽 42px(7모듈)만 비고 막대가 오른쪽 끝까지 닿았다. 첫 자리 숫자는 그 왼쪽 여백에 쓴다.
        var aniModern = IsAniLabelModern(obj);
        const int quiet = 7;
        var quietRight = aniModern ? 0 : quiet;
        var total = modules.Length + quiet + quietRight;
        var barColor = ColorUtil.Parse(obj.Fill, alpha);
        // 캡션이 없어도 시작·가운데·끝 가드는 아래로 조금 더 길게(아이라벨 EAN-13 실측).
        var silentGuard = !eanHri && !hyphenHri ? Math.Min(barH * 0.12f, Math.Max(0.9f, barH * 0.08f)) : 0f;
        var flatGuards = !GuardBarsOn(obj);
        var guardExtra = flatGuards ? 0f : eanHri ? textH * 0.92f : silentGuard;
        var bodyH = eanHri || flatGuards ? barH : Math.Max(1f, barH - silentGuard);

        FillBarcodeBackground(canvas, obj, alpha);

        var leftPad = 0f;
        SKFont? font = null;
        if (eanHri)
        {
            var faceSize = obj.FontSize > 0.5f ? obj.FontSize : HriFontMm(obj, textH);
            font = CreateHriFont(obj, faceSize);
            // 애니라벨은 첫 자리를 7모듈 여백 안에 쓴다. 상자를 따로 넓히지 않는다.
            leftPad = aniModern ? 0f : font.MeasureText(encoded[0].ToString()) * 1.15f;
        }

        var addOn = ResolveEanSupplement(obj.BarcodeSupplement);
        const int addGap = 9;
        var addTotal = addOn is { Length: > 0 } ? addOn.Length + 1 : 0;
        var barAreaW = Math.Max(1f, obj.Width - leftPad);
        var unit = barAreaW / Math.Max(1, total + (addTotal > 0 ? addGap + addTotal : 0));
        var mainW = unit * total;
        var cellW = unit;
        DrawEanModules(canvas, modules, quiet, mainW, bodyH, barColor, leftPad, guardExtra,
            retailGuards: guardExtra > 0, quietRight: quietRight);

        if (addOn is { Length: > 0 })
        {
            var addX = leftPad + unit * (total + addGap);
            var addW = unit * addOn.Length;
            var cap = EanSupplementCaption(obj.BarcodeSupplement);
            DrawEanSupplement(canvas, obj, addOn, addX, addW, bodyH, cap, barColor, alpha);
        }

        if (showText && hyphenHri)
            DrawBooklandHri(canvas, obj, value, barH, textH, HriColor(obj, alpha), alpha);
        else if (eanHri && font is not null)
            DrawEan13Hri(canvas, obj, encoded, leftPad, cellW, quiet, 1f, barH, textH, font, HriColor(obj, alpha), alpha, boxed: true);
        if (extraH > 0.2f)
        {
            using var extraFont = CreateHriFont(obj, obj.FontSize > 0.5f ? obj.FontSize : HriFontMm(obj, extraH));
            using var extraPaint = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
            DrawCenteredHri(canvas, obj, extraCaption, barH + textH, extraH, extraFont, extraPaint, obj.Width);
        }

        font?.Dispose();
        return true;
    }

    private static bool[]? ResolveEanSupplement(string? raw)
    {
        var d = DigitsOnly(raw ?? "");
        if (d.Length is 0) return null;
        if (d.Length <= 2)
            return Barcode1DEncoders.EncodeEan2(d.PadLeft(2, '0'));
        return Barcode1DEncoders.EncodeEan5(d.PadLeft(5, '0')[^5..]);
    }

    /// <summary>아이라벨 부가코드 캡션. EAN-2는 12&gt;, EAN-5는 12345&gt;.</summary>
    private static string EanSupplementCaption(string? raw)
    {
        var d = DigitsOnly(raw ?? "");
        if (d.Length is 0) return "";
        var shown = d.Length <= 2 ? d.PadLeft(2, '0') : d.PadLeft(5, '0')[^5..];
        return $"{shown}>";
    }

    /// <summary>아이라벨 EAN 부가코드. 막대 위 캡션은 12&gt; 형태.</summary>
    private static void DrawEanSupplement(
        SKCanvas canvas, DesignObject obj, bool[] modules, float x, float width, float barH,
        string caption, SKColor color, byte alpha)
    {
        var capH = 0f;
        if (!string.IsNullOrEmpty(caption) && DocumentRenderer.FontsReady)
        {
            capH = Math.Min(barH * 0.22f, Math.Max(1.6f, obj.FontSize > 0.5f ? obj.FontSize : 2.2f));
            using var font = CreateHriFont(obj, capH * 0.85f);
            using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
            DrawCenteredHri(canvas, obj, caption, 0, capH, font, tp, width, x);
        }

        var bodyH = Math.Max(1f, barH - capH);
        canvas.Save();
        canvas.Translate(0, capH);
        DrawEanModules(canvas, modules, 0, width, bodyH, color, x);
        canvas.Restore();
    }

    private static bool TryDrawEan8(SKCanvas canvas, DesignObject obj, string value, BarcodeFormat format, byte alpha)
    {
        if (!IsEan8Family(obj.BarcodeFormat) && format != BarcodeFormat.EAN_8)
            return false;

        var digits = DigitsOnly(value);
        var encoded = EnsureEan8(digits);
        var modules = EncodeEan8Modules(encoded);
        if (modules is null) return false;

        var showText = obj.BarcodeShowText;
        var textH = HriBand(obj, showText);
        var barH = Math.Max(1f, obj.Height - textH);
        // 애니라벨 신형 EAN-8은 여백이 없다. 34번 칸 원본 그림 402px가 67모듈 × 6px로 딱 맞는다.
        var quiet = IsAniLabelModern(obj) ? 0 : 7;
        var total = modules.Length + quiet * 2;
        var barColor = ColorUtil.Parse(obj.Fill, alpha);
        var eanHri = showText && encoded.Length == 8;
        var silentGuard = !eanHri ? Math.Min(barH * 0.12f, Math.Max(0.9f, barH * 0.08f)) : 0f;
        var flatGuards = !GuardBarsOn(obj);
        var guardExtra = flatGuards ? 0f : eanHri ? textH * 0.92f : silentGuard;
        var bodyH = eanHri || flatGuards ? barH : Math.Max(1f, barH - silentGuard);

        FillBarcodeBackground(canvas, obj, alpha);

        var addOn = ResolveEanSupplement(obj.BarcodeSupplement);
        const int addGap = 9;
        var addTotal = addOn is { Length: > 0 } ? addOn.Length + 1 : 0;
        var unit = obj.Width / Math.Max(1, total + (addTotal > 0 ? addGap + addTotal : 0));
        var mainW = unit * total;
        DrawEanModules(canvas, modules, quiet, mainW, bodyH, barColor, 0, guardExtra,
            retailGuards: guardExtra > 0, RetailGuardKind.Ean8);

        if (addOn is { Length: > 0 })
        {
            var addX = unit * (total + addGap);
            var addW = unit * addOn.Length;
            var cap = showText ? EanSupplementCaption(obj.BarcodeSupplement) : "";
            DrawEanSupplement(canvas, obj, addOn, addX, addW, bodyH, cap, barColor, alpha);
        }

        if (eanHri)
        {
            using var font = CreateHriFont(obj, obj.FontSize > 0.5f ? obj.FontSize : HriFontMm(obj, textH));
            DrawEan8Hri(canvas, obj, encoded, 0, unit, quiet, 1f, barH, textH, font, HriColor(obj, alpha), alpha, boxed: true);
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
        using var tp = new SKPaint
        {
            Color = new SKColor(color.Red, color.Green, color.Blue, alpha),
            IsAntialias = true
        };
        DrawCenteredHri(canvas, obj, shown, barH, textH, font, tp, obj.Width);
    }

    /// <summary>
    /// Code 39 / 폼텍 PZN.
    /// 파일 값은 123456이지만, 막대는 IFA Code 39: `-` + PZN + mod-11 체크.
    /// 123456 → *-1234562*. 기준: IFA Technische Hinweise Code 39, ISO/IEC 16388.
    /// </summary>
    private static bool TryDrawCode39(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        // UPU·Code 32 는 애니라벨 신형만 여기로 들어온다. 기본 경로(UPU=Code 128)는 그대로 둔다.
        var aniCode39 = id is "UPU" or "CODE_32" && IsAniLabelModern(obj);
        if (!aniCode39 && id is not ("PZN" or "CODE_39" or "CODE39" or "CODE_39_EXT"))
            return false;

        var vendor = Barcode1DEncoders.VendorOf(obj);
        // 애니라벨 신형 PZN 은 값을 일곱 자리로 왼쪽 채움한 뒤 앞에 `-` 만 붙인다(가져오기에서 채운다).
        var upuValue = id is "UPU" ? Barcode1DEncoders.WithUpuS10Check(value) : value;
        string? payload;
        if (id is "PZN")
            payload = vendor switch
            {
                BarcodeVendorKind.Formtec => ToPznCode39Payload(value),
                // 애니라벨 구형 0x29 실측(36번 칸): 막대는 `*-123456*` 9심볼로 체크문자가 없고,
                // 앞에 `-` 하나만 붙는다. 캡션은 `PZN - 123456`이다.
                BarcodeVendorKind.AniLabel => "-" + value.Trim().ToUpperInvariant(),
                _ => value.Trim().ToUpperInvariant()
            };
        else if (id is "CODE_32")
            payload = Barcode1DEncoders.ToCode32Payload(DigitsOnly(value));
        else if (id is "UPU")
            payload = upuValue;
        else if (id is "CODE_39_EXT")
            payload = Barcode1DEncoders.ToCode39FullAscii(value);
        else
            payload = value.Trim().ToUpperInvariant();
        if (string.IsNullOrEmpty(payload)) return false;
        // 폼텍 실측: N:W=1:2, 글자 사이 1X, 시작/종료 * (46글자=230막대).
        // 애니라벨도 1:2다.「바코드 타입 전체.lbl」 3번 칸 EMF에서 좁은 막대 5~6, 넓은 막대 11~12다.
        // ISO/IEC 16388은 1:2~1:3 허용. 공칭 1:3은 직접 추가분.
        var wide = vendor is BarcodeVendorKind.Formtec or BarcodeVendorKind.AniLabel ? 2 : 3;
        // 구형 애니라벨(PSOFT) Code 39 Full ASCII는 데이터 앞에 `*+$*` 네 글자를 늘 붙인다.
        //「바코드 타입 전체.lbl」 45번 칸 EMF 실측: AB → `* *+$* AB *`(8문자),
        // PSOFTab → `* *+$* PSOFT +A +B *`(15문자). 길이·내용이 달라도 앞머리는 같다.
        // 신형(LBL 0x1B 0x10)에는 없다. 캡션에는 앞머리가 나오지 않으므로 막대에만 넣는다.
        var barPayload = id is "CODE_39_EXT" && Barcode1DEncoders.IsAniLabelLegacy(obj)
            ? "*+$*" + payload
            : payload;
        var modules = EncodeCode39(barPayload, wide);
        if (modules is null) return false;

        var hriBody = vendor == BarcodeVendorKind.ILabel && !string.IsNullOrWhiteSpace(obj.Text)
            ? obj.Text.Trim()
            : payload;
        // 애니라벨 캡션은 구형·신형이 다르다. 원본 그림 실측이다.
        //   PZN     구형 `PZN - 123456`   신형 `PZN-0123456`(일곱 자리)
        //   Code 32 `A` + 아홉 자리 십진수. 막대에 실린 32진 여섯 글자는 쓰지 않는다.
        //   UPU     `*EE123456785CN*` 로 Code 39 시작·정지 문자까지 그대로 쓴다.
        var shown = id switch
        {
            "PZN" when vendor == BarcodeVendorKind.AniLabel =>
                IsAniLabelModern(obj) ? "PZN-" + value.Trim() : "PZN - " + value.Trim(),
            "CODE_32" when aniCode39 => "A" + DigitsOnly(value).PadLeft(9, '0'),
            "UPU" when aniCode39 => $"*{upuValue}*",
            _ => obj.BarcodeShowStartEnd
                ? $"*{hriBody}*"
                : (id is "CODE_39_EXT" ? value.Trim() : hriBody)
        };
        // 애니라벨은 막대가 상자 좌우 끝까지 닿는다. EMF 실측에서 0~500을 다 채운다.
        var quiet = vendor == BarcodeVendorKind.AniLabel ? 0 : Barcode1DEncoders.Quiet(vendor, 10);
        var showHri = obj.BarcodeShowText && DocumentRenderer.FontsReady;
        var faceSize = obj.FontSize > 0.5f ? obj.FontSize : 2.4f;
        using var font = CreateHriFont(obj, faceSize);
        if (showHri)
            FitHriToWidth(font, shown, obj.Width * 0.98f);
        var textH = showHri
            ? Math.Min(obj.Height * 0.40f,
                Math.Max(1.6f, faceSize * (IsAniLabelModern(obj) ? AniLabelHriLine : 1.25f)))
            : 0f;
        var barH = Math.Max(1f, obj.Height - textH);
        var barColor = ColorUtil.Parse(obj.Fill, alpha);
        FillBarcodeBackground(canvas, obj, alpha);
        canvas.Save();
        canvas.ClipRect(new SKRect(0, 0, obj.Width, barH));
        DrawEanModules(canvas, modules, quiet, obj.Width, barH, barColor);
        canvas.Restore();
        if (showHri)
        {
            using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
            DrawCenteredHri(canvas, obj, shown, barH, textH, font, tp, obj.Width);
        }
        return true;
    }

    /// <summary>
    /// ISO/IEC 16388 Code 39. 1=wide, 0=narrow, 순서 bar-space-bar-space-bar-space-bar-space-bar.
    /// 글자당 9요소 중 3개가 wide. 글자 사이 간격은 1X. 시작/종료는 '*'.
    /// </summary>
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

    private static bool[]? EncodeCode39(string payload, int wide = 3)
    {
        if (payload.Length == 0) return null;
        foreach (var ch in payload)
            if (!Code39Patterns.ContainsKey(ch)) return null;

        wide = Math.Clamp(wide, 2, 3);
        var bits = new List<bool>(24 + payload.Length * (6 + 3 * wide + 1));
        void AppendChar(char ch)
        {
            var p = Code39Patterns[ch];
            for (var i = 0; i < 9; i++)
            {
                var n = p[i] == '1' ? wide : 1;
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

    private static bool TryDrawCode93(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("CODE_93" or "CODE93" or "CODE_93_EXT"))
            return false;

        var vendor = Barcode1DEncoders.VendorOf(obj);
        var extended = id is "CODE_93_EXT";
        var anylabel = vendor == BarcodeVendorKind.AniLabel;
        // 폼텍 Standard(PSOFT)는 C/K 있음. Extended(Formtec)만 C/K 없음. ISO는 둘 다 C/K.
        // 애니라벨은 구형(LBL 0x07) Standard만 C/K가 있다. EMF 실측 PSOFT → `*PSOFT$B*` 9심볼.
        // 같은 구형 Extended는 `*PSOFT*` 7심볼이고, 신형(LBL 0x1B) 0x13·0x14도 둘 다 C/K가 없다.
        var checksum = vendor switch
        {
            BarcodeVendorKind.Formtec => !extended,
            BarcodeVendorKind.AniLabel => !extended && Barcode1DEncoders.IsAniLabelLegacy(obj),
            _ => true
        };
        var payload = value.Trim();
        var modules = Barcode1DEncoders.EncodeCode93(payload, extended, checksum);
        if (modules is null) return false;

        var shown = extended ? payload : payload.ToUpperInvariant();
        // 구형 애니라벨은 시작 문자의 막대 3개와 정지 문자+종단 막대 4개를 글자 칸 끝까지 늘린다.
        // 신형 비트맵에는 이 긴 막대가 없다. 막대가 상자 좌우 끝까지 닿는 것은 구형·신형이 같다.
        var guards = GuardBarsOn(obj);
        var quiet = anylabel ? 0 : Barcode1DEncoders.Quiet(vendor, 10);
        return TryDrawLinear(canvas, obj, modules, shown, quiet, alpha,
            edgeGuardRuns: guards ? 3 : 0, trailGuardRuns: guards ? 4 : 0);
    }

    private static bool TryDrawCode128(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        var vendor = Barcode1DEncoders.VendorOf(obj);
        var payload = value.Trim();
        var ean = id is "EAN_128" or "GS1_128" or "UCC_EAN_128" or "UCC_128";
        var ilabel128 = vendor == BarcodeVendorKind.ILabel && id is "CODE_128" or "CODE128";
        if (!ean && !ilabel128)
            return false;

        bool[]? modules;
        if (ilabel128)
        {
            // 폼텍 CODE_128은 ZXing Auto를 유지. 아이라벨만 세트 Auto/A/B/C를 우리 표로 그린다.
            modules = Barcode1DEncoders.EncodeCode128(payload, gs1: false, formtecEan128: false, ParseILabelCode128Set(obj.QrKind));
        }
        else if (ean && vendor == BarcodeVendorKind.ILabel)
        {
            // 아이라벨 EAN-128 실측: Start C + FNC1 + 숫자 쌍(13자리→37막대). 폼텍(Start A+69)은 그대로.
            modules = Barcode1DEncoders.EncodeCode128(
                payload, gs1: false, formtecEan128: false, Barcode1DEncoders.Code128Subset.Auto, fnc1: true);
        }
        else if (ean && IsAniLabelModern(obj))
        {
            modules = Barcode1DEncoders.EncodeCode128(
                payload, gs1: true, formtecEan128: false, aniLabelEan128: true);
        }
        else
        {
            var formtecEan = vendor == BarcodeVendorKind.Formtec;
            modules = Barcode1DEncoders.EncodeCode128(payload, gs1: !formtecEan, formtecEan128: formtecEan);
        }
        var shown = ean && vendor == BarcodeVendorKind.ILabel
            ? FormatILabelGs1Hri(payload)
            : payload;
        return modules is not null
            && TryDrawLinear(canvas, obj, modules, shown, Barcode1DEncoders.Quiet(vendor, 10), alpha);
    }

    /// <summary>
    /// GS1 HRI. 괄호는 표시용이고 막대에는 넣지 않는다.
    /// 아이라벨은 저장된 값의 앞 2자리를 AI로 본다. 12345678 → (12)345678.
    /// </summary>
    private static string FormatILabelGs1Hri(string value)
    {
        var s = value.Trim();
        if (s.Length < 2 || s.Contains('('))
            return s;
        return $"({s[..2]}){s[2..]}";
    }

    private static Barcode1DEncoders.Code128Subset ParseILabelCode128Set(string? kind)
        => (kind ?? "").Trim().ToUpperInvariant() switch
        {
            "A" => Barcode1DEncoders.Code128Subset.A,
            "B" => Barcode1DEncoders.Code128Subset.B,
            "C" => Barcode1DEncoders.Code128Subset.C,
            _ => Barcode1DEncoders.Code128Subset.Auto
        };

    /// <summary>
    /// 막대 폭이 모두 같고 사이 간격만 달라지는 세 가지 — Channel Code·BC309·BC412.
    ///
    /// 애니라벨 신형(0x25·0x26·0x27)이 쓰는 것을 원본 그림에서 풀어 확인했다.
    /// 여백은 두지 않는다. 39·40번 칸 그림이 왼쪽 끝부터 막대로 시작한다.
    /// </summary>
    private static bool TryDrawSingleWidth(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        var modules = id switch
        {
            "CHANNEL_CODE" => Barcode1DEncoders.EncodeChannelCode(DigitsOnly(value)),
            "BC309" => Barcode1DEncoders.EncodeBc309(value),
            "BC412" => Barcode1DEncoders.EncodeBc412(value),
            "FLATTERMARKEN" => Barcode1DEncoders.EncodeFlattermarken(DigitsOnly(value)),
            "CLOCKED_35" => Barcode1DEncoders.EncodeClocked35(DigitsOnly(value)),
            "CPC_BINARY" => Barcode1DEncoders.EncodeCpcBinary(value),
            _ => null
        };
        if (modules is null) return false;

        // Channel Code 캡션은 채널 수보다 한 자리 적게 왼쪽을 0으로 채운다(규격 9.2).
        var shown = id == "CHANNEL_CODE"
            ? ChannelCodeHri(DigitsOnly(value))
            : value.Trim().ToUpperInvariant();
        var quiet = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel ? 0 : 5;
        return TryDrawLinear(canvas, obj, modules, shown, quiet, alpha);
    }

    private static string ChannelCodeHri(string digits)
    {
        if (digits.Length is 0 or > 7) return digits;
        var channels = digits.Length + 1;
        if (!int.TryParse(digits, out var v)) return digits;
        if (v > 576688) channels = Math.Max(channels, 8);
        else if (v > 44072) channels = Math.Max(channels, 7);
        else if (v > 3493) channels = Math.Max(channels, 6);
        else if (v > 292) channels = Math.Max(channels, 5);
        else if (v > 26) channels = Math.Max(channels, 4);
        return digits.PadLeft(Math.Max(digits.Length, channels - 1), '0');
    }

    /// <summary>아이라벨 UPC-E 실측: 가드가 캡션 띠의 0.54만큼 내려온다(47px / 86.6px).</summary>
    private const float UpcEGuardDrop = 0.54f;

    /// <summary>양옆 숫자는 가드가 내려온 띠 안에 놓이고(22px), 가운데보다 작다(57px / 73px).</summary>
    private const float UpcESideBaseline = 0.25f;
    private const float UpcESideScale = 0.78f;

    /// <summary>
    /// UPC-E. 아이라벨 실측(1234567): 넘버 시스템 1 → 패리티 OOOEEE로 0계열의 반전, 51모듈.
    /// 캡션은 왼쪽 여백에 넘버 시스템 1자리, 막대 아래에 데이터 6자리, 오른쪽 여백에 체크 1자리다.
    /// </summary>
    private static bool TryDrawUpcE(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("UPC_E" or "UPCE" or "UPC_E0" or "UPC_E1"))
            return false;

        var vendor = Barcode1DEncoders.VendorOf(obj);
        var digits = DigitsOnly(value);
        // UPC-E0/E1은 넘버 시스템이 형식으로 정해진다. 아이라벨 UPC-E는 값의 첫 자리를 쓴다.
        bool? numberSystem1 = id switch { "UPC_E0" => false, "UPC_E1" => true, _ => null };
        // 애니라벨 신형은 일곱 자리를 「데이터 여섯 + 체크 하나」로 쓴다. 1234567 → 0 123456 5.
        var modules = Barcode1DEncoders.EncodeUpcE(
            digits, numberSystem1, out var hri, IsAniLabelModern(obj));
        if (modules is null) return false;

        var quiet = Barcode1DEncoders.Quiet(vendor, 9);
        var showText = obj.BarcodeShowText && DocumentRenderer.FontsReady;
        if (!showText || quiet < 1)
            return TryDrawLinear(canvas, obj, modules, digits, quiet, alpha);

        var textH = HriBand(obj, true);
        var barH = Math.Max(1f, obj.Height - textH);
        var cellW = obj.Width / (modules.Length + quiet * 2);
        var sideW = quiet * cellW;
        var bodyX = (quiet + 3) * cellW;
        var bodyW = 42 * cellW;
        var body = hri.Substring(1, 6);

        FillBarcodeBackground(canvas, obj, alpha);
        canvas.Save();
        canvas.ClipRect(new SKRect(0, 0, obj.Width, obj.Height));
        var guardDrop = GuardBarsOn(obj) ? textH * UpcEGuardDrop : 0f;
        DrawEanModules(canvas, modules, quiet, obj.Width, barH, ColorUtil.Parse(obj.Fill, alpha),
            0, guardDrop, retailGuards: guardDrop > 0, RetailGuardKind.UpcE);

        var faceMm = HriFontMm(obj, textH);
        using var font = CreateHriFont(obj, faceMm);
        FitHriToWidth(font, body, bodyW);
        using var sideFont = CreateHriFont(obj, faceMm * UpcESideScale);
        using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };

        DrawHriText(canvas, obj, body,
            bodyX + Math.Max(0, (bodyW - font.MeasureText(body)) * 0.5f),
            barH + textH * 0.82f, SKTextAlign.Left, font, tp);

        var sideY = barH + textH * UpcESideBaseline;
        var lead = hri[..1];
        var tail = hri[7..];
        DrawHriText(canvas, obj, lead,
            Math.Max(0, (sideW - sideFont.MeasureText(lead)) * 0.5f),
            sideY, SKTextAlign.Left, sideFont, tp);
        DrawHriText(canvas, obj, tail,
            (quiet + modules.Length) * cellW + Math.Max(0, (sideW - sideFont.MeasureText(tail)) * 0.5f),
            sideY, SKTextAlign.Left, sideFont, tp);
        canvas.Restore();
        return true;
    }

    private static bool TryDrawItf(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("ITF" or "ITF_6" or "ITF_14" or "ITF_16" or "LEITCODE" or "IDENTCODE"))
            return false;

        var vendor = Barcode1DEncoders.VendorOf(obj);
        var digits = DigitsOnly(value);
        var modules = Barcode1DEncoders.EncodeItf(
            digits, vendor, Barcode1DEncoders.IsAniLabelLegacy(obj));
        // 폼텍 ITF-14·시작/끝 표시만 베어러. 아이라벨은 막대만(실측).
        // 애니라벨은 구형 0x10에만 테두리가 있고 ITF-14(0x17)에는 없어서 가져오기 표시만 따른다.
        // 신형은 ITF-6·14·16 세 가지에만 있고, 가져올 때 원본 그림에서 보고 표시를 세운다.
        var bearer = vendor switch
        {
            BarcodeVendorKind.ILabel => false,
            BarcodeVendorKind.AniLabel => obj.BarcodeShowStartEnd,
            _ => id is "ITF_14" || obj.BarcodeShowStartEnd
        };
        var hri = !string.IsNullOrWhiteSpace(obj.Text)
            ? obj.Text.Trim()
            : digits;
        if ((obj.QrKind ?? "").Equals("CHECK_CAPTION", StringComparison.OrdinalIgnoreCase))
            hri = digits;
        // 신형 애니라벨 테두리는 여백 바깥에 따로 선다. 원본 그림에서 테두리 3X + 흰 여백 10X였다.
        var quiet = bearer && vendor == BarcodeVendorKind.AniLabel
                    && !Barcode1DEncoders.IsAniLabelLegacy(obj)
            ? 13
            : Barcode1DEncoders.Quiet(vendor, 10);
        return modules is not null
            && TryDrawLinear(canvas, obj, modules, hri, quiet, alpha, bearer);
    }

    /// <summary>
    /// 폼텍 OPC(0x1B, 1234567897) 실측은 ITF 1:2. 광학업계 관례 Code 39는 직접 추가분.
    /// </summary>
    private static bool TryDrawOpc(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not "OPC") return false;

        var vendor = Barcode1DEncoders.VendorOf(obj);
        var digits = DigitsOnly(value);
        if (vendor == BarcodeVendorKind.Formtec)
        {
            var itf = Barcode1DEncoders.EncodeItf(digits, vendor);
            return itf is not null
                && TryDrawLinear(canvas, obj, itf, digits, Barcode1DEncoders.Quiet(vendor, 10), alpha);
        }

        // 애니라벨 구형 0x25 실측(32번 칸): 막대는 저장값 10자리 그대로 ITF 1:2로 57요소,
        // 캡션만 5-4-1로 끊어 `12345-6789-7`로 찍는다. 막대가 상자 좌우 끝까지 닿아 여백이 없다.
        if (vendor == BarcodeVendorKind.AniLabel)
        {
            var aniItf = Barcode1DEncoders.EncodeItf(digits, vendor);
            return aniItf is not null
                && TryDrawLinear(canvas, obj, aniItf, OpcGroupedCaption(digits), 0, alpha);
        }

        // 아이라벨 실측(123456789): ITF 57요소, 캡션 1234567897. 9자리 + Luhn 체크.
        if (digits.Length == 0) return false;
        var payload = digits + LuhnCheckDigit(digits);
        var itfModules = Barcode1DEncoders.EncodeItf(payload, vendor);
        return itfModules is not null
            && TryDrawLinear(canvas, obj, itfModules, payload, Barcode1DEncoders.Quiet(vendor, 10), alpha);
    }

    /// <summary>OPC 캡션은 제조사 5 · 제품 4 · 체크 1로 끊는다. 자릿수가 다르면 그대로 둔다.</summary>
    private static string OpcGroupedCaption(string digits)
        => digits.Length == 10
            ? $"{digits[..5]}-{digits[5..9]}-{digits[9..]}"
            : digits;

    /// <summary>ISO/IEC 7812 Luhn. 오른쪽부터 한 칸 걸러 2배, 10 이상이면 자릿수 합.</summary>
    private static int LuhnCheckDigit(string digits)
    {
        var sum = 0;
        for (var i = 0; i < digits.Length; i++)
        {
            var d = digits[digits.Length - 1 - i] - '0';
            if (i % 2 == 0)
            {
                d *= 2;
                if (d > 9) d -= 9;
            }
            sum += d;
        }
        return (10 - sum % 10) % 10;
    }

    /// <summary>
    /// Numly(ESN). 아이라벨 실측(19자리): Code 39 209요소 = `*` + 19자리 + `*`, N:W=1:3.
    /// 캡션은 막대 값이 아니라 `ESN 12345-678901-234567-89` 형태다.
    /// </summary>
    private static bool TryDrawNumly(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        if (!BarcodeCatalog.IsNumly(obj.BarcodeFormat)) return false;

        var digits = DigitsOnly(value);
        if (digits.Length == 0) return false;
        var modules = EncodeCode39(digits, wide: 3);
        if (modules is null) return false;

        var vendor = Barcode1DEncoders.VendorOf(obj);
        var caption = BarcodeCatalog.FormatILabelNumlyCaption(value);
        return TryDrawLinear(canvas, obj, modules, caption, Barcode1DEncoders.Quiet(vendor, 10), alpha);
    }

    private static bool TryDrawMsi(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("MSI" or "MSI_PLESSEY"))
            return false;

        var vendor = Barcode1DEncoders.VendorOf(obj);
        var digits = DigitsOnly(value);
        // 폼텍 MSI/Plessey(0x19): 왼쪽 짝수자리×2 Mod10 두 번 → …463. 표준 MSI-1010(…422)과 다름.
        var formtec = vendor == BarcodeVendorKind.Formtec;
        var modules = Barcode1DEncoders.EncodeMsi(digits, checkCount: formtec ? 2 : 0, formtecChecks: formtec);
        return modules is not null
            && TryDrawLinear(canvas, obj, modules, digits, Barcode1DEncoders.Quiet(vendor, 12), alpha);
    }

    /// <summary>
    /// 한국 우체국 우편번호. 폼텍은 막대만 이 상자(약 4.3mm)에 두고, 설명 글자는 별도 텍스트 객체다.
    /// </summary>
    private static bool TryDrawKoreanPost(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not "KOREAN_POST") return false;
        var vendor = Barcode1DEncoders.VendorOf(obj);
        var payload = DigitsOnly(value);
        if (payload.Length == 0)
            payload = DigitsOnly(obj.Text ?? "");
        var modules = Barcode1DEncoders.EncodeKoreaPost(payload.Length > 0 ? payload : value, vendor);
        if (modules is null) return false;

        var caption = (obj.Text ?? "").Trim();
        if (caption is "LABEL UP" or "LABELUP" or "새 텍스트")
            caption = "";
        var hasCaption = obj.BarcodeShowText && caption.Length > 0;
        var showHri = obj.BarcodeShowText && !hasCaption;
        const float formtecBarMm = 4.3f;
        float barH;
        if (hasCaption && obj.Height > formtecBarMm + 3f)
            barH = formtecBarMm;
        else if (hasCaption || showHri)
            barH = Math.Max(1f, obj.Height - HriBand(obj, true));
        else
            barH = obj.Height;

        var quiet = Barcode1DEncoders.Quiet(vendor, 10);
        var barColor = ColorUtil.Parse(
            string.IsNullOrWhiteSpace(obj.Fill) || obj.Fill is "transparent" or "none" ? "#000000" : obj.Fill,
            alpha);
        if (HasOpaqueBackground(obj))
        {
            using var bg = new SKPaint
            {
                Color = ColorUtil.Parse(obj.BackgroundFill, alpha),
                IsAntialias = false,
                Style = SKPaintStyle.Fill
            };
            canvas.DrawRect(0, 0, obj.Width, barH, bg);
        }

        DrawEanModules(canvas, modules, quiet, obj.Width, barH, barColor);

        if (hasCaption)
            DrawPostalCaption(canvas, obj, caption, alpha);
        else if (showHri)
        {
            var hri = DigitsOnly(value);
            var textH = obj.Height - barH;
            if (textH > 0.6f && hri.Length > 0)
            {
                using var font = CreateHriFont(obj, HriFontMm(obj, textH));
                using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
                DrawCenteredHri(canvas, obj, hri, barH, textH, font, tp, obj.Width);
            }
        }
        return true;
    }

    private static void DrawPostalCaption(SKCanvas canvas, DesignObject obj, string caption, byte alpha)
    {
        var lines = caption.Replace("\r\n", "\n").Replace('\r', '\n')
            .Split('\n', StringSplitOptions.None);
        var size = obj.FontSize > 0.5f ? obj.FontSize : 3.2f;
        using var font = CreateHriFont(obj, size);
        font.ScaleX = DocumentRenderer.HriWidthScale(obj.FontFamily);
        using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
        font.GetFontMetrics(out var metrics);
        var lineH = Math.Max(size * 1.25f, -metrics.Ascent + metrics.Descent);
        var block = lineH * lines.Length;
        var y = (obj.Height - block) / 2f - metrics.Ascent;
        foreach (var line in lines)
        {
            var text = line.TrimEnd();
            FitHriToWidth(font, text, obj.Width * 0.96f);
            var tw = font.MeasureText(text);
            var x = Math.Max(0f, (obj.Width - tw) / 2f);
            DrawHriText(canvas, obj, text, x, y, SKTextAlign.Left, font, tp);
            y += lineH;
        }
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
    /// 이산형 2 of 5. IATA는 막대만 굵기 변화, Datalogic은 막대·간격 모두 변화.
    /// ZXing ITF(Interleaved)로 그리면 안 된다.
    /// </summary>
    private static bool TryDrawDiscrete25(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("I25_IATA" or "IATA" or "I25_DATALOGIC" or "DATALOGIC"
            or "I25_INDUSTRIAL" or "INDUSTRIAL" or "I25_MATRIX" or "MATRIX"
            or "I25_INVERT" or "INVERT" or "COOP25" or "COOP"))
            return false;
        var vendor = Barcode1DEncoders.VendorOf(obj);
        var digits = DigitsOnly(value);
        var modules = Barcode1DEncoders.EncodeDiscrete25(
            id, digits, vendor, Barcode1DEncoders.IsAniLabelLegacy(obj));
        if (modules is null) return false;
        var quiet = Barcode1DEncoders.Quiet(vendor, 4);
        return TryDrawLinear(canvas, obj, modules, digits, quiet, alpha);
    }

    private static bool TryDrawLinear(
        SKCanvas canvas, DesignObject obj, bool[] modules, string hri, int quiet, byte alpha,
        bool bearer = false, int edgeGuardRuns = 0, int trailGuardRuns = -1)
    {
        var textH = HriBand(obj, obj.BarcodeShowText);
        var barH = Math.Max(1f, obj.Height - textH);
        var guardExtraH = textH;
        // 가드는 캡션 칸까지 내려오는 것이라 캡션이 없으면 늘어날 자리가 없다.
        // 사용자가 「가드 길게」를 직접 고른 경우에만 소매 심볼과 같은 방식으로 몸통을 줄여 자리를 만든다.
        // auto는 변환 결과를 그대로 두려고 건드리지 않는다.
        if (guardExtraH <= 0 && (edgeGuardRuns > 0 || trailGuardRuns > 0)
            && (obj.BarcodeGuardBars ?? "").Trim() == DesignObject.GuardBarsOn)
        {
            guardExtraH = Math.Min(barH * 0.12f, Math.Max(0.9f, barH * 0.08f));
            barH = Math.Max(1f, barH - guardExtraH);
        }
        var barColor = ColorUtil.Parse(obj.Fill, alpha);
        FillBarcodeBackground(canvas, obj, alpha);
        canvas.Save();
        canvas.ClipRect(new SKRect(0, 0, obj.Width, obj.Height));
        DrawEanModules(canvas, modules, quiet, obj.Width, barH, barColor,
            guardExtraH: guardExtraH, edgeGuardRuns: edgeGuardRuns, trailGuardRuns: trailGuardRuns);
        if (bearer)
            DrawItfBearer(canvas, obj, modules, quiet, barH, barColor);
        if (textH > 0)
        {
            using var font = CreateHriFont(obj, HriFontMm(obj, textH));
            using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
            DrawCenteredHri(canvas, obj, hri, barH, textH, font, tp, obj.Width);
        }
        canvas.Restore();
        return true;
    }

    /// <summary>위·아래 전폭 가로줄. 두께는 ITF 굵은 막대(폼텍 2X, 그 외 3X)와 같다.</summary>
    private static void DrawItfBearer(
        SKCanvas canvas, DesignObject obj, bool[] modules, int quiet, float barH, SKColor color)
    {
        var total = modules.Length + quiet * 2;
        var cellW = obj.Width / Math.Max(1, total);
        var vendor = Barcode1DEncoders.VendorOf(obj);
        using var paint = new SKPaint
        {
            Color = color,
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };

        if (vendor == BarcodeVendorKind.AniLabel)
        {
            // 애니라벨은 위아래 굵은 베어러가 아니라 막대 영역을 두르는 가는 사각 테두리다.
            //「바코드 타입 전체.lbl」15번 칸 EMF: 500×434 논리 공간에서 네 변 모두 두께 4단위,
            // 좁은 막대도 4단위다. 논리 단위가 가로세로로 달라서 가로줄이 세로줄보다 얇게 나온다.
            //
            // 신형(0x1B)은 더 굵다. 「1D barcode 타입.lbl」13~15번 칸(ITF-6·14·16)에 든
            // 애니라벨 원본 그림은 좁은 막대가 6px, 네 변이 모두 18px로 3배다.
            // 그림 높이는 심볼 길이와 상관없이 늘 540px이라 가로줄은 상자 높이의 18/540이다.
            var legacy = Barcode1DEncoders.IsAniLabelLegacy(obj);
            var sideW = legacy ? cellW : cellW * 3f;
            var lineH = legacy
                ? (obj.Width > 0 ? cellW * obj.Height / obj.Width : cellW)
                : obj.Height * (18f / 540f);
            canvas.DrawRect(0, 0, obj.Width, lineH, paint);
            canvas.DrawRect(0, barH - lineH, obj.Width, lineH, paint);
            canvas.DrawRect(0, 0, sideW, barH, paint);
            canvas.DrawRect(obj.Width - sideW, 0, sideW, barH, paint);
            return;
        }

        var wide = vendor == BarcodeVendorKind.Formtec ? 2 : 3;
        var t = Math.Min(barH * 0.35f, cellW * wide);
        canvas.DrawRect(0, 0, obj.Width, t, paint);
        canvas.DrawRect(0, barH - t, obj.Width, t, paint);
    }

    private static bool TryDrawCodabar(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("CODABAR" or "ABC_CODABAR")) return false;
        var vendor = Barcode1DEncoders.VendorOf(obj);
        var abc = id == "ABC_CODABAR";
        var modules = Barcode1DEncoders.EncodeCodabar(
            value, vendor, abc, legacyPsoft: Barcode1DEncoders.IsAniLabelLegacy(obj));
        var hri = obj.BarcodeShowText && !string.IsNullOrWhiteSpace(obj.Text)
            ? obj.Text.Trim()
            : value.Trim();
        // 애니라벨(PSOFT)은 시작·정지 문자의 막대 4개씩을 글자 칸 끝까지 늘려 그린다.
        // 「바코드 타입 전체.lbl」 2번 칸 EMF에서 237개 중 앞 4·뒤 4개만 전체 높이다.
        // 같은 EMF에서 막대가 상자 좌우 끝(0~500)까지 닿으므로 여백도 없다.
        // 다만 8번 칸 ABC Codabar(0x09)는 EMF 막대 24개가 모두 같은 높이다. 긴 막대가 없다.
        // 신형(LBL 0x1B) Codabar도「1D barcode 타입.lbl」2번 칸 비트맵에서 막대 높이가 모두 같다.
        var anylabel = vendor == BarcodeVendorKind.AniLabel;
        var guards = GuardBarsOn(obj);
        var quiet = anylabel ? 0 : Barcode1DEncoders.Quiet(vendor, 8);
        return modules is not null
               && TryDrawLinear(canvas, obj, modules, hri, quiet, alpha,
                   edgeGuardRuns: guards ? 4 : 0);
    }

    private static bool TryDrawCode11(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not "CODE_11") return false;
        var payload = value.Trim();
        // 애니라벨은 막대가 상자 좌우 끝까지 닿는다(「바코드 타입 전체.lbl」35번 칸 실측).
        // 체크문자는 엔진마다 다르다. 구형 PSOFT(0x07)는 C·K 둘을 붙이지만,
        // 신형(0x1B)은 하나도 붙이지 않는다. 「1D barcode 타입.lbl」 3번 칸 애니라벨 원본 그림이
        // 71요소 = 시작 5 + 간격 1 + 열 글자 6씩 60 + 정지 5 로, 체크문자 자리가 없다.
        // Code 11 체크문자는 규격에서도 선택이라 둘 다 규격 안이다.
        var anylabel = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel;
        var checks = anylabel && Barcode1DEncoders.IsAniLabelLegacy(obj) ? 2 : 0;
        var modules = Barcode1DEncoders.EncodeCode11(payload, checks);
        return modules is not null
               && TryDrawLinear(canvas, obj, modules, payload, anylabel ? 0 : 4, alpha);
    }

    private static bool TryDrawEanAddon(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        var digits = DigitsOnly(value);
        var modules = id switch
        {
            "EAN_2" or "EAN2" => Barcode1DEncoders.EncodeEan2(digits.PadLeft(2, '0')[^2..]),
            "EAN_5" or "EAN5" => Barcode1DEncoders.EncodeEan5(digits.PadLeft(5, '0')[^5..]),
            _ => null
        };
        return modules is not null && TryDrawLinear(canvas, obj, modules, digits, quiet: 7, alpha);
    }

    private static bool TryDrawFim(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not "FIM") return false;
        // 애니라벨은 빈자리를 1모듈로만 잡아 폭이 좁고, 막대가 상자 좌우 끝까지 닿는다(38번 칸 실측).
        var anylabel = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel;
        var modules = Barcode1DEncoders.EncodeFim(value, compact: anylabel);
        return modules is not null
               && TryDrawLinear(canvas, obj, modules, value.Trim(), anylabel ? 0 : 4, alpha);
    }

    /// <summary>
    /// Telepen. 좁음 1모듈 / 넓음 3모듈이며 한 문자가 16모듈이다.
    /// 애니라벨 구형 0x31은 막대가 상자 좌우 끝까지 닿아 여백이 없다(39번 칸 실측).
    /// </summary>
    private static bool TryDrawTelepen(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not "TELEPEN") return false;
        // 검사문자는 엔진마다 다르다. 구형 PSOFT(0x31)는 붙이지만 신형(0x1B 0x17)은 붙이지 않는다.
        // 25번 칸 원본 그림 175요소 대 우리 185요소이고 앞 166요소가 같아 한 문자만큼 모자란다.
        var modules = Barcode1DEncoders.EncodeTelepen(value.Trim(), withCheck: !IsAniLabelModern(obj));
        if (modules is null) return false;
        var quiet = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel ? 0 : 10;
        return TryDrawLinear(canvas, obj, modules, value.Trim(), quiet, alpha);
    }

    /// <summary>
    /// Plessey. 애니라벨 신형만 우리 표로 그린다. 기본 경로(ZXing)는 그대로 둔다.
    /// 신형은 CRC 검사문자를 붙이지 않고 칸 하나가 5모듈이다(<see cref="Barcode1DEncoders.EncodePlesseyAniLabel"/>).
    /// </summary>
    private static bool TryDrawPlessey(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not "PLESSEY" || !IsAniLabelModern(obj)) return false;
        var modules = Barcode1DEncoders.EncodePlesseyAniLabel(value);
        return modules is not null
               && TryDrawLinear(canvas, obj, modules, value.Trim(), 0, alpha);
    }

    /// <summary>
    /// Pharmacode One-track(Laetus). 인코더가 없어 Code 128 로 흘러가던 것을 여기서 끊는다.
    ///
    /// 막대가 상자 좌우 끝까지 닿는다. 「1D barcode 타입.lbl」 26번 칸 애니라벨 원본 그림
    /// 360px 이 60모듈 × 6px 로 딱 맞아 여백이 한 칸도 없다.
    /// </summary>
    private static bool TryDrawPharmaOne(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("PHARMA_1" or "PHARMA")) return false;
        var digits = DigitsOnly(value);
        var modules = Barcode1DEncoders.EncodePharmaOne(digits);
        if (modules is null) return false;

        var quiet = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel
            ? 0
            : Barcode1DEncoders.Quiet(Barcode1DEncoders.VendorOf(obj), 6);
        return TryDrawLinear(canvas, obj, modules, digits, quiet, alpha);
    }

    /// <summary>
    /// Pharmacode Two-track(Laetus). 막대 폭과 막대 사이가 1:1 이고(a3 = c3 = 1mm),
    /// 위 칸·아래 칸 높이가 각각 전높이의 절반이다(e3 = e4 = e5 ÷ 2). 사이를 띄우지 않는다.
    /// 「1D barcode 타입.lbl」 27번 칸 애니라벨 원본 그림도 막대 6px·사이 6px 에
    /// 위 칸 0~232행·아래 칸 233~466행으로 맞닿아 있었다.
    /// </summary>
    private static bool TryDrawPharmaTwo(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not "PHARMA_2") return false;
        var bars = Barcode1DEncoders.EncodePharmaTwo(DigitsOnly(value));
        if (bars is null) return false;

        var textH = HriBand(obj, obj.BarcodeShowText);
        var barH = Math.Max(1f, obj.Height - textH);
        var barColor = ColorUtil.Parse(obj.Fill, alpha);
        FillBarcodeBackground(canvas, obj, alpha);

        // 애니라벨은 좌우에 12모듈씩 비운다(원본 그림 330px = 여백 12 + 내용 31 + 여백 12 모듈).
        var quiet = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel ? 12 : 10;
        var total = bars.Length * 2 - 1 + quiet * 2;
        var unit = obj.Width / Math.Max(1, total);
        var half = barH * 0.5f;
        using var paint = new SKPaint
        {
            Color = barColor,
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };
        for (var i = 0; i < bars.Length; i++)
        {
            var x = (quiet + i * 2) * unit;
            var kind = bars[i];
            if (kind is 1 or 2)
                canvas.DrawRect(x, 0, unit, half, paint);
            if (kind is 0 or 2)
                canvas.DrawRect(x, barH - half, unit, half, paint);
        }

        if (textH > 0)
        {
            using var font = CreateHriFont(obj, HriFontMm(obj, textH));
            using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
            DrawCenteredHri(canvas, obj, DigitsOnly(value), barH, textH, font, tp, obj.Width);
        }
        return true;
    }

    /// <summary>
    /// 코닥 패치 코드. 자료를 싣지 않고 스캐너에 「여기서 문서를 나눠라」만 알리는 표시다.
    ///
    /// 넓은 막대 둘(0.20인치)과 좁은 막대 둘(0.08인치)을 좁은 간격(0.08인치) 셋으로 띄운다.
    /// 여섯 가지뿐이라 값이 곧 종류다. 1=WWnn 2=WnnW 3=WnWn 4=nWWn 6=nnWW T=nWnW.
    /// 전체 폭은 늘 0.80인치(좁은 폭 10개분)라 어떤 종류든 크기가 같다.
    ///
    /// 다른 1D 와 달리 막대가 가로로 눕고 위에서 아래로 읽는다.
    /// 애니라벨 신형 0x2A(43번 칸 값 `1`)도 30·12·30·12·12·12·12 픽셀로 눕혀 그려
    /// 넓은 막대 대 좁은 막대가 2.5:1 인 규격과 맞는다. 자료가 없으니 캡션도 없다.
    /// </summary>
    private static bool TryDrawPatchCode(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("PATCH_CODE" or "PATCHCODE")) return false;

        var kind = (value ?? "").Trim().ToUpperInvariant();
        var bars = kind switch
        {
            "1" => "WWnn",
            "2" => "WnnW",
            "3" => "WnWn",
            "4" => "nWWn",
            "6" => "nnWW",
            "T" => "nWnW",
            _ => null
        };
        if (bars is null) return false;

        FillBarcodeBackground(canvas, obj, alpha);
        using var paint = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Fill, alpha),
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };

        // 좁은 폭을 1로 보면 넓은 막대 2.5, 좁은 막대 1, 간격 1 이라 전부 10이다.
        var unit = obj.Height / 10f;
        var y = 0f;
        for (var i = 0; i < 4; i++)
        {
            var thick = (bars[i] == 'W' ? 2.5f : 1f) * unit;
            canvas.DrawRect(0, y, obj.Width, thick, paint);
            y += thick + unit;
        }
        return true;
    }

    private static bool TryDrawPostnet(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("POSTNET" or "PLANET")) return false;
        var digits = DigitsOnly(value);

        // 애니라벨 실측(25번 칸 POSTNET): 막대 폭과 간격이 1:1, 짧은 막대는 전체 높이의 절반이다.
        // 폼텍·아이라벨은 기존 값(간격 0.6X, 짧은 막대 0.4)을 그대로 쓴다.
        var anylabel = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel;
        var pattern = Barcode1DEncoders.EncodePostnetPattern(digits, id is "PLANET", !anylabel);
        if (pattern is null) return false;

        var textH = HriBand(obj, obj.BarcodeShowText);
        var barH = Math.Max(1f, obj.Height - textH);
        var barColor = ColorUtil.Parse(obj.Fill, alpha);
        FillBarcodeBackground(canvas, obj, alpha);

        var gap = anylabel ? 2f : 1.6f;
        var total = pattern.Length * gap - (gap - 1f);
        var unit = obj.Width / Math.Max(1f, total);
        var shortH = barH * (anylabel ? 0.5f : 0.4f);
        using var paint = new SKPaint
        {
            Color = barColor,
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };
        for (var i = 0; i < pattern.Length; i++)
        {
            var tall = pattern[i] == 'L';
            var h = tall ? barH : shortH;
            var y = barH - h;
            canvas.DrawRect(i * gap * unit, y, unit, h, paint);
        }

        if (textH > 0)
        {
            using var font = CreateHriFont(obj, HriFontMm(obj, textH));
            using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
            DrawCenteredHri(canvas, obj, digits, barH, textH, font, tp, obj.Width);
        }
        return true;
    }

    /// <summary>애니라벨 구형 0x1F. 시작·정지 막대는 있고 체크문자만 없는 4상태다.</summary>
    public const string FourStateNoCheck = "FOUR_STATE_NO_CHECK";

    /// <summary>
    /// RM4SCC·KIX 4상태 우편 심볼. 막대 칸을 세로로 3등분해서
    /// F는 전체, A는 위 2/3, T는 가운데 1/3, D는 아래 2/3를 채운다.
    /// 애니라벨 구형 EMF 실측(27번 칸): 전체 0~363, 상승부 0~242, 추적부 121~242, 하강부 121~363.
    /// 막대 폭과 간격은 1:1이다. KIX는 시작·정지 막대도 체크문자도 없다.
    /// USPS Intelligent Mail(65막대)과 Australia Post(37막대)도 같은 그리기 규칙을 쓴다.
    /// </summary>
    private static bool TryDrawFourState(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("RM4SCC" or "KIX" or "KIX4S" or "ONECODE" or "AUSPOST" or "JAPAN_POST"
            or "POSTBAR"))
            return false;

        string? pattern;
        var shown = value.Trim();
        if (id is "ONECODE")
        {
            pattern = Barcode1DEncoders.EncodeImb(value);
        }
        else if (id is "AUSPOST")
        {
            pattern = Barcode1DEncoders.EncodeAusPost(value, out var apHri);
            // 애니라벨도 `59 32211324 A124B 26 23 20 52` 처럼 칸을 띄고 검사심볼까지 적는다.
            if (pattern is not null) shown = apHri;
        }
        else if (id is "POSTBAR")
        {
            pattern = Barcode1DEncoders.EncodePostBar(value, out var pbHri);
            // 애니라벨도 `B K1A4S2 1234 18283160` 처럼 칸을 띄고 검사심볼까지 적는다.
            if (pattern is not null) shown = pbHri;
        }
        else if (id is "JAPAN_POST")
        {
            pattern = Barcode1DEncoders.EncodeJapanPost(value, out var jpHri);
            // 캡션에 검사문자를 붙여 보여 준다. 애니라벨도 `1234567-890-AA` 로 찍는다.
            if (pattern is not null) shown = jpHri;
        }
        else
        {
            var kix = id is not "RM4SCC";
            var noCheck = kix
                || (obj.QrKind ?? "").Equals(FourStateNoCheck, StringComparison.OrdinalIgnoreCase);
            pattern = Barcode1DEncoders.EncodeFourState(value, startStop: !kix, check: !noCheck);
        }
        if (pattern is null) return false;

        var textH = HriBand(obj, obj.BarcodeShowText);
        var barH = Math.Max(1f, obj.Height - textH);
        FillBarcodeBackground(canvas, obj, alpha);
        canvas.Save();
        canvas.ClipRect(new SKRect(0, 0, obj.Width, obj.Height));

        var unit = obj.Width / Math.Max(1f, pattern.Length * 2 - 1);
        var third = barH / 3f;
        using var paint = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Fill, alpha),
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };
        for (var i = 0; i < pattern.Length; i++)
        {
            var (top, bottom) = pattern[i] switch
            {
                'F' => (0f, barH),
                'A' => (0f, third * 2f),
                'D' => (third, barH),
                _ => (third, third * 2f)
            };
            canvas.DrawRect(i * 2 * unit, top, unit, bottom - top, paint);
        }

        if (textH > 0)
        {
            using var font = CreateHriFont(obj, HriFontMm(obj, textH));
            using var tp = new SKPaint { Color = HriColor(obj, alpha), IsAntialias = true };
            DrawCenteredHri(canvas, obj, shown, barH, textH, font, tp, obj.Width);
        }
        canvas.Restore();
        return true;
    }

    /// <summary>
    /// 애니라벨은 값만 있는 MaxiCode도 모드 5(전체 오류정정)로 찍는다.
    /// 「QR코드 타입.lbl」 7번 칸을 복호해 부호어 0~20이 모드 5와 그대로 맞는 것을 확인했다.
    /// </summary>
    private const int AniLabelMaxiCodeMode = 5;

    /// <summary>
    /// 그림 8의 행 간격 Y ÷ 칸 너비 X = √3/2. 애니라벨 BMP는 5px ÷ 4px = 0.800으로 납작하지만
    /// 그쪽 과녁은 찌그러짐 없는 지름 42px 원이고, 42 ÷ 9 = 4.67px는 심볼 높이에서 되짚은
    /// X(133 ÷ 28.87 = 4.61px)와 맞는다. 즉 애니라벨도 √3/2를 의도했고 행 간격만
    /// 4.33px에서 정수로 내림한 것이라 규격 값을 그대로 쓴다.
    /// </summary>
    private const float MaxiRowPitchRatio = 0.8660254f;

    /// <summary>육각형 긴 지름 V ÷ 짧은 지름 X = 2/√3.</summary>
    private const float MaxiHexHeightRatio = 1.1547005f;

    /// <summary>
    /// MaxiCode (ISO/IEC 16023). 정육각형 모듈을 벌집으로 쌓고 가운데에 과녁(동심원 셋)을 올린다.
    /// 사각 모듈이 아니라 ZXing BitMatrix 경로를 쓸 수 없어 여기서 끝까지 그린다.
    /// 홀수 행은 반 칸 오른쪽으로 밀리고 열이 하나 적어 29칸이다(그림 5의 오른쪽 끝 자리가 비어 있다).
    /// </summary>
    private static bool TryDrawMaxiCode(SKCanvas canvas, DesignObject obj, string value, byte alpha)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is not ("MAXICODE" or "MAXI_CODE")) return false;

        var anylabel = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel;
        var grid = (anylabel ? MaxiCodeEncoder.Encode(value, AniLabelMaxiCodeMode) : null)
                   ?? MaxiCodeEncoder.Encode(value);
        if (grid is null)
        {
            // 모드 4 용량(부호어 93개)을 넘었거나 라틴1 밖 글자가 섞였다.
            // 여기서 false를 돌려주면 ZXing 격자 경로로 흘러 엉뚱한 심볼이 나온다.
            DrawPlaceholder(canvas, obj, "바코드 오류", alpha);
            return true;
        }

        FillBarcodeBackground(canvas, obj, alpha);
        canvas.Save();
        canvas.ClipRect(new SKRect(0, 0, obj.Width, obj.Height));

        // 33행 × 30열은 고정 크기라 상자 비율과 무관하게 심볼 비율을 지키고 왼쪽 위에 붙인다.
        var symbolHeight = 32f * MaxiRowPitchRatio + MaxiHexHeightRatio;
        var hexWidth = Math.Min(obj.Width / MaxiCodeEncoder.Columns, obj.Height / symbolHeight);
        var hexHeight = hexWidth * MaxiHexHeightRatio;
        var rowPitch = hexWidth * MaxiRowPitchRatio;

        using var paint = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Fill, alpha),
            IsAntialias = true,
            Style = SKPaintStyle.Fill
        };
        using var honeycomb = new SKPath();
        for (var row = 0; row < MaxiCodeEncoder.Rows; row++)
        {
            var odd = (row & 1) != 0;
            var cy = hexHeight / 2f + row * rowPitch;
            var cols = MaxiCodeEncoder.Columns - (odd ? 1 : 0);
            for (var col = 0; col < cols; col++)
            {
                if (!grid[row, col]) continue;
                var cx = (col + (odd ? 1f : 0.5f)) * hexWidth;
                AppendMaxiHexagon(honeycomb, cx, cy, hexWidth, hexHeight);
            }
        }
        canvas.DrawPath(honeycomb, paint);

        // 4.11.4 과녁. 전체 지름은 9X고, 첫 지름 V에서 같은 폭으로 다섯 번 커진다.
        var step = (9f * hexWidth - hexHeight) / 5f;
        using var ring = new SKPaint
        {
            Color = paint.Color,
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = step / 2f
        };
        var bullX = 14.5f * hexWidth;
        var bullY = symbolHeight * hexWidth / 2f;
        for (var k = 5; k >= 1; k -= 2)
            canvas.DrawCircle(bullX, bullY, (hexHeight + step * k - step / 2f) / 2f, ring);

        canvas.Restore();
        return true;
    }

    /// <summary>그림 8의 정육각형. 위아래가 꼭짓점이고 좌우가 평면이라 가로로 맞물린다.</summary>
    private static void AppendMaxiHexagon(SKPath path, float cx, float cy, float w, float h)
    {
        var half = w / 2f;
        var shoulder = h / 4f;
        path.MoveTo(cx, cy - h / 2f);
        path.LineTo(cx + half, cy - shoulder);
        path.LineTo(cx + half, cy + shoulder);
        path.LineTo(cx, cy + h / 2f);
        path.LineTo(cx - half, cy + shoulder);
        path.LineTo(cx - half, cy - shoulder);
        path.Close();
    }

    /// <summary>
    /// 아이라벨 Bookland 값은 9자리(123456789) → 978123456789 + 체크 = 9781234567897.
    /// 폼텍 ISBN-10은 10자리 → 978+앞9자리. ISMN=979+9자리, ISSN=977+ISSN7+00.
    /// </summary>
    private static string ToBooklandEan13(string id, string digits)
    {
        if (digits.Length >= 13) return digits[..13];
        if (digits.Length == 12) return digits;
        if (id is "ISSN" && digits.Length >= 7)
            return ("977" + digits.PadRight(9, '0'))[..12];
        if (id is "ISMN")
        {
            if (digits.Length == 10) return "979" + digits[..9];
            if (digits.Length == 9) return "979" + digits;
            if (digits.Length is > 0 and < 9) return "979" + digits.PadLeft(9, '0');
        }
        if (digits.Length == 10)
            return "978" + digits[..9];
        if (digits.Length == 9)
            return "978" + digits;
        if (digits.Length is > 0 and < 9 && id is "ISBN" or "BOOKLAND")
            return "978" + digits.PadLeft(9, '0');
        return digits;
    }

    /// <summary>아이라벨 JAN-13 10자리 → 49 + 10자리. 이후 EAN-13 체크로 4912345678904.</summary>
    private static string ToILabelJan13(string digits)
    {
        if (digits.Length >= 13) return digits[..13];
        if (digits.Length == 12) return digits;
        if (digits.Length == 10) return "49" + digits;
        if (digits.Length is > 0 and < 10) return "49" + digits.PadLeft(10, '0');
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
            "EAN_128" or "GS1_128" or "UCC_EAN_128" or "UCC_128" => BarcodeFormat.CODE_128,
            "EAN_13" or "EAN13" or "JAN_13" or "ISBN" or "ISSN" or "ISMN" => BarcodeFormat.EAN_13,
            "NUMLY" or "ESBN" or "ESN" => BarcodeFormat.CODE_39,
            "EAN_5" or "EAN_2" => BarcodeFormat.CODE_128,
            "EAN_8" or "EAN8" or "JAN_8" => BarcodeFormat.EAN_8,
            "UPC_A" or "UPCA" => BarcodeFormat.UPC_A,
            "UPC_E" or "UPCE" or "UPC_E0" or "UPC_E1" => BarcodeFormat.UPC_E,
            "ITF" or "ITF_14" or "ITF_6" or "ITF_16" or "LEITCODE" or "IDENTCODE" => BarcodeFormat.ITF,
            "CODABAR" or "ABC_CODABAR" => BarcodeFormat.CODABAR,
            "MSI" => BarcodeFormat.MSI,
            "PLESSEY" => BarcodeFormat.PLESSEY,
            "PHARMA_1" or "PHARMA_2" => BarcodeFormat.PHARMA_CODE,
            "RSS_14" or "RSS_LIMITED" => BarcodeFormat.RSS_14,
            "RSS_EXPANDED" => BarcodeFormat.RSS_EXPANDED,
            "ONECODE" => BarcodeFormat.IMB,
            _ => objTypeFallback(key)
        };

        static BarcodeFormat objTypeFallback(string k)
            => k.Contains("QR") ? BarcodeFormat.QR_CODE : BarcodeFormat.CODE_128;
    }

    private static BitMatrix? EncodeBest(DesignObject obj, BarcodeFormat format, string value, int pxW, int pxH)
    {
        foreach (var (fmt, payload) in EncodeCandidates(obj.BarcodeFormat, format, value))
        {
            var matrix = TryEncode(obj, fmt, payload, pxW, pxH);
            if (matrix is { Width: > 0, Height: > 0 })
                return matrix;
        }
        if (IsMatrixFormat(format))
            return null;
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is "KOREAN_POST")
            return null;
        return TryEncode(obj, BarcodeFormat.CODE_128, value, pxW, 2);
    }

    private static IEnumerable<(BarcodeFormat Format, string Value)> EncodeCandidates(string? catalogId, BarcodeFormat format, string value)
    {
        var id = (catalogId ?? "").ToUpperInvariant();
        var digits = DigitsOnly(value);

        if (id is "EAN_2" or "EAN_5" or "FIM" or "POSTNET" or "PLANET"
            or "I25_IATA" or "IATA" or "I25_DATALOGIC" or "DATALOGIC"
            or "I25_INDUSTRIAL" or "I25_MATRIX" or "I25_INVERT" or "COOP25"
            or "CODE_11" or "PHARMA_2" or "CODABAR" or "ABC_CODABAR" or "TELEPEN"
            or "ONECODE" or "AUSPOST" or "JAPAN_POST" or "POSTBAR" or "CPC_BINARY"
            or "CLOCKED_35"
            or "CODE_93" or "CODE93" or "CODE_93_EXT"
            or "EAN_128" or "GS1_128" or "UCC_EAN_128" or "UCC_128"
            or "UPC_E" or "UPCE" or "UPC_E0" or "UPC_E1"
            or "ITF" or "ITF_6" or "ITF_14" or "ITF_16" or "LEITCODE" or "IDENTCODE"
            or "MSI" or "MSI_PLESSEY")
            yield break;

        if (id is "PATCH_CODE" or "KIX" or "RM4SCC" or "UPU")
        {
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (id is "KOREAN_POST")
            yield break;

        if (format == BarcodeFormat.CODABAR)
        {
            yield return (BarcodeFormat.CODABAR, EnsureCodabar(value));
            yield return (BarcodeFormat.CODE_128, value);
            yield break;
        }

        if (format == BarcodeFormat.EAN_13)
        {
            var ean = id is "ISBN" or "ISSN" or "ISMN" or "BOOKLAND"
                ? ToBooklandEan13(id, digits)
                : digits;
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
            // 체크숫자를 고쳐서 넘긴다. 저장값 그대로면 ZXing 이 거절해 Code 128 로 떨어진다.
            if (digits.Length is 11 or 12)
                yield return (BarcodeFormat.UPC_A, EnsureUpcA(digits));
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

        if (id is "PZN")
            yield break;

        if (id is "OPC")
            yield break;

        if (format == BarcodeFormat.CODE_39)
        {
            var payload = id is "CODE_39_EXT"
                ? Barcode1DEncoders.ToCode39FullAscii(value)
                : value.ToUpperInvariant();
            yield return (BarcodeFormat.CODE_39, payload);
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

        if (format == BarcodeFormat.PHARMA_CODE && id is not "PHARMA_2")
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

    private static BitMatrix? TryEncode(DesignObject obj, BarcodeFormat format, string value, int pxW, int pxH)
    {
        if (format == BarcodeFormat.EAN_13)
        {
            var modules = EncodeEan13Modules(DigitsOnly(value));
            if (modules is not null) return ModulesToMatrix(modules);
        }

        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        if (id is "MICRO_QR" or "MICRO_QR_CODE")
        {
            // 애니라벨은 판형·오류정정·마스크를 BMP에서 재어 넣어 둔다. 없으면 규격대로 자동이다.
            var microQrVersion = obj.QrVersion is > 0 and <= 4 ? obj.QrVersion : 0;
            return MicroQrEncoder.Encode(value, microQrVersion, obj.QrEcc, ParseMaskHint(obj.QrKind) ?? -1)
                   ?? MicroQrEncoder.Encode(value);
        }
        if (id is "HANXIN" or "HAN_XIN")
        {
            // 애니라벨은 판형·오류정정·마스크를 BMP에서 재어 넣어 둔다. 없으면 규격대로 자동이다.
            var hanXinVersion = obj.QrVersion is > 0 and <= 84 ? obj.QrVersion : 0;
            return HanXinEncoder.Encode(value, hanXinVersion, obj.QrEcc, ParseMaskHint(obj.QrKind) ?? -1)
                   ?? HanXinEncoder.Encode(value);
        }
        if (id is "GRID_MATRIX" or "GRIDMATRIX")
        {
            // 오류정정 등급은 심볼에 적히지 않아 자동에 맡긴다. 판형만 실측값을 넘긴다.
            // 애니라벨 심볼은 혼합 모드 빈칸과 바이트 블록 길이가 규격을 벗어나 있어, 가져온 객체에만
            // 그 결함을 켜서 원본과 모듈까지 같게 찍는다. 편집기에서 새로 만든 것은 규격대로 둔다.
            var gridVersion = obj.QrVersion is > 0 and <= 13 ? obj.QrVersion : 0;
            var gridQuirks = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel;
            return GridMatrixEncoder.Encode(value, gridVersion, 0, gridQuirks)
                   ?? GridMatrixEncoder.Encode(value, 0, 0, gridQuirks);
        }
        if (id is "RSS_14" or "RSS14" or "GS1_DATABAR")
        {
            var rss14 = GS1DataBarEncoder.EncodeRss14(value);
            if (rss14 is not null) return rss14;
        }
        if (id is "RSS_LIMITED" or "GS1_DATABAR_LIMITED")
        {
            var rssLimited = GS1DataBarEncoder.EncodeRssLimited(value);
            if (rssLimited is not null) return rssLimited;
        }
        if (id is "RSS_EXPANDED" or "GS1_DATABAR_EXPANDED")
        {
            var rssExpanded = GS1DataBarEncoder.EncodeRssExpanded(value);
            if (rssExpanded is not null) return rssExpanded;
        }
        if (id is "CODE_16K" or "CODE16K")
        {
            var code16k = Code16kEncoder.Encode(value);
            if (code16k is not null) return code16k;
        }
        if (id is "MICRO_PDF417" or "MICRO_PDF_417")
        {
            // 애니라벨은 열·행을 BMP에서 재어 `1x17` 꼴로 넣어 둔다. 없으면 규격 AutoSize를 쓴다.
            TryParseGrid(obj.QrKind, out var microCols, out var microRows);
            var latch = Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel;
            return MicroPdf417Encoder.Encode(value, microCols, microRows, latch)
                   ?? MicroPdf417Encoder.Encode(value, leadTextLatch: latch);
        }

        EncodingOptions? options = null;
        try
        {
            options = format switch
            {
                BarcodeFormat.QR_CODE => new QrCodeEncodingOptions
                {
                    Width = 0,
                    Height = 0,
                    Margin = 0,
                    CharacterSet = SkipQrEci(obj, value) ? null : "UTF-8",
                    ErrorCorrection = ParseEcc(obj.QrEcc)
                },
                BarcodeFormat.DATA_MATRIX => MakeDataMatrixOptions(obj),
                BarcodeFormat.PDF_417 => MakePdf417Options(obj, value),
                BarcodeFormat.AZTEC => new AztecEncodingOptions
                {
                    Width = 0,
                    Height = 0,
                    Margin = 0,
                    PureBarcode = true
                },
                _ => new EncodingOptions { Width = pxW, Height = format is BarcodeFormat.QR_CODE ? pxH : 2, Margin = 0, PureBarcode = true }
            };
            if (format == BarcodeFormat.QR_CODE && options is QrCodeEncodingOptions qr)
            {
                if (obj.QrVersion is > 0 and <= 40)
                    qr.Hints[EncodeHintType.QR_VERSION] = obj.QrVersion;
                if (Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.ILabel
                    && ILabelQrMasks.TryGetValue(MaskKey(obj, value), out var ilabelMask))
                    qr.Hints[EncodeHintType.QR_MASK_PATTERN] = ilabelMask;
                // 애니라벨은 마스크 번호를 가져오기 때 BMP에서 읽어 `MASK:n`으로 넣어 둔다.
                else if (ParseMaskHint(obj.QrKind) is { } aniMask)
                    qr.Hints[EncodeHintType.QR_MASK_PATTERN] = aniMask;
            }

            var encoded = new BarcodeWriterGeneric { Format = format, Options = options }.Encode(value);
            if (format == BarcodeFormat.PDF_417
                && Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.ILabel)
                encoded = SqueezeIdenticalRows(encoded);
            return encoded;
        }
        catch
        {
            var fallbackVendor = Barcode1DEncoders.VendorOf(obj);
            if (format == BarcodeFormat.DATA_MATRIX
                && fallbackVendor is BarcodeVendorKind.ILabel or BarcodeVendorKind.AniLabel
                && options is DatamatrixEncodingOptions dm)
            {
                try
                {
                    // 실측 크기·인코딩에 안 들어가는 자료로 바뀐 경우다. 제약을 풀고 다시 시도한다.
                    dm.DefaultEncodation = Encodation.ASCII;
                    if (fallbackVendor == BarcodeVendorKind.AniLabel)
                    {
                        dm.MinSize = null;
                        dm.MaxSize = null;
                        dm.SymbolShape = SymbolShapeHint.FORCE_SQUARE;
                    }
                    return new BarcodeWriterGeneric { Format = format, Options = dm }.Encode(value);
                }
                catch
                {
                    return null;
                }
            }
            if (format == BarcodeFormat.PDF_417
                && fallbackVendor == BarcodeVendorKind.AniLabel
                && options is PDF417EncodingOptions pdf)
            {
                try
                {
                    // 못 박은 열·행에 안 들어갈 만큼 자료가 길어졌다. 열만 지키고 행은 푼다.
                    pdf.Dimensions = new Dimensions(1, 30, 3, 90);
                    return new BarcodeWriterGeneric { Format = format, Options = pdf }.Encode(value);
                }
                catch
                {
                    return null;
                }
            }
            return null;
        }
    }

    /// <summary>
    /// 아이라벨이 고른 QR 마스크를 화면에서 읽어 적어 둔 표. 키는 자료·오류정정·버전이다.
    /// 규격(ISO/IEC 18004)은 벌점 규칙 1~4의 합이 가장 낮은 마스크를 쓰라고 하지만 아이라벨은 다르게
    /// 고른다. 벌점 규칙 구현 변형 30,600가지를 실측 7건에 맞춰 봐도 다 맞는 건 없었다(최고 6/7,
    /// 규격 그대로는 3/7). 규칙을 못 찾았으니 억지로 흉내내지 않고, 읽어 낸 값만 적고 나머지는
    /// ZXing 기본(=규격 최소 벌점)에 맡긴다. 아이라벨 인코더는 정해진 함수라 같은 자료·오류정정·
    /// 버전이면 항상 같은 마스크가 나오므로, 표에 있는 조합은 언제나 맞다.
    /// 마스크는 자료·오류정정·버전을 바꾸지 않으므로 어느 쪽이든 읽히는 값은 같다. 무늬만 다르다.
    /// 폼텍과 우리 에디터가 만드는 QR은 이 표를 타지 않는다.
    /// </summary>
    private static readonly Dictionary<string, int> ILabelQrMasks = new(StringComparer.OrdinalIgnoreCase)
    {
        ["123456789012:L:0"] = 7,     // 6페이지 10번. 규격 최소는 3
        ["123456789012:H:0"] = 2,     // 6페이지 8·9번. 규격 최소는 7
        ["123456789012:H:1"] = 2,     // 6페이지 13번
        ["123456789012:H:4"] = 0,     // 6페이지 14번. 규격 최소는 7
        ["123456789012:H:10"] = 2     // 7페이지 1번. 규격 최소는 0
        // 6페이지 11·12번(Medium·Quarter)과 7페이지 2번(값 1234567)은 아이라벨도 ZXing과 같은
        // 마스크를 골라 적을 게 없다.
    };

    private static string MaskKey(DesignObject obj, string value)
        => $"{value}:{(obj.QrEcc ?? "M").Trim().ToUpperInvariant()}:{obj.QrVersion}";

    /// <summary>
    /// ZXing은 문자셋 힌트가 있으면 값과 상관없이 ECI 머리말 12비트를 먼저 넣는다.
    /// 애니라벨은 ECI를 쓰지 않아 같은 버전·오류정정 수준이라도 그림이 달라지고,
    /// 「QR코드 타입.lbl」 1번 칸(버전 1·H·마스크 3)처럼 아예 그 버전에 안 들어가기도 한다.
    /// 라틴1로 적히는 값이면 힌트를 빼야 애니라벨과 같은 심볼이 나온다.
    /// </summary>
    private static bool SkipQrEci(DesignObject obj, string value)
        => Barcode1DEncoders.VendorOf(obj) == BarcodeVendorKind.AniLabel
           && value.All(c => c <= 0xFF);

    /// <summary>`MASK:3` 형태면 마스크 번호를, 아니면 null을 준다.</summary>
    private static int? ParseMaskHint(string? raw)
    {
        var key = (raw ?? "").Trim();
        if (!key.StartsWith("MASK:", StringComparison.OrdinalIgnoreCase)) return null;
        return int.TryParse(key[5..], NumberStyles.Integer, CultureInfo.InvariantCulture, out var n)
               && n is >= 0 and <= 7
            ? n
            : null;
    }

    /// <summary>`3x9` 형태를 열·행으로 가른다. 애니라벨 2D 모양 실측값이 이 꼴로 들어온다.</summary>
    private static bool TryParseGrid(string? raw, out int cols, out int rows)
    {
        cols = rows = 0;
        var parts = (raw ?? "").Trim().ToUpperInvariant().Split('X');
        return parts.Length == 2
               && int.TryParse(parts[0], NumberStyles.Integer, CultureInfo.InvariantCulture, out cols)
               && int.TryParse(parts[1], NumberStyles.Integer, CultureInfo.InvariantCulture, out rows)
               && cols > 0 && rows > 0;
    }

    /// <summary>
    /// 표준/Truncated PDF417. Micro는 <see cref="MicroPdf417Encoder"/>가 담당한다.
    /// 아이라벨은 ECC·최소 열·종횡비를 옵션으로 넘기고, 폼텍은 ZXing 기본(ECC 2, 열 2–30)을 유지한다.
    /// </summary>
    private static PDF417EncodingOptions MakePdf417Options(DesignObject obj, string value)
    {
        var id = (obj.BarcodeFormat ?? "").Replace("-", "_").ToUpperInvariant();
        var opt = new PDF417EncodingOptions
        {
            Width = 0,
            Height = 0,
            Margin = 0,
            PureBarcode = true,
            Compact = id is "PDF_417_TRUNC" or "PDF417_TRUNCATED" or "PDF_417_TRUNCATED"
        };
        var vendor = Barcode1DEncoders.VendorOf(obj);
        if (vendor == BarcodeVendorKind.AniLabel)
        {
            // PDF417은 행 수·열 수·오류정정 수준을 인코더가 고르게 되어 있어(ISO/IEC 15438)
            // 같은 자료라도 모양이 달라진다. 애니라벨은 데이터 8열을 쓴다 — 37번 칸 EMF에서
            // 한 행이 막대 49개(= 시작 4 + 코드워드 10묶음 × 4 + 정지 5)이고 가로가 205모듈
            // (= 17 × (8+4) + 1), 행 윗변이 0·100·200·300·400이라 5행이다.
            //
            // 실측 1행의 왼쪽 행표시자는 오류정정 2단계를 가리키지만, ZXing의 텍스트 압축은
            // 애니라벨보다 코드워드를 하나 더 써서 2단계로 맞추면 6행이 되어 모양이 어긋난다.
            // 1단계로 두면 5행 × 8열이 그대로 나오고 읽히는 값도 같다.
            // 열만 최소 8로 묶고 행은 풀어두어 자료가 길어져도 인코딩이 실패하지 않게 한다.
            //
            // 신형 0x1C는 BMP가 함께 저장되어 열·행을 직접 잴 수 있다. 그때는 실측값을 그대로 쓴다.
            if (TryParseGrid(obj.QrKind, out var pdfCols, out var pdfRows))
            {
                opt.Dimensions = new Dimensions(pdfCols, pdfCols, pdfRows, pdfRows);
                opt.ErrorCorrection = FitPdf417Ecc(value, pdfCols, pdfRows);
                return opt;
            }
            opt.ErrorCorrection = PDF417ErrorCorrectionLevel.L1;
            opt.Dimensions = new Dimensions(8, 30, 3, 90);
            return opt;
        }
        if (vendor != BarcodeVendorKind.ILabel)
            return opt;

        // 아이라벨 실측(12345)은 ZXing 기본과 같은 2열×6행(103모듈)이다. 열·행을 강제하지 않는다.
        opt.ErrorCorrection = ParsePdf417Ecc(obj.QrEcc);
        var minCols = ParsePdf417MinColumns(obj.QrKind);
        if (minCols > 0)
            opt.Dimensions = new Dimensions(minCols, Math.Max(minCols, 30), 3, 90);
        // 아이라벨 표준 PDF417은 숫자만 있는 자료를 늘 숫자 압축(902 잠금)으로 넣는다. ZXing 자동은
        // 13자리 미만이면 텍스트 압축을 골라 크기는 같아도 가운데 코드워드가 달라진다.
        // 5페이지 6번(123456789) 실측 코드워드 902·1·486·885·289와 비트까지 일치한다.
        // Micro PDF417은 반대로 텍스트 압축을 쓰며 MicroPdf417Encoder가 따로 처리한다.
        if (value.Length > 0 && value.All(char.IsAsciiDigit))
            opt.Compaction = Compaction.NUMERIC;
        return opt;
    }

    private static readonly Dictionary<string, PDF417ErrorCorrectionLevel> Pdf417EccFit = new(StringComparer.Ordinal);

    /// <summary>
    /// 열·행을 못 박은 채 들어갈 수 있는 가장 높은 오류정정 수준을 고른다.
    /// 수준을 올릴수록 검사 코드워드가 배로 늘어나니, 못 박은 칸에 딱 차는 수준은 사실상 하나다.
    /// 메움 코드워드로 남는 칸을 채우는 낮은 수준보다 이쪽이 원본과 맞는다.
    /// 한 번 고른 값은 같은 값·크기에 대해 다시 쓰도록 담아 둔다.
    /// </summary>
    private static PDF417ErrorCorrectionLevel FitPdf417Ecc(string value, int cols, int rows)
    {
        var key = $"{cols}x{rows}:{value}";
        if (Pdf417EccFit.TryGetValue(key, out var cached)) return cached;

        var picked = PDF417ErrorCorrectionLevel.L2;
        for (var level = 8; level >= 0; level--)
        {
            var probe = new PDF417EncodingOptions
            {
                Width = 0,
                Height = 0,
                Margin = 0,
                PureBarcode = true,
                Dimensions = new Dimensions(cols, cols, rows, rows),
                ErrorCorrection = (PDF417ErrorCorrectionLevel)level
            };
            try
            {
                if (new BarcodeWriterGeneric { Format = BarcodeFormat.PDF_417, Options = probe }.Encode(value) is null)
                    continue;
                picked = (PDF417ErrorCorrectionLevel)level;
                break;
            }
            catch
            {
                // 이 수준은 못 박은 칸에 안 들어간다. 한 단계 낮춰 본다.
            }
        }

        Pdf417EccFit[key] = picked;
        return picked;
    }

    private static PDF417ErrorCorrectionLevel ParsePdf417Ecc(string? raw)
    {
        var key = (raw ?? "").Trim();
        if (int.TryParse(key, NumberStyles.Integer, CultureInfo.InvariantCulture, out var n) && n is >= 0 and <= 8)
            return (PDF417ErrorCorrectionLevel)n;
        return PDF417ErrorCorrectionLevel.L2;
    }

    private static int ParsePdf417MinColumns(string? raw)
    {
        var key = (raw ?? "").Trim();
        if (key.StartsWith("COL:", StringComparison.OrdinalIgnoreCase)
            && int.TryParse(key[4..], NumberStyles.Integer, CultureInfo.InvariantCulture, out var n))
            return n;
        return 0;
    }

    /// <summary>
    /// ZXing PDF417 기본 AspectRatio=4는 논리 행을 4번 복제한다. 아이라벨은 논리 행만 그린다.
    /// </summary>
    private static BitMatrix SqueezeIdenticalRows(BitMatrix src)
    {
        if (src.Height < 2)
            return src;
        var keep = new List<int>(src.Height);
        var prev = new bool[src.Width];
        var hasPrev = false;
        for (var y = 0; y < src.Height; y++)
        {
            var same = hasPrev;
            for (var x = 0; x < src.Width && same; x++)
                same = prev[x] == src[x, y];
            if (!same)
            {
                keep.Add(y);
                for (var x = 0; x < src.Width; x++)
                    prev[x] = src[x, y];
                hasPrev = true;
            }
        }
        if (keep.Count == src.Height)
            return src;
        var dst = new BitMatrix(src.Width, keep.Count);
        for (var i = 0; i < keep.Count; i++)
        {
            var y = keep[i];
            for (var x = 0; x < src.Width; x++)
            {
                if (src[x, y])
                    dst[x, i] = true;
            }
        }
        return dst;
    }

    /// <summary>
    /// 폼텍·직접 추가는 정사각 최소 크기. 아이라벨만 Size/Compaction을 ZXing 옵션으로 넘긴다.
    /// </summary>
    private static DatamatrixEncodingOptions MakeDataMatrixOptions(DesignObject obj)
    {
        var opt = new DatamatrixEncodingOptions
        {
            Width = 0,
            Height = 0,
            Margin = 0,
            PureBarcode = true,
            SymbolShape = SymbolShapeHint.FORCE_SQUARE
        };
        var kind = Barcode1DEncoders.VendorOf(obj);
        if (kind == BarcodeVendorKind.AniLabel)
        {
            // 애니라벨 Data Matrix는 텍스트 인코딩으로 넣는다.
            // 심볼 크기는 파일에 없어 가져오기 때 BMP에서 재어 `20x20` 꼴로 넣어 둔다.
            // 「QR코드 타입.lbl」 5번 칸이 20×20 400모듈 전부 일치한다.
            opt.DefaultEncodation = Encodation.TEXT;
            if (TryParseGrid(obj.QrKind, out var dmCols, out var dmRows))
                ILabelDataMatrix.ApplySymbolSize(opt, dmCols, dmRows);
            return opt;
        }
        if (kind != BarcodeVendorKind.ILabel)
            return opt;

        var size = (obj.QrKind ?? "").Trim().ToUpperInvariant();
        var enc = (obj.QrEcc ?? "").Trim().ToUpperInvariant();
        ILabelDataMatrix.ApplyEncodation(opt, enc);

        if (size is "AUTO" or "AUTOSQ")
        {
            if (ILabelDataMatrix.TryPickAutoSize(obj.BarcodeValue, enc, squareOnly: size is "AUTOSQ", out var cols, out var rows))
                ILabelDataMatrix.ApplySymbolSize(opt, cols, rows);
            else
                opt.SymbolShape = size is "AUTOSQ" ? SymbolShapeHint.FORCE_SQUARE : SymbolShapeHint.FORCE_NONE;
        }
        else if (size.Contains('X')
                 && int.TryParse(size.Split('X')[0], NumberStyles.Integer, CultureInfo.InvariantCulture, out var w)
                 && int.TryParse(size.Split('X')[1], NumberStyles.Integer, CultureInfo.InvariantCulture, out var h)
                 && w > 0 && h > 0)
            ILabelDataMatrix.ApplySymbolSize(opt, w, h);
        return opt;
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

    private static void DrawEanModules(
        SKCanvas canvas, bool[] modules, int quiet, float width, float barH, SKColor color,
        float originX = 0, float guardExtraH = 0, bool retailGuards = false,
        RetailGuardKind guardKind = RetailGuardKind.Ean13, int edgeGuardRuns = 0,
        int trailGuardRuns = -1, int quietRight = -1)
    {
        if (trailGuardRuns < 0) trailGuardRuns = edgeGuardRuns;
        if (quietRight < 0) quietRight = quiet;
        var total = modules.Length + quiet + quietRight;
        var cellW = width / Math.Max(1, total);
        using var paint = new SKPaint
        {
            Color = color,
            IsAntialias = false,
            Style = SKPaintStyle.Fill
        };
        var runs = (edgeGuardRuns > 0 || trailGuardRuns > 0) && guardExtraH > 0 ? CountBarRuns(modules) : 0;
        var run = 0;
        var i = 0;
        var x = originX + quiet * cellW;
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
            var edge = runs > 0 && (run < edgeGuardRuns || run >= runs - trailGuardRuns);
            var guard = edge || (retailGuards && guardExtraH > 0 && RunTouchesRetailGuard(i, n, guardKind));
            var h = guard ? barH + guardExtraH : barH;
            canvas.DrawRect(x, 0, n * cellW, h, paint);
            x += n * cellW;
            i += n;
            run++;
        }
    }

    private static int CountBarRuns(bool[] modules)
    {
        var runs = 0;
        for (var i = 0; i < modules.Length; i++)
        {
            if (!modules[i] || (i > 0 && modules[i - 1])) continue;
            runs++;
        }
        return runs;
    }

    private enum RetailGuardKind { Ean13, Ean8, UpcE }

    /// <summary>
    /// EAN-13 가드: 0–2 시작, 45–49 가운데, 92–94 끝. EAN-8: 0–2 / 31–35 / 64–66.
    /// UPC-E는 가운데 가드가 없고 0–2 시작, 45–50 끝뿐이다.
    /// </summary>
    private static bool RunTouchesRetailGuard(int start, int count, RetailGuardKind kind)
    {
        for (var i = start; i < start + count; i++)
        {
            var hit = kind switch
            {
                RetailGuardKind.Ean8 => i is >= 0 and < 3 or >= 31 and < 36 or >= 64 and < 67,
                RetailGuardKind.UpcE => i is >= 0 and < 3 or >= 45 and < 51,
                _ => i is >= 0 and < 3 or >= 45 and < 50 or >= 92 and < 95
            };
            if (hit) return true;
        }
        return false;
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

    private static bool[]? EncodeEan8Modules(string digits)
    {
        digits = EnsureEan8(digits);
        if (digits.Length != 8) return null;
        foreach (var ch in digits)
            if (!char.IsAsciiDigit(ch)) return null;

        var bits = new bool[67];
        var i = 0;
        WriteModules(bits, ref i, "101");
        for (var d = 0; d < 4; d++)
            WriteModules(bits, ref i, Ean7(digits[d] - '0', 'L'));
        WriteModules(bits, ref i, "01010");
        for (var d = 0; d < 4; d++)
            WriteModules(bits, ref i, Ean7(digits[d + 4] - '0', 'R'));
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
