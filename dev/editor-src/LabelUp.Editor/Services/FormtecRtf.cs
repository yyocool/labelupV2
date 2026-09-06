using System.Globalization;
using System.Text;
using LabelUp.Editor.Models;

namespace LabelUp.Editor.Services;

/// <summary>
/// 폼텍 확장문자열 RTF. md_formtec/DGZ_확장문자열_최종분석.md
/// 글자별 폰트·크기·색, 문단별 정렬. 박스 전체 속성이 아니다.
/// </summary>
internal static class FormtecRtf
{
    public static bool TryParse(byte[] data, int offset, int length, out List<TextParagraph> paragraphs, out string plain)
    {
        paragraphs = [];
        plain = "";
        if (offset < 0 || length < 6 || offset + length > data.Length) return false;
        var src = Encoding.Latin1.GetString(data, offset, length).TrimEnd('\0');
        if (!src.StartsWith("{\\rtf", StringComparison.OrdinalIgnoreCase)) return false;

        var fonts = ParseFontTable(src);
        var colors = ParseColorTable(src);
        paragraphs = ParseBody(src, fonts, colors);
        var sb = new StringBuilder();
        for (var i = 0; i < paragraphs.Count; i++)
        {
            if (i > 0) sb.Append('\n');
            foreach (var span in paragraphs[i].Spans)
                sb.Append(span.Text);
        }
        plain = sb.ToString().TrimEnd('\r', '\n');
        return true;
    }

    private static Dictionary<int, string> ParseFontTable(string src)
    {
        var fonts = new Dictionary<int, string>();
        var start = IndexOfControl(src, "fonttbl");
        if (start < 0) return fonts;
        var group = start;
        while (group > 0 && src[group] != '{') group--;
        var end = SkipGroup(src, group);
        var i = start;
        while (i < end)
        {
            if (src[i] == '{' && i + 3 < end && src[i + 1] == '\\' && src[i + 2] == 'f' && char.IsDigit(src[i + 3]))
            {
                var innerEnd = SkipGroup(src, i);
                var inner = src[(i + 1)..innerEnd];
                if (TryReadIntAfter(inner, "\\f", out var id))
                {
                    var name = DecodeEmbedded(inner);
                    var cut = name.LastIndexOf(';');
                    if (cut >= 0) name = name[..cut];
                    name = name.Trim();
                    if (name.Length > 0)
                        fonts[id] = FontCatalog.CanonicalId(name);
                }
                i = innerEnd + 1;
                continue;
            }
            i++;
        }
        return fonts;
    }

    private static List<string> ParseColorTable(string src)
    {
        var colors = new List<string> { "#000000" };
        var start = IndexOfControl(src, "colortbl");
        if (start < 0) return colors;
        var group = start;
        while (group > 0 && src[group] != '{') group--;
        var end = SkipGroup(src, group);
        var body = src[start..end];
        foreach (var part in body.Split(';', StringSplitOptions.None))
        {
            var rm = System.Text.RegularExpressions.Regex.Match(
                part, @"\\red(\d+)\\green(\d+)\\blue(\d+)");
            if (!rm.Success) continue;
            colors.Add(
                $"#{byte.Parse(rm.Groups[1].Value):x2}{byte.Parse(rm.Groups[2].Value):x2}{byte.Parse(rm.Groups[3].Value):x2}");
        }
        return colors;
    }

    private static List<TextParagraph> ParseBody(string src, Dictionary<int, string> fonts, List<string> colors)
    {
        var style = new RunStyle();
        if (fonts.TryGetValue(0, out var defFont))
            style.Family = defFont;

        var paragraphs = new List<TextParagraph>();
        var current = new TextParagraph { Align = "left" };
        var hex = new List<byte>();
        var stack = new Stack<RunStyle>();

        void FlushHex()
        {
            if (hex.Count == 0) return;
            try
            {
                Emit(Encoding.GetEncoding(949).GetString(hex.ToArray()), style, current);
            }
            catch { /* ignore */ }
            hex.Clear();
        }

        void NewParagraph()
        {
            FlushHex();
            paragraphs.Add(current);
            current = new TextParagraph { Align = style.Align };
        }

        var i = 0;
        var len = src.Length;
        while (i < len)
        {
            var ch = src[i];
            if (ch == '{')
            {
                FlushHex();
                if (IsStarDest(src, i) || IsNamedGroup(src, i, "fonttbl") || IsNamedGroup(src, i, "colortbl")
                    || IsNamedGroup(src, i, "stylesheet") || IsNamedGroup(src, i, "info"))
                {
                    i = SkipGroup(src, i) + 1;
                    continue;
                }
                stack.Push(style.Clone());
                i++;
                continue;
            }

            if (ch == '}')
            {
                FlushHex();
                if (stack.Count > 0) style = stack.Pop();
                i++;
                continue;
            }

            if (ch == '\\' && i + 1 < len)
            {
                var next = src[i + 1];
                if (next is '\\' or '{' or '}')
                {
                    FlushHex();
                    Emit(next.ToString(), style, current);
                    i += 2;
                    continue;
                }

                if (next == '\'' && i + 3 < len
                    && int.TryParse(src.AsSpan(i + 2, 2), NumberStyles.HexNumber, null, out var b))
                {
                    hex.Add((byte)b);
                    i += 4;
                    continue;
                }

                FlushHex();
                i++;
                var word = ReadWord(src, ref i, out var arg, out var dest);
                if (dest)
                {
                    if (i < len && src[i] == ' ') i++;
                    continue;
                }

                switch (word)
                {
                    case "par":
                    case "line":
                        NewParagraph();
                        break;
                    case "pard":
                        style.Align = "left";
                        current.Align = "left";
                        break;
                    case "ql":
                        style.Align = current.Align = "left";
                        break;
                    case "qc":
                        style.Align = current.Align = "center";
                        break;
                    case "qr":
                        style.Align = current.Align = "right";
                        break;
                    case "qj":
                        style.Align = current.Align = "justify";
                        break;
                    case "f" when arg is >= 0:
                        style.Family = fonts.TryGetValue(arg, out var fam) ? fam : style.Family;
                        break;
                    case "fs" when arg is > 0:
                        style.SizeMm = FontCatalog.FromPt(Math.Max(1, arg / 2f));
                        break;
                    case "cf" when arg is >= 0:
                        style.Fill = arg < colors.Count ? colors[arg] : style.Fill;
                        break;
                    case "b":
                        style.Bold = arg != 0;
                        break;
                    case "i":
                        style.Italic = arg != 0;
                        break;
                    case "ul":
                    case "ulc":
                        style.Underline = arg != 0;
                        break;
                    case "ulnone":
                        style.Underline = false;
                        break;
                    case "strike":
                    case "striked":
                        style.Strikeout = arg != 0;
                        break;
                    case "u":
                        var code = arg;
                        if (code < 0) code += 65536;
                        if (code is > 0 and < 0x110000 && !FormtecRecords.IsInvisibleFormat(code))
                            Emit(char.ConvertFromUtf32(code), style, current);
                        if (i < len && src[i] == '?') i++;
                        break;
                    case "tab":
                        Emit(" ", style, current);
                        break;
                    case "fonttbl":
                    case "colortbl":
                    case "stylesheet":
                    case "generator":
                    case "info":
                        break;
                }

                if (i < len && src[i] == ' ') i++;
                continue;
            }

            if (ch is '\r' or '\n')
            {
                i++;
                continue;
            }

            FlushHex();
            if (!FormtecRecords.IsInvisibleFormat(ch))
                Emit(ch.ToString(), style, current);
            i++;
        }

        FlushHex();
        if (current.Spans.Count > 0 || paragraphs.Count == 0)
            paragraphs.Add(current);
        while (paragraphs.Count > 1
               && paragraphs[^1].Spans.Count == 0)
            paragraphs.RemoveAt(paragraphs.Count - 1);
        return paragraphs;
    }

    private static void Emit(string text, RunStyle style, TextParagraph para)
    {
        if (string.IsNullOrEmpty(text)) return;
        if (para.Spans.Count > 0 && para.Spans[^1].SameStyle(style.ToSpan("")))
        {
            para.Spans[^1].Text += text;
            return;
        }
        para.Spans.Add(style.ToSpan(text));
        if (para.Spans.Count == 1)
            para.Align = style.Align;
    }

    private static string DecodeEmbedded(string raw)
    {
        var hex = new List<byte>();
        var sb = new StringBuilder();
        void Flush()
        {
            if (hex.Count == 0) return;
            try { sb.Append(Encoding.GetEncoding(949).GetString(hex.ToArray())); }
            catch { /* ignore */ }
            hex.Clear();
        }

        for (var i = 0; i < raw.Length; i++)
        {
            if (raw[i] == '\\' && i + 3 < raw.Length && raw[i + 1] == '\''
                && int.TryParse(raw.AsSpan(i + 2, 2), NumberStyles.HexNumber, null, out var b))
            {
                hex.Add((byte)b);
                i += 3;
                continue;
            }
            if (raw[i] == '\\')
            {
                Flush();
                i++;
                while (i < raw.Length && (char.IsLetter(raw[i]) || raw[i] == '*')) i++;
                if (i < raw.Length && (raw[i] == '-' || char.IsDigit(raw[i])))
                {
                    if (raw[i] == '-') i++;
                    while (i < raw.Length && char.IsDigit(raw[i])) i++;
                }
                if (i < raw.Length && raw[i] == ' ') { }
                continue;
            }
            Flush();
            if (raw[i] is not '{' and not '}')
                sb.Append(raw[i]);
        }
        Flush();
        return sb.ToString();
    }

    private static int IndexOfControl(string src, string word)
    {
        var needle = "\\" + word;
        var i = 0;
        while (true)
        {
            var at = src.IndexOf(needle, i, StringComparison.Ordinal);
            if (at < 0) return -1;
            var after = at + needle.Length;
            if (after >= src.Length || !char.IsLetter(src[after]))
                return at;
            i = after;
        }
    }

    private static bool IsStarDest(string src, int i)
        => i + 2 < src.Length && src[i] == '{' && src[i + 1] == '\\' && src[i + 2] == '*';

    private static bool IsNamedGroup(string src, int i, string word)
    {
        if (i + 2 + word.Length >= src.Length || src[i] != '{') return false;
        var p = i + 1;
        if (p < src.Length && src[p] == '\\' && src.AsSpan(p + 1).StartsWith(word, StringComparison.Ordinal))
        {
            var after = p + 1 + word.Length;
            return after >= src.Length || !char.IsLetter(src[after]);
        }
        return false;
    }

    private static int SkipGroup(string src, int start)
    {
        var depth = 0;
        for (var i = start; i < src.Length; i++)
        {
            if (src[i] == '\\' && i + 1 < src.Length)
            {
                i++;
                continue;
            }
            if (src[i] == '{') depth++;
            else if (src[i] == '}')
            {
                depth--;
                if (depth <= 0) return i;
            }
        }
        return src.Length - 1;
    }

    private static string ReadWord(string src, ref int i, out int arg, out bool dest)
    {
        dest = false;
        arg = 1;
        if (i < src.Length && src[i] == '*')
        {
            dest = true;
            i++;
            if (i < src.Length && src[i] == '\\') i++;
        }

        var start = i;
        while (i < src.Length && char.IsLetter(src[i])) i++;
        var word = src[start..i];
        var hasArg = false;
        var neg = false;
        if (i < src.Length && src[i] == '-')
        {
            neg = true;
            hasArg = true;
            i++;
        }
        var nStart = i;
        while (i < src.Length && char.IsDigit(src[i])) i++;
        if (i > nStart)
        {
            hasArg = true;
            arg = int.Parse(src[nStart..i], CultureInfo.InvariantCulture);
            if (neg) arg = -arg;
        }
        else if (!hasArg && (word is "b" or "i" or "ul" or "strike" or "striked"))
            arg = 1;
        else if (!hasArg)
            arg = -1;
        return word;
    }

    private static bool TryReadIntAfter(string src, string prefix, out int value)
    {
        value = 0;
        var at = src.IndexOf(prefix, StringComparison.Ordinal);
        if (at < 0) return false;
        var i = at + prefix.Length;
        var start = i;
        while (i < src.Length && char.IsDigit(src[i])) i++;
        return i > start && int.TryParse(src[start..i], out value);
    }

    private sealed class RunStyle
    {
        public string Family = "Pretendard";
        public float SizeMm = FontCatalog.FromPt(10);
        public string Fill = "#000000";
        public bool Bold;
        public bool Italic;
        public bool Underline;
        public bool Strikeout;
        public string Align = "left";

        public RunStyle Clone() => (RunStyle)MemberwiseClone();

        public TextSpan ToSpan(string text) => new()
        {
            Text = text,
            FontFamily = Family,
            FontSize = SizeMm,
            Fill = Fill,
            Bold = Bold,
            Italic = Italic,
            Underline = Underline,
            Strikeout = Strikeout
        };
    }
}
