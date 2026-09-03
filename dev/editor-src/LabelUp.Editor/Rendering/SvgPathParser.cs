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
        var i = 0;
        var cmd = 'M';
        var first = true;

        while (i < tokens.Count)
        {
            if (tokens[i].Length == 1 && char.IsLetter(tokens[i][0]))
            {
                cmd = tokens[i][0];
                i++;
            }

            var rel = char.IsLower(cmd);
            var c = char.ToUpperInvariant(cmd);

            switch (c)
            {
                case 'M':
                    Read(rel, ref cx, ref cy);
                    if (first) { path.MoveTo(cx, cy); first = false; }
                    else path.MoveTo(cx, cy);
                    sx = cx; sy = cy;
                    cmd = rel ? 'l' : 'L';
                    break;
                case 'L':
                    Read(rel, ref cx, ref cy);
                    path.LineTo(cx, cy);
                    break;
                case 'H':
                    var x = Num();
                    cx = rel ? cx + x : x;
                    path.LineTo(cx, cy);
                    break;
                case 'V':
                    var y = Num();
                    cy = rel ? cy + y : y;
                    path.LineTo(cx, cy);
                    break;
                case 'C':
                    var x1 = Num(); var y1 = Num(); var x2 = Num(); var y2 = Num();
                    Read(rel, ref cx, ref cy);
                    if (rel) { x1 += lastCx; y1 += lastCy; x2 += lastCx; y2 += lastCy; }
                    path.CubicTo(x1, y1, x2, y2, cx, cy);
                    break;
                case 'Q':
                    var qx = Num(); var qy = Num();
                    Read(rel, ref cx, ref cy);
                    if (rel) { qx += lastCx; qy += lastCy; }
                    path.QuadTo(qx, qy, cx, cy);
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
            else if (ch == '-' && sb.Length > 0)
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
