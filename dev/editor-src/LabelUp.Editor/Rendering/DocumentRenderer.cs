using LabelUp.Editor.Models;
using LabelUp.Editor.Services;
using SkiaSharp;

namespace LabelUp.Editor.Rendering;

public static class ColorUtil
{
    public static bool IsTransparent(string? hex)
        => string.IsNullOrWhiteSpace(hex) || hex is "transparent" or "none";

    /// <summary>HTML &lt;input type="color"&gt;는 #rrggbb 소문자만 유효하다.</summary>
    public static string ToHtmlColor(string? hex, string fallback = "#000000")
    {
        if (IsTransparent(hex)) return fallback;
        var s = hex!.Trim();
        if (s.StartsWith('#')) s = s[1..];
        if (s.Length == 8) s = s[2..];
        if (s.Length != 6) return fallback;
        foreach (var ch in s)
        {
            if (!char.IsAsciiHexDigit(ch)) return fallback;
        }
        return "#" + s.ToLowerInvariant();
    }

    public static SKColor Parse(string? hex, byte alpha = 255)
    {
        if (IsTransparent(hex))
            return SKColors.Transparent;

        hex = hex!.Trim();
        if (hex.StartsWith('#')) hex = hex[1..];
        try
        {
            if (hex.Length == 6)
            {
                var r = Convert.ToByte(hex[..2], 16);
                var g = Convert.ToByte(hex[2..4], 16);
                var b = Convert.ToByte(hex[4..6], 16);
                return new SKColor(r, g, b, alpha);
            }
            if (hex.Length == 8)
            {
                var a = Convert.ToByte(hex[..2], 16);
                var r = Convert.ToByte(hex[2..4], 16);
                var g = Convert.ToByte(hex[4..6], 16);
                var b = Convert.ToByte(hex[6..8], 16);
                return new SKColor(r, g, b, a);
            }
        }
        catch
        {
            // fall through
        }
        return new SKColor(0x7B, 0x28, 0x40, alpha);
    }
}

public static class DocumentRenderer
{
    private static readonly Dictionary<string, SKBitmap> ImageCache = new();
    private static FontCatalog? Fonts;
    private static FontAwesomeCatalog? Icons;

    public static void SetFontCatalog(FontCatalog? catalog) => Fonts = catalog;
    public static void SetIconCatalog(FontAwesomeCatalog? catalog) => Icons = catalog;
    public static void ClearImageCache()
    {
        foreach (var bmp in ImageCache.Values)
            bmp.Dispose();
        ImageCache.Clear();
    }

    public static SKTypeface ResolveTypeface(bool bold = false, int codepoint = 0)
        => ResolveTypeface(null, bold, codepoint);

    public static SKTypeface ResolveTypeface(string? family, bool bold = false, int codepoint = 0)
        => ResolveTypeface(family, bold, italic: false, codepoint);

    public static SKTypeface ResolveTypeface(string? family, bool bold, bool italic, int codepoint = 0)
    {
        if (Fonts is not null) return Fonts.Resolve(family, bold, codepoint, italic);
        return SKTypeface.Default;
    }

    public static float HriWidthScale(string? family)
        => Fonts?.WidthScaleFor(family) ?? FontCatalog.WidthScale(family);

    public static bool HasItalicFace(string? family)
        => Fonts?.HasItalicFace(family) == true;

    public static SKBitmap? GetBitmap(DesignObject obj)
    {
        if (string.IsNullOrEmpty(obj.ImageData)) return null;
        var key = RasterImage.CacheKey(obj.ImageData);
        if (ImageCache.TryGetValue(key, out var cached) && cached != null) return cached;
        try
        {
            var data = obj.ImageData!;
            var comma = data.IndexOf(',');
            if (comma >= 0) data = data[(comma + 1)..];
            var bytes = Convert.FromBase64String(data);
            var bmp = RasterImage.Decode(bytes);
            if (bmp != null) ImageCache[key] = bmp;
            return bmp;
        }
        catch
        {
            return null;
        }
    }

    public static void InvalidateImage(string id)
    {
        ClearImageCache();
    }

    public static void DrawCell(
        SKCanvas canvas,
        LabelDocument doc,
        LabelCell cell,
        Func<DesignObject, string>? resolve = null,
        bool forExport = false,
        Action? afterBackground = null,
        float? widthMm = null,
        float? heightMm = null)
    {
        var w = widthMm ?? doc.WidthMm;
        var h = heightMm ?? doc.HeightMm;
        using var clip = CreateLabelPath(doc.Paper.Shape, w, h);
        canvas.Save();
        canvas.ClipPath(clip, SKClipOperation.Intersect, antialias: true);

        using var bg = new SKPaint { Color = ColorUtil.Parse(doc.Background), IsAntialias = true, Style = SKPaintStyle.Fill };
        canvas.DrawRect(0, 0, w, h, bg);

        if (!forExport)
        {
            afterBackground?.Invoke();
            using var border = new SKPaint
            {
                Color = new SKColor(0xE5, 0xDD, 0xD3),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = 0.15f
            };
            canvas.DrawPath(clip, border);
        }

        DrawGuides(canvas, doc.Paper.Shape, w, h);

        foreach (var obj in cell.OrderedObjects())
            DrawObject(canvas, obj, resolve);

        canvas.Restore();
    }

    /// <summary>Backward-compatible: draw current document cell 0.</summary>
    public static void DrawDocument(SKCanvas canvas, LabelDocument doc, bool forExport = false)
    {
        doc.EnsureStructure();
        DrawCell(canvas, doc, doc.Pages[0].Cells[0], null, forExport);
    }

    public static SKPath CreateLabelPath(PaperShape shape, float w, float h)
    {
        var path = new SKPath();
        switch (shape.Kind)
        {
            case "ellipse" or "circle":
                path.AddOval(new SKRect(0, 0, w, h));
                break;
            case "roundrect":
                path.AddRoundRect(new SKRoundRect(new SKRect(0, 0, w, h), shape.CornerRadiusMm, shape.CornerRadiusMm));
                break;
            case "svg" when !string.IsNullOrWhiteSpace(shape.Svg):
                using (var parsed = SvgPathParser.Parse(ExtractPath(shape.Svg!), w, h, fitToBounds: !shape.SvgIsLabelMm))
                    path.AddPath(parsed);
                if (path.IsEmpty) path.AddRect(new SKRect(0, 0, w, h));
                break;
            default:
                path.AddRect(new SKRect(0, 0, w, h));
                break;
        }

        if (shape.Hole is { Width: > 0, Height: > 0 } hole)
        {
            path.FillType = SKPathFillType.EvenOdd;
            var r = Math.Min(hole.Width, hole.Height) * 0.12f;
            path.AddRoundRect(new SKRoundRect(new SKRect(hole.X, hole.Y, hole.X + hole.Width, hole.Y + hole.Height), r, r));
        }

        if (shape.Guides is { Count: > 0 })
        {
            var fit = !shape.SvgIsLabelMm;
            foreach (var g in shape.Guides)
            {
                if (!g.IsHole || string.IsNullOrWhiteSpace(g.D)) continue;
                path.FillType = SKPathFillType.EvenOdd;
                using var holePath = SvgPathParser.Parse(g.D, w, h, fitToBounds: fit);
                path.AddPath(holePath);
            }
        }

        return path;
    }

    private static void DrawGuides(SKCanvas canvas, PaperShape shape, float w, float h)
    {
        var fit = !shape.SvgIsLabelMm;
        if (shape.Guides is { Count: > 0 })
        {
            foreach (var g in shape.Guides)
            {
                if (g.IsHole || string.IsNullOrWhiteSpace(g.D)) continue;
                using var path = SvgPathParser.Parse(g.D, w, h, fitToBounds: fit);
                path.FillType = g.EvenOdd ? SKPathFillType.EvenOdd : SKPathFillType.Winding;
                if (!string.IsNullOrWhiteSpace(g.Fill))
                {
                    using var fill = new SKPaint
                    {
                        Color = ColorUtil.Parse(g.Fill),
                        IsAntialias = true,
                        Style = SKPaintStyle.Fill
                    };
                    canvas.DrawPath(path, fill);
                }

                if (!string.IsNullOrWhiteSpace(g.Stroke))
                {
                    using var stroke = new SKPaint
                    {
                        Color = ColorUtil.Parse(g.Stroke),
                        IsAntialias = true,
                        Style = SKPaintStyle.Stroke,
                        StrokeWidth = g.StrokeWidthMm > 0 ? g.StrokeWidthMm : 0.28f,
                        StrokeJoin = SKStrokeJoin.Round,
                        StrokeCap = SKStrokeCap.Round
                    };
                    canvas.DrawPath(path, stroke);
                }
            }
            return;
        }

        if (string.IsNullOrWhiteSpace(shape.GuideSvg)) return;
        using var guide = SvgPathParser.Parse(ExtractPath(shape.GuideSvg!), w, h, fitToBounds: fit);
        using var guidePaint = new SKPaint
        {
            Color = new SKColor(0x2E, 0x2A, 0x27),
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = 0.28f,
            StrokeJoin = SKStrokeJoin.Round,
            StrokeCap = SKStrokeCap.Round
        };
        canvas.DrawPath(guide, guidePaint);
    }

    private static string ExtractPath(string svg)
    {
        var d = svg.IndexOf(" d=\"", StringComparison.OrdinalIgnoreCase);
        if (d >= 0)
        {
            var start = d + 4;
            var end = svg.IndexOf('"', start);
            if (end > start) return svg[start..end];
        }
        return svg;
    }

    public static void DrawObject(SKCanvas canvas, DesignObject obj, Func<DesignObject, string>? resolve = null)
    {
        if (!obj.Visible) return;
        canvas.Save();
        try
        {
            canvas.Translate(obj.X + obj.Width / 2f, obj.Y + obj.Height / 2f);
            if (Math.Abs(obj.Rotation) > 0.01f)
                canvas.RotateDegrees(obj.Rotation);
            canvas.Translate(-obj.Width / 2f, -obj.Height / 2f);

            var alpha = (byte)Math.Clamp((int)(obj.Opacity * 255), 0, 255);
            var text = resolve?.Invoke(obj) ?? obj.Text;

            switch (obj.Type)
            {
                case ObjectType.Rect:
                case ObjectType.Ellipse:
                case ObjectType.Line:
                case ObjectType.Shape:
                    DrawShape(canvas, obj, alpha);
                    break;
                case ObjectType.Text:
                    DrawText(canvas, obj, text, alpha);
                    break;
                case ObjectType.Image:
                    DrawImage(canvas, obj, alpha);
                    break;
                case ObjectType.Barcode:
                case ObjectType.Qr:
                    BarcodeRenderer.Draw(canvas, obj, resolve?.Invoke(obj) ?? obj.BarcodeValue, alpha);
                    break;
                case ObjectType.Table:
                    DrawTable(canvas, obj, alpha);
                    break;
                case ObjectType.Clipart:
                case ObjectType.Icon:
                    if (obj.SvgParts is { Count: > 0 })
                        DrawSvgParts(canvas, obj, alpha);
                    else if (obj.Type == ObjectType.Clipart && IsRasterImageData(obj.ImageData))
                        DrawImage(canvas, obj, alpha);
                    else
                        DrawSvgShape(canvas, obj, alpha);
                    break;
                case ObjectType.Gradient:
                    DrawGradient(canvas, obj, alpha);
                    break;
            }
        }
        catch (Exception ex)
        {
            EditorLog.Error($"객체 그리기 실패: {obj.Type}", ex);
        }
        finally
        {
            canvas.Restore();
        }
    }

    private static ShapeKind ResolveShape(DesignObject obj) => obj.Type switch
    {
        ObjectType.Ellipse => ShapeKind.Ellipse,
        ObjectType.Line => ShapeKind.Line,
        ObjectType.Shape => obj.ShapeKind,
        _ => obj.ShapeKind
    };

    private static void DrawGradient(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        var rect = new SKRect(0, 0, Math.Max(0.2f, obj.Width), Math.Max(0.2f, obj.Height));
        var start = ColorUtil.Parse(obj.Fill, alpha);
        var end = ColorUtil.Parse(string.IsNullOrWhiteSpace(obj.GradientEnd) ? "#FFFFFF" : obj.GradientEnd, alpha);
        var (a, b) = obj.GradientDirection switch
        {
            1 => (new SKPoint(rect.Right, rect.MidY), new SKPoint(rect.Left, rect.MidY)),
            2 => (new SKPoint(rect.MidX, rect.Top), new SKPoint(rect.MidX, rect.Bottom)),
            3 => (new SKPoint(rect.MidX, rect.Bottom), new SKPoint(rect.MidX, rect.Top)),
            _ => (new SKPoint(rect.Left, rect.MidY), new SKPoint(rect.Right, rect.MidY))
        };

        var steps = Math.Clamp(obj.GradientPrecision, 2, 100);
        var colors = new SKColor[steps];
        var pos = new float[steps];
        for (var i = 0; i < steps; i++)
        {
            var t = i / (float)(steps - 1);
            colors[i] = LerpColor(start, end, t);
            pos[i] = t;
        }

        using var fill = new SKPaint { IsAntialias = true, Style = SKPaintStyle.Fill };
        using var shader = SKShader.CreateLinearGradient(a, b, colors, pos, SKShaderTileMode.Clamp);
        fill.Shader = shader;
        canvas.DrawRect(rect, fill);

        var frameW = obj.StrokeWidth < 0 ? 0.2f : obj.StrokeWidth;
        if (frameW > 0.01f && !ColorUtil.IsTransparent(obj.Stroke))
        {
            using var stroke = new SKPaint
            {
                Color = ColorUtil.Parse(obj.Stroke, alpha),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = frameW,
                StrokeJoin = SKStrokeJoin.Miter
            };
            var inset = frameW / 2f;
            canvas.DrawRect(inset, inset, rect.Width - frameW, rect.Height - frameW, stroke);
        }
    }

    private static SKColor LerpColor(SKColor a, SKColor b, float t)
    {
        t = Math.Clamp(t, 0, 1);
        byte Mix(byte x, byte y) => (byte)Math.Clamp((int)(x + (y - x) * t), 0, 255);
        return new SKColor(Mix(a.Red, b.Red), Mix(a.Green, b.Green), Mix(a.Blue, b.Blue), Mix(a.Alpha, b.Alpha));
    }

    private static void DrawShape(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        var kind = ResolveShape(obj);
        var sw = Math.Max(0, obj.StrokeWidth);
        var inside = kind is not (ShapeKind.Line or ShapeKind.Arrow);
        var strokeRect = InsideStrokeRect(obj.Width, obj.Height, inside ? sw : 0);
        using var fill = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true, Style = SKPaintStyle.Fill };
        using var stroke = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Stroke, alpha),
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = Math.Max(sw, kind is ShapeKind.Line or ShapeKind.Arrow ? 0.35f : 0),
            StrokeCap = SKStrokeCap.Round,
            StrokeJoin = SKStrokeJoin.Miter
        };

        switch (kind)
        {
            case ShapeKind.Ellipse:
            case ShapeKind.Circle:
                canvas.DrawOval(strokeRect, fill);
                if (sw > 0) canvas.DrawOval(strokeRect, stroke);
                break;
            case ShapeKind.RoundRect:
                var radius = Math.Max(0.1f, obj.CornerRadiusMm - (inside ? sw / 2f : 0));
                var rr = new SKRoundRect(strokeRect, radius, radius);
                canvas.DrawRoundRect(rr, fill);
                if (sw > 0) canvas.DrawRoundRect(rr, stroke);
                break;
            case ShapeKind.Triangle:
                using (var path = TrianglePath(strokeRect.Width, strokeRect.Height))
                {
                    path.Transform(SKMatrix.CreateTranslation(strokeRect.Left, strokeRect.Top));
                    canvas.DrawPath(path, fill);
                    if (sw > 0) canvas.DrawPath(path, stroke);
                }
                break;
            case ShapeKind.Polygon:
                using (var path = PolygonPath(strokeRect.Width, strokeRect.Height, obj.PolygonSides))
                {
                    path.Transform(SKMatrix.CreateTranslation(strokeRect.Left, strokeRect.Top));
                    canvas.DrawPath(path, fill);
                    if (sw > 0) canvas.DrawPath(path, stroke);
                }
                break;
            case ShapeKind.Line:
                canvas.DrawLine(0, obj.Height / 2f, obj.Width, obj.Height / 2f, stroke);
                break;
            case ShapeKind.Arrow:
                DrawArrow(canvas, obj, fill, stroke);
                break;
            default:
                canvas.DrawRect(strokeRect, fill);
                if (sw > 0) canvas.DrawRect(strokeRect, stroke);
                break;
        }
    }

    /// <summary>GDI PS_INSIDEFRAME처럼 선 두께가 객체 박스 안쪽으로만 커진다.</summary>
    private static SKRect InsideStrokeRect(float w, float h, float strokeWidth)
    {
        var half = Math.Max(0, strokeWidth) / 2f;
        var rw = Math.Max(0.05f, w - strokeWidth);
        var rh = Math.Max(0.05f, h - strokeWidth);
        return new SKRect(half, half, half + rw, half + rh);
    }

    private static SKPath TrianglePath(float w, float h)
    {
        var path = new SKPath();
        path.MoveTo(w / 2f, 0);
        path.LineTo(w, h);
        path.LineTo(0, h);
        path.Close();
        return path;
    }

    private static SKPath PolygonPath(float w, float h, int sides)
    {
        sides = Math.Clamp(sides, 3, 16);
        var path = new SKPath();
        var cx = w / 2f;
        var cy = h / 2f;
        var rx = w / 2f;
        var ry = h / 2f;
        for (var i = 0; i < sides; i++)
        {
            var a = -MathF.PI / 2f + i * (MathF.PI * 2f / sides);
            var x = cx + rx * MathF.Cos(a);
            var y = cy + ry * MathF.Sin(a);
            if (i == 0) path.MoveTo(x, y);
            else path.LineTo(x, y);
        }
        path.Close();
        return path;
    }

    private static void DrawArrow(SKCanvas canvas, DesignObject obj, SKPaint fill, SKPaint stroke)
    {
        var y = obj.Height / 2f;
        var head = Math.Min(obj.Width * 0.22f, obj.Height);
        var start = obj.ArrowHeads is ArrowHeads.Start or ArrowHeads.Both;
        var end = obj.ArrowHeads is ArrowHeads.End or ArrowHeads.Both;
        var x1 = start ? head : 0;
        var x2 = end ? obj.Width - head : obj.Width;
        canvas.DrawLine(x1, y, x2, y, stroke);
        if (end) DrawArrowHead(canvas, obj.Width, y, -1, head, fill, stroke);
        if (start) DrawArrowHead(canvas, 0, y, 1, head, fill, stroke);
    }

    private static void DrawArrowHead(SKCanvas canvas, float tipX, float y, int dir, float size, SKPaint fill, SKPaint stroke)
    {
        using var path = new SKPath();
        path.MoveTo(tipX, y);
        path.LineTo(tipX + dir * size, y - size * 0.55f);
        path.LineTo(tipX + dir * size, y + size * 0.55f);
        path.Close();
        canvas.DrawPath(path, fill);
        canvas.DrawPath(path, stroke);
    }

    private static void DrawText(SKCanvas canvas, DesignObject obj, string text, byte alpha)
    {
        if (!obj.BackgroundTransparent && !string.IsNullOrWhiteSpace(obj.BackgroundFill))
        {
            using var bg = new SKPaint { Color = ColorUtil.Parse(obj.BackgroundFill, alpha), IsAntialias = true };
            canvas.DrawRect(0, 0, obj.Width, obj.Height, bg);
        }

        text = string.IsNullOrEmpty(text) ? " " : StripInvisibleFormat(text);
        if (string.IsNullOrEmpty(text)) text = " ";
        canvas.Save();
        if (obj.FlipHorizontal)
        {
            canvas.Translate(obj.Width, 0);
            canvas.Scale(-1, 1);
        }

        if (obj.Italic && !HasItalicFace(obj.FontFamily))
            canvas.Skew(-0.25f, 0);

        var style = obj.TextMode == TextMode.WordArt ? obj.WordArtStyle : WordArtStyle.None;
        if (style is WordArtStyle.ArcUp or WordArtStyle.ArcDown or WordArtStyle.Circle or WordArtStyle.Wave or WordArtStyle.Rounded)
        {
            DrawWordArt(canvas, obj, text, alpha, style == WordArtStyle.Rounded ? WordArtStyle.ArcUp : style);
            canvas.Restore();
            return;
        }

        using var paint = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true };
        using var font = new SKFont(ResolveTypeface(obj.FontFamily, obj.Bold, obj.Italic), obj.FontSize);

        if (obj.TextDirection == "vertical")
        {
            DrawVerticalText(canvas, obj, text, font, paint, alpha);
            canvas.Restore();
            return;
        }

        if (style == WordArtStyle.Stretch)
        {
            DrawStretchedText(canvas, obj, text, font, paint, alpha);
            canvas.Restore();
            return;
        }

        // 폼텍은 박스 폭으로 줄바꿈한다. 좌우 1mm씩 빼면 세로 박스(7.1mm·10pt)에
        // '세로' 두 글자가 안 들어가 한 글자씩 쌓이고 위가 잘린다.
        var inset = Math.Clamp(obj.Width * 0.015f, 0.08f, 0.35f);
        var maxW = Math.Max(0.3f, obj.Width);
        var lines = WrapText(text, font, obj.FontFamily, obj.Bold, maxW, obj.TextWrap);
        var lineH = obj.FontSize * Math.Max(0.62f, obj.LineHeight);
        var totalH = lineH * lines.Count;
        float startY = obj.VerticalAlign switch
        {
            "top" => lineH,
            "bottom" => obj.Height - (lines.Count - 1) * lineH - 0.4f,
            _ => (obj.Height - totalH) / 2f + lineH * 0.78f
        };

        canvas.Save();
        canvas.ClipRect(new SKRect(0, 0, obj.Width, obj.Height));
        for (var i = 0; i < lines.Count; i++)
        {
            var line = lines[i];
            var tw = MeasureLine(font, obj.FontFamily, obj.Bold, line);
            float x = obj.TextAlign switch
            {
                "left" => inset,
                "right" => obj.Width - tw - inset,
                _ => (obj.Width - tw) / 2f
            };
            var y = startY + i * lineH;
            DrawGlyph(canvas, obj, line, x, y, SKTextAlign.Left, font, paint, alpha);
            if (obj.Underline || obj.Strikeout)
            {
                using var lp = new SKPaint
                {
                    Color = ColorUtil.Parse(obj.Fill, alpha),
                    StrokeWidth = Math.Max(0.15f, obj.FontSize * 0.06f),
                    IsAntialias = true
                };
                if (obj.Underline) canvas.DrawLine(x, y + 0.4f, x + tw, y + 0.4f, lp);
                if (obj.Strikeout) canvas.DrawLine(x, y - obj.FontSize * 0.35f, x + tw, y - obj.FontSize * 0.35f, lp);
            }
        }
        canvas.Restore();
        canvas.Restore();
    }

    private static List<string> WrapText(string text, SKFont font, string? family, bool bold, float maxWidth, string? mode)
    {
        var hard = text.Replace("\r\n", "\n").Replace('\r', '\n').Split('\n');
        if (string.Equals(mode, "none", StringComparison.OrdinalIgnoreCase))
            return [.. hard];

        var word = string.Equals(mode, "word", StringComparison.OrdinalIgnoreCase);
        var lines = new List<string>();
        foreach (var para in hard)
        {
            if (para.Length == 0)
            {
                lines.Add("");
                continue;
            }

            var current = "";
            foreach (var ch in para)
            {
                var test = current + ch;
                if (MeasureLine(font, family, bold, test) <= maxWidth + 0.35f || current.Length == 0)
                {
                    current = test;
                    continue;
                }

                if (word)
                {
                    var cut = LastBreak(current);
                    if (cut > 0)
                    {
                        lines.Add(current[..cut].TrimEnd());
                        current = current[cut..].TrimStart() + ch;
                        continue;
                    }
                }

                lines.Add(current);
                current = ch.ToString();
            }

            if (current.Length > 0)
                lines.Add(current);
        }
        return lines.Count == 0 ? [""] : lines;
    }

    private static int LastBreak(string s)
    {
        for (var i = s.Length - 1; i > 0; i--)
        {
            if (char.IsWhiteSpace(s[i])) return i;
        }
        return -1;
    }

    private static void DrawStretchedText(SKCanvas canvas, DesignObject obj, string text, SKFont font, SKPaint paint, byte alpha)
    {
        var line = text.Replace("\r\n", " ").Replace('\n', ' ');
        var tw = Math.Max(0.2f, font.MeasureText(line));
        var scaleX = obj.Width / tw;
        var scaleY = obj.Height / Math.Max(0.4f, obj.FontSize * 1.15f);
        canvas.Save();
        canvas.Scale(scaleX, scaleY);
        DrawGlyph(canvas, obj, line, 0, obj.FontSize * 0.92f, SKTextAlign.Left, font, paint, alpha);
        canvas.Restore();
    }

    private static void DrawGlyph(SKCanvas canvas, DesignObject obj, string text, float x, float y, SKTextAlign align, SKFont font, SKPaint paint, byte alpha)
    {
        if (NeedsMixedGlyphs(font, text))
        {
            DrawMixedGlyphs(canvas, obj, text, x, y, align, font, paint, alpha);
            return;
        }

        if (obj.Shadow)
        {
            using var shade = new SKPaint { Color = new SKColor(0x40, 0x3A, 0x36, (byte)(alpha * 0.45f)), IsAntialias = true };
            canvas.DrawText(text, x + obj.FontSize * 0.12f, y + obj.FontSize * 0.12f, align, font, shade);
        }
        if (obj.Outline)
        {
            using var stroke = new SKPaint
            {
                Color = ColorUtil.Parse(obj.Fill, alpha),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = Math.Max(0.18f, obj.FontSize * 0.1f),
                StrokeJoin = SKStrokeJoin.Round
            };
            canvas.DrawText(text, x, y, align, font, stroke);
            return;
        }
        canvas.DrawText(text, x, y, align, font, paint);
    }

    private static string StripInvisibleFormat(string text)
    {
        var sb = new System.Text.StringBuilder(text.Length);
        foreach (var rune in text.EnumerateRunes())
        {
            if (IsInvisibleFormat(rune.Value)) continue;
            sb.Append(rune);
        }
        return sb.ToString();
    }

    private static bool IsInvisibleFormat(int code)
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

    private static bool NeedsMixedGlyphs(SKFont font, string text)
    {
        foreach (var rune in text.EnumerateRunes())
        {
            if (IsInvisibleFormat(rune.Value)) continue;
            if (FontCatalog.PrefersSymbolRange(rune.Value))
                return true;
        }
        return HasMissingGlyph(font, text);
    }

    private static bool HasMissingGlyph(SKFont font, string text)
    {
        try
        {
            var glyphs = font.GetGlyphs(text);
            foreach (var g in glyphs)
            {
                if (g == 0) return true;
            }
        }
        catch
        {
            return false;
        }
        return false;
    }

    private static float MeasureLine(SKFont font, string? family, bool bold, string text)
        => NeedsMixedGlyphs(font, text)
            ? MeasureMixed(font, family, bold, text)
            : font.MeasureText(text);

    private static float MeasureMixed(SKFont font, string? family, bool bold, string text)
    {
        var width = 0f;
        foreach (var rune in text.EnumerateRunes())
        {
            if (IsInvisibleFormat(rune.Value)) continue;
            var ch = rune.ToString();
            using (var face = new SKFont(ResolveTypeface(family, bold, rune.Value), font.Size))
                width += Math.Max(0.2f, face.MeasureText(ch));
        }
        return width;
    }

    private static void DrawMixedGlyphs(SKCanvas canvas, DesignObject obj, string text, float x, float y, SKTextAlign align, SKFont font, SKPaint paint, byte alpha)
    {
        var total = MeasureMixed(font, obj.FontFamily, obj.Bold, text);
        var cursor = align switch
        {
            SKTextAlign.Right => x - total,
            SKTextAlign.Center => x - total / 2f,
            _ => x
        };
        if (obj.Shadow)
        {
            using var shade = new SKPaint { Color = new SKColor(0x40, 0x3A, 0x36, (byte)(alpha * 0.45f)), IsAntialias = true };
            DrawMixedRun(canvas, obj.FontFamily, obj.Bold, text, cursor + obj.FontSize * 0.12f, y + obj.FontSize * 0.12f, font, shade);
        }
        if (obj.Outline)
        {
            using var stroke = new SKPaint
            {
                Color = ColorUtil.Parse(obj.Fill, alpha),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = Math.Max(0.18f, obj.FontSize * 0.1f),
                StrokeJoin = SKStrokeJoin.Round
            };
            DrawMixedRun(canvas, obj.FontFamily, obj.Bold, text, cursor, y, font, stroke);
            return;
        }
        DrawMixedRun(canvas, obj.FontFamily, obj.Bold, text, cursor, y, font, paint);
    }

    private static void DrawMixedRun(SKCanvas canvas, string? family, bool bold, string text, float x, float y, SKFont font, SKPaint paint)
    {
        foreach (var rune in text.EnumerateRunes())
        {
            if (IsInvisibleFormat(rune.Value)) continue;
            var ch = rune.ToString();
            using (var face = new SKFont(ResolveTypeface(family, bold, rune.Value), font.Size))
            {
                if (HasMissingGlyph(face, ch))
                {
                    DrawFallbackGlyph(canvas, rune.Value, x, y, font.Size, paint);
                    x += font.Size * 0.92f;
                    continue;
                }
                canvas.DrawText(ch, x, y, SKTextAlign.Left, face, paint);
                x += Math.Max(0.2f, face.MeasureText(ch));
            }
        }
    }

    /// <summary>상자 그리기·도형 특수문자는 Pretendard에 없어 선으로 그린다.</summary>
    private static void DrawFallbackGlyph(SKCanvas canvas, int code, float x, float y, float size, SKPaint paint)
    {
        var cell = size;
        var left = x;
        var right = left + cell;
        var bottom = y + size * 0.12f;
        var top = bottom - cell;
        var cx = (left + right) / 2f;
        var cy = (top + bottom) / 2f;
        var thick = Math.Max(0.18f, size * (IsHeavyBox(code) ? 0.14f : 0.08f));
        using var stroke = new SKPaint
        {
            Color = paint.Color,
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = thick,
            StrokeCap = SKStrokeCap.Square
        };

        if (code is >= 0x25A0 and <= 0x25FF)
        {
            DrawGeometricFallback(canvas, code, left, top, right, bottom, paint, stroke);
            return;
        }

        if (!TryBoxArms(code, out var n, out var s, out var e, out var w))
        {
            canvas.DrawRect(left + cell * 0.15f, top + cell * 0.15f, cell * 0.7f, cell * 0.7f, stroke);
            return;
        }

        if (n) canvas.DrawLine(cx, cy, cx, top, stroke);
        if (s) canvas.DrawLine(cx, cy, cx, bottom, stroke);
        if (e) canvas.DrawLine(cx, cy, right, cy, stroke);
        if (w) canvas.DrawLine(cx, cy, left, cy, stroke);
    }

    private static bool IsHeavyBox(int code)
        => code is 0x2501 or 0x2503 or 0x250F or 0x2513 or 0x2517 or 0x251B
            or 0x2523 or 0x252B or 0x2533 or 0x253B or 0x254B;

    private static bool TryBoxArms(int code, out bool n, out bool s, out bool e, out bool w)
    {
        n = s = e = w = false;
        switch (code)
        {
            case 0x2500 or 0x2501: e = w = true; return true;
            case 0x2502 or 0x2503: n = s = true; return true;
            case 0x250C or 0x250F: s = e = true; return true;
            case 0x2510 or 0x2513: s = w = true; return true;
            case 0x2514 or 0x2517: n = e = true; return true;
            case 0x2518 or 0x251B: n = w = true; return true;
            case 0x251C or 0x2523: n = s = e = true; return true;
            case 0x2524 or 0x252B: n = s = w = true; return true;
            case 0x252C or 0x2533: s = e = w = true; return true;
            case 0x2534 or 0x253B: n = e = w = true; return true;
            case 0x253C or 0x254B: n = s = e = w = true; return true;
            default: return false;
        }
    }

    private static void DrawGeometricFallback(SKCanvas canvas, int code, float left, float top, float right, float bottom, SKPaint fill, SKPaint stroke)
    {
        var w = right - left;
        var h = bottom - top;
        switch (code)
        {
            case 0x25A0:
                canvas.DrawRect(left, top, w, h, fill);
                break;
            case 0x25A1:
                canvas.DrawRect(left, top, w, h, stroke);
                break;
            case 0x25A3:
                canvas.DrawRect(left, top, w, h, stroke);
                canvas.DrawRect(left + w * 0.22f, top + h * 0.22f, w * 0.56f, h * 0.56f, fill);
                break;
            default:
                canvas.DrawRect(left + w * 0.12f, top + h * 0.12f, w * 0.76f, h * 0.76f, stroke);
                break;
        }
    }

    private static void DrawVerticalText(SKCanvas canvas, DesignObject obj, string text, SKFont font, SKPaint paint, byte alpha)
    {
        var chars = text.Replace("\r\n", "").Replace("\n", "").ToCharArray();
        if (chars.Length == 0) return;
        var lineH = obj.FontSize * Math.Max(0.9f, obj.LineHeight);
        var colW = obj.FontSize * 1.15f;
        var rows = Math.Max(1, (int)Math.Floor(obj.Height / lineH));
        var cols = (int)Math.Ceiling(chars.Length / (double)rows);
        var totalW = cols * colW;
        var startX = obj.TextAlign switch
        {
            "left" => 0.4f,
            "right" => Math.Max(0.4f, obj.Width - totalW),
            _ => Math.Max(0, (obj.Width - totalW) / 2f)
        };
        var startY = obj.VerticalAlign switch
        {
            "top" => lineH,
            "bottom" => obj.Height - 0.4f,
            _ => (obj.Height + lineH * Math.Min(rows, chars.Length)) / 2f
        };

        for (var i = 0; i < chars.Length; i++)
        {
            var col = i / rows;
            var row = i % rows;
            var x = startX + (cols - 1 - col) * colW + colW * 0.5f;
            var y = obj.VerticalAlign == "top"
                ? lineH + row * lineH
                : startY - (Math.Min(rows, chars.Length) - 1 - row) * lineH;
            DrawGlyph(canvas, obj, chars[i].ToString(), x, y, SKTextAlign.Center, font, paint, alpha);
        }
    }

    private static void DrawWordArt(SKCanvas canvas, DesignObject obj, string text, byte alpha, WordArtStyle style)
    {
        using var paint = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true };
        using var font = new SKFont(ResolveTypeface(obj.FontFamily, obj.Bold, obj.Italic), obj.FontSize);
        var chars = text.Replace("\n", "").ToCharArray();
        if (chars.Length == 0) return;

        if (obj.WordArtGuide)
        {
            using var g = new SKPaint
            {
                Color = new SKColor(0xC4, 0xA5, 0x74, 140),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = 0.2f,
                PathEffect = SKPathEffect.CreateDash([1.2f, 1.2f], 0)
            };
            canvas.DrawOval(new SKRect(1, 1, obj.Width - 1, obj.Height - 1), g);
        }

        if (style == WordArtStyle.Wave)
        {
            var total = chars.Sum(ch => font.MeasureText(ch.ToString()) + obj.LetterSpacing);
            var x = Math.Max(0, (obj.Width - total) / 2f);
            for (var i = 0; i < chars.Length; i++)
            {
                var s = chars[i].ToString();
                var cw = font.MeasureText(s);
                var wave = MathF.Sin(i / (float)Math.Max(1, chars.Length - 1) * MathF.PI * 2f) * (obj.Height * 0.18f);
                DrawGlyph(canvas, obj, s, x, obj.Height * 0.62f + wave, SKTextAlign.Left, font, paint, alpha);
                x += cw + obj.LetterSpacing;
            }
            return;
        }

        var cx = obj.Width / 2f;
        var cy = obj.Height / 2f;
        var rx = obj.Width / 2f - obj.FontSize * 0.4f;
        var ry = obj.Height / 2f - obj.FontSize * 0.4f;
        float startDeg, sweep;
        if (style == WordArtStyle.Circle)
        {
            startDeg = -90;
            sweep = 360;
        }
        else if (style == WordArtStyle.ArcDown)
        {
            startDeg = 200;
            sweep = 140;
        }
        else
        {
            startDeg = -20 - obj.WordArtBend * 0.4f;
            sweep = 180 + obj.WordArtBend * 0.3f;
        }

        for (var i = 0; i < chars.Length; i++)
        {
            var t = chars.Length == 1 ? 0.5f : i / (float)(chars.Length - (style == WordArtStyle.Circle ? 0 : 1));
            if (style == WordArtStyle.Circle) t = i / (float)chars.Length;
            var deg = startDeg + sweep * t;
            var rad = deg * MathF.PI / 180f;
            var x = cx + rx * MathF.Cos(rad);
            var y = cy + ry * MathF.Sin(rad);
            canvas.Save();
            canvas.Translate(x, y);
            canvas.RotateDegrees(deg + 90);
            DrawGlyph(canvas, obj, chars[i].ToString(), 0, 0, SKTextAlign.Center, font, paint, alpha);
            canvas.Restore();
        }
    }

    private static void DrawImage(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        var bmp = GetBitmap(obj);
        var dest = new SKRect(0, 0, obj.Width, obj.Height);
        if (bmp != null)
        {
            using var paint = new SKPaint { IsAntialias = true, Color = SKColors.White.WithAlpha(alpha) };
            using var image = SKImage.FromBitmap(bmp);
            var src = new SKRect(0, 0, bmp.Width, bmp.Height);
            if (obj.ImageFit == "contain")
            {
                var scale = Math.Min(obj.Width / bmp.Width, obj.Height / bmp.Height);
                var dw = bmp.Width * scale;
                var dh = bmp.Height * scale;
                dest = new SKRect((obj.Width - dw) / 2f, (obj.Height - dh) / 2f, (obj.Width + dw) / 2f, (obj.Height + dh) / 2f);
            }
            else if (obj.ImageFit == "cover")
            {
                var scale = Math.Max(obj.Width / bmp.Width, obj.Height / bmp.Height);
                var sw = obj.Width / scale;
                var sh = obj.Height / scale;
                src = new SKRect((bmp.Width - sw) / 2f, (bmp.Height - sh) / 2f, (bmp.Width + sw) / 2f, (bmp.Height + sh) / 2f);
            }
            canvas.DrawImage(image, src, dest, new SKSamplingOptions(SKFilterMode.Linear, SKMipmapMode.Linear), paint);
        }
        else
        {
            using var fill = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true };
            canvas.DrawRoundRect(dest, 1.2f, 1.2f, fill);
            using var stroke = new SKPaint
            {
                Color = ColorUtil.Parse(obj.Stroke, alpha),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = 0.35f,
                PathEffect = SKPathEffect.CreateDash([1.2f, 1.2f], 0)
            };
            canvas.DrawRoundRect(dest, 1.2f, 1.2f, stroke);
        }
    }

    private static void DrawTable(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        obj.EnsureTableSize();
        var rows = obj.TableRows;
        var cols = obj.TableCols;
        var cw = obj.Width / cols;
        var rh = obj.Height / rows;
        var sw = Math.Max(0.12f, obj.TableBorderWidth);
        for (var r = 0; r < rows; r++)
        {
            for (var c = 0; c < cols; c++)
            {
                var fillCss = obj.TableCellFill(r, c);
                if (string.IsNullOrWhiteSpace(fillCss) || ColorUtil.IsTransparent(fillCss))
                    continue;
                using var fill = new SKPaint { Color = ColorUtil.Parse(fillCss, alpha), IsAntialias = true };
                canvas.DrawRect(c * cw, r * rh, cw, rh, fill);
            }
        }
        using var stroke = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Stroke, alpha),
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = sw
        };
        for (var r = 0; r <= rows; r++)
            canvas.DrawLine(0, r * rh, obj.Width, r * rh, stroke);
        for (var c = 0; c <= cols; c++)
            canvas.DrawLine(c * cw, 0, c * cw, obj.Height, stroke);
        using var tp = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true };
        using var font = new SKFont(ResolveTypeface(obj.FontFamily, obj.Bold, obj.Italic), Math.Min(obj.FontSize, rh * 0.55f));
        for (var r = 0; r < rows; r++)
        {
            for (var c = 0; c < cols; c++)
            {
                var cell = obj.GetTableCell(r, c);
                if (string.IsNullOrWhiteSpace(cell)) continue;
                canvas.Save();
                canvas.ClipRect(new SKRect(c * cw, r * rh, (c + 1) * cw, (r + 1) * rh));
                var tw = font.MeasureText(cell);
                var x = c * cw + Math.Max(0.3f, (cw - tw) / 2f);
                var y = r * rh + rh * 0.68f;
                canvas.DrawText(cell, x, y, SKTextAlign.Left, font, tp);
                canvas.Restore();
            }
        }
    }

    private static void DrawSvgParts(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        if (obj.SvgParts is not { Count: > 0 } parts) return;
        var sx = obj.Width / 100f;
        var sy = obj.Height / 100f;
        foreach (var part in parts)
        {
            if (string.IsNullOrWhiteSpace(part.D)) continue;
            using var path = SvgPathParser.Parse(part.D, obj.Width, obj.Height, 100, fitToBounds: false);
            path.Transform(SKMatrix.CreateScale(sx, sy));
            if (!ColorUtil.IsTransparent(part.Fill))
            {
                using var fill = new SKPaint
                {
                    Color = ColorUtil.Parse(part.Fill, alpha),
                    IsAntialias = true,
                    Style = SKPaintStyle.Fill
                };
                canvas.DrawPath(path, fill);
            }
            if (part.StrokeWidth > 0.01f && !ColorUtil.IsTransparent(part.Stroke))
            {
                using var stroke = new SKPaint
                {
                    Color = ColorUtil.Parse(part.Stroke, alpha),
                    IsAntialias = true,
                    Style = SKPaintStyle.Stroke,
                    StrokeWidth = part.StrokeWidth * Math.Min(sx, sy),
                    StrokeJoin = SKStrokeJoin.Round,
                    StrokeCap = SKStrokeCap.Round
                };
                canvas.DrawPath(path, stroke);
            }
        }
    }

    private static void DrawSvgShape(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        var d = obj.Svg;
        if (string.IsNullOrWhiteSpace(d))
        {
            if (obj.Type == ObjectType.Icon)
            {
                if (Icons?.TryGetPath(obj.IconName, out var fa) == true)
                    d = fa;
                else
                    d = SvgLibrary.Icons.FirstOrDefault(i => i.Id == obj.IconName).Path;
            }
            else
                d = SvgLibrary.Cliparts.FirstOrDefault(i => i.Id == obj.ClipartId).Path;
        }
        if (string.IsNullOrWhiteSpace(d)) d = SvgLibrary.StarPath;

        using var path = SvgPathParser.Parse(d, obj.Width, obj.Height);
        using var fill = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true, Style = SKPaintStyle.Fill };
        canvas.DrawPath(path, fill);
        if (obj.StrokeWidth > 0)
        {
            using var stroke = new SKPaint
            {
                Color = ColorUtil.Parse(obj.Stroke, alpha),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = obj.StrokeWidth
            };
            canvas.DrawPath(path, stroke);
        }
    }

    private static bool IsRasterImageData(string? data)
        => data is not null
           && (data.StartsWith("data:image/png", StringComparison.OrdinalIgnoreCase)
               || data.StartsWith("data:image/jpeg", StringComparison.OrdinalIgnoreCase)
               || data.StartsWith("data:image/jpg", StringComparison.OrdinalIgnoreCase)
               || data.StartsWith("data:image/bmp", StringComparison.OrdinalIgnoreCase)
               || data.StartsWith("data:image/gif", StringComparison.OrdinalIgnoreCase));

    public static void DrawSelection(SKCanvas canvas, DesignObject obj, float pxPerMm, float zoom)
    {
        canvas.Save();
        canvas.Translate(obj.X + obj.Width / 2f, obj.Y + obj.Height / 2f);
        if (Math.Abs(obj.Rotation) > 0.01f)
            canvas.RotateDegrees(obj.Rotation);
        canvas.Translate(-obj.Width / 2f, -obj.Height / 2f);

        var pad = 0.6f;
        var rect = new SKRect(-pad, -pad, obj.Width + pad, obj.Height + pad);
        using var box = new SKPaint
        {
            Color = new SKColor(0x7B, 0x28, 0x40, 220),
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = 0.35f / zoom
        };
        canvas.DrawRect(rect, box);

        var hs = Math.Max(1.1f / zoom, 0.9f);
        foreach (var (cx, cy) in HandleCenters(obj, pad))
        {
            using var hp = new SKPaint { Color = SKColors.White, IsAntialias = true, Style = SKPaintStyle.Fill };
            using var hb = new SKPaint
            {
                Color = new SKColor(0x7B, 0x28, 0x40),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = 0.25f / zoom
            };
            canvas.DrawCircle(cx, cy, hs, hp);
            canvas.DrawCircle(cx, cy, hs, hb);
        }

        var rx = obj.Width / 2f;
        var ry = -pad - 4.5f / zoom;
        using var line = new SKPaint
        {
            Color = new SKColor(0x7B, 0x28, 0x40, 180),
            IsAntialias = true,
            StrokeWidth = 0.25f / zoom
        };
        canvas.DrawLine(obj.Width / 2f, -pad, rx, ry + hs, line);
        using var rh = new SKPaint { Color = new SKColor(0xC4, 0xA5, 0x74), IsAntialias = true };
        canvas.DrawCircle(rx, ry, hs * 1.05f, rh);

        canvas.Restore();
    }

    public static IEnumerable<(float X, float Y)> HandleCenters(DesignObject obj, float pad = 0.6f)
    {
        float l = -pad, t = -pad, r = obj.Width + pad, b = obj.Height + pad;
        float cx = obj.Width / 2f, cy = obj.Height / 2f;
        yield return (l, t);
        yield return (cx, t);
        yield return (r, t);
        yield return (l, cy);
        yield return (r, cy);
        yield return (l, b);
        yield return (cx, b);
        yield return (r, b);
    }

    public static byte[] ExportPng(LabelDocument doc, LabelCell? cell = null, float dpi = 300f, Func<DesignObject, string>? resolve = null, float? widthMm = null, float? heightMm = null, int quality = 100)
    {
        doc.EnsureStructure();
        cell ??= doc.Pages[0].Cells[0];
        var wMm = widthMm ?? doc.WidthMm;
        var hMm = heightMm ?? doc.HeightMm;
        var scale = dpi / 25.4f;
        var w = Math.Max(1, (int)Math.Ceiling(wMm * scale));
        var h = Math.Max(1, (int)Math.Ceiling(hMm * scale));
        var info = new SKImageInfo(w, h, SKColorType.Rgba8888, SKAlphaType.Premul);
        using var surface = SKSurface.Create(info);
        var canvas = surface.Canvas;
        canvas.Clear(SKColors.Transparent);
        canvas.Scale(scale);
        DrawCell(canvas, doc, cell, resolve, forExport: true, widthMm: wMm, heightMm: hMm);
        using var image = surface.Snapshot();
        using var data = image.Encode(SKEncodedImageFormat.Png, Math.Clamp(quality, 10, 100));
        return data.ToArray();
    }

    public static byte[] ExportSheetPng(
        LabelDocument doc,
        int pageIndex,
        float dpi,
        float offsetXMm,
        float offsetYMm,
        Func<int, DesignObject, string>? resolve = null)
    {
        doc.EnsureStructure();
        pageIndex = Math.Clamp(pageIndex, 0, doc.Pages.Count - 1);
        var paper = doc.Paper;
        var scale = dpi / 25.4f;
        var w = Math.Max(1, (int)Math.Ceiling(paper.PaperWidthMm * scale));
        var h = Math.Max(1, (int)Math.Ceiling(paper.PaperHeightMm * scale));
        var info = new SKImageInfo(w, h, SKColorType.Rgba8888, SKAlphaType.Premul);
        using var surface = SKSurface.Create(info);
        var canvas = surface.Canvas;
        var paperBg = ColorUtil.Parse(paper.LabelColor);
        canvas.Clear(paperBg.Alpha == 0 ? SKColors.White : paperBg);
        canvas.Scale(scale);
        canvas.Translate(offsetXMm, offsetYMm);

        var page = doc.Pages[pageIndex];
        var per = paper.LabelsPerPage;
        foreach (var slot in paper.EnumerateSlots())
        {
            if (slot.Index >= page.Cells.Count) continue;
            var cell = page.Cells[slot.Index];
            var global = pageIndex * per + slot.Index;
            canvas.Save();
            canvas.Translate(slot.X, slot.Y);
            DrawCell(canvas, doc, cell, obj => resolve?.Invoke(global, obj) ?? obj.Text, forExport: true, widthMm: slot.W, heightMm: slot.H);
            using (var outline = new SKPaint
            {
                Color = new SKColor(0xC4, 0x28, 0x3A),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = 0.18f
            })
            using (var path = CreateLabelPath(paper.Shape, slot.W, slot.H))
            {
                canvas.DrawPath(path, outline);
            }
            canvas.Restore();
        }

        using var image = surface.Snapshot();
        using var data = image.Encode(SKEncodedImageFormat.Png, 92);
        return data.ToArray();
    }
}
