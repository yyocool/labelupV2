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

    public static (int Row, int Col)? HitTableCell(DesignObject o, float docX, float docY)
    {
        if (o.Type != ObjectType.Table || o.TableRows < 1 || o.TableCols < 1) return null;
        var local = ToLocal(o, docX, docY);
        if (local.X < 0 || local.Y < 0 || local.X > o.Width || local.Y > o.Height) return null;
        var col = Math.Clamp((int)(local.X / (o.Width / o.TableCols)), 0, o.TableCols - 1);
        var row = Math.Clamp((int)(local.Y / (o.Height / o.TableRows)), 0, o.TableRows - 1);
        return (row, col);
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

    private const float MinSizeMm = 2f;

    public static void ApplyResize(DesignObject o, HandleKind handle, float docX, float docY, float startX, float startY, DesignObject start)
    {
        if (start.KeepsSquare)
        {
            ApplySquareResize(o, handle, docX, docY, startX, startY, start);
            return;
        }

        if (start.LockAspectRatio && start.Width > 0.01f && start.Height > 0.01f)
        {
            ApplyLockedResize(o, handle, docX, docY, startX, startY, start);
            return;
        }

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

    /// <summary>
    /// 원을 정사각형 상자에 묶어 크기를 바꾼다. 어느 손잡이를 잡아도 지름 하나가 나온다.
    /// 변 손잡이는 잡은 변이 마우스를 그대로 따라가고 남는 축은 가운데로 맞춘다.
    /// 모서리는 잡지 않은 반대 모서리를 못으로 박고 두 축 변화량의 평균을 지름에 더한다.
    /// 상자가 정사각형이 아닌 채로 들어와도(변환본 등) 첫 조정에서 지름 하나로 모인다.
    /// </summary>
    private static void ApplySquareResize(DesignObject o, HandleKind handle, float docX, float docY, float startX, float startY, DesignObject start)
    {
        var dx = docX - startX;
        var dy = docY - startY;
        float x0 = start.X, y0 = start.Y, w0 = start.Width, h0 = start.Height;

        float d;
        switch (handle)
        {
            case HandleKind.E: d = w0 + dx; break;
            case HandleKind.W: d = w0 - dx; break;
            case HandleKind.S: d = h0 + dy; break;
            case HandleKind.N: d = h0 - dy; break;
            case HandleKind.Se: d = (w0 + dx + h0 + dy) / 2f; break;
            case HandleKind.Sw: d = (w0 - dx + h0 + dy) / 2f; break;
            case HandleKind.Ne: d = (w0 + dx + h0 - dy) / 2f; break;
            case HandleKind.Nw: d = (w0 - dx + h0 - dy) / 2f; break;
            default: return;
        }
        d = Math.Max(MinSizeMm, d);

        // 잡지 않은 변·모서리를 제자리에 둔다. 변 손잡이는 남는 축을 가운데로 맞춘다.
        var x = handle switch
        {
            HandleKind.W or HandleKind.Sw or HandleKind.Nw => x0 + w0 - d,
            HandleKind.N or HandleKind.S => x0 + (w0 - d) / 2f,
            _ => x0
        };
        var y = handle switch
        {
            HandleKind.N or HandleKind.Ne or HandleKind.Nw => y0 + h0 - d,
            HandleKind.E or HandleKind.W => y0 + (h0 - d) / 2f,
            _ => y0
        };

        o.X = x; o.Y = y; o.Width = d; o.Height = d;
    }

    /// <summary>
    /// 가로세로 비를 묶은 채 크기를 바꾼다. 기준 비는 드래그를 잡는 순간의 상자 Width:Height 다.
    /// 모서리는 잡지 않은 반대 모서리를 못으로 박고 대각선에 마우스를 투영해 배율 하나를 뽑는다.
    /// 변은 잡은 변만 움직이고 나머지 한 축은 비에서 따라오며, 남는 쪽은 가운데로 맞춘다.
    /// </summary>
    private static void ApplyLockedResize(DesignObject o, HandleKind handle, float docX, float docY, float startX, float startY, DesignObject start)
    {
        float w0 = start.Width, h0 = start.Height;
        var minScale = Math.Max(MinSizeMm / w0, MinSizeMm / h0);
        float x = start.X, y = start.Y, w = w0, h = h0;

        switch (handle)
        {
            case HandleKind.E:
            case HandleKind.W:
            {
                var dx = docX - startX;
                var scale = ((handle == HandleKind.E ? w0 + dx : w0 - dx) / w0);
                scale = Math.Max(minScale, scale);
                w = w0 * scale;
                h = h0 * scale;
                x = handle == HandleKind.E ? start.X : start.X + w0 - w;
                y = start.Y + (h0 - h) / 2f;
                break;
            }
            case HandleKind.S:
            case HandleKind.N:
            {
                var dy = docY - startY;
                var scale = ((handle == HandleKind.S ? h0 + dy : h0 - dy) / h0);
                scale = Math.Max(minScale, scale);
                w = w0 * scale;
                h = h0 * scale;
                y = handle == HandleKind.S ? start.Y : start.Y + h0 - h;
                x = start.X + (w0 - w) / 2f;
                break;
            }
            case HandleKind.Se:
            case HandleKind.Sw:
            case HandleKind.Ne:
            case HandleKind.Nw:
            {
                var right = handle is HandleKind.Se or HandleKind.Ne;
                var down = handle is HandleKind.Se or HandleKind.Sw;
                // 못은 잡지 않은 반대 모서리. 손잡이는 잡은 자리에서 끌린 만큼만 움직여
                // 커서가 손잡이 한가운데를 벗어나 있어도 상자가 튀지 않는다.
                var ax = right ? start.X : start.X + w0;
                var ay = down ? start.Y : start.Y + h0;
                var hx = (right ? start.X + w0 : start.X) + (docX - startX);
                var hy = (down ? start.Y + h0 : start.Y) + (docY - startY);
                var vx = right ? hx - ax : ax - hx;
                var vy = down ? hy - ay : ay - hy;

                var scale = (vx * w0 + vy * h0) / (w0 * w0 + h0 * h0);
                scale = Math.Max(minScale, scale);
                w = w0 * scale;
                h = h0 * scale;
                x = right ? ax : ax - w;
                y = down ? ay : ay - h;
                break;
            }
            default:
                return;
        }

        o.X = x; o.Y = y; o.Width = w; o.Height = h;
    }
}
