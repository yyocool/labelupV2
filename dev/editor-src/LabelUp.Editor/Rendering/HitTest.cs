using LabelUp.Editor.Models;
using SkiaSharp;

namespace LabelUp.Editor.Rendering;

public static class HitTest
{
    public static DesignObject? HitObject(IList<DesignObject> objects, float docX, float docY, float padMm = 0.8f)
    {
        var ordered = objects.Where(o => o.Visible).OrderBy(o => o.ZIndex).ThenBy(objects.IndexOf).ToList();
        for (var i = ordered.Count - 1; i >= 0; i--)
        {
            var o = ordered[i];
            if (ContainsPoint(o, docX, docY, padMm)) return o;
        }
        return null;
    }

    public static DesignObject? HitObject(LabelDocument doc, float docX, float docY)
    {
        doc.EnsureStructure();
        return HitObject(doc.Pages[0].Cells[0].Objects, docX, docY);
    }

    public static bool Intersects(DesignObject o, float x1, float y1, float x2, float y2)
    {
        var l = Math.Min(x1, x2);
        var r = Math.Max(x1, x2);
        var t = Math.Min(y1, y2);
        var b = Math.Max(y1, y2);
        return o.X < r && o.X + o.Width > l && o.Y < b && o.Y + o.Height > t;
    }

    public static bool ContainsPoint(DesignObject o, float docX, float docY, float pad = 0.8f)
    {
        var local = ToLocal(o, docX, docY);
        return local.X >= -pad && local.Y >= -pad && local.X <= o.Width + pad && local.Y <= o.Height + pad;
    }

    public static HandleKind HitHandle(DesignObject o, float docX, float docY, float zoom, float extraMm = 0)
    {
        var local = ToLocal(o, docX, docY);
        var thresh = Math.Max(2.2f / zoom, 1.6f) + Math.Max(0, extraMm);
        float pad = 0.6f;
        float l = -pad, t = -pad, r = o.Width + pad, b = o.Height + pad;
        float cx = o.Width / 2f, cy = o.Height / 2f;

        var rotateY = -pad - 4.5f / zoom;
        if (Dist(local.X, local.Y, cx, rotateY) <= thresh * 1.2f) return HandleKind.Rotate;

        if (Dist(local.X, local.Y, l, t) <= thresh) return HandleKind.Nw;
        if (Dist(local.X, local.Y, cx, t) <= thresh) return HandleKind.N;
        if (Dist(local.X, local.Y, r, t) <= thresh) return HandleKind.Ne;
        if (Dist(local.X, local.Y, l, cy) <= thresh) return HandleKind.W;
        if (Dist(local.X, local.Y, r, cy) <= thresh) return HandleKind.E;
        if (Dist(local.X, local.Y, l, b) <= thresh) return HandleKind.Sw;
        if (Dist(local.X, local.Y, cx, b) <= thresh) return HandleKind.S;
        if (Dist(local.X, local.Y, r, b) <= thresh) return HandleKind.Se;

        if (local.X >= -pad && local.Y >= -pad && local.X <= o.Width + pad && local.Y <= o.Height + pad)
            return HandleKind.Move;

        return HandleKind.None;
    }

    public static SKPoint ToLocal(DesignObject o, float docX, float docY)
    {
        var cx = o.X + o.Width / 2f;
        var cy = o.Y + o.Height / 2f;
        var dx = docX - cx;
        var dy = docY - cy;
        var rad = -o.Rotation * MathF.PI / 180f;
        var cos = MathF.Cos(rad);
        var sin = MathF.Sin(rad);
        var lx = dx * cos - dy * sin + o.Width / 2f;
        var ly = dx * sin + dy * cos + o.Height / 2f;
        return new SKPoint(lx, ly);
    }

    private static float Dist(float x1, float y1, float x2, float y2)
    {
        var dx = x1 - x2;
        var dy = y1 - y2;
        return MathF.Sqrt(dx * dx + dy * dy);
    }

    public static void ApplyResize(DesignObject o, HandleKind handle, float docX, float docY, float startX, float startY, DesignObject start)
    {
        // Simplified axis-aligned resize (ignores rotation for v1 stability)
        var dx = docX - startX;
        var dy = docY - startY;
        float x = start.X, y = start.Y, w = start.Width, h = start.Height;

        switch (handle)
        {
            case HandleKind.E: w = Math.Max(2f, start.Width + dx); break;
            case HandleKind.W: x = start.X + dx; w = Math.Max(2f, start.Width - dx); break;
            case HandleKind.S: h = Math.Max(2f, start.Height + dy); break;
            case HandleKind.N: y = start.Y + dy; h = Math.Max(2f, start.Height - dy); break;
            case HandleKind.Se: w = Math.Max(2f, start.Width + dx); h = Math.Max(2f, start.Height + dy); break;
            case HandleKind.Sw: x = start.X + dx; w = Math.Max(2f, start.Width - dx); h = Math.Max(2f, start.Height + dy); break;
            case HandleKind.Ne: w = Math.Max(2f, start.Width + dx); y = start.Y + dy; h = Math.Max(2f, start.Height - dy); break;
            case HandleKind.Nw: x = start.X + dx; y = start.Y + dy; w = Math.Max(2f, start.Width - dx); h = Math.Max(2f, start.Height - dy); break;
        }

        o.X = x; o.Y = y; o.Width = w; o.Height = h;
    }
}
