using System.Globalization;
using System.Text;
using System.Text.Json.Serialization;

namespace LabelUp.Editor.Models;

/// <summary>
/// 라벨 용지 규격. 애니라벨 용지 헤더(용지번호·용지크기·라벨크기·여백·간격·형상)를 기준으로 한 통일 JSON.
/// </summary>
public sealed class PaperSpec
{
    public int Version { get; set; } = 1;
    public string PaperNo { get; set; } = "LU-3230";
    public string Name { get; set; } = "A4 70×36 mm";
    public string Category { get; set; } = "A4";
    public string Brand { get; set; } = "LabelUp";

    public float PaperWidthMm { get; set; } = 210f;
    public float PaperHeightMm { get; set; } = 297f;
    public float LabelWidthMm { get; set; } = 70f;
    public float LabelHeightMm { get; set; } = 36f;

    public int Columns { get; set; } = 2;
    public int Rows { get; set; } = 7;

    public float LeftMarginMm { get; set; } = 32.5f;
    public float TopMarginMm { get; set; } = 13.5f;
    public float RightMarginMm { get; set; } = 32.5f;
    public float BottomMarginMm { get; set; } = 13.5f;
    public float HGapMm { get; set; } = 5f;
    public float VGapMm { get; set; } = 3f;

    public string LabelColor { get; set; } = "#FFFFFF";
    public PaperShape Shape { get; set; } = new();
    public string? DesignImageUrl { get; set; }

    [JsonIgnore]
    public int LabelsPerPage => Math.Max(1, Columns) * Math.Max(1, Rows);

    public PaperSpec Clone()
    {
        return new PaperSpec
        {
            Version = Version,
            PaperNo = PaperNo,
            Name = Name,
            Category = Category,
            Brand = Brand,
            PaperWidthMm = PaperWidthMm,
            PaperHeightMm = PaperHeightMm,
            LabelWidthMm = LabelWidthMm,
            LabelHeightMm = LabelHeightMm,
            Columns = Columns,
            Rows = Rows,
            LeftMarginMm = LeftMarginMm,
            TopMarginMm = TopMarginMm,
            RightMarginMm = RightMarginMm,
            BottomMarginMm = BottomMarginMm,
            HGapMm = HGapMm,
            VGapMm = VGapMm,
            LabelColor = LabelColor,
            Shape = Shape.Clone(),
            DesignImageUrl = DesignImageUrl
        };
    }

    public IEnumerable<LabelSlot> EnumerateSlots()
    {
        var cols = Math.Max(1, Columns);
        var rows = Math.Max(1, Rows);
        for (var r = 0; r < rows; r++)
        {
            for (var c = 0; c < cols; c++)
            {
                var x = LeftMarginMm + c * (LabelWidthMm + HGapMm);
                var y = TopMarginMm + r * (LabelHeightMm + VGapMm);
                yield return new LabelSlot(c, r, r * cols + c, x, y, LabelWidthMm, LabelHeightMm);
            }
        }
    }

    public string ToSheetSvg(float maxW = 220f)
    {
        var pw = Math.Max(1f, PaperWidthMm);
        var ph = Math.Max(1f, PaperHeightMm);
        var scale = maxW / pw;
        var w = pw * scale;
        var h = ph * scale;
        var sb = new StringBuilder();
        sb.Append(CultureInfo.InvariantCulture,
            $"<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {pw:0.###} {ph:0.###}' width='{w:0.##}' height='{h:0.##}'>");
        sb.Append("<rect x='0' y='0' width='100%' height='100%' fill='#f7f4f1' stroke='#d9cfc0' stroke-width='0.4'/>");
        foreach (var slot in EnumerateSlots())
            sb.Append(Shape.ToPreviewSvg(slot.X, slot.Y, slot.W, slot.H, LabelColor));
        sb.Append("</svg>");
        return sb.ToString();
    }

    public static PaperSpec CreateDefault() => BuiltInPapers.Lu3230();

    public void RecalcMarginsFromGaps()
    {
        var cols = Math.Max(1, Columns);
        var rows = Math.Max(1, Rows);
        var usedW = LabelWidthMm * cols + HGapMm * Math.Max(0, cols - 1);
        var usedH = LabelHeightMm * rows + VGapMm * Math.Max(0, rows - 1);
        var remainW = Math.Max(0, PaperWidthMm - usedW);
        var remainH = Math.Max(0, PaperHeightMm - usedH);
        LeftMarginMm = remainW / 2f;
        RightMarginMm = remainW / 2f;
        TopMarginMm = remainH / 2f;
        BottomMarginMm = remainH / 2f;
    }
}

public sealed class PaperShape
{
    /// <summary>rect | roundrect | ellipse | svg</summary>
    public string Kind { get; set; } = "rect";
    public float CornerRadiusMm { get; set; } = 1.2f;
    public string? Svg { get; set; }
    public PaperHole? Hole { get; set; }

    public PaperShape Clone() => new()
    {
        Kind = Kind,
        CornerRadiusMm = CornerRadiusMm,
        Svg = Svg,
        Hole = Hole is null ? null : new PaperHole
        {
            X = Hole.X,
            Y = Hole.Y,
            Width = Hole.Width,
            Height = Hole.Height
        }
    };

    public string ToPreviewSvg(float x, float y, float w, float h, string fill)
    {
        var fillEsc = string.IsNullOrWhiteSpace(fill) ? "#fff" : fill;
        var stroke = "#c4b8aa";
        return Kind switch
        {
            "ellipse" or "circle" =>
                $"<ellipse cx='{x + w / 2f}' cy='{y + h / 2f}' rx='{w / 2f}' ry='{h / 2f}' fill='{fillEsc}' stroke='{stroke}' stroke-width='0.25'/>",
            "roundrect" =>
                $"<rect x='{x}' y='{y}' width='{w}' height='{h}' rx='{CornerRadiusMm}' ry='{CornerRadiusMm}' fill='{fillEsc}' stroke='{stroke}' stroke-width='0.25'/>",
            "svg" when !string.IsNullOrWhiteSpace(Svg) =>
                $"<g transform='translate({x.ToString("0.###", CultureInfo.InvariantCulture)},{y.ToString("0.###", CultureInfo.InvariantCulture)})'>{WrapShapeSvg(Svg!, w, h, fillEsc)}</g>",
            _ =>
                $"<rect x='{x}' y='{y}' width='{w}' height='{h}' rx='0.6' ry='0.6' fill='{fillEsc}' stroke='{stroke}' stroke-width='0.25'/>"
        };
    }

    private static string WrapShapeSvg(string svg, float w, float h, string fill)
    {
        if (svg.Contains("<svg", StringComparison.OrdinalIgnoreCase))
            return svg;
        return $"<svg viewBox='0 0 {w.ToString("0.###", CultureInfo.InvariantCulture)} {h.ToString("0.###", CultureInfo.InvariantCulture)}' width='{w.ToString("0.###", CultureInfo.InvariantCulture)}' height='{h.ToString("0.###", CultureInfo.InvariantCulture)}'><path d='{svg}' fill='{fill}' stroke='#c4b8aa' stroke-width='0.25'/></svg>";
    }
}

public sealed class PaperHole
{
    public float X { get; set; }
    public float Y { get; set; }
    public float Width { get; set; }
    public float Height { get; set; }
}

public readonly record struct LabelSlot(int Col, int Row, int Index, float X, float Y, float W, float H);

public static class BuiltInPapers
{
    public static PaperSpec Lu3230()
    {
        var p = new PaperSpec
        {
            PaperNo = "LU-3230",
            Name = "A4 70×36 mm 14칸",
            Category = "A4",
            LabelWidthMm = 70f,
            LabelHeightMm = 36f,
            Columns = 2,
            Rows = 7,
            HGapMm = 5f,
            VGapMm = 3f,
            Shape = new PaperShape { Kind = "roundrect", CornerRadiusMm = 1.5f }
        };
        p.RecalcMarginsFromGaps();
        return p;
    }

    public static PaperSpec Lu3630()
    {
        return new PaperSpec
        {
            PaperNo = "LU-3630",
            Name = "A4 원형 40 mm 24칸",
            Category = "A4",
            LabelWidthMm = 40f,
            LabelHeightMm = 40f,
            Columns = 4,
            Rows = 6,
            LeftMarginMm = 16f,
            RightMarginMm = 16f,
            TopMarginMm = 13.5f,
            BottomMarginMm = 13.5f,
            HGapMm = 6f,
            VGapMm = 6f,
            Shape = new PaperShape { Kind = "ellipse" }
        };
    }

    public static PaperSpec Lu3775()
    {
        return new PaperSpec
        {
            PaperNo = "LU-3775",
            Name = "A4 84×58 mm 타공 8칸",
            Category = "A4",
            LabelWidthMm = 84f,
            LabelHeightMm = 58f,
            Columns = 2,
            Rows = 4,
            LeftMarginMm = 14f,
            RightMarginMm = 14f,
            TopMarginMm = 17.5f,
            BottomMarginMm = 17.5f,
            HGapMm = 14f,
            VGapMm = 10f,
            Shape = new PaperShape
            {
                Kind = "roundrect",
                CornerRadiusMm = 2f,
                Hole = new PaperHole { X = 30.5f, Y = 17.5f, Width = 23f, Height = 23f }
            }
        };
    }

    public static PaperSpec Lu3659()
    {
        var p = new PaperSpec
        {
            PaperNo = "LU-3659",
            Name = "A4 50×30 mm 21칸",
            Category = "A4",
            LabelWidthMm = 50f,
            LabelHeightMm = 30f,
            Columns = 3,
            Rows = 7,
            HGapMm = 5f,
            VGapMm = 4f,
            Shape = new PaperShape { Kind = "roundrect", CornerRadiusMm = 1.2f }
        };
        p.RecalcMarginsFromGaps();
        return p;
    }

    public static PaperSpec Lu3102()
    {
        var p = new PaperSpec
        {
            PaperNo = "LU-3102",
            Name = "A4 100×50 mm 10칸",
            Category = "A4",
            LabelWidthMm = 100f,
            LabelHeightMm = 50f,
            Columns = 2,
            Rows = 5,
            HGapMm = 4f,
            VGapMm = 4f,
            Shape = new PaperShape { Kind = "rect" }
        };
        p.RecalcMarginsFromGaps();
        return p;
    }

    public static PaperSpec LuHeart()
    {
        var p = new PaperSpec
        {
            PaperNo = "LU-H100",
            Name = "A4 하트 25×20 mm",
            Category = "A4",
            LabelWidthMm = 25f,
            LabelHeightMm = 20f,
            Columns = 6,
            Rows = 10,
            HGapMm = 3f,
            VGapMm = 3f,
            Shape = new PaperShape
            {
                Kind = "svg",
                Svg = SvgLibrary.HeartPath
            }
        };
        p.RecalcMarginsFromGaps();
        return p;
    }

    public static IReadOnlyList<PaperSpec> All() =>
    [
        Lu3230(),
        Lu3659(),
        Lu3630(),
        Lu3102(),
        Lu3775(),
        LuHeart()
    ];
}
