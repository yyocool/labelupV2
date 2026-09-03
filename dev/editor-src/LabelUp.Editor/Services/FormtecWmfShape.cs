using System.Globalization;
using System.Text;
using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

/// <summary>
/// 폼텍 Placeable WMF → 라벨 mm SVG path.
/// 글자(캐릭터 인쇄위치 등)는 폰트가 아니라 PolyPolygon 채움(그림자 후 본문).
/// </summary>
internal static class FormtecWmfShape
{
    private const ushort MetaEof = 0x0000;
    private const ushort MetaSetPolyFill = 0x0106;
    private const ushort MetaSelectObject = 0x012D;
    private const ushort MetaDeleteObject = 0x01F0;
    private const ushort MetaSetWindowOrg = 0x020B;
    private const ushort MetaSetWindowExt = 0x020C;
    private const ushort MetaCreatePen = 0x02FA;
    private const ushort MetaCreateFont = 0x02FB;
    private const ushort MetaCreateBrush = 0x02FC;
    private const ushort MetaEllipse = 0x0418;
    private const ushort MetaRectangle = 0x041B;
    private const ushort MetaRoundRect = 0x061C;
    private const ushort MetaPolygon = 0x0324;
    private const ushort MetaPolyline = 0x0325;
    private const ushort MetaPolyPolygon = 0x0538;

    private const int BsHollow = 1;
    private const int PsNull = 5;

    internal static bool TryConvert(byte[] data, float labelW, float labelH, out string outer, out List<PaperGuidePath> guides)
    {
        outer = "";
        guides = [];
        if (!TryReadWindow(data, out var orgX, out var orgY, out var extX, out var extY, out var start))
            return false;
        if (Math.Abs(extX) < 2 || Math.Abs(extY) < 2) return false;

        var table = new List<GdiObj?>();
        GdiObj? brush = null;
        GdiObj? pen = null;
        var evenOdd = true;
        var drawn = new List<(double Area, bool Fill, string D, PaperGuidePath Guide)>();

        var i = start;
        while (i + 6 <= data.Length)
        {
            var words = BitConverter.ToUInt32(data, i);
            var func = BitConverter.ToUInt16(data, i + 4);
            var bytes = (int)words * 2;
            if (bytes < 6 || i + bytes > data.Length) break;
            if (func == MetaEof) break;

            var payload = i + 6;
            switch (func)
            {
                case MetaCreateBrush when bytes >= 12:
                    AddObj(table, ParseBrush(data, payload));
                    break;
                case MetaCreatePen when bytes >= 16:
                    AddObj(table, ParsePen(data, payload));
                    break;
                case MetaCreateFont:
                    AddObj(table, new GdiObj(false, true, "#000000", 0, true));
                    break;
                case MetaSelectObject when bytes >= 8:
                    SelectObj(table, BitConverter.ToUInt16(data, payload), ref brush, ref pen);
                    break;
                case MetaDeleteObject when bytes >= 8:
                    DeleteObj(table, BitConverter.ToUInt16(data, payload));
                    break;
                case MetaSetPolyFill when bytes >= 8:
                    evenOdd = BitConverter.ToUInt16(data, payload) == 1;
                    break;
                case MetaPolyPolygon:
                case MetaPolygon:
                case MetaPolyline:
                case MetaEllipse:
                case MetaRectangle:
                case MetaRoundRect:
                    foreach (var piece in ReadPieces(data, i, func, orgX, orgY, extX, extY, labelW, labelH))
                        ApplyDraw(piece, func, brush, pen, evenOdd, extX, labelW, drawn);
                    break;
            }

            i += bytes;
        }

        if (drawn.Count == 0) return false;

        var fills = drawn.Where(p => p.Fill).ToList();
        if (fills.Count > 0)
        {
            var best = fills.MaxBy(p => p.Area);
            outer = best.D;
            var threshold = best.Area * 0.85;
            foreach (var p in drawn)
            {
                if (p.Fill && p.D == best.D) continue;
                if (p.Fill && p.Area >= threshold) continue;
                guides.Add(p.Guide);
            }
        }
        else
        {
            foreach (var p in drawn)
                guides.Add(p.Guide);
        }

        if (string.IsNullOrWhiteSpace(outer) && guides.Count > 0)
            outer = guides[0].D;
        return !string.IsNullOrWhiteSpace(outer);
    }

    internal static string? ToSvgPath(byte[] data, float labelW, float labelH)
        => TryConvert(data, labelW, labelH, out var outer, out _) ? outer : null;

    private static void ApplyDraw(
        WmfPiece piece,
        ushort func,
        GdiObj? brush,
        GdiObj? pen,
        bool evenOdd,
        int extX,
        float labelW,
        List<(double Area, bool Fill, string D, PaperGuidePath Guide)> drawn)
    {
        if (string.IsNullOrWhiteSpace(piece.D)) return;

        if (func != MetaPolyline && brush is { Hollow: false } fb)
        {
            drawn.Add((piece.Area, true, piece.D, new PaperGuidePath
            {
                D = piece.D,
                Fill = fb.Color,
                EvenOdd = evenOdd
            }));
            return;
        }

        if (pen is { Hollow: false } sp)
        {
            drawn.Add((0, false, piece.D, new PaperGuidePath
            {
                D = piece.D,
                Stroke = sp.Color,
                StrokeWidthMm = StrokeMm(sp.WidthWu, extX, labelW)
            }));
        }
    }

    private static float StrokeMm(int widthWu, int extX, float labelW)
    {
        if (widthWu <= 0 || Math.Abs(extX) < 2) return 0.22f;
        var mm = (float)(widthWu / (double)Math.Abs(extX) * labelW);
        return Math.Clamp(mm, 0.12f, 1.2f);
    }

    private readonly record struct GdiObj(bool IsPen, bool Hollow, string Color, int WidthWu, bool Ignore = false);

    private static GdiObj ParseBrush(byte[] data, int payload)
    {
        var style = BitConverter.ToUInt16(data, payload);
        return new GdiObj(false, style == BsHollow, ColorRef(data, payload + 2), 0);
    }

    private static GdiObj ParsePen(byte[] data, int payload)
    {
        var style = BitConverter.ToUInt16(data, payload);
        var width = BitConverter.ToInt16(data, payload + 2);
        return new GdiObj(true, style == PsNull, ColorRef(data, payload + 6), Math.Abs(width));
    }

    private static string ColorRef(byte[] data, int off)
    {
        var c = BitConverter.ToUInt32(data, off);
        return string.Create(CultureInfo.InvariantCulture,
            $"#{c & 0xFF:X2}{(c >> 8) & 0xFF:X2}{(c >> 16) & 0xFF:X2}");
    }

    private static void AddObj(List<GdiObj?> table, GdiObj obj)
    {
        for (var i = 0; i < table.Count; i++)
        {
            if (table[i] is null)
            {
                table[i] = obj;
                return;
            }
        }
        table.Add(obj);
    }

    private static void SelectObj(List<GdiObj?> table, int handle, ref GdiObj? brush, ref GdiObj? pen)
    {
        if (handle < 0 || handle >= table.Count || table[handle] is not { } obj || obj.Ignore) return;
        if (obj.IsPen) pen = obj;
        else brush = obj;
    }

    private static void DeleteObj(List<GdiObj?> table, int handle)
    {
        if (handle >= 0 && handle < table.Count)
            table[handle] = null;
    }

    private readonly record struct WmfPiece(string D, double Area, bool Closed);

    private static IEnumerable<WmfPiece> ReadPieces(
        byte[] data, int rec, ushort func,
        int orgX, int orgY, int extX, int extY,
        float labelW, float labelH)
    {
        var payload = rec + 6;
        switch (func)
        {
            case MetaEllipse:
            case MetaRectangle:
            case MetaRoundRect:
                if (payload + 8 > data.Length) yield break;
                var bottom = BitConverter.ToInt16(data, payload);
                var right = BitConverter.ToInt16(data, payload + 2);
                var top = BitConverter.ToInt16(data, payload + 4);
                var left = BitConverter.ToInt16(data, payload + 6);
                var x = MapX(left, orgX, extX, labelW);
                var y = MapY(top, orgY, extY, labelH);
                var w = MapX(right, orgX, extX, labelW) - x;
                var h = MapY(bottom, orgY, extY, labelH) - y;
                if (w < 0) { x += w; w = -w; }
                if (h < 0) { y += h; h = -h; }
                var d = func == MetaEllipse
                    ? EllipsePath(x + w / 2, y + h / 2, w / 2, h / 2)
                    : RectPath(x, y, w, h);
                yield return new WmfPiece(d, Math.Abs(w * h), true);
                yield break;
            case MetaPolygon:
            case MetaPolyline:
            case MetaPolyPolygon:
                foreach (var piece in ReadPolygons(data, payload, func, orgX, orgY, extX, extY, labelW, labelH))
                    yield return piece;
                yield break;
        }
    }

    private static IEnumerable<WmfPiece> ReadPolygons(
        byte[] data, int payload, ushort func,
        int orgX, int orgY, int extX, int extY,
        float labelW, float labelH)
    {
        var p = payload;
        var polyPolygon = func == MetaPolyPolygon;
        int polyCount;
        int[] counts;
        if (polyPolygon)
        {
            if (p + 2 > data.Length) yield break;
            polyCount = BitConverter.ToInt16(data, p);
            p += 2;
            if (polyCount is < 1 or > 128 || p + polyCount * 2 > data.Length) yield break;
            counts = new int[polyCount];
            for (var i = 0; i < polyCount; i++)
            {
                counts[i] = BitConverter.ToInt16(data, p);
                p += 2;
            }
        }
        else
        {
            if (p + 2 > data.Length) yield break;
            polyCount = 1;
            counts = [BitConverter.ToInt16(data, p)];
            p += 2;
        }

        var closed = func != MetaPolyline;
        var combined = new StringBuilder();
        var minx = double.MaxValue;
        var miny = double.MaxValue;
        var maxx = double.MinValue;
        var maxy = double.MinValue;
        var any = false;

        for (var i = 0; i < polyCount; i++)
        {
            var n = counts[i];
            if (n is < 2 or > 4096 || p + n * 4 > data.Length) yield break;
            var pts = new (double X, double Y)[n];
            for (var k = 0; k < n; k++)
            {
                var wx = BitConverter.ToInt16(data, p);
                var wy = BitConverter.ToInt16(data, p + 2);
                p += 4;
                pts[k] = (MapX(wx, orgX, extX, labelW), MapY(wy, orgY, extY, labelH));
                minx = Math.Min(minx, wx);
                miny = Math.Min(miny, wy);
                maxx = Math.Max(maxx, wx);
                maxy = Math.Max(maxy, wy);
            }

            if (combined.Length > 0) combined.Append(' ');
            combined.Append(CultureInfo.InvariantCulture, $"M {F(pts[0].X)} {F(pts[0].Y)}");
            for (var k = 1; k < n; k++)
                combined.Append(CultureInfo.InvariantCulture, $" L {F(pts[k].X)} {F(pts[k].Y)}");
            if (closed) combined.Append(" Z");
            any = true;

            if (!polyPolygon)
            {
                yield return new WmfPiece(combined.ToString(), Math.Abs((maxx - minx) * (maxy - miny)), closed);
                yield break;
            }
        }

        if (any)
            yield return new WmfPiece(combined.ToString(), Math.Abs((maxx - minx) * (maxy - miny)), closed);
    }

    private static bool TryReadWindow(
        byte[] data, out int orgX, out int orgY, out int extX, out int extY, out int recordsStart)
    {
        orgX = orgY = 0;
        extX = extY = 0;
        recordsStart = 0;
        var i = 0;
        if (data.Length >= 22 && data[0] == 0xD7 && data[1] == 0xCD)
        {
            var left = BitConverter.ToInt16(data, 6);
            var top = BitConverter.ToInt16(data, 8);
            var right = BitConverter.ToInt16(data, 10);
            var bottom = BitConverter.ToInt16(data, 12);
            orgX = left;
            orgY = top;
            extX = right - left;
            extY = bottom - top;
            i = 22;
        }

        if (i + 18 > data.Length) return false;
        var headerWords = BitConverter.ToUInt16(data, i + 2);
        if (headerWords < 9) return false;
        i += headerWords * 2;
        recordsStart = i;
        var pos = i;
        while (pos + 6 <= data.Length)
        {
            var words = BitConverter.ToUInt32(data, pos);
            var func = BitConverter.ToUInt16(data, pos + 4);
            var bytes = (int)words * 2;
            if (bytes < 6 || pos + bytes > data.Length) break;
            if (func == MetaEof) break;
            if (func == MetaSetWindowOrg && bytes >= 10)
            {
                orgY = BitConverter.ToInt16(data, pos + 6);
                orgX = BitConverter.ToInt16(data, pos + 8);
            }
            else if (func == MetaSetWindowExt && bytes >= 10)
            {
                var ey = BitConverter.ToInt16(data, pos + 6);
                var ex = BitConverter.ToInt16(data, pos + 8);
                if (Math.Abs(ex) > 1 && Math.Abs(ey) > 1)
                {
                    extY = ey;
                    extX = ex;
                }
            }
            pos += bytes;
        }

        return extX != 0 && extY != 0;
    }

    private static double MapX(int x, int orgX, int extX, float labelW)
        => (x - orgX) / (double)extX * labelW;

    private static double MapY(int y, int orgY, int extY, float labelH)
        => (y - orgY) / (double)extY * labelH;

    private static string RectPath(double x, double y, double w, double h)
        => string.Create(CultureInfo.InvariantCulture,
            $"M {F(x)} {F(y)} H {F(x + w)} V {F(y + h)} H {F(x)} Z");

    private static string EllipsePath(double cx, double cy, double rx, double ry)
        => string.Create(CultureInfo.InvariantCulture,
            $"M {F(cx - rx)} {F(cy)} A {F(rx)} {F(ry)} 0 1 0 {F(cx + rx)} {F(cy)} " +
            $"A {F(rx)} {F(ry)} 0 1 0 {F(cx - rx)} {F(cy)} Z");

    private static string F(double v)
        => v.ToString("0.###", CultureInfo.InvariantCulture);
}
