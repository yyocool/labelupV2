using System.Globalization;
using SkiaSharp;

namespace LabelUp.Editor.Rendering;

public static class SvgPathParser
{
    public static SKPath Parse(string pathData, float destW, float destH, float src = 100f, bool fitToBounds = true)
    {
        var path = new SKPath();
        if (string.IsNullOrWhiteSpace(pathData)) return path;

        var tokens = Tokenize(pathData);
        float cx = 0, cy = 0, sx = 0, sy = 0, lastCx = 0, lastCy = 0;
        float ctrlX = 0, ctrlY = 0;
        var prevCubic = false;
        var prevQuad = false;
        var i = 0;
        var cmd = 'M';

        while (i < tokens.Count)
        {
            if (tokens[i].Length == 1 && char.IsLetter(tokens[i][0]))
            {
                cmd = tokens[i][0];
                i++;
            }

            var rel = char.IsLower(cmd);
            var c = char.ToUpperInvariant(cmd);
            var cubic = false;
            var quad = false;

            switch (c)
            {
                case 'M':
                    Read(rel, ref cx, ref cy);
                    path.MoveTo(cx, cy);
                    sx = cx; sy = cy;
                    cmd = rel ? 'l' : 'L';
                    break;
                case 'L':
                    Read(rel, ref cx, ref cy);
                    path.LineTo(cx, cy);
                    break;
                case 'H':
                    var hx = Num();
                    cx = rel ? cx + hx : hx;
                    path.LineTo(cx, cy);
                    break;
                case 'V':
                    var vy = Num();
                    cy = rel ? cy + vy : vy;
                    path.LineTo(cx, cy);
                    break;
                case 'C':
                {
                    var x1 = Num(); var y1 = Num(); var x2 = Num(); var y2 = Num();
                    Read(rel, ref cx, ref cy);
                    if (rel) { x1 += lastCx; y1 += lastCy; x2 += lastCx; y2 += lastCy; }
                    path.CubicTo(x1, y1, x2, y2, cx, cy);
                    ctrlX = x2; ctrlY = y2;
                    cubic = true;
                    break;
                }
                case 'S':
                {
                    var x1 = prevCubic ? 2 * lastCx - ctrlX : lastCx;
                    var y1 = prevCubic ? 2 * lastCy - ctrlY : lastCy;
                    var x2 = Num(); var y2 = Num();
                    Read(rel, ref cx, ref cy);
                    if (rel) { x2 += lastCx; y2 += lastCy; }
                    path.CubicTo(x1, y1, x2, y2, cx, cy);
                    ctrlX = x2; ctrlY = y2;
                    cubic = true;
                    break;
                }
                case 'Q':
                {
                    var qx = Num(); var qy = Num();
                    Read(rel, ref cx, ref cy);
                    if (rel) { qx += lastCx; qy += lastCy; }
                    path.QuadTo(qx, qy, cx, cy);
                    ctrlX = qx; ctrlY = qy;
                    quad = true;
                    break;
                }
                case 'T':
                {
                    var qx = prevQuad ? 2 * lastCx - ctrlX : lastCx;
                    var qy = prevQuad ? 2 * lastCy - ctrlY : lastCy;
                    Read(rel, ref cx, ref cy);
                    path.QuadTo(qx, qy, cx, cy);
                    ctrlX = qx; ctrlY = qy;
                    quad = true;
                    break;
                }
                case 'A':
                    AddArc(path, lastCx, lastCy, Num(), Num(), Num(), Num() != 0, Num() != 0, ReadAbs(rel, lastCx, lastCy, out cx, out cy));
                    break;
                case 'Z':
                    path.Close();
                    cx = sx; cy = sy;
                    break;
                default:
                    i++;
                    break;
            }

            lastCx = cx;
            lastCy = cy;
            prevCubic = cubic;
            prevQuad = quad;
        }

        if (fitToBounds)
        {
            var bounds = path.Bounds;
            if (bounds.Width > 0.01f && bounds.Height > 0.01f)
            {
                var matrix = SKMatrix.CreateIdentity();
                matrix = matrix.PostConcat(SKMatrix.CreateTranslation(-bounds.Left, -bounds.Top));
                var scale = Math.Min(destW / bounds.Width, destH / bounds.Height);
                matrix = matrix.PostConcat(SKMatrix.CreateScale(scale, scale));
                var ox = (destW - bounds.Width * scale) / 2f;
                var oy = (destH - bounds.Height * scale) / 2f;
                matrix = matrix.PostConcat(SKMatrix.CreateTranslation(ox, oy));
                path.Transform(matrix);
            }
            else
            {
                var scale = Math.Min(destW / src, destH / src);
                path.Transform(SKMatrix.CreateScale(scale, scale));
            }
        }

        return path;

        void Read(bool relative, ref float x, ref float y)
        {
            var nx = Num();
            var ny = Num();
            if (relative) { x += nx; y += ny; }
            else { x = nx; y = ny; }
        }

        (float X, float Y) ReadAbs(bool relative, float ox, float oy, out float x, out float y)
        {
            x = ox; y = oy;
            Read(relative, ref x, ref y);
            return (x, y);
        }

        float Num()
        {
            if (i >= tokens.Count) return 0;
            if (float.TryParse(tokens[i], NumberStyles.Float, CultureInfo.InvariantCulture, out var v))
            {
                i++;
                return v;
            }
            i++;
            return 0;
        }
    }

    /// <summary>SVG elliptical arc → cubic. https://www.w3.org/TR/SVG/implnote.html#ArcImplementationNotes</summary>
    private static void AddArc(
        SKPath path, float x1, float y1, float rx, float ry, float phiDeg, bool large, bool sweep, (float X, float Y) end)
    {
        var x2 = end.X;
        var y2 = end.Y;
        if (Math.Abs(x1 - x2) < 1e-6f && Math.Abs(y1 - y2) < 1e-6f)
            return;
        rx = Math.Abs(rx);
        ry = Math.Abs(ry);
        if (rx < 1e-6f || ry < 1e-6f)
        {
            path.LineTo(x2, y2);
            return;
        }

        var phi = phiDeg * MathF.PI / 180f;
        var cos = MathF.Cos(phi);
        var sin = MathF.Sin(phi);
        var dx = (x1 - x2) / 2f;
        var dy = (y1 - y2) / 2f;
        var x1p = cos * dx + sin * dy;
        var y1p = -sin * dx + cos * dy;

        var lambda = x1p * x1p / (rx * rx) + y1p * y1p / (ry * ry);
        if (lambda > 1f)
        {
            var s = MathF.Sqrt(lambda);
            rx *= s;
            ry *= s;
        }

        var rx2 = rx * rx;
        var ry2 = ry * ry;
        var x1p2 = x1p * x1p;
        var y1p2 = y1p * y1p;
        var num = rx2 * ry2 - rx2 * y1p2 - ry2 * x1p2;
        var den = rx2 * y1p2 + ry2 * x1p2;
        var rad = den <= 0 ? 0 : MathF.Sqrt(Math.Max(0, num / den));
        if (large == sweep) rad = -rad;
        var cxp = rad * rx * y1p / ry;
        var cyp = rad * -ry * x1p / rx;

        var cx = cos * cxp - sin * cyp + (x1 + x2) / 2f;
        var cy = sin * cxp + cos * cyp + (y1 + y2) / 2f;

        var theta1 = Atan2((y1p - cyp) / ry, (x1p - cxp) / rx);
        var dtheta = Atan2((-y1p - cyp) / ry, (-x1p - cxp) / rx) - theta1;
        if (!sweep && dtheta > 0) dtheta -= 2 * MathF.PI;
        else if (sweep && dtheta < 0) dtheta += 2 * MathF.PI;

        var segs = Math.Max(1, (int)MathF.Ceiling(Math.Abs(dtheta) / (MathF.PI / 2f)));
        var delta = dtheta / segs;
        var t = 4f / 3f * MathF.Tan(delta / 4f);
        var a1 = theta1;
        for (var s = 0; s < segs; s++)
        {
            var a2 = a1 + delta;
            var e1x = MathF.Cos(a1);
            var e1y = MathF.Sin(a1);
            var e2x = MathF.Cos(a2);
            var e2y = MathF.Sin(a2);
            var p1x = cx + rx * (cos * e1x - sin * e1y);
            var p1y = cy + ry * (sin * e1x + cos * e1y);
            var p2x = cx + rx * (cos * e2x - sin * e2y);
            var p2y = cy + ry * (sin * e2x + cos * e2y);
            var q1x = p1x + t * rx * (-cos * e1y - sin * e1x);
            var q1y = p1y + t * ry * (-sin * e1y + cos * e1x);
            var q2x = p2x + t * rx * (cos * e2y + sin * e2x);
            var q2y = p2y + t * ry * (sin * e2y - cos * e2x);
            path.CubicTo(q1x, q1y, q2x, q2y, p2x, p2y);
            a1 = a2;
        }
    }

    private static float Atan2(float y, float x) => MathF.Atan2(y, x);

    private static List<string> Tokenize(string data)
    {
        var list = new List<string>();
        var sb = new System.Text.StringBuilder();
        foreach (var ch in data)
        {
            if (char.IsLetter(ch))
            {
                Flush();
                list.Add(ch.ToString());
            }
            else if (ch is ',' or ' ' or '\n' or '\r' or '\t')
            {
                Flush();
            }
            else if ((ch is '-' or '+') && sb.Length > 0)
            {
                Flush();
                sb.Append(ch);
            }
            else if (ch == '.' && sb.ToString().Contains('.'))
            {
                Flush();
                sb.Append(ch);
            }
            else sb.Append(ch);
        }
        Flush();
        return list;

        void Flush()
        {
            if (sb.Length == 0) return;
            list.Add(sb.ToString());
            sb.Clear();
        }
    }
}
