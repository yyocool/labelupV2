using System.Diagnostics;
using System.Globalization;
using System.IO.Compression;
using System.Text;
using System.Text.RegularExpressions;
using LabelUp.Editor.Models;
using LabelUp.Editor.Vendor;
using SkiaSharp;

namespace LabelUp.Editor.Services;

/// <summary>
/// 애니라벨 .lbl 을 LabelUp DesignObject 로 변환한다.
/// 기준: md_anylabel. 폼텍·아이라벨 변환과 분리된 파일이다.
/// 에디터 항목 속성은 바꾸지 않고, LBL → 기존 DesignObject 매핑만 담당한다.
/// </summary>
internal static class AniLabelImporter
{
    private const byte TypeData = 0x00;
    private const byte TypeLine = 0x02;
    private const byte TypeRect = 0x04;
    private const byte TypeEllipse = 0x05;
    private const byte TypePoly = 0x19;
    private const byte TypeBarcode = 0x07;
    private const byte TypeImage = 0x09;
    private const byte TypeText = 0x1A;
    private const byte TypeBarcode1D = 0x1B;
    private const byte TypeBarcode2D = 0x1C;

    private static readonly byte[] Footer = [0x64, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00];
    private static readonly byte[] RtfTable = Encoding.Unicode.GetBytes("RTF TABLE");
    private static readonly byte[] RtfClass = Encoding.Unicode.GetBytes("RTF");
    private static readonly byte[] PngSignature = [0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A];
    private static readonly Regex TableCaption = new(@"(\d+)\s*열\s*(\d+)\s*행", RegexOptions.Compiled);

    public static Task<LabelDocument> ImportAsync(byte[] fileBytes, string name, PaperCatalog papers, Func<string, int, Task>? progress)
        => ImportCoreAsync(fileBytes, name, papers, progress);

    public static LabelDocument Import(byte[] fileBytes, string name, PaperCatalog papers)
        => ImportCoreAsync(fileBytes, name, papers, null).GetAwaiter().GetResult();

    private static async Task<LabelDocument> ImportCoreAsync(byte[] fileBytes, string name, PaperCatalog papers, Func<string, int, Task>? progress)
    {
        if (!ExternalImportService.LooksLikeLbl(fileBytes))
            throw new InvalidDataException("애니라벨 LBL 시그니처(Printec Label Maker)가 아닙니다.");

        await papers.EnsureLoadedAsync();
        var design = await InflateAsync(fileBytes, progress);
        if (progress is not null)
            await progress("라벨을 읽는 중…", 72);

        var pos = 0;
        var strings = ReadHeaderStrings(design, ref pos);
        var paperNo = strings.FirstOrDefault(s => s.StartsWith('V') && s.Length is >= 4 and <= 8);

        float pw = 210, ph = 297, lw = 70, lh = 36, left = 0, top = 0, right = 0, bottom = 0, hg = 0, vg = 0;
        var cols = 0;
        var rows = 0;
        uint paperColor = 0;
        if (TryPaperLayout(design, pos, out var layoutEnd, out pw, out ph, out cols, out rows,
                out left, out top, out right, out bottom, out hg, out vg, out paperColor))
        {
            pos = layoutEnd;
            var gapsW = hg * Math.Max(0, cols - 1);
            var gapsH = vg * Math.Max(0, rows - 1);
            if (cols > 0) lw = (pw - left - right - gapsW) / cols;
            if (rows > 0) lh = (ph - top - bottom - gapsH) / rows;
        }

        var paper = ExternalImportService.ResolvePaper(
            papers, "anylabel", paperNo, lw, lh, cols, rows, pw, ph, left, top, right, bottom, hg, vg);
        ApplyPaperAppearance(design, ref pos, paper, paperColor, paperNo, papers.AniLabelWmf);
        var doc = LabelDocument.CreateBlank(paper);
        doc.Name = name;
        doc.SourceVendor = "anylabel";
        doc.Background = paper.LabelColor;
        foreach (var cell in doc.Pages[0].Cells)
            cell.Objects.Clear();

        var per = Math.Max(1, paper.LabelsPerPage);
        var linked = new List<(int Global, string Field, string Value)>();
        var z = 0;
        var sections = 0;
        var parseWatch = Stopwatch.StartNew();
        while (TryReadSection(design, ref pos, per, out var globalIdx, out var sectionEnd)
               && z < ExternalImportService.MaxImportObjects)
        {
            if (parseWatch.Elapsed.TotalSeconds > 45)
                throw new TimeoutException("변환이 너무 오래 걸립니다. 파일이 너무 큽니다.");
            sections++;
            if (progress is not null && sections % 2 == 0)
                await progress($"라벨을 읽는 중… {sections}칸", 72 + Math.Min(24, sections / 2));

            var cursor = pos;
            if (cursor + 2 <= sectionEnd && design[cursor] == 0x2D && design[cursor + 1] == 0x01)
                cursor += 2;
            var miss = 0;
            while (cursor + 41 <= sectionEnd && z < ExternalImportService.MaxImportObjects)
            {
                if (!ExternalImportService.TryGeom(design, cursor, sectionEnd, out var type, out var x, out var y, out var w, out var h)
                    || !IsType(type))
                {
                    cursor += ++miss > 80 ? 64 : 1;
                    continue;
                }

                if (type == TypeData && !LooksLikeLinked(design, cursor + 41, sectionEnd))
                {
                    cursor++;
                    miss++;
                    continue;
                }

                miss = 0;
                var payloadStart = cursor + 41;
                var payloadEnd = ResolvePayloadEnd(design, type, payloadStart, sectionEnd);
                if (payloadEnd <= cursor)
                    payloadEnd = Math.Min(sectionEnd, cursor + 41);
                var obj = Map(design, cursor, payloadStart, payloadEnd, type, x, y, w, h, ref linked, globalIdx, sectionEnd);
                if (obj is not null)
                {
                    obj.ZIndex = ++z;
                    ExternalImportService.Place(doc, obj, (int)globalIdx, per);
                }
                cursor = payloadEnd;
            }
            pos = sectionEnd;
        }

        // 섹션이 있는데 객체를 못 읽은 경우만 느슨 스캔. 빈 페이지 파일은 섹션이 없으므로 건드리지 않는다.
        if (sections > 0 && design.Length < 2_000_000 && doc.Pages.All(p => p.Cells.All(c => c.Objects.Count == 0)))
            ScanLoose(design, doc, per, ref linked, ref z);

        BindLinkedSheet(doc, name, linked);
        InferTableGridFromCaptions(doc);

        EditorLog.Info(
            $"애니라벨 변환: paper={paper.PaperNo} {paper.Columns}x{paper.Rows} " +
            $"label={paper.LabelWidthMm:0.#}x{paper.LabelHeightMm:0.#} objects={z} pages={doc.Pages.Count} " +
            $"sections={sections} shape={paper.Shape.Kind} hole={(paper.Shape.Hole is { Width: > 0 } ? "Y" : "N")}");
        return doc;
    }

    private static void ScanLoose(
        byte[] design, LabelDocument doc, int per,
        ref List<(int Global, string Field, string Value)> linked, ref int z)
    {
        var last = Math.Min(design.Length - 41, 800_000);
        for (var i = 0; i < last && z < 80; i++)
        {
            if (!IsType(design[i])) continue;
            if (!ExternalImportService.TryGeom(design, i, design.Length, out var type, out var x, out var y, out var w, out var h))
                continue;
            if (type == TypeData && !LooksLikeLinked(design, i + 41, design.Length))
                continue;
            var end = Math.Min(design.Length, i + 8000);
            var obj = Map(design, i, i + 41, end, type, x, y, w, h, ref linked, 1, design.Length);
            if (obj is null) continue;
            obj.ZIndex = ++z;
            ExternalImportService.Place(doc, obj, 1, per);
            i += 40;
        }
    }

    private static bool IsType(byte type)
        => type is TypeData or TypeLine or TypeRect or TypeEllipse or TypePoly
            or TypeBarcode or TypeImage or TypeText or TypeBarcode1D or TypeBarcode2D;

    private static DesignObject? Map(
        byte[] data, int geom, int start, int end, byte type, float x, float y, float w, float h,
        ref List<(int Global, string Field, string Value)> linked, uint globalIdx, int sectionEnd)
    {
        switch (type)
        {
            case TypeLine:
                return MapLine(data, start, end, x, y, w, h);
            case TypeRect:
                return MapBoxShape(data, start, end, x, y, w, h);
            case TypeEllipse:
                return MapStyledShape(data, start, end, ShapeKind.Ellipse, x, y, w, h);
            case TypePoly:
                return MapPoly(data, start, end, x, y, w, h);
            case TypeImage:
                return MapImage(data, start, end, x, y, w, h);
            case TypeBarcode:
            case TypeBarcode1D:
            case TypeBarcode2D:
                return MapBarcode(data, start, end, type, x, y, w, h);
            case TypeData:
                return MapLinked(data, start, end, x, y, w, h, ref linked, globalIdx);
            case TypeText:
                // 표 객체 클래스명은 "RTF". "RTF TABLE"은 일반 텍스트(설명 문구 포함).
                if (LooksLikeTable(data, start, end, sectionEnd))
                    return MapTable(data, start, sectionEnd, x, y, w, h);
                if (TryCollectText(data, start, end, out var run))
                    return MapText(data, start, end, x, y, w, h, run);
                return null;
            default:
                return null;
        }
    }

    private readonly record struct AniTextRun(
        string Text, float FontSize, string? FontName, uint Color, ushort LayoutHint, uint Style);

    private static DesignObject MapText(byte[] data, int start, int end, float x, float y, float w, float h, AniTextRun run)
    {
        var o = ExternalImportService.TextAt(x, y, w, h, run.Text);
        o.FontSize = ResolveTextMm(run.FontSize, h);
        o.Fill = TColorCss(run.Color);
        FontCatalog.ApplyImportedFamily(o, ResolveAniFontName(run.FontName, run.Text));
        o.Bold = (run.Style & 0x01) != 0;
        o.Italic = (run.Style & 0x02) != 0;
        o.Underline = (run.Style & 0x04) != 0;
        o.Strikeout = (run.Style & 0x08) != 0;
        if (run.LayoutHint == 0 || h > w * 1.5f)
        {
            o.TextDirection = "vertical";
            // 애니라벨 세로는 em의 약 0.87. 기본 행간 1.2면 상자 밖으로 넘어 두 열이 된다.
            o.LineHeight = 0.86f;
        }
        ApplyTextBackground(data, start, o);
        ApplyTextAlign(data, start, end, o);
        ApplyLineSpacing(data, start, end, o);
        return o;
    }

    /// <summary>
    /// 「도형 타입 및 채우기 색 변경.lbl」
    /// 0x02 선/화살표. +63=2 이면 화살표.
    /// 크기 박스(X/Y/W/H)는 그대로 두고, 실제 선은 렌더러가 상하좌우 3mm 들여 그린다.
    /// </summary>
    private static DesignObject MapLine(byte[] data, int start, int end, float x, float y, float w, float h)
    {
        var arrow = start + 63 < end && data[start + 63] == 2;
        var o = ExternalImportService.Shape(arrow ? ShapeKind.Arrow : ShapeKind.Line, x, y, w, h);
        ApplyShapeStyle(o, data, start, end, outlineOnly: true);
        if (arrow)
        {
            o.ArrowHeads = ArrowHeads.End;
            o.Fill = "#000000";
        }
        o.BarcodeVendor = "anylabel";
        return o;
    }

    /// <summary>0x04 사각형. +57 IEEE754 2.0 이면 둥근 모서리.</summary>
    private static DesignObject MapBoxShape(byte[] data, int start, int end, float x, float y, float w, float h)
    {
        var kind = ShapeKind.Rect;
        var radius = 0f;
        if (start + 61 <= end)
        {
            var r = BitConverter.ToSingle(data, start + 57);
            if (r is > 0.2f and < 40f)
            {
                kind = ShapeKind.RoundRect;
                radius = r;
            }
        }
        var o = MapStyledShape(data, start, end, kind, x, y, w, h);
        if (radius > 0)
            o.CornerRadiusMm = radius;
        return o;
    }

    /// <summary>
    /// 0x19 다각형. payload+51 타입: 0=정n각(+52=변), 1=별(+52=꼭지),
    /// 2=마름모, 3=사다리꼴, 4=평행사변형. +62 u32 꼭지 수, +66부터 int32 (x,y).
    /// 좌표는 박스 상대 약 -50..50. 「도형 타입 및 채우기 색 변경.lbl」에서 확인.
    /// </summary>
    private static DesignObject MapPoly(byte[] data, int start, int end, float x, float y, float w, float h)
    {
        var kind = ShapeKind.Polygon;
        var sides = 5;
        var ratio = 0.22f;
        if (start + 64 <= end)
        {
            var flag = data[start + 51];
            var pts = data[start + 52];
            var verts = BitConverter.ToUInt16(data, start + 62);
            if (flag == 1 && pts >= 3)
            {
                kind = ShapeKind.Star;
                sides = pts;
            }
            else if (verts == 3)
            {
                kind = ShapeKind.Triangle;
            }
            else if (flag == 3 && verts == 4)
            {
                kind = ShapeKind.Trapezoid;
                ratio = pts is >= 8 and <= 80 ? pts / 200f : 0.22f;
            }
            else if (flag == 4 && verts == 4)
            {
                kind = ShapeKind.Parallelogram;
                ratio = pts is >= 8 and <= 80 ? pts / 100f : 0.40f;
            }
            else
            {
                sides = verts >= 3 ? verts : Math.Max(3, pts > 0 ? (int)pts : flag);
            }

            if (TryReadPolyPoints(data, start, end, out var points))
            {
                if (kind == ShapeKind.Trapezoid)
                    ratio = TrapezoidInset(points);
                else if (kind == ShapeKind.Parallelogram)
                    ratio = ParallelogramSkew(points);
                else if (kind == ShapeKind.Star)
                    ratio = StarInnerRatio(points);
            }

            EditorLog.Info(
                $"애니라벨 다각형: flag={flag} pts={pts} verts={verts} kind={kind} ratio={ratio:0.###}");
        }
        var o = MapStyledShape(data, start, end, kind, x, y, w, h);
        if (kind is ShapeKind.Polygon or ShapeKind.Star)
            o.PolygonSides = Math.Clamp(sides, 3, 24);
        if (kind is ShapeKind.Trapezoid or ShapeKind.Parallelogram or ShapeKind.Star)
            o.CornerRadiusMm = ratio;
        return o;
    }

    /// <summary>+62 u32 개수, +66부터 int32 x,y 쌍.</summary>
    private static bool TryReadPolyPoints(byte[] data, int start, int end, out (int X, int Y)[] points)
    {
        points = [];
        if (start + 70 > end) return false;
        var n = (int)BitConverter.ToUInt32(data, start + 62);
        if (n is < 3 or > 64) return false;
        if (start + 66L + n * 8L > end) return false;
        points = new (int X, int Y)[n];
        for (var i = 0; i < n; i++)
        {
            var ox = start + 66 + i * 8;
            points[i] = (BitConverter.ToInt32(data, ox), BitConverter.ToInt32(data, ox + 4));
        }
        return true;
    }

    private static float TrapezoidInset((int X, int Y)[] pts)
    {
        var minX = pts.Min(p => p.X);
        var span = Math.Max(1, pts.Max(p => p.X) - minX);
        var topMin = pts.OrderBy(p => p.Y).Take(2).Min(p => p.X);
        return Math.Clamp((topMin - minX) / (float)span, 0.04f, 0.45f);
    }

    private static float ParallelogramSkew((int X, int Y)[] pts)
    {
        var span = Math.Max(1, pts.Max(p => p.X) - pts.Min(p => p.X));
        var ordered = pts.OrderBy(p => p.Y).ToArray();
        var topMin = ordered.Take(2).Min(p => p.X);
        var botMin = ordered.TakeLast(2).Min(p => p.X);
        return Math.Clamp(Math.Abs(topMin - botMin) / (float)span, 0.04f, 0.49f);
    }

    private static float StarInnerRatio((int X, int Y)[] pts)
    {
        var rs = pts.Select(p => MathF.Sqrt(p.X * p.X + p.Y * p.Y)).Where(r => r > 1f).OrderBy(r => r).ToArray();
        if (rs.Length < 4) return 0.38f;
        var half = rs.Length / 2;
        var inner = rs.Take(half).Average();
        var outer = rs.Skip(half).Average();
        return outer < 1f ? 0.38f : Math.Clamp(inner / outer, 0.18f, 0.62f);
    }

    private static DesignObject MapStyledShape(
        byte[] data, int start, int end, ShapeKind kind, float x, float y, float w, float h)
    {
        var o = ExternalImportService.Shape(kind, x, y, w, h);
        ApplyShapeStyle(o, data, start, end, outlineOnly: false);
        return o;
    }

    /// <summary>
    /// 「도형 외곽선 타입.lbl」
    /// +0 TColor 채우기(Delphi BGR). +4=1 이면 테두리만(흰색 포함).
    /// +5..+7 RGB 선색(0,0,0=검정). +8 예약. +9 TPen.Style(0실선 1파선 2점선 3한점쇄선 4두점쇄선).
    /// +10 Extended80 선 두께 mm.
    /// </summary>
    private static void ApplyShapeStyle(DesignObject o, byte[] data, int start, int end, bool outlineOnly)
    {
        o.Stroke = "#000000";
        o.StrokeWidth = 0.2f;
        o.DashStyle = 0;
        o.Fill = "transparent";
        o.BackgroundTransparent = true;
        if (start + 4 <= end)
        {
            var fill = BitConverter.ToUInt32(data, start);
            var flag = start + 5 <= end ? data[start + 4] : (byte)0;
            if (!outlineOnly && (fill >> 24) == 0 && flag != 1 && fill != 0x00FFFFFF)
            {
                o.Fill = TColorCss(fill);
                o.BackgroundFill = o.Fill;
                o.BackgroundTransparent = false;
            }
        }
        if (start + 8 <= end)
        {
            var r = data[start + 5];
            var g = data[start + 6];
            var b = data[start + 7];
            o.Stroke = string.Create(CultureInfo.InvariantCulture, $"#{r:X2}{g:X2}{b:X2}");
        }
        if (start + 10 <= end)
        {
            var dash = data[start + 9];
            if (dash is >= 1 and <= 4)
                o.DashStyle = dash;
            else if (dash == 5)
                o.StrokeWidth = 0;
        }
        if (start + 20 <= end)
        {
            try
            {
                var thick = Extended80.ReadStandard(data.AsSpan(start + 10, 10));
                if (thick is > 0.05 and < 20 && o.StrokeWidth > 0)
                    o.StrokeWidth = (float)Math.Clamp(thick, 0.1, 8);
            }
            catch
            {
                // Extended80 범위 밖
            }
        }
        EditorLog.Info(
            $"애니라벨 도형: kind={o.ShapeKind} fill={o.Fill} stroke={o.Stroke} " +
            $"w={o.StrokeWidth:0.##} dash={o.DashStyle}");
    }

    /// <summary>
    /// 객체 헤더 정렬. 「텍스트 정렬 및 줄 간격.lbl」로 재확인.
    /// geom+216 u32: 0=위 1=가운데 2=아래. geom+283 u8: 0=왼쪽 1=가운데 2=오른쪽.
    /// </summary>
    private static void ApplyTextAlign(byte[] data, int payloadStart, int payloadEnd, DesignObject o)
    {
        var geom = payloadStart - 41;
        if (geom < 0) return;
        if (geom + 220 <= payloadEnd && geom + 220 <= data.Length)
        {
            var v = BitConverter.ToUInt32(data, geom + 216);
            if (v <= 2)
                o.VerticalAlign = v switch { 0 => "top", 2 => "bottom", _ => "middle" };
        }
        if (geom + 284 <= payloadEnd && geom + 284 <= data.Length)
        {
            var h = data[geom + 283];
            if (h <= 2)
                o.TextAlign = h switch { 0 => "left", 2 => "right", _ => "center" };
        }
    }

    /// <summary>
    /// geom+275 IEEE754 double = 줄간격 배수(1 / 1.5 / 2 / 2.5 / 3).
    /// 자간 필드는 없다(사용자 확인).
    /// </summary>
    private static void ApplyLineSpacing(byte[] data, int payloadStart, int payloadEnd, DesignObject o)
    {
        var geom = payloadStart - 41;
        if (geom + 283 > payloadEnd || geom + 283 > data.Length) return;
        var spacing = BitConverter.ToDouble(data, geom + 275);
        if (double.IsNaN(spacing) || spacing is < 0.5 or > 5.0) return;
        var factor = (float)spacing;
        o.LineHeight = o.TextDirection == "vertical" ? 0.86f * factor : factor;
    }

    private static void ApplyTextBackground(byte[] data, int start, DesignObject o)
    {
        if (start + 8 > data.Length) return;
        var bg = BitConverter.ToUInt32(data, start);
        var flag = BitConverter.ToUInt32(data, start + 4);
        if (flag != 1 || (bg >> 24) != 0) return;
        if (bg is 0 or 0x00FFFFFF) return;
        o.BackgroundFill = TColorCss(bg);
        o.BackgroundTransparent = false;
    }

    /// <summary>
    /// 얼굴 태그 디코드. ASCII → UTF-16LE → UTF-8 → CP949 → EUC-KR → Johab.
    /// `?? ??`는 바이트가 이미 0x3F인 ANSI 치환값이라 인코딩을 바꿔도 한글이 나오지 않는다.
    /// </summary>
    private static string DecodeAniFaceTag(ReadOnlySpan<byte> raw)
    {
        if (raw.Length == 0) return "";

        if (LooksLikeUtf16Le(raw))
        {
            var wide = Encoding.Unicode.GetString(raw).TrimEnd('\0').Trim();
            if (wide.Length > 0) return wide;
        }

        if (IsAllAscii(raw))
            return Encoding.ASCII.GetString(raw).TrimEnd('\0').Trim();

        foreach (var codePage in new[] { 65001, 949, 51949, 1361 })
        {
            try
            {
                var s = Encoding.GetEncoding(codePage).GetString(raw).TrimEnd('\0').Trim();
                if (s.Length == 0 || s.Contains('\uFFFD')) continue;
                if (s.Any(ch => ch is >= '\uAC00' and <= '\uD7A3'))
                    return s;
            }
            catch (ArgumentException)
            {
                // WASM에 해당 코드페이지가 없으면 다음 후보
            }
        }

        return ExternalImportService.DecodeAnsi(raw);
    }

    private static bool IsAllAscii(ReadOnlySpan<byte> raw)
    {
        foreach (var b in raw)
        {
            if (b >= 128) return false;
        }
        return true;
    }

    private static bool LooksLikeUtf16Le(ReadOnlySpan<byte> raw)
    {
        if (raw.Length < 4 || raw.Length % 2 != 0) return false;
        var zeros = 0;
        for (var i = 1; i < raw.Length; i += 2)
        {
            if (raw[i] == 0) zeros++;
        }
        return zeros * 2 >= raw.Length / 2;
    }

    /// <summary>
    /// ASCII 태그는 그대로. `?? ??`는 한글 얼굴(맑은 고딕)이 ANSI에서 ?로 치환된 값.
    /// 태그가 전부 ?이고 본문 길이가 태그와 같으며 카탈로그 폰트명이면 본문을 쓴다.
    /// </summary>
    private static string ResolveAniFontName(string? tag, string? text = null)
    {
        if (!string.IsNullOrWhiteSpace(tag))
        {
            var name = tag.Trim().TrimStart('@').Trim();
            if (name.Length >= 2 && !name.Contains('?'))
            {
                if (name.Contains("Nato Sans", StringComparison.OrdinalIgnoreCase))
                    return "Noto Sans KR";
                return name;
            }
            if (name is "?? ??")
                return "맑은 고딕";
        }

        var body = (text ?? "").Trim();
        var lost = (tag ?? "").Trim().TrimStart('@');
        if (body.Length is >= 2 and <= 40
            && lost.Length == body.Length
            && lost.All(ch => ch is '?' or ' ')
            && FontCatalog.IsKnownFamily(body))
            return FontCatalog.CanonicalId(body);
        return "맑은 고딕";
    }

    /// <summary>문자 SizeParam이 4~96이면 pt→mm. 아니면 상자 높이에 맞춘다.</summary>
    private static float ResolveTextMm(float sizeParam, float boxH)
    {
        if (sizeParam is >= 4 and <= 96)
            return Math.Clamp(sizeParam * 25.4f / 72f, 1.4f, 28f);
        if (boxH > 0.8f)
            return Math.Clamp(boxH * 0.55f, 1.8f, 14f);
        return 3.2f;
    }

    private static DesignObject? MapLinked(
        byte[] data, int start, int end, float x, float y, float w, float h,
        ref List<(int Global, string Field, string Value)> linked, uint globalIdx)
    {
        if (!TryReadLinked(data, start, end, out var field, out var text, out var fontName, out var fontSize))
            return null;
        linked.Add(((int)globalIdx, field, text));
        var o = ExternalImportService.TextAt(x, y, w, h, string.IsNullOrWhiteSpace(text) ? field : text, true, field);
        o.FontSize = ResolveTextMm(fontSize, h);
        if (!string.IsNullOrWhiteSpace(fontName) && !fontName.Contains('?'))
            o.FontFamily = fontName;
        return o;
    }

    private static DesignObject MapTable(byte[] data, int start, int sectionEnd, float x, float y, float w, float h)
    {
        TryTableDigits(data, start, sectionEnd, out var tc, out var tr);
        var table = DesignObject.CreateDefault(ObjectType.Table, x, y);
        table.Width = w;
        table.Height = h;
        table.TableCols = tc > 0 ? tc : 0;
        table.TableRows = tr > 0 ? tr : 0;
        table.TableCells = [];
        table.Fill = "#2E2A27";
        table.Stroke = "#2E2A27";
        table.StrokeWidth = 0.2f;
        table.BackgroundFill = "transparent";
        table.BackgroundTransparent = true;
        if (table.TableCols >= 1 && table.TableRows >= 1)
            table.EnsureTableSize();
        EditorLog.Info($"애니라벨 표: {table.TableRows}행 × {table.TableCols}열 {w:0.#}×{h:0.#} mm");
        return table;
    }

    private static DesignObject MapImage(byte[] data, int start, int end, float x, float y, float w, float h)
    {
        if (!TryFindEmbeddedImage(data, start, end, out var found))
        {
            var img = DesignObject.CreateDefault(ObjectType.Image, x, y);
            img.Width = w;
            img.Height = h;
            img.ImageData = ExternalImportService.FindImageDataUrl(data, start, end);
            return img;
        }

        var bytes = data.AsSpan(found.Start, found.Length).ToArray();
        var clipart = LooksLikeClipArt(found.Width, found.Height);
        var obj = DesignObject.CreateDefault(clipart ? ObjectType.Clipart : ObjectType.Image, x, y);
        obj.Width = w;
        obj.Height = h;
        obj.Svg = null;
        obj.ClipartId = clipart ? "embedded" : obj.ClipartId;
        obj.Fill = "transparent";
        obj.StrokeWidth = 0f;
        obj.BackgroundTransparent = true;
        if (clipart)
            obj.ImageFit = "stretch";
        obj.ImageData = clipart && found.Kind == "BMP"
            ? RasterWithWhiteKey(bytes, found.Mime)
            : ExternalImportService.ToDataUrl(bytes, found.Mime);
        EditorLog.Info(
            clipart
                ? $"애니라벨 클립아트: {found.Width}x{found.Height} {found.Kind} {found.Length}b"
                : $"애니라벨 이미지: {found.Width}x{found.Height} {found.Kind} {found.Length}b");
        return obj;
    }

    private static DesignObject? MapBarcode(byte[] data, int start, int end, byte type, float x, float y, float w, float h)
    {
        string value;
        string format;
        var is2d = type == TypeBarcode2D;
        var afterBmp = start;
        if (is2d && AniLabelBarcodes.TryRead2D(data, start, end, out _, out var v2, out var f2, out afterBmp))
        {
            value = v2;
            format = f2;
            is2d = ExternalImportService.Is2dBarcode(format);
        }
        else if (type == TypeBarcode1D && AniLabelBarcodes.TryRead1D(data, start, end, out _, out var v1, out var f1))
        {
            value = v1;
            format = f1;
            is2d = false;
        }
        else if (type == TypeBarcode && AniLabelBarcodes.TryRead1DLegacy(data, start, end, out var v0, out var f0))
        {
            value = v0;
            format = f0;
            is2d = false;
        }
        else if (type == TypeBarcode)
        {
            var strings = ExternalImportService.ExtractPrintable(data, start, Math.Min(end, start + 2048), 1);
            value = ReadLengthPrefixedAscii(data, start, Math.Min(end, start + 400))
                    ?? strings.LastOrDefault(s => s.Any(char.IsLetterOrDigit)) ?? "";
            if (string.IsNullOrWhiteSpace(value)) return null;
            format = ExternalImportService.MapBarcode(
                strings.FirstOrDefault(s => s.Length < 28 && !s.Equals(value, StringComparison.Ordinal)));
        }
        else
        {
            return null;
        }

        var bar = DesignObject.CreateDefault(is2d ? ObjectType.Qr : ObjectType.Barcode, x, y);
        bar.Width = w;
        bar.Height = h;
        bar.BarcodeValue = value;
        bar.BarcodeFormat = format;
        bar.BarcodeVendor = "anylabel";
        if (type == TypeBarcode2D && afterBmp > start)
            AniLabelBarcodes.Apply2DStyle(bar, data, afterBmp, end);
        else if (type != TypeBarcode2D)
            AniLabelBarcodes.Apply1DStyle(bar, data, start, end);
        return bar;
    }

    private static void BindLinkedSheet(LabelDocument doc, string name, List<(int Global, string Field, string Value)> linked)
    {
        if (linked.Count == 0) return;
        var fields = linked.Select(t => t.Field)
            .Where(s => s.Length > 0)
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .OrderBy(TextFieldOrder)
            .ThenBy(s => s, StringComparer.OrdinalIgnoreCase)
            .ToList();
        if (fields.Count == 0) return;

        var sheet = new DataSheet { SourceName = name, SourceKind = "lbl" };
        sheet.Columns.AddRange(fields);
        foreach (var group in linked.GroupBy(t => t.Global).OrderBy(g => g.Key))
        {
            var row = fields.Select(f =>
                group.LastOrDefault(t => string.Equals(t.Field, f, StringComparison.OrdinalIgnoreCase)).Value ?? "").ToList();
            if (row.Any(v => !string.IsNullOrWhiteSpace(v)))
                sheet.Rows.Add(row);
        }
        if (sheet.RowCount == 0) return;
        doc.Data = sheet;
        doc.EnsurePagesForData();
        EditorLog.Info($"애니라벨 자료연결: {sheet.RowCount}행 × {fields.Count}열 ({string.Join(", ", fields)})");
    }

    private static int TextFieldOrder(string name)
    {
        if (name.StartsWith("Text", StringComparison.OrdinalIgnoreCase)
            && int.TryParse(name.AsSpan(4), out var n))
            return n;
        return int.MaxValue;
    }

    /// <summary>
    /// 표기본.lbl: 표 객체 클래스명은 UTF-16 "RTF".
    /// "RTF TABLE"은 옆의 설명 텍스트다. 셀 한글을 표 내용으로 보면 안 된다.
    /// </summary>
    private static bool LooksLikeTable(byte[] data, int start, int end, int sectionEnd)
    {
        var probe = Math.Min(Math.Min(end, start + 200), data.Length);
        if (ContainsUtf16(data, start, probe, RtfTable))
            return false;
        if (ContainsUtf16(data, start, probe, RtfClass))
            return true;
        return !TryCollectText(data, start, end, out _)
               && TryTableDigits(data, start, sectionEnd, out _, out _);
    }

    private static bool ContainsUtf16(byte[] data, int start, int end, byte[] token)
    {
        var last = Math.Min(end, data.Length) - token.Length;
        for (var i = start; i <= last; i++)
            if (data.AsSpan(i, token.Length).SequenceEqual(token))
                return true;
        return false;
    }

    private static bool TryTableDigits(byte[] data, int start, int end, out int cols, out int rows)
    {
        cols = 0;
        rows = 0;
        var digits = new List<int>();
        var last = Math.Min(end, data.Length) - 16;
        for (var i = start; i <= last && digits.Count < 2; i++)
        {
            if (BitConverter.ToUInt64(data, i) != 0x64) continue;
            var ch = data[i + 8];
            if (ch is < (byte)'1' or > (byte)'9') continue;
            if (data[i + 9] != 0 || data[i + 10] != 0x04 || data[i + 11] != 0x0C) continue;
            digits.Add(ch - '0');
        }
        if (digits.Count < 2) return false;
        cols = digits[0];
        rows = digits[1];
        return cols is >= 1 and <= 50 && rows is >= 1 and <= 50;
    }

    /// <summary>
    /// 문자 레코드(약 280~320바이트 간격):
    /// u16 unicode, u16 layoutHint(가로 0x095A / 세로 0), u32 fontSizePt, u32 TColor, u8 tagLen, tag[].
    /// 레코드 끝 Footer(64 00..) 직전 u32 = TFont.Style (1볼드 2이탤릭 4밑줄 8취소).
    /// +8은 0이 아니라 Delphi TColor다. 빨강=0x000000FF 처럼 값이 크면 예전 zero 검사에서 버려졌다.
    /// </summary>
    private static bool TryCollectText(byte[] data, int start, int end, out AniTextRun run)
    {
        run = default;
        end = Math.Min(end, data.Length);
        end = Math.Min(end, start + 128_000);
        var chars = new List<(int Off, char Ch, uint Size, uint Color, ushort Hint, string Tag)>();
        for (var i = start; i + 14 < end; i++)
        {
            var code = BitConverter.ToUInt16(data, i);
            if (!IsTextChar(code)) continue;
            var hint = BitConverter.ToUInt16(data, i + 2);
            var size = BitConverter.ToUInt32(data, i + 4);
            var color = BitConverter.ToUInt32(data, i + 8);
            if ((color >> 24) != 0 || size is < 4 or > 200) continue;
            var slen = data[i + 12];
            if (slen is < 1 or > 64 || i + 13 + slen > end) continue;
            if (!IsPrintableTag(data.AsSpan(i + 13, slen))) continue;
            var tag = DecodeAniFaceTag(data.AsSpan(i + 13, slen));
            chars.Add((i, (char)code, size, color, hint, tag));
            i += 12;
        }
        if (chars.Count == 0) return false;

        List<(int Off, char Ch, uint Size, uint Color, ushort Hint, string Tag)> best = chars.Count == 1 ? chars : [];
        for (var s = 0; s < chars.Count && chars.Count > 1; s++)
        {
            var group = new List<(int Off, char Ch, uint Size, uint Color, ushort Hint, string Tag)> { chars[s] };
            var last = chars[s].Off;
            for (var i = s + 1; i < chars.Count; i++)
            {
                var delta = chars[i].Off - last;
                if (delta is >= 200 and <= 400)
                {
                    group.Add(chars[i]);
                    last = chars[i].Off;
                }
            }
            if (group.Count > best.Count)
                best = group;
        }
        if (best.Count == 0)
            best = chars;

        var sb = new StringBuilder(best.Count);
        foreach (var c in best)
            sb.Append(c.Ch == '\r' ? '\n' : c.Ch);
        var text = sb.ToString().Trim();
        if (text.Length == 0) return false;
        uint style = 0;
        foreach (var c in best)
            style |= ReadCharStyle(data, c.Off, end);
        run = new AniTextRun(text, best[0].Size, best[0].Tag, best[0].Color, best[0].Hint, style);
        return true;
    }

    /// <summary>문자 레코드 Footer 직전 u32. 폰트 타입 체크3에서 확정.</summary>
    private static uint ReadCharStyle(byte[] data, int charOff, int end)
    {
        if (charOff + 13 >= data.Length) return 0;
        var slen = data[charOff + 12];
        var from = charOff + 13 + slen;
        var last = Math.Min(end, Math.Min(data.Length, charOff + 400)) - Footer.Length;
        for (var i = from; i <= last; i++)
        {
            if (data[i] != 0x64) continue;
            if (!data.AsSpan(i, 8).SequenceEqual(Footer)) continue;
            if (i < 4) return 0;
            var style = BitConverter.ToUInt32(data, i - 4);
            return style <= 15 ? style : 0;
        }
        return 0;
    }

    private static bool IsTextChar(ushort code)
        => code is 9 or 10 or 13
           or (>= 0x20 and <= 0x7E)
           or (>= 0xA0 and <= 0x024F)
           or (>= 0x1100 and <= 0x11FF)
           or (>= 0x3130 and <= 0x318F)
           or (>= 0xAC00 and <= 0xD7A3)
           or (>= 0x4E00 and <= 0x9FFF);

    private static bool IsPrintableTag(ReadOnlySpan<byte> tag)
    {
        if (tag.Length == 0) return false;
        var ok = 0;
        foreach (var b in tag)
        {
            // ASCII + CP949/EUC-KR 리드바이트(0x81~0xFE)
            if (b is >= 32 and <= 126 or >= 0x81)
                ok++;
        }
        return ok * 2 >= tag.Length;
    }

    private static bool LooksLikeLinked(byte[] data, int start, int end)
        => TryReadLinked(data, start, end, out _, out _, out _, out _);

    private static bool TryReadLinked(
        byte[] data, int start, int end,
        out string field, out string value, out string fontName, out float fontSize)
    {
        field = "";
        value = "";
        fontName = "";
        fontSize = 0;
        var marker = ExternalImportService.FindU32(data, start, Math.Min(end, start + 160), AniLabelBarcodes.Marker);
        if (marker < 0) return false;

        if (!TryReadLinkedValue(data, marker, end, out var valueOff, out var valueLen, out value))
            return false;
        if (!TryReadLinkedName(data, valueOff + valueLen, end, out var nameOff, out field))
            return false;

        var pos = nameOff + field.Length;
        if (pos + 4 <= end)
        {
            var fontLen = BitConverter.ToInt32(data, pos);
            if (fontLen is >= 1 and <= 64 && pos + 4 + fontLen <= end)
            {
                fontName = ExternalImportService.DecodeAnsi(data.AsSpan(pos + 4, fontLen));
                pos += 4 + fontLen;
                if (pos < end && data[pos] is >= 6 and <= 72)
                    fontSize = data[pos];
            }
        }
        return true;
    }

    private static bool TryReadLinkedValue(byte[] data, int marker, int end, out int valueOff, out int valueLen, out string value)
    {
        valueOff = 0;
        valueLen = 0;
        value = "";
        foreach (var extra in new[] { 0, 1 })
        {
            var lenAt = marker + 20 + extra;
            if (lenAt + 4 > end) continue;
            var len = BitConverter.ToInt32(data, lenAt);
            if (len is < 0 or > 512 || lenAt + 4 + len > end) continue;
            var off = lenAt + 4;
            if (len > 0 && !IsMostlyLatin1(data.AsSpan(off, len))) continue;
            if (!TryReadLinkedName(data, off + len, end, out _, out _)) continue;
            valueOff = off;
            valueLen = len;
            value = len == 0 ? "" : Encoding.Latin1.GetString(data, off, len);
            return true;
        }
        return false;
    }

    private static bool TryReadLinkedName(byte[] data, int from, int end, out int nameOff, out string name)
    {
        nameOff = 0;
        name = "";
        var last = Math.Min(end, from + 96) - 8;
        for (var i = from; i <= last; i++)
        {
            var n = BitConverter.ToInt32(data, i);
            if (n is < 4 or > 16 || i + 4 + n > end) continue;
            if (data[i + 4] != (byte)'T' || data[i + 5] != (byte)'e') continue;
            name = Encoding.ASCII.GetString(data, i + 4, n);
            if (!name.StartsWith("Text", StringComparison.Ordinal)) continue;
            nameOff = i + 4;
            return true;
        }
        return false;
    }

    private static bool IsMostlyLatin1(ReadOnlySpan<byte> raw)
    {
        if (raw.Length == 0) return true;
        var ok = 0;
        foreach (var b in raw)
        {
            if (b is >= 32 and <= 126 or 9 or 10 or 13 or >= 128)
                ok++;
        }
        return ok * 2 >= raw.Length;
    }

    private static string? ReadLengthPrefixedAscii(byte[] data, int start, int end)
    {
        for (var i = start; i + 8 < end && i < start + 400; i++)
        {
            var n = BitConverter.ToInt32(data, i);
            if (n is < 1 or > 80 || i + 4 + n > end) continue;
            var slice = data.AsSpan(i + 4, n);
            if (slice.ToArray().All(b => b is >= 0x20 and <= 0x7E))
                return Encoding.ASCII.GetString(slice);
        }
        return null;
    }

    private readonly record struct EmbeddedImage(int Start, int Length, string Kind, string Mime, int Width, int Height);

    private static bool TryFindEmbeddedImage(byte[] data, int start, int end, out EmbeddedImage image)
    {
        image = default;
        var scanEnd = Math.Min(end, start + 1024);
        for (var i = start; i + 8 <= scanEnd; i++)
        {
            if (TryReadPng(data, i, end, out image)
                || TryReadJpeg(data, i, end, out image)
                || TryReadGif(data, i, end, out image)
                || TryReadBmp(data, i, end, out image))
                return true;
        }
        return false;
    }

    private static bool TryReadBmp(byte[] data, int offset, int limit, out EmbeddedImage image)
    {
        image = default;
        if (offset + 30 > limit || data[offset] != (byte)'B' || data[offset + 1] != (byte)'M')
            return false;
        var fileSize = BitConverter.ToInt32(data, offset + 2);
        if (fileSize < 54 || fileSize > 50_000_000 || offset + fileSize > limit)
            return false;
        var pixelOffset = BitConverter.ToInt32(data, offset + 10);
        var headerSize = BitConverter.ToInt32(data, offset + 14);
        if (pixelOffset < 54 || pixelOffset > fileSize || headerSize is < 40 or > 256)
            return false;
        var width = BitConverter.ToInt32(data, offset + 18);
        var height = Math.Abs(BitConverter.ToInt32(data, offset + 22));
        var planes = BitConverter.ToUInt16(data, offset + 26);
        var bitCount = BitConverter.ToUInt16(data, offset + 28);
        if (planes != 1 || bitCount is not (1 or 4 or 8 or 16 or 24 or 32)
            || width is < 1 or > 8000 || height is < 1 or > 8000)
            return false;
        image = new EmbeddedImage(offset, fileSize, "BMP", "image/bmp", width, height);
        return true;
    }

    private static bool TryReadPng(byte[] data, int offset, int limit, out EmbeddedImage image)
    {
        image = default;
        if (offset + 24 > limit || !data.AsSpan(offset, 8).SequenceEqual(PngSignature))
            return false;
        var i = offset + 8;
        while (i + 12 <= limit)
        {
            var chunkLen = (data[i] << 24) | (data[i + 1] << 16) | (data[i + 2] << 8) | data[i + 3];
            if (chunkLen is < 0 or > 50_000_000 || i + 12 + chunkLen > limit)
                return false;
            var type = data.AsSpan(i + 4, 4);
            i += 12 + chunkLen;
            if (type.SequenceEqual("IEND"u8))
            {
                var w = (data[offset + 16] << 24) | (data[offset + 17] << 16) | (data[offset + 18] << 8) | data[offset + 19];
                var h = (data[offset + 20] << 24) | (data[offset + 21] << 16) | (data[offset + 22] << 8) | data[offset + 23];
                image = new EmbeddedImage(offset, i - offset, "PNG", "image/png", w, h);
                return true;
            }
        }
        return false;
    }

    private static bool TryReadJpeg(byte[] data, int offset, int limit, out EmbeddedImage image)
    {
        image = default;
        if (offset + 4 > limit || data[offset] != 0xFF || data[offset + 1] != 0xD8 || data[offset + 2] != 0xFF)
            return false;
        for (var i = offset + 2; i + 1 < limit; i++)
        {
            if (data[i] != 0xFF || data[i + 1] != 0xD9) continue;
            var length = i + 2 - offset;
            if (length < 32) return false;
            image = new EmbeddedImage(offset, length, "JPEG", "image/jpeg", 0, 0);
            return true;
        }
        return false;
    }

    private static bool TryReadGif(byte[] data, int offset, int limit, out EmbeddedImage image)
    {
        image = default;
        if (offset + 10 > limit || data[offset] != (byte)'G' || data[offset + 1] != (byte)'I' || data[offset + 2] != (byte)'F')
            return false;
        for (var i = offset + 6; i < limit; i++)
        {
            if (data[i] != 0x3B) continue;
            image = new EmbeddedImage(offset, i + 1 - offset, "GIF", "image/gif", 0, 0);
            return true;
        }
        return false;
    }

    /// <summary>클립아트는 긴 변이 약 800px로 래스터화된다. 일반 사진(예: 545×161)과 구분.</summary>
    private static bool LooksLikeClipArt(int width, int height)
    {
        if (width < 1 || height < 1) return false;
        var min = Math.Min(width, height);
        var max = Math.Max(width, height);
        return min >= 400 && max is >= 480 and <= 1024;
    }

    /// <summary>24bit 클립아트 BMP는 알파가 없고 바깥이 흰색이다. 흰 픽셀을 투명 처리한다.</summary>
    private static string RasterWithWhiteKey(byte[] bytes, string mime)
    {
        try
        {
            using var src = RasterImage.Decode(bytes);
            if (src is null)
                return ExternalImportService.ToDataUrl(bytes, mime);
            using var dst = new SKBitmap(src.Width, src.Height, SKColorType.Bgra8888, SKAlphaType.Unpremul);
            using (var canvas = new SKCanvas(dst))
            {
                canvas.Clear(SKColors.Transparent);
                canvas.DrawBitmap(src, 0, 0);
            }
            var pix = dst.GetPixelSpan();
            for (var i = 0; i + 3 < pix.Length; i += 4)
            {
                if (pix[i] >= 250 && pix[i + 1] >= 250 && pix[i + 2] >= 250)
                    pix[i + 3] = 0;
            }
            using var image = SKImage.FromBitmap(dst);
            using var encoded = image.Encode(SKEncodedImageFormat.Png, 80);
            var png = encoded?.ToArray();
            if (png is not { Length: > 0 })
                return ExternalImportService.ToDataUrl(bytes, mime);
            return $"data:image/png;base64,{Convert.ToBase64String(png)}";
        }
        catch (Exception ex)
        {
            EditorLog.Warn("애니라벨 클립아트 투명 처리 실패: " + ex.Message);
            return ExternalImportService.ToDataUrl(bytes, mime);
        }
    }

    private static int ResolvePayloadEnd(byte[] data, byte type, int payloadStart, int sectionEnd)
    {
        if (type == TypeBarcode2D && AniLabelBarcodes.TryBmpEnd(data, payloadStart, sectionEnd, out var bmpEnd))
        {
            var logical = AniLabelBarcodes.LogicalEndAfterBmp(data, bmpEnd, sectionEnd);
            return NextTypedObject(data, logical, sectionEnd) ?? logical;
        }

        if (type is TypeBarcode or TypeBarcode1D && TryBarcodePayloadEnd(data, payloadStart, sectionEnd) is int barEnd)
            return barEnd;

        if (type == TypeImage && TryFindEmbeddedImage(data, payloadStart, sectionEnd, out var img))
            return NextTypedObject(data, img.Start + img.Length, sectionEnd) ?? (img.Start + img.Length);

        if (type == TypeData)
        {
            var nextLinked = NextTypedObject(data, payloadStart + 16, sectionEnd);
            if (nextLinked is int n) return n;
        }

        if (type is TypeText or TypeLine or TypeRect or TypeEllipse or TypePoly
            && FindNextRtfObject(data, payloadStart + 24, sectionEnd) is int nextRtf)
            return nextRtf;

        return NextObject(data, payloadStart, sectionEnd) ?? sectionEnd;
    }

    /// <summary>다음 0x1A + 0x2711 마커. 표와 설명 텍스트를 나눈다.</summary>
    private static int? FindNextRtfObject(byte[] data, int from, int sectionEnd)
    {
        var last = sectionEnd - 41;
        for (var i = Math.Max(from, 0); i <= last; i++)
        {
            if (data[i] != TypeText) continue;
            if (!ExternalImportService.TryGeom(data, i, sectionEnd, out var type, out _, out _, out _, out _)
                || type != TypeText)
                continue;
            if (FindU32(data, i + 41, Math.Min(sectionEnd, i + 41 + 96), 0x00002711) < 0)
                continue;
            return i;
        }
        return null;
    }

    private static int FindU32(byte[] data, int start, int end, uint value)
    {
        var last = end - 4;
        for (var i = Math.Max(0, start); i <= last; i++)
            if (BitConverter.ToUInt32(data, i) == value)
                return i;
        return -1;
    }

    private static void InferTableGridFromCaptions(LabelDocument doc)
    {
        foreach (var page in doc.Pages)
        foreach (var cell in page.Cells)
        {
            var tables = cell.Objects.Where(o => o.Type == ObjectType.Table).ToList();
            if (tables.Count == 0) continue;
            var captions = cell.Objects
                .Where(o => o.Type == ObjectType.Text && !string.IsNullOrWhiteSpace(o.Text))
                .Select(o => o.Text!)
                .ToList();
            foreach (var table in tables)
            {
                if (table.TableRows >= 1 && table.TableCols >= 1)
                {
                    table.EnsureTableSize();
                    continue;
                }
                foreach (var text in captions)
                {
                    var m = TableCaption.Match(text);
                    if (!m.Success) continue;
                    if (!int.TryParse(m.Groups[1].Value, out var cols)
                        || !int.TryParse(m.Groups[2].Value, out var rows))
                        continue;
                    if (cols is < 1 or > 50 || rows is < 1 or > 50) continue;
                    table.TableCols = cols;
                    table.TableRows = rows;
                    EditorLog.Info($"애니라벨 표 행열 ← 설명 '{text}': {rows}행 × {cols}열");
                    break;
                }
                if (table.TableRows < 1) table.TableRows = 2;
                if (table.TableCols < 1) table.TableCols = 2;
                table.TableCells = [];
                table.EnsureTableSize();
            }
        }
    }

    private static int? TryBarcodePayloadEnd(byte[] data, int payloadStart, int sectionEnd)
    {
        var marker = ExternalImportService.FindU32(data, payloadStart, Math.Min(sectionEnd, payloadStart + 8192), AniLabelBarcodes.Marker);
        var from = marker >= 0 ? marker : payloadStart;
        if (TryReadEmfRange(data, from, sectionEnd, out var emfStart, out var emfLen))
            return Math.Min(sectionEnd, emfStart + emfLen);
        if (marker >= 0)
            return NextObject(data, marker + 8, sectionEnd);
        return null;
    }

    private static bool TryReadEmfRange(byte[] data, int from, int end, out int start, out int length)
    {
        start = 0;
        length = 0;
        ReadOnlySpan<byte> sig = " EMF"u8;
        var last = Math.Min(end, from + 8192) - 48;
        for (var i = Math.Max(0, from); i <= last; i++)
        {
            if (!data.AsSpan(i, 4).SequenceEqual(sig)) continue;
            var header = i - 40;
            if (header < 0 || header + 52 > data.Length) continue;
            if (BitConverter.ToUInt32(data, header) != 1) continue;
            var nBytes = (int)BitConverter.ToUInt32(data, header + 48);
            if (nBytes is < 80 or > 8_000_000 || header + nBytes > data.Length) continue;
            start = header;
            length = Math.Min(nBytes, Math.Max(0, end - header));
            return length >= 80;
        }
        return false;
    }

    private static int? NextTypedObject(byte[] data, int from, int sectionEnd)
    {
        var last = Math.Min(sectionEnd - 41, from + 8192);
        for (var i = Math.Max(0, from); i <= last; i++)
        {
            var t = data[i];
            if (t is not (TypeText or TypeBarcode or TypeBarcode1D or TypeBarcode2D or TypeImage
                or TypeLine or TypeRect or TypeEllipse or TypePoly or TypeData))
                continue;
            if (!ExternalImportService.TryGeom(data, i, sectionEnd, out var type, out _, out _, out _, out _) || !IsType(type))
                continue;
            if (type == TypeData && !LooksLikeLinked(data, i + 41, sectionEnd))
                continue;
            return i;
        }
        return null;
    }

    private static int? NextObject(byte[] data, int from, int sectionEnd)
    {
        var last = Math.Min(sectionEnd - 41, from + 131_072);
        for (var i = from + 8; i <= last; i++)
        {
            if (i >= 8 && !data.AsSpan(i - 8, 8).SequenceEqual(Footer))
                continue;
            if (ExternalImportService.TryGeom(data, i, sectionEnd, out var type, out _, out _, out _, out _) && IsType(type))
                return i;
        }
        return null;
    }

    private static bool TryReadSection(byte[] data, ref int pos, int per, out uint globalIdx, out int sectionEnd)
    {
        globalIdx = 0;
        sectionEnd = pos;
        var max = (uint)Math.Max(64, per * 256);
        var limit = Math.Min(data.Length - 11, pos + 32_768);
        for (var i = pos; i <= limit; i++)
        {
            var idx = BitConverter.ToUInt32(data, i);
            var len = BitConverter.ToInt32(data, i + 4);
            if (idx is < 1 || idx > max || len < 44 || i + 8 + len > data.Length) continue;
            if (data[i + 8] != 0x2D || data[i + 9] != 0x01) continue;
            pos = i + 8;
            globalIdx = idx;
            sectionEnd = i + 8 + len;
            return true;
        }
        return false;
    }

    private static List<string> ReadHeaderStrings(byte[] data, ref int pos)
    {
        var strings = new List<string>();
        while (pos + 4 <= data.Length && strings.Count < 12)
        {
            if (TryPaperLayout(data, pos, out _, out _, out _, out _, out _, out _, out _, out _, out _, out _, out _, out _))
                break;
            if (data[pos] == 0) { pos++; continue; }
            var n = BitConverter.ToInt32(data, pos);
            if (n is < 1 or > 200 || pos + 4 + n > data.Length)
            {
                pos++;
                continue;
            }
            var s = ExternalImportService.DecodeAnsi(data.AsSpan(pos + 4, n));
            if (s.Length == 0) { pos++; continue; }
            strings.Add(s);
            pos += 4 + n;
        }
        return strings;
    }

    private static bool TryPaperLayout(
        byte[] data, int start,
        out int end, out float pw, out float ph, out int cols, out int rows,
        out float left, out float top, out float right, out float bottom, out float hg, out float vg,
        out uint color)
    {
        end = start;
        pw = ph = left = top = right = bottom = hg = vg = 0;
        cols = rows = 0;
        color = 0;
        if (start < 0 || start + 116 > data.Length) return false;
        try
        {
            pw = (float)Extended80.ReadStandard(data.AsSpan(start, 10));
            ph = (float)Extended80.ReadStandard(data.AsSpan(start + 10, 10));
            color = BitConverter.ToUInt32(data, start + 40);
            cols = (int)BitConverter.ToUInt32(data, start + 48);
            rows = (int)BitConverter.ToUInt32(data, start + 52);
            left = (float)Extended80.ReadStandard(data.AsSpan(start + 56, 10));
            top = (float)Extended80.ReadStandard(data.AsSpan(start + 66, 10));
            right = (float)Extended80.ReadStandard(data.AsSpan(start + 76, 10));
            bottom = (float)Extended80.ReadStandard(data.AsSpan(start + 86, 10));
            hg = (float)Extended80.ReadStandard(data.AsSpan(start + 96, 10));
            vg = (float)Extended80.ReadStandard(data.AsSpan(start + 106, 10));
        }
        catch { return false; }
        if (cols is < 1 or > 50 || rows is < 1 or > 50 || pw is < 20 or > 2000 || ph is < 20 or > 2000)
            return false;
        end = start + 116;
        return true;
    }

    /// <summary>
    /// 용지 레이아웃 뒤: WMF 경로 + ShapeType + 타공 X/Y/W/H(Extended80).
    /// 타공은 WMF가 아니라 LBL 수치로 저장된다.
    /// </summary>
    private static void ApplyPaperAppearance(
        byte[] data, ref int pos, PaperSpec paper, uint paperColor, string? paperNo, AniLabelWmfCatalog wmf)
    {
        if ((paperColor & 0xFF000000) == 0 && paperColor != 0 && paperColor != 0x00FFFFFF)
        {
            paper.LabelColor = TColorCss(paperColor);
            EditorLog.Info($"애니라벨 용지 색: {paper.LabelColor}");
        }

        string? wmfName = null;
        if (pos + 4 <= data.Length)
        {
            var n = BitConverter.ToInt32(data, pos);
            if (n is >= 8 and <= 260 && pos + 4 + n <= data.Length)
            {
                var path = ExternalImportService.DecodeAnsi(data.AsSpan(pos + 4, n));
                if (path.Contains(".wmf", StringComparison.OrdinalIgnoreCase) || path.Contains(":\\"))
                {
                    wmfName = Path.GetFileName(path);
                    EditorLog.Info("애니라벨 WMF 참조: " + wmfName);
                    pos += 4 + n;
                }
            }
            else if (n == 0)
            {
                pos += 4;
            }
        }

        if (!string.IsNullOrWhiteSpace(wmfName)
            && wmf.TryConvert(wmfName, paper.LabelWidthMm, paper.LabelHeightMm, out var outer, out var guides))
        {
            paper.Shape.Kind = "svg";
            paper.Shape.Svg = outer;
            paper.Shape.Guides = guides.Count == 0 ? null : guides;
            paper.Shape.GuideSvg = null;
            paper.Shape.SvgIsLabelMm = true;
            EditorLog.Info(
                $"애니라벨 용지 형상: {wmfName} → {paper.LabelWidthMm:0.#}×{paper.LabelHeightMm:0.#} mm " +
                $"(가이드 {guides.Count})");
        }
        else if (!string.IsNullOrWhiteSpace(wmfName) && !wmf.Has(wmfName))
        {
            EditorLog.Warn($"{AniLabelWmfCatalog.MissingMessage} ({wmfName})");
        }

        var holeApplied = TryReadHole(data, pos, paper, out var shapeType, out var consumed);
        if (consumed)
            pos += 41;

        if (paper.Shape.Kind == "svg")
            return;

        InferOuterShape(paper, shapeType, paperNo, wmfName, holeApplied);
    }

    private static bool TryReadHole(byte[] data, int pos, PaperSpec paper, out byte shapeType, out bool consumed)
    {
        shapeType = 0;
        consumed = false;
        if (pos + 41 > data.Length) return false;
        shapeType = data[pos];
        if (shapeType > 16) return false;
        double hx, hy, hw, hh;
        try
        {
            hx = Extended80.ReadStandard(data.AsSpan(pos + 1, 10));
            hy = Extended80.ReadStandard(data.AsSpan(pos + 11, 10));
            hw = Extended80.ReadStandard(data.AsSpan(pos + 21, 10));
            hh = Extended80.ReadStandard(data.AsSpan(pos + 31, 10));
        }
        catch { return false; }

        if (double.IsNaN(hx) || double.IsNaN(hy) || double.IsNaN(hw) || double.IsNaN(hh)
            || hx is < -5 or > 500 || hy is < -5 or > 500
            || hw is < 0 or > 400 || hh is < 0 or > 400)
            return false;

        consumed = true;
        if (hw < 0.5 || hh < 0.5) return false;

        paper.Shape.Hole = new PaperHole
        {
            X = (float)hx,
            Y = (float)hy,
            Width = (float)hw,
            Height = (float)hh
        };
        EditorLog.Info(
            $"애니라벨 타공: {hw:0.##}×{hh:0.##} mm @({hx:0.##},{hy:0.##}) type={shapeType}");
        return true;
    }

    /// <summary>
    /// 외곽 형상. 정사각만으로 원형 단정하지 않는다.
    /// V3210(99.05×93.1)은 둥근 사각형인데 비율 0.94로 원으로 오인됐다.
    /// 원형은 WMF 이름(040R 등) 또는 카탈로그 ellipse, 도넛(정사각+타공)만.
    /// </summary>
    private static void InferOuterShape(
        PaperSpec paper, byte shapeType, string? paperNo, string? wmfName, bool holeApplied)
    {
        if (LooksCircularWmf(wmfName)
            || (holeApplied && IsNearlySquare(paper.LabelWidthMm, paper.LabelHeightMm)))
        {
            paper.Shape.Kind = "ellipse";
            EditorLog.Info(
                $"애니라벨 원형 용지: {paper.LabelWidthMm:0.#}×{paper.LabelHeightMm:0.#} mm " +
                $"({paperNo} wmf={wmfName ?? "-"} hole={holeApplied})");
            return;
        }

        if (paper.Shape.Kind is "ellipse" or "circle")
            return;

        if (paper.Shape.Kind is "rect" or "")
        {
            paper.Shape.Kind = "roundrect";
            if (paper.Shape.CornerRadiusMm < 0.2f)
                paper.Shape.CornerRadiusMm = 1.2f;
        }
        _ = shapeType;
    }

    /// <summary>WPF AniLabelWmfShape와 동일. 040R.wmf 계열만 원형.</summary>
    private static bool LooksCircularWmf(string? wmfName)
    {
        if (string.IsNullOrWhiteSpace(wmfName)) return false;
        var stem = Path.GetFileNameWithoutExtension(wmfName);
        return stem.EndsWith("R", StringComparison.OrdinalIgnoreCase)
               || stem.EndsWith("R_L", StringComparison.OrdinalIgnoreCase)
               || stem.Contains("040R", StringComparison.OrdinalIgnoreCase);
    }

    private static bool IsNearlySquare(float w, float h)
    {
        var min = Math.Min(w, h);
        var max = Math.Max(w, h);
        return max >= 1f && min / max >= 0.92f;
    }

    private static string TColorCss(uint color)
    {
        var r = color & 0xFF;
        var g = (color >> 8) & 0xFF;
        var b = (color >> 16) & 0xFF;
        return string.Create(CultureInfo.InvariantCulture, $"#{r:X2}{g:X2}{b:X2}");
    }

    private static async Task<byte[]> InflateAsync(byte[] fileBytes, Func<string, int, Task>? progress)
    {
        var jpeg = -1;
        var search = Math.Min(fileBytes.Length - 3, 2_000_000);
        for (var i = 0; i < search; i++)
        {
            if (fileBytes[i] == 0xFF && fileBytes[i + 1] == 0xD8 && fileBytes[i + 2] == 0xFF)
            { jpeg = i; break; }
        }
        if (jpeg < 4) throw new InvalidDataException("애니라벨 LBL JPEG 미리보기를 찾지 못했습니다.");
        var jpegLen = BitConverter.ToInt32(fileBytes, jpeg - 4);
        if (jpegLen < 20 || jpeg + jpegLen > fileBytes.Length)
            throw new InvalidDataException("애니라벨 LBL JPEG 길이가 올바르지 않습니다.");
        if (fileBytes[jpeg + jpegLen - 2] != 0xFF || fileBytes[jpeg + jpegLen - 1] != 0xD9)
            throw new InvalidDataException("애니라벨 LBL JPEG 종료 마커가 일치하지 않습니다.");
        var rest = jpeg + jpegLen;
        if (rest + 6 > fileBytes.Length)
            throw new InvalidDataException("LBL zlib 설계 블록이 없습니다.");
        var uncompressed = BitConverter.ToInt32(fileBytes, rest + 1);
        if (uncompressed > 80_000_000)
            throw new InvalidDataException("설계 데이터가 너무 큽니다 (80MB 초과).");
        var zlib = fileBytes.AsSpan(rest + 5).ToArray();
        if (zlib.Length < 2 || zlib[0] != 0x78)
            throw new InvalidDataException("LBL zlib 시그니처가 없습니다.");
        using var input = new MemoryStream(zlib);
        using var zs = new ZLibStream(input, CompressionMode.Decompress);
        using var output = new MemoryStream(uncompressed > 0 && uncompressed < 80_000_000 ? uncompressed : 4096);
        var buffer = new byte[256 * 1024];
        var total = 0;
        var sw = Stopwatch.StartNew();
        int read;
        while ((read = zs.Read(buffer, 0, buffer.Length)) > 0)
        {
            total += read;
            if (total > 80_000_000)
                throw new InvalidDataException("설계 데이터가 너무 큽니다 (80MB 초과).");
            if (sw.Elapsed.TotalSeconds > 45)
                throw new TimeoutException("변환이 너무 오래 걸립니다. 파일이 너무 큽니다.");
            output.Write(buffer, 0, read);
            if (progress is not null && (total <= buffer.Length || total % (1024 * 1024) < buffer.Length))
            {
                var target = uncompressed > 0 ? uncompressed : Math.Max(total, 1);
                var pct = 8 + (int)(64.0 * total / target);
                await progress($"설계 압축을 푸는 중… {Mb(total)} / {Mb(target)}", Math.Min(72, pct));
            }
        }
        return output.ToArray();
    }

    private static string Mb(int bytes)
        => $"{bytes / (1024.0 * 1024.0):0.#}MB";
}
