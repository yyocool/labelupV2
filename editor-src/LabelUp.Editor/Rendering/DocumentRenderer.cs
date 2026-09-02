using LabelUp.Editor.Models;
using LabelUp.Editor.Services;
using SkiaSharp;

namespace LabelUp.Editor.Rendering;

public static class ColorUtil
{
    public static SKColor Parse(string? hex, byte alpha = 255)
    {
        if (string.IsNullOrWhiteSpace(hex) || hex is "transparent" or "none")
            return SKColors.Transparent;

        hex = hex.Trim();
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

    public static void SetFontCatalog(FontCatalog? catalog) => Fonts = catalog;
    public static void ClearImageCache() => ImageCache.Clear();

    public static SKTypeface ResolveTypeface(bool bold = false)
    {
        if (Fonts is not null) return Fonts.Resolve(bold);
        return SKTypeface.Default;
    }

    public static SKBitmap? GetBitmap(DesignObject obj)
    {
        if (string.IsNullOrEmpty(obj.ImageData)) return null;
        if (ImageCache.TryGetValue(obj.Id, out var cached) && cached != null) return cached;
        try
        {
            var data = obj.ImageData!;
            var comma = data.IndexOf(',');
            if (comma >= 0) data = data[(comma + 1)..];
            var bytes = Convert.FromBase64String(data);
            var bmp = SKBitmap.Decode(bytes);
            if (bmp != null) ImageCache[obj.Id] = bmp;
            return bmp;
        }
        catch
        {
            return null;
        }
    }

    public static void InvalidateImage(string id)
    {
        if (ImageCache.Remove(id, out var bmp)) bmp.Dispose();
    }

    public static void DrawCell(
        SKCanvas canvas,
        LabelDocument doc,
        LabelCell cell,
        Func<DesignObject, string>? resolve = null,
        bool forExport = false)
    {
        var w = doc.WidthMm;
        var h = doc.HeightMm;
        using var clip = CreateLabelPath(doc.Paper.Shape, w, h);
        canvas.Save();
        canvas.ClipPath(clip, SKClipOperation.Intersect, antialias: true);

        using var bg = new SKPaint { Color = ColorUtil.Parse(doc.Background), IsAntialias = true, Style = SKPaintStyle.Fill };
        canvas.DrawRect(0, 0, w, h, bg);

        if (doc.Paper.Shape.Hole is { Width: > 0, Height: > 0 } hole)
        {
            using var holePaint = new SKPaint { BlendMode = SKBlendMode.Clear, IsAntialias = true };
            canvas.DrawOval(new SKRect(hole.X, hole.Y, hole.X + hole.Width, hole.Y + hole.Height), holePaint);
        }

        if (!forExport)
        {
            using var border = new SKPaint
            {
                Color = new SKColor(0xE5, 0xDD, 0xD3),
                IsAntialias = true,
                Style = SKPaintStyle.Stroke,
                StrokeWidth = 0.15f
            };
            canvas.DrawPath(clip, border);
        }

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
                using (var parsed = SvgPathParser.Parse(ExtractPath(shape.Svg!), w, h))
                    path.AddPath(parsed);
                if (path.IsEmpty) path.AddRect(new SKRect(0, 0, w, h));
                break;
            default:
                path.AddRect(new SKRect(0, 0, w, h));
                break;
        }
        return path;
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
                DrawSvgShape(canvas, obj, alpha);
                break;
        }

        canvas.Restore();
    }

    private static ShapeKind ResolveShape(DesignObject obj) => obj.Type switch
    {
        ObjectType.Ellipse => ShapeKind.Ellipse,
        ObjectType.Line => ShapeKind.Line,
        ObjectType.Shape => obj.ShapeKind,
        _ => obj.ShapeKind
    };

    private static void DrawShape(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        var kind = ResolveShape(obj);
        using var fill = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true, Style = SKPaintStyle.Fill };
        using var stroke = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Stroke, alpha),
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = Math.Max(obj.StrokeWidth, kind is ShapeKind.Line or ShapeKind.Arrow ? 0.35f : 0),
            StrokeCap = SKStrokeCap.Round,
            StrokeJoin = SKStrokeJoin.Round
        };

        switch (kind)
        {
            case ShapeKind.Ellipse:
            case ShapeKind.Circle:
                var oval = new SKRect(0, 0, obj.Width, obj.Height);
                canvas.DrawOval(oval, fill);
                if (obj.StrokeWidth > 0) canvas.DrawOval(oval, stroke);
                break;
            case ShapeKind.RoundRect:
                var rr = new SKRoundRect(new SKRect(0, 0, obj.Width, obj.Height), obj.CornerRadiusMm, obj.CornerRadiusMm);
                canvas.DrawRoundRect(rr, fill);
                if (obj.StrokeWidth > 0) canvas.DrawRoundRect(rr, stroke);
                break;
            case ShapeKind.Triangle:
                using (var path = TrianglePath(obj.Width, obj.Height))
                {
                    canvas.DrawPath(path, fill);
                    if (obj.StrokeWidth > 0) canvas.DrawPath(path, stroke);
                }
                break;
            case ShapeKind.Polygon:
                using (var path = PolygonPath(obj.Width, obj.Height, obj.PolygonSides))
                {
                    canvas.DrawPath(path, fill);
                    if (obj.StrokeWidth > 0) canvas.DrawPath(path, stroke);
                }
                break;
            case ShapeKind.Line:
                canvas.DrawLine(0, obj.Height / 2f, obj.Width, obj.Height / 2f, stroke);
                break;
            case ShapeKind.Arrow:
                DrawArrow(canvas, obj, fill, stroke);
                break;
            default:
                var rect = new SKRect(0, 0, obj.Width, obj.Height);
                canvas.DrawRect(rect, fill);
                if (obj.StrokeWidth > 0) canvas.DrawRect(rect, stroke);
                break;
        }
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

        text = string.IsNullOrEmpty(text) ? " " : text;
        var style = obj.TextMode == TextMode.WordArt ? obj.WordArtStyle : WordArtStyle.None;
        if (style is WordArtStyle.ArcUp or WordArtStyle.ArcDown or WordArtStyle.Circle or WordArtStyle.Wave)
        {
            DrawWordArt(canvas, obj, text, alpha, style);
            return;
        }

        using var paint = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true };
        using var font = new SKFont(ResolveTypeface(obj.Bold), obj.FontSize);
        if (obj.Italic)
            canvas.Skew(-0.25f, 0);

        if (obj.TextDirection == "vertical")
        {
            DrawVerticalText(canvas, obj, text, font, paint);
            return;
        }

        var lines = text.Replace("\r\n", "\n").Split('\n');
        var lineH = obj.FontSize * Math.Max(0.8f, obj.LineHeight);
        var totalH = lineH * lines.Length;
        float startY = obj.VerticalAlign switch
        {
            "top" => lineH,
            "bottom" => obj.Height - (lines.Length - 1) * lineH - 0.4f,
            _ => (obj.Height - totalH) / 2f + lineH * 0.78f
        };

        for (var i = 0; i < lines.Length; i++)
        {
            var line = lines[i];
            var tw = font.MeasureText(line);
            float x = obj.TextAlign switch
            {
                "left" => 1f,
                "right" => obj.Width - tw - 1f,
                _ => (obj.Width - tw) / 2f
            };
            var y = startY + i * lineH;
            canvas.DrawText(line, x, y, SKTextAlign.Left, font, paint);
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
    }

    private static void DrawVerticalText(SKCanvas canvas, DesignObject obj, string text, SKFont font, SKPaint paint)
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
            canvas.DrawText(chars[i].ToString(), x, y, SKTextAlign.Center, font, paint);
        }
    }

    private static void DrawWordArt(SKCanvas canvas, DesignObject obj, string text, byte alpha, WordArtStyle style)
    {
        using var paint = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true };
        using var font = new SKFont(ResolveTypeface(obj.Bold), obj.FontSize);
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
                canvas.DrawText(s, x, obj.Height * 0.62f + wave, SKTextAlign.Left, font, paint);
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
            canvas.DrawText(chars[i].ToString(), 0, 0, SKTextAlign.Center, font, paint);
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
        if (!obj.BackgroundTransparent
            && !string.IsNullOrWhiteSpace(obj.BackgroundFill)
            && obj.BackgroundFill is not "transparent" and not "none")
        {
            using var fill = new SKPaint
            {
                Color = ColorUtil.Parse(obj.BackgroundFill, alpha),
                IsAntialias = true
            };
            canvas.DrawRect(0, 0, obj.Width, obj.Height, fill);
        }
        using var stroke = new SKPaint
        {
            Color = ColorUtil.Parse(obj.Stroke, alpha),
            IsAntialias = true,
            Style = SKPaintStyle.Stroke,
            StrokeWidth = Math.Max(0.12f, obj.TableBorderWidth)
        };
        using var tp = new SKPaint { Color = ColorUtil.Parse(obj.Fill, alpha), IsAntialias = true };
        using var font = new SKFont(ResolveTypeface(obj.Bold), obj.FontSize);
        for (var r = 0; r <= rows; r++)
            canvas.DrawLine(0, r * rh, obj.Width, r * rh, stroke);
        for (var c = 0; c <= cols; c++)
            canvas.DrawLine(c * cw, 0, c * cw, obj.Height, stroke);
        for (var r = 0; r < rows; r++)
        {
            for (var c = 0; c < cols; c++)
            {
                var cell = obj.GetTableCell(r, c);
                if (string.IsNullOrEmpty(cell)) continue;
                var tw = font.MeasureText(cell);
                var x = c * cw + Math.Max(0.4f, (cw - tw) / 2f);
                var y = r * rh + rh * 0.68f;
                canvas.DrawText(cell, x, y, SKTextAlign.Left, font, tp);
            }
        }
    }

    private static void DrawSvgShape(SKCanvas canvas, DesignObject obj, byte alpha)
    {
        var d = obj.Svg;
        if (string.IsNullOrWhiteSpace(d))
        {
            if (obj.Type == ObjectType.Icon)
                d = SvgLibrary.Icons.FirstOrDefault(i => i.Id == obj.IconName).Path;
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

    public static byte[] ExportPng(LabelDocument doc, LabelCell? cell = null, float dpi = 300f, Func<DesignObject, string>? resolve = null)
    {
        doc.EnsureStructure();
        cell ??= doc.Pages[0].Cells[0];
        var scale = dpi / 25.4f;
        var w = Math.Max(1, (int)Math.Ceiling(doc.WidthMm * scale));
        var h = Math.Max(1, (int)Math.Ceiling(doc.HeightMm * scale));
        var info = new SKImageInfo(w, h, SKColorType.Rgba8888, SKAlphaType.Premul);
        using var surface = SKSurface.Create(info);
        var canvas = surface.Canvas;
        canvas.Clear(SKColors.Transparent);
        canvas.Scale(scale);
        DrawCell(canvas, doc, cell, resolve, forExport: true);
        using var image = surface.Snapshot();
        using var data = image.Encode(SKEncodedImageFormat.Png, 100);
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
        canvas.Clear(SKColors.White);
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
            DrawCell(canvas, doc, cell, obj => resolve?.Invoke(global, obj) ?? obj.Text, forExport: true);
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
