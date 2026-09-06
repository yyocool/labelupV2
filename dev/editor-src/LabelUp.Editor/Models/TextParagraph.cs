namespace LabelUp.Editor.Models;

/// <summary>확장문자열 RTF의 한 문단. 정렬은 문단 속성이다.</summary>
public sealed class TextParagraph
{
    public string Align { get; set; } = "left";
    public List<TextSpan> Spans { get; set; } = [];

    public TextParagraph Clone() => new()
    {
        Align = Align,
        Spans = Spans.Select(s => s.Clone()).ToList()
    };
}

/// <summary>확장문자열 RTF의 한 서식 구간. 박스 전체가 아니라 글자 구간 속성이다.</summary>
public sealed class TextSpan
{
    public string Text { get; set; } = "";
    public string FontFamily { get; set; } = "Pretendard";
    public float FontSize { get; set; } = 3.5f;
    public string Fill { get; set; } = "#000000";
    public bool Bold { get; set; }
    public bool Italic { get; set; }
    public bool Underline { get; set; }
    public bool Strikeout { get; set; }

    public TextSpan Clone() => new()
    {
        Text = Text,
        FontFamily = FontFamily,
        FontSize = FontSize,
        Fill = Fill,
        Bold = Bold,
        Italic = Italic,
        Underline = Underline,
        Strikeout = Strikeout
    };

    public bool SameStyle(TextSpan other)
        => FontFamily == other.FontFamily
           && Math.Abs(FontSize - other.FontSize) < 0.01f
           && Fill == other.Fill
           && Bold == other.Bold
           && Italic == other.Italic
           && Underline == other.Underline
           && Strikeout == other.Strikeout;
}
