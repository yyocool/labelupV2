namespace LabelUp.Editor.Models;

/// <summary>클립아트 WMF/복합 SVG의 한 조각. viewBox 0 0 100 100.</summary>
public sealed class SvgPart
{
    public string D { get; set; } = "";
    public string? Fill { get; set; }
    public string? Stroke { get; set; }
    public float StrokeWidth { get; set; }

    public SvgPart Clone() => new()
    {
        D = D,
        Fill = Fill,
        Stroke = Stroke,
        StrokeWidth = StrokeWidth
    };
}
