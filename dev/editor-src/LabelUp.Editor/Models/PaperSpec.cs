using System.Globalization;
using System.Linq;
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
    /// <summary>WMF 용지 모양이 없을 때 사용자에게 보여줄 안내.</summary>
    public string? ShapeWarning { get; set; }
    /// <summary>
    /// DB 규격값이 서로 어긋날 때(여백+라벨이 용지보다 크거나 칸수가 안 맞을 때) 보여줄 경고.
    /// 문서에 저장할 값이 아니라 선택 시점에만 쓰는 안내라 직렬화하지 않는다.
    /// </summary>
    [JsonIgnore]
    public string? LayoutIssue { get; set; }
    public PaperShape Shape { get; set; } = new();
    public string? DesignImageUrl { get; set; }
    /// <summary>불규칙 용지. 있으면 격자 대신 이 좌표를 쓴다 (X/Y/H/W 순으로 저장된 배열).</summary>
    public List<LabelSlot>? CustomSlots { get; set; }

    /// <summary>
    /// 편집 칸에 속하지 않는 칼선(Path d, 좌표는 시트 mm). '맞춤 일반'에서 편집 영역 밖에
    /// 남는 바깥 칼선이 여기 들어간다. 칼선 인쇄를 켰을 때만 그린다.
    /// </summary>
    public List<string>? SheetCutPaths { get; set; }

    [JsonIgnore]
    public int LabelsPerPage => CustomSlots is { Count: > 0 }
        ? CustomSlots.Count
        : Math.Max(1, Columns) * Math.Max(1, Rows);

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
            ShapeWarning = ShapeWarning,
            LayoutIssue = LayoutIssue,
            Shape = Shape.Clone(),
            DesignImageUrl = DesignImageUrl,
            CustomSlots = CustomSlots is { Count: > 0 }
                ? CustomSlots.Select(s => s with { Shape = s.Shape?.Clone() }).ToList()
                : null,
            SheetCutPaths = SheetCutPaths is { Count: > 0 } ? [.. SheetCutPaths] : null
        };
    }

    public PaperShape ShapeFor(int cellIndex)
    {
        if (CustomSlots is { Count: > 0 })
        {
            var i = Math.Clamp(cellIndex, 0, CustomSlots.Count - 1);
            if (CustomSlots[i].Shape is { } slotShape)
                return slotShape;
        }
        return Shape;
    }

    public PaperShape ShapeFor(LabelSlot slot) => slot.Shape ?? Shape;

    public IEnumerable<LabelSlot> EnumerateSlots()
        => CustomSlots is { Count: > 0 } ? CustomSlots : EnumerateGridSlots();

    private IEnumerable<LabelSlot> EnumerateGridSlots()
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
            sb.Append(ShapeFor(slot).ToPreviewSvg(slot.X, slot.Y, slot.W, slot.H, LabelColor));
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
    /// <summary>세로 모서리 반경. 없으면 <see cref="CornerRadiusMm"/>과 같은 정원 모서리.</summary>
    public float? CornerRadiusYMm { get; set; }
    /// <summary>
    /// 원형 라벨의 반지름(mm). 규격에 값이 적혀 있을 때만 채운다. 채워져 있으면 칸 가운데에
    /// 이 반지름의 정원을 그린다. 비어 있으면 종전처럼 칸에 내접하는 타원을 그린다.
    /// 칸이 정사각이 아닌 원형 라벨에서 타원을 그리면 실제 타공과 어긋나기 때문이다.
    /// </summary>
    public float? CircleRadiusMm { get; set; }
    public string? Svg { get; set; }
    /// <summary>구버전: 가이드를 한 path 문자열로 둔 경우(선만).</summary>
    public string? GuideSvg { get; set; }
    /// <summary>WMF 가이드(채운 글자·그림자·내부 선). 좌표는 라벨 mm.</summary>
    public List<PaperGuidePath>? Guides { get; set; }
    /// <summary>
    /// <see cref="Guides"/>에 외곽 조각까지 들어 있으면 true. 규격 SVG를 통째로 풀어 넣은
    /// 경우가 그렇다. 이때 외곽은 가이드가 제 선 굵기·색으로 이미 그리므로, 그리는 쪽이
    /// 칼선용 기본 테두리를 한 번 더 덧그리지 않아야 선이 두 겹으로 굵어지지 않는다.
    /// WMF 용지는 외곽을 <see cref="Svg"/>에만 두므로 false 그대로다.
    /// </summary>
    public bool GuidesIncludeOutline { get; set; }
    /// <summary>Svg/GuideSvg 좌표가 이미 라벨 mm이면 true. 하트 등 0–100 path는 false.</summary>
    public bool SvgIsLabelMm { get; set; }
    public PaperHole? Hole { get; set; }

    [JsonIgnore]
    public bool HasGuides =>
        Guides is { Count: > 0 } || !string.IsNullOrWhiteSpace(GuideSvg);

    [JsonIgnore]
    public float RadiusYMm => CornerRadiusYMm ?? CornerRadiusMm;

    /// <summary>
    /// 칸 안에 그릴 원의 반지름(mm). 규격에 적힌 값을 칸 밖으로 넘지 않게 줄여 돌려준다.
    /// 적힌 값이 없으면 null이고, 그때는 칸에 내접하는 타원을 그려야 한다.
    /// </summary>
    public float? CircleRadiusFor(float w, float h)
        => CircleRadiusMm is { } r && r > 0f ? Math.Min(r, Math.Min(w, h) / 2f) : null;

    public PaperShape Clone() => new()
    {
        Kind = Kind,
        CornerRadiusMm = CornerRadiusMm,
        CornerRadiusYMm = CornerRadiusYMm,
        CircleRadiusMm = CircleRadiusMm,
        Svg = Svg,
        GuideSvg = GuideSvg,
        Guides = Guides?.Select(g => g.Clone()).ToList(),
        GuidesIncludeOutline = GuidesIncludeOutline,
        SvgIsLabelMm = SvgIsLabelMm,
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
        var outer = Kind switch
        {
            "ellipse" or "circle" => EllipseSvg(x, y, w, h, fillEsc, stroke),
            "roundrect" =>
                $"<rect x='{x}' y='{y}' width='{w}' height='{h}' rx='{CornerRadiusMm}' ry='{RadiusYMm}' fill='{fillEsc}' stroke='{stroke}' stroke-width='0.25'/>",
            "svg" when !string.IsNullOrWhiteSpace(Svg) =>
                $"<g transform='translate({x.ToString("0.###", CultureInfo.InvariantCulture)},{y.ToString("0.###", CultureInfo.InvariantCulture)})'>{WrapShapeSvg(this, w, h, fillEsc)}{WrapGuides(this, w, h)}</g>",
            _ =>
                $"<rect x='{x}' y='{y}' width='{w}' height='{h}' rx='0.6' ry='0.6' fill='{fillEsc}' stroke='{stroke}' stroke-width='0.25'/>"
        };
        if (Hole is not { Width: > 0, Height: > 0 } hole)
            return outer;
        var hx = (x + hole.X + hole.Width / 2f).ToString("0.###", CultureInfo.InvariantCulture);
        var hy = (y + hole.Y + hole.Height / 2f).ToString("0.###", CultureInfo.InvariantCulture);
        var rx = (hole.Width / 2f).ToString("0.###", CultureInfo.InvariantCulture);
        var ry = (hole.Height / 2f).ToString("0.###", CultureInfo.InvariantCulture);
        return $"<g>{outer}<ellipse cx='{hx}' cy='{hy}' rx='{rx}' ry='{ry}' fill='#d8d2cc' stroke='{stroke}' stroke-width='0.2'/></g>";
    }

    /// <summary>원형 라벨 한 칸. 규격에 반지름이 있으면 칸 가운데의 정원을, 없으면 내접 타원을 그린다.</summary>
    private string EllipseSvg(float x, float y, float w, float h, string fill, string stroke)
    {
        var inv = CultureInfo.InvariantCulture;
        var r = CircleRadiusFor(w, h);
        var rx = r ?? w / 2f;
        var ry = r ?? h / 2f;
        return $"<ellipse cx='{(x + w / 2f).ToString("0.###", inv)}' cy='{(y + h / 2f).ToString("0.###", inv)}'"
               + $" rx='{rx.ToString("0.###", inv)}' ry='{ry.ToString("0.###", inv)}'"
               + $" fill='{fill}' stroke='{stroke}' stroke-width='0.25'/>";
    }

    /// <summary>
    /// 미리보기용 외곽 한 장. 가이드가 외곽까지 그리는 용지는 바탕만 깔고 선은 생략한다.
    /// 그러지 않으면 같은 자리에 선이 두 겹으로 그려진다.
    /// 도넛 칸처럼 구멍이 달려 있으면 구멍 Path를 같이 넣고 홀짝 규칙으로 뚫어 보여 준다.
    /// </summary>
    private static string WrapShapeSvg(PaperShape shape, float w, float h, string fill)
    {
        var svg = shape.Svg ?? "";
        if (svg.Contains("<svg", StringComparison.OrdinalIgnoreCase))
            return svg;

        var inv = CultureInfo.InvariantCulture;
        var edge = shape.GuidesIncludeOutline ? "none" : "#2E2A27";
        var rule = "";
        if (shape.Guides is { Count: > 0 })
        {
            var sb = new StringBuilder(svg);
            foreach (var g in shape.Guides)
            {
                if (!g.IsHole || string.IsNullOrWhiteSpace(g.D)) continue;
                sb.Append(' ').Append(g.D);
                rule = " fill-rule='evenodd'";
            }
            svg = sb.ToString();
        }

        return $"<svg viewBox='0 0 {w.ToString("0.###", inv)} {h.ToString("0.###", inv)}' width='{w.ToString("0.###", inv)}' height='{h.ToString("0.###", inv)}'><path d='{svg}' fill='{fill}' stroke='{edge}' stroke-width='0.25'{rule}/></svg>";
    }

    private static string WrapGuides(PaperShape shape, float w, float h)
    {
        var inv = CultureInfo.InvariantCulture;
        var inner = new StringBuilder();
        if (shape.Guides is { Count: > 0 })
        {
            foreach (var g in shape.Guides)
            {
                if (g.IsHole || string.IsNullOrWhiteSpace(g.D)) continue;
                var fill = string.IsNullOrWhiteSpace(g.Fill) ? "none" : g.Fill;
                var stroke = string.IsNullOrWhiteSpace(g.Stroke) ? "none" : g.Stroke;
                var sw = g.StrokeWidthMm.ToString("0.###", inv);
                var rule = g.EvenOdd ? " fill-rule='evenodd'" : "";
                inner.Append(inv,
                    $"<path d='{g.D}' fill='{fill}' stroke='{stroke}' stroke-width='{sw}' stroke-linejoin='round'{rule}/>");
            }
        }
        else if (!string.IsNullOrWhiteSpace(shape.GuideSvg))
        {
            inner.Append(inv,
                $"<path d='{shape.GuideSvg}' fill='none' stroke='#2E2A27' stroke-width='0.28' stroke-linejoin='round'/>");
        }

        if (inner.Length == 0) return "";
        return string.Create(inv,
            $"<svg viewBox='0 0 {w:0.###} {h:0.###}' width='{w:0.###}' height='{h:0.###}'>{inner}</svg>");
    }
}

public sealed class PaperGuidePath
{
    public string D { get; set; } = "";
    public string? Fill { get; set; }
    public string? Stroke { get; set; }
    public float StrokeWidthMm { get; set; } = 0.28f;
    public bool EvenOdd { get; set; }
    /// <summary>외곽보다 작은 채움. 라벨에서 빼는 구멍(가운데 타공).</summary>
    public bool IsHole { get; set; }

    public PaperGuidePath Clone() => new()
    {
        D = D,
        Fill = Fill,
        Stroke = Stroke,
        StrokeWidthMm = StrokeWidthMm,
        EvenOdd = EvenOdd,
        IsHole = IsHole
    };
}

public sealed class PaperHole
{
    public float X { get; set; }
    public float Y { get; set; }
    public float Width { get; set; }
    public float Height { get; set; }
}

public readonly record struct LabelSlot(
    int Col, int Row, int Index, float X, float Y, float W, float H, PaperShape? Shape = null);

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
