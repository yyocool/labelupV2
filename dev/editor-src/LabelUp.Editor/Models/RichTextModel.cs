using System.Globalization;
using System.Net;
using System.Text;
using System.Text.Json.Serialization;
using LabelUp.Editor.Rendering;
using LabelUp.Editor.Services;

namespace LabelUp.Editor.Models;

/// <summary>
/// 일반텍스트의 구간 서식. 박스 전체가 아니라 글자 구간마다 글꼴·기울기·색을 가진다.
/// </summary>
public static class RichTextModel
{
    private static readonly CultureInfo Inv = CultureInfo.InvariantCulture;

    public static bool UsesRich(DesignObject obj)
        => obj.Type == ObjectType.Text && TextModes.IsPlain(obj.TextMode);

    public static void Ensure(DesignObject obj)
    {
        if (obj.RichText is { Count: > 0 })
        {
            Merge(obj.RichText);
            SyncPlain(obj);
            return;
        }

        obj.RichText = FromPlain(obj.Text, obj);
        SyncPlain(obj);
    }

    public static List<TextParagraph> FromPlain(string? text, DesignObject style)
    {
        var lines = (text ?? "").Replace("\r\n", "\n").Replace('\r', '\n').Split('\n');
        if (lines.Length == 0)
            lines = [""];
        var align = string.IsNullOrWhiteSpace(style.TextAlign) ? "left" : style.TextAlign;
        return lines.Select(line => new TextParagraph
        {
            Align = align,
            Spans = [SpanFromBox(style, line)]
        }).ToList();
    }

    public static TextSpan SpanFromBox(DesignObject style, string text)
        => new()
        {
            Text = text,
            FontFamily = string.IsNullOrWhiteSpace(style.FontFamily) ? "Pretendard" : style.FontFamily,
            FontSize = style.FontSize <= 0 ? 5f : style.FontSize,
            Fill = string.IsNullOrWhiteSpace(style.Fill) || ColorUtil.IsTransparent(style.Fill)
                ? "#2E2A27"
                : style.Fill,
            Bold = style.Bold,
            Italic = style.Italic,
            Underline = style.Underline,
            Strikeout = style.Strikeout
        };

    public static string Plain(IEnumerable<TextParagraph> paragraphs)
        => string.Join('\n', paragraphs.Select(p => string.Concat(p.Spans.Select(s => s.Text ?? ""))));

    public static void SyncPlain(DesignObject obj)
    {
        if (obj.RichText is { Count: > 0 })
            obj.Text = Plain(obj.RichText);
    }

    public static void SyncBoxFromFirst(DesignObject obj)
    {
        var span = obj.RichText?.SelectMany(p => p.Spans).FirstOrDefault();
        if (span is null) return;
        obj.FontFamily = span.FontFamily;
        obj.FontSize = span.FontSize;
        if (!ColorUtil.IsTransparent(span.Fill))
            obj.Fill = span.Fill;
        obj.Bold = span.Bold;
        obj.Italic = span.Italic;
        obj.Underline = span.Underline;
        obj.Strikeout = span.Strikeout;
        if (obj.RichText is { Count: > 0 } && !string.IsNullOrWhiteSpace(obj.RichText[0].Align))
            obj.TextAlign = obj.RichText[0].Align;
    }

    public static void ApplyAllSpans(DesignObject obj, Action<TextSpan> apply)
    {
        Ensure(obj);
        foreach (var para in obj.RichText!)
        {
            foreach (var span in para.Spans)
                apply(span);
        }

        Merge(obj.RichText);
        SyncPlain(obj);
        SyncBoxFromFirst(obj);
    }

    public static void ApplyAlign(DesignObject obj, string align)
    {
        Ensure(obj);
        foreach (var para in obj.RichText!)
            para.Align = align;
        obj.TextAlign = align;
    }

    public static void Merge(List<TextParagraph> paragraphs)
    {
        foreach (var para in paragraphs)
        {
            var merged = new List<TextSpan>();
            foreach (var span in para.Spans)
            {
                if (merged.Count > 0 && merged[^1].SameStyle(span))
                    merged[^1].Text += span.Text;
                else
                    merged.Add(span.Clone());
            }

            para.Spans = merged;
        }
    }

    public static void ReplaceFromDto(DesignObject obj, RichTextDto dto)
    {
        var fallback = ColorUtil.ToHtmlColor(obj.Fill, "#2e2a27");
        obj.RichText = (dto.Paragraphs ?? [])
            .Select(p => new TextParagraph
            {
                Align = NormalizeAlign(p.Align),
                Spans = (p.Spans ?? []).Select(s => new TextSpan
                {
                    Text = s.Text ?? "",
                    FontFamily = FontCatalog.CanonicalId(s.FontFamily),
                    FontSize = FontCatalog.FromPt(s.FontSizePt > 0 ? s.FontSizePt : 12f),
                    Fill = ColorUtil.ToHtmlColor(s.Fill, fallback),
                    Bold = s.Bold,
                    Italic = s.Italic,
                    Underline = s.Underline,
                    Strikeout = s.Strikeout
                }).ToList()
            })
            .ToList();
        if (obj.RichText.Count == 0)
            obj.RichText = FromPlain(obj.Text, obj);
        Merge(obj.RichText);
        SyncPlain(obj);
        SyncBoxFromFirst(obj);
    }

    public static string ToHtml(DesignObject obj)
    {
        Ensure(obj);
        var sb = new StringBuilder();
        foreach (var para in obj.RichText!)
        {
            sb.Append("<div style=\"text-align:")
              .Append(WebUtility.HtmlEncode(NormalizeAlign(para.Align)))
              .Append("\">");
            if (para.Spans.Count == 0)
                sb.Append("<br>");
            foreach (var span in para.Spans)
            {
                var pt = FontCatalog.ToPt(span.FontSize).ToString("0.##", Inv);
                var color = ColorUtil.ToHtmlColor(span.Fill, "#2e2a27");
                var deco = span.Underline && span.Strikeout ? "underline line-through"
                    : span.Underline ? "underline"
                    : span.Strikeout ? "line-through"
                    : "none";
                sb.Append("<span style=\"font-family:")
                  .Append(CssFont(span.FontFamily))
                  .Append(";font-size:").Append(pt).Append("pt;color:").Append(color)
                  .Append(";font-weight:").Append(span.Bold ? "700" : "400")
                  .Append(";font-style:").Append(span.Italic ? "italic" : "normal")
                  .Append(";text-decoration:").Append(deco)
                  .Append("\">")
                  .Append(WebUtility.HtmlEncode(span.Text ?? "").Replace(" ", "&nbsp;"))
                  .Append("</span>");
            }

            sb.Append("</div>");
        }

        return sb.ToString();
    }

    public static IEnumerable<string> Families(DesignObject obj)
    {
        if (obj.RichText is { Count: > 0 })
        {
            foreach (var fam in obj.RichText.SelectMany(p => p.Spans).Select(s => s.FontFamily))
            {
                if (!string.IsNullOrWhiteSpace(fam))
                    yield return fam;
            }
        }

        if (!string.IsNullOrWhiteSpace(obj.FontFamily))
            yield return obj.FontFamily;
    }

    private static string NormalizeAlign(string? align)
        => align switch
        {
            "center" or "right" or "justify" => align,
            _ => "left"
        };

    private static string CssFont(string? family)
    {
        var id = FontCatalog.CanonicalId(family);
        return id.Contains(' ', StringComparison.Ordinal) ? "'" + id.Replace("'", "") + "'" : id;
    }
}

public sealed class RichTextDto
{
    public List<RichParaDto> Paragraphs { get; set; } = [];
}

public sealed class RichParaDto
{
    public string Align { get; set; } = "left";
    public List<RichSpanDto> Spans { get; set; } = [];
}

public sealed class RichSpanDto
{
    public string Text { get; set; } = "";
    public string FontFamily { get; set; } = "Pretendard";
    public float FontSizePt { get; set; } = 12;
    public string Fill { get; set; } = "#2e2a27";
    public bool Bold { get; set; }
    public bool Italic { get; set; }
    public bool Underline { get; set; }
    public bool Strikeout { get; set; }
}

public sealed class RichCaretStyle
{
    public string FontFamily { get; set; } = "Pretendard";
    public float FontSizePt { get; set; } = 12;
    public string Fill { get; set; } = "#2e2a27";
    public bool Bold { get; set; }
    public bool Italic { get; set; }
    public bool Underline { get; set; }
    public bool Strikeout { get; set; }
    public string Align { get; set; } = "left";

    [JsonIgnore]
    public float FontSizeMm => FontCatalog.FromPt(FontSizePt > 0 ? FontSizePt : 12f);
}

public static class TextModes
{
    public static bool IsPlain(TextMode mode) => mode is TextMode.Normal or TextMode.Extended;

    public static void Unify(DesignObject obj)
    {
        if (obj.Type != ObjectType.Text) return;
        if (obj.TextMode == TextMode.Extended)
            obj.TextMode = TextMode.Normal;
        if (IsPlain(obj.TextMode) && obj.RichText is { Count: > 0 })
            RichTextModel.SyncPlain(obj);
    }
}
