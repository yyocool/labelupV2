using System.Globalization;
using System.Text.RegularExpressions;
using System.Xml.Linq;
using LabelUp.Editor.Models;
using LabelUp.Editor.Services;
using SkiaSharp;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// 관리자(admin/shop/specs)에서 형태 '맞춤'으로 올린 용지 규격 SVG를 읽는다.
/// 이 SVG는 칼선 그림이고, 안에 든 <b>닫힌 도형</b>이 각각 편집할 수 있는 라벨 한 칸이 된다.
/// 좌표는 SVG의 viewBox 를 라벨 한 칸에 맞추는 것으로 본다. 그림의 잉크 테두리에 맞추면
/// 작성자가 둔 여백만큼 전체가 확대되어 칼선이 실제 타공에서 밀리기 때문이다.
/// </summary>
public static class SvgShapeImporter
{
    /// <summary>
    /// 겹쳐 그린 칼선을 편집 칸으로 바꾸는 방식. 규격의 형태('맞춤 일반' / '맞춤 도넛')로 정해진다.
    /// </summary>
    public enum SlotMode
    {
        /// <summary>바깥 칼선과 안쪽 칼선 사이는 편집할 수 없고, 안쪽 칼선 안만 편집 칸이 된다.</summary>
        Inner,
        /// <summary>바깥 칼선과 안쪽 칼선 사이(고리)가 편집 칸이고, 안쪽 칼선 안은 오려 낸다.</summary>
        Donut
    }

    /// <param name="Outline">오려내기에 쓸 전체 외곽(라벨 칸 mm).</param>
    /// <param name="Guides">칸으로 쪼개지 못했을 때 쓸 그림 조각들(라벨 칸 mm).</param>
    /// <param name="Closed">편집 칸이 될 닫힌 도형들. 읽은 순서는 위에서 아래, 왼쪽에서 오른쪽.</param>
    /// <param name="FrameCuts">칸이 아닌 칼선(라벨 칸 mm). 칼선 인쇄를 켰을 때만 그린다.</param>
    public sealed record Result(
        string Outline, List<PaperGuidePath> Guides, List<ClosedShape> Closed, List<string> FrameCuts);

    /// <summary>닫힌 도형 하나. 좌표는 라벨 칸 왼쪽 위를 0으로 둔 mm.</summary>
    public sealed record ClosedShape(float XMm, float YMm, float WMm, float HMm, PaperShape Shape);

    /// <summary>선 색을 안 적은 조각은 보이기라도 해야 하므로 이 색으로 그린다.</summary>
    private const string DefaultStroke = "#2E2A27";
    private const float DefaultStrokeWidthMm = 0.28f;
    private const float MinStrokeWidthMm = 0.05f;

    /// <summary>칸으로 셀 만큼 크지 않은 조각은 버린다. 머리카락 같은 선이 칸이 되면 안 된다.</summary>
    private const float MinSlotMm = 0.5f;

    /// <summary>바깥에서 들어온 자료이므로 품을 제한한다. 지나치게 깊거나 많으면 중간에 끊는다.</summary>
    private const int MaxDepth = 24;
    private const int MaxPieces = 4000;

    /// <summary>
    /// SVG 마크업을 라벨 칸 mm 좌표로 바꾼다. 맨 path 문자열이거나 읽을 수 없으면 null을
    /// 돌려주어, 부르는 쪽이 예전 방식(외곽 한 장)으로 처리하게 한다.
    /// </summary>
    public static Result? Read(string? svg, float labelWmm, float labelHmm, SlotMode mode)
    {
        var text = (svg ?? "").Trim();
        if (text.Length == 0) return null;
        // 'M 0 0 L 10 0 ...' 처럼 마크업이 아닌 path 문자열은 예전 경로가 이미 잘 처리한다.
        if (!text.Contains('<')) return null;
        if (labelWmm <= 0.01f || labelHmm <= 0.01f) return null;

        XElement root;
        try
        {
            root = XElement.Parse(text, LoadOptions.None);
        }
        catch (Exception ex)
        {
            EditorLog.Warn($"규격 SVG를 읽지 못했습니다: {ex.Message}");
            return null;
        }

        var css = ReadCssRules(root);
        var pieces = new List<Piece>();
        Collect(root, SKMatrix.CreateIdentity(), new SvgStyle(), css, pieces, 0);

        if (pieces.Count == 0)
        {
            EditorLog.Warn("규격 SVG에 그릴 수 있는 요소가 없습니다.");
            return null;
        }

        var fit = BuildFitMatrix(root, pieces, labelWmm, labelHmm, out var scale);
        if (fit is null)
        {
            DisposeAll(pieces);
            EditorLog.Warn("규격 SVG의 크기를 잴 수 없습니다(너비 또는 높이가 0).");
            return null;
        }

        foreach (var piece in pieces)
            piece.Path.Transform(fit.Value);

        // 편집 칸은 닫힌 도형만 된다. 열린 선은 소속될 칸이 없어 버린다.
        var closedPieces = pieces.Where(p => p.Closed).ToList();
        var drawn = closedPieces;
        if (closedPieces.Count == 0)
        {
            EditorLog.Warn("규격 SVG에 닫힌 도형이 없습니다. 칸으로 쪼개지 않고 외곽으로만 씁니다.");
            drawn = pieces;
        }
        else if (closedPieces.Count < pieces.Count)
        {
            EditorLog.Warn($"규격 SVG의 열린 선 {pieces.Count - closedPieces.Count}개는 "
                           + "닫힌 도형이 아니라 편집 칸이 될 수 없어 그리지 않습니다.");
        }

        var outline = BuildOutline(drawn, scale);
        var guides = BuildGuides(drawn, scale);
        var frameCuts = new List<string>();
        var closed = BuildSlots(closedPieces, mode, scale, frameCuts);
        DisposeAll(pieces);

        if (outline.Length == 0 || guides.Count == 0)
        {
            EditorLog.Warn("규격 SVG를 라벨 좌표로 바꾸지 못했습니다.");
            return null;
        }

        EditorLog.Info($"규격 SVG 해석 완료({(mode == SlotMode.Donut ? "맞춤 도넛" : "맞춤 일반")}): "
                       + $"편집 칸 {closed.Count}개 · 칸 밖 칼선 {frameCuts.Count}개 · 배율 {scale:0.####} "
                       + $"· 라벨칸 {labelWmm:0.###}×{labelHmm:0.###}mm");
        return new Result(outline, guides, closed, frameCuts);
    }

    /// <summary>SVG 요소 하나에서 갈라낸 외곽선 한 줄.</summary>
    private sealed class Piece
    {
        public required SKPath Path { get; init; }
        public required SvgStyle Style { get; init; }
        /// <summary>Z로 닫혀 면적이 있는 외곽선인지. 편집 칸이 될 수 있는 조건이다.</summary>
        public required bool Closed { get; init; }
        /// <summary>rect·circle 처럼 본래 모양을 아는 요소면 그 종류. 모르면 null.</summary>
        public ShapeHint? Hint { get; init; }
    }

    /// <summary>
    /// 본래 요소에서 알아낸 모양. 라운드 사각형을 svg Path가 아니라 roundrect 로 둘 수 있어
    /// 미리보기·모서리 처리가 사각 용지와 똑같이 깔끔해진다.
    /// </summary>
    private sealed record ShapeHint(string Kind, float RadiusX, float RadiusY, float CircleRadius);

    // ---------------------------------------------------------------- 좌표 맞추기

    /// <summary>
    /// SVG 좌표를 라벨 칸 mm로 옮기는 행렬. 기준은 viewBox(없으면 width/height)이고,
    /// 가로세로 비는 지켜서 칸 가운데에 둔다. 기준을 못 찾으면 그림의 잉크 테두리로 물러난다.
    /// </summary>
    private static SKMatrix? BuildFitMatrix(
        XElement root, List<Piece> pieces, float labelWmm, float labelHmm, out float scale)
    {
        scale = 1f;
        var box = ReadViewBox(root);
        if (box is null)
        {
            box = InkBounds(pieces);
            if (box is not null)
                EditorLog.Warn("규격 SVG에 viewBox가 없어 그림 테두리에 맞춥니다. "
                               + "칼선 위치를 정확히 맞추려면 viewBox를 넣어 주세요.");
        }
        if (box is not { } src || src.Width <= 0.0001f || src.Height <= 0.0001f)
            return null;

        scale = Math.Min(labelWmm / src.Width, labelHmm / src.Height);
        var m = SKMatrix.CreateIdentity();
        m = m.PostConcat(SKMatrix.CreateTranslation(-src.Left, -src.Top));
        m = m.PostConcat(SKMatrix.CreateScale(scale, scale));
        m = m.PostConcat(SKMatrix.CreateTranslation(
            (labelWmm - src.Width * scale) / 2f,
            (labelHmm - src.Height * scale) / 2f));
        return m;
    }

    private static SKRect? ReadViewBox(XElement root)
    {
        var raw = root.Attribute("viewBox")?.Value;
        if (!string.IsNullOrWhiteSpace(raw))
        {
            var nums = Regex.Matches(raw, @"[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?");
            if (nums.Count >= 4
                && TryNum(nums[0].Value, out var x) && TryNum(nums[1].Value, out var y)
                && TryNum(nums[2].Value, out var w) && TryNum(nums[3].Value, out var h)
                && w > 0.0001f && h > 0.0001f)
                return new SKRect(x, y, x + w, y + h);
        }

        if (TryNum(root.Attribute("width")?.Value, out var ww)
            && TryNum(root.Attribute("height")?.Value, out var hh)
            && ww > 0.0001f && hh > 0.0001f)
            return new SKRect(0f, 0f, ww, hh);

        return null;
    }

    private static SKRect? InkBounds(List<Piece> pieces)
    {
        using var union = new SKPath { FillType = SKPathFillType.Winding };
        foreach (var piece in pieces)
            union.AddPath(piece.Path);
        var bounds = union.TightBounds;
        if (bounds.Width <= 0.01f || bounds.Height <= 0.01f)
            bounds = union.Bounds;
        return bounds.Width > 0.01f && bounds.Height > 0.01f ? bounds : null;
    }

    // ---------------------------------------------------------------- 결과 만들기

    /// <summary>
    /// 오려내기 틀. 조각의 채움 영역뿐 아니라 선을 살로 바꾼 영역까지 합친다.
    /// 채움만 합치면 테두리선의 바깥 절반이 틀 밖으로 밀려 반쪽만 보인다.
    /// </summary>
    private static string BuildOutline(List<Piece> pieces, float scale)
    {
        using var union = new SKPath { FillType = SKPathFillType.Winding };
        foreach (var piece in pieces)
        {
            union.AddPath(piece.Path);

            var width = (piece.Style.StrokeWidth ?? 0f) * scale;
            if (piece.Style.Stroke is null || width <= 0f) continue;
            using var stroked = new SKPath();
            using var pen = new SKPaint { Style = SKPaintStyle.Stroke, StrokeWidth = width };
            if (pen.GetFillPath(piece.Path, stroked))
                union.AddPath(stroked);
        }
        return union.ToSvgPathData() ?? "";
    }

    private static List<PaperGuidePath> BuildGuides(List<Piece> pieces, float scale)
    {
        var guides = new List<PaperGuidePath>(pieces.Count);
        foreach (var piece in pieces)
        {
            var d = piece.Path.ToSvgPathData();
            if (string.IsNullOrWhiteSpace(d)) continue;

            var style = piece.Style;
            // 채움도 선도 없으면 아무것도 안 보인다. 그럴 때만 기본 선을 입혀 모양이 드러나게 한다.
            var stroke = style.Stroke ?? (style.Fill is null ? DefaultStroke : null);
            var widthMm = style.StrokeWidth is { } sw && sw > 0f
                ? Math.Max(MinStrokeWidthMm, sw * scale)
                : DefaultStrokeWidthMm;

            guides.Add(new PaperGuidePath
            {
                D = d,
                Fill = style.Fill,
                Stroke = stroke,
                StrokeWidthMm = widthMm,
                EvenOdd = style.EvenOdd
            });
        }
        return guides;
    }

    /// <summary>
    /// 닫힌 도형을 편집 칸으로 바꾼다. 겹쳐 그린 칼선(바깥 외곽 + 안쪽 외곽)은 형태 설정에 따라
    /// 안쪽만 칸이 되거나(맞춤 일반), 바깥에서 안쪽을 뺀 고리가 칸이 된다(맞춤 도넛).
    /// 칸이 되지 못한 조각은 <paramref name="frameCuts"/>에 담아 칼선으로만 남긴다.
    /// </summary>
    private static List<ClosedShape> BuildSlots(
        List<Piece> pieces, SlotMode mode, float scale, List<string> frameCuts)
    {
        var parent = FindParents(pieces);
        var hasChild = new bool[pieces.Count];
        foreach (var p in parent)
        {
            if (p >= 0) hasChild[p] = true;
        }

        var list = new List<ClosedShape>(pieces.Count);
        for (var i = 0; i < pieces.Count; i++)
        {
            var piece = pieces[i];

            // 맞춤 도넛은 가장 바깥 조각이 칸이 되고, 그 바로 안쪽 조각은 구멍으로 쓰인다.
            // 맞춤 일반은 아무것도 품지 않은 가장 안쪽 조각만 칸이 된다.
            var isSlot = mode == SlotMode.Donut ? parent[i] < 0 : !hasChild[i];
            if (!isSlot)
            {
                // 도넛에서 칸의 바로 안쪽 조각은 구멍으로 쓰이므로 칼선으로 또 담지 않는다.
                var usedAsHole = mode == SlotMode.Donut && parent[i] >= 0 && parent[parent[i]] < 0;
                if (!usedAsHole) AddFrameCut(frameCuts, piece.Path);
                continue;
            }

            var bounds = BoundsOf(piece.Path);
            if (bounds.Width < MinSlotMm || bounds.Height < MinSlotMm)
            {
                AddFrameCut(frameCuts, piece.Path);
                continue;
            }

            List<SKPath>? holes = null;
            if (mode == SlotMode.Donut)
            {
                for (var j = 0; j < pieces.Count; j++)
                {
                    if (parent[j] == i) (holes ??= []).Add(pieces[j].Path);
                }
            }

            list.Add(new ClosedShape(
                bounds.Left, bounds.Top, bounds.Width, bounds.Height,
                SlotShape(piece, bounds, scale, holes)));
        }

        // 칸 번호가 눈으로 보는 순서와 같아야 한다. 위에서 아래로, 같은 줄은 왼쪽에서 오른쪽으로.
        if (list.Count > 1)
        {
            var band = Math.Max(1f, list.Min(s => s.HMm) / 2f);
            list = list
                .OrderBy(s => MathF.Floor(s.YMm / band))
                .ThenBy(s => s.XMm)
                .ToList();
        }
        return list;
    }

    private static void AddFrameCut(List<string> frameCuts, SKPath path)
    {
        var d = path.ToSvgPathData();
        if (!string.IsNullOrWhiteSpace(d)) frameCuts.Add(d);
    }

    /// <summary>
    /// 조각마다 자기를 감싸는 가장 작은 조각을 찾는다. 겹쳐 그린 칼선에서 바깥·안쪽을 가르는 데 쓴다.
    /// 테두리 네모가 서로 들어맞는지만 보지 않고, 안쪽 조각의 가운데가 바깥 조각 안에 실제로
    /// 들어가는지까지 본다. 그래야 나란히 놓인 도형을 부모·자식으로 잘못 엮지 않는다.
    /// </summary>
    private static int[] FindParents(List<Piece> pieces)
    {
        var n = pieces.Count;
        var parent = new int[n];
        Array.Fill(parent, -1);
        if (n < 2) return parent;

        var bounds = new SKRect[n];
        for (var i = 0; i < n; i++)
            bounds[i] = BoundsOf(pieces[i].Path);

        for (var i = 0; i < n; i++)
        {
            var best = -1;
            for (var j = 0; j < n; j++)
            {
                if (i == j || !Surrounds(bounds[j], bounds[i])) continue;
                if (!pieces[j].Path.Contains(bounds[i].MidX, bounds[i].MidY)) continue;
                if (best < 0 || Area(bounds[j]) < Area(bounds[best])) best = j;
            }
            parent[i] = best;
        }
        return parent;

        static float Area(SKRect r) => r.Width * r.Height;

        // 거의 같은 크기면 같은 칼선을 두 번 그린 것으로 보고 부모·자식으로 엮지 않는다.
        static bool Surrounds(SKRect outer, SKRect inner)
        {
            const float tol = 0.01f;
            if (MathF.Abs(outer.Width - inner.Width) < 0.05f
                && MathF.Abs(outer.Height - inner.Height) < 0.05f) return false;
            return outer.Left <= inner.Left + tol && outer.Top <= inner.Top + tol
                   && outer.Right >= inner.Right - tol && outer.Bottom >= inner.Bottom - tol;
        }
    }

    private static SKRect BoundsOf(SKPath path)
    {
        var b = path.TightBounds;
        if (b.Width <= 0.01f || b.Height <= 0.01f) b = path.Bounds;
        return b;
    }

    /// <summary>칸 하나의 모양. 본래 요소를 알면 그 종류로, 모르면 Path 그대로 쓴다.</summary>
    private static PaperShape SlotShape(Piece piece, SKRect bounds, float scale, List<SKPath>? holes)
    {
        if (holes is { Count: > 0 })
            return DonutShape(piece, bounds, holes);

        if (piece.Hint is { } hint)
        {
            switch (hint.Kind)
            {
                case "rect":
                    return new PaperShape { Kind = "rect", CornerRadiusMm = 0f };
                case "roundrect":
                {
                    var limit = Math.Min(bounds.Width, bounds.Height) / 2f;
                    var rx = Math.Clamp(hint.RadiusX * scale, 0f, limit);
                    var ry = Math.Clamp(hint.RadiusY * scale, 0f, limit);
                    return new PaperShape
                    {
                        Kind = rx > 0f ? "roundrect" : "rect",
                        CornerRadiusMm = rx,
                        CornerRadiusYMm = Math.Abs(ry - rx) > 0.005f ? ry : null
                    };
                }
                case "ellipse":
                {
                    // 정원이면 반지름을 적어 둔다. 칸이 정사각이 아닐 때 타원으로 늘어나지 않는다.
                    var r = hint.CircleRadius * scale;
                    var fitsBox = Math.Abs(bounds.Width - bounds.Height) < 0.01f;
                    return new PaperShape
                    {
                        Kind = "ellipse",
                        CircleRadiusMm = r > 0f && !fitsBox ? r : null
                    };
                }
            }
        }

        // 모양을 모르는 Path는 칸 왼쪽 위를 0으로 옮겨 그대로 쓴다.
        using var local = new SKPath(piece.Path);
        local.Transform(SKMatrix.CreateTranslation(-bounds.Left, -bounds.Top));
        return new PaperShape
        {
            Kind = "svg",
            CornerRadiusMm = 0f,
            Svg = local.ToSvgPathData(),
            SvgIsLabelMm = true
        };
    }

    /// <summary>
    /// 맞춤 도넛의 칸 하나. 바깥 외곽을 칸 모양으로 두고 안쪽 외곽을 구멍으로 달아,
    /// 둘 사이의 고리만 편집·인쇄 영역이 되게 한다. 구멍 Path는 그리는 쪽에서 홀짝 규칙으로
    /// 빼내므로 여기서는 좌표만 칸 기준으로 옮겨 담는다.
    /// </summary>
    private static PaperShape DonutShape(Piece piece, SKRect bounds, List<SKPath> holes)
    {
        var shift = SKMatrix.CreateTranslation(-bounds.Left, -bounds.Top);

        using var outer = new SKPath(piece.Path);
        outer.Transform(shift);

        var guides = new List<PaperGuidePath>(holes.Count);
        foreach (var hole in holes)
        {
            using var local = new SKPath(hole);
            local.Transform(shift);
            var d = local.ToSvgPathData();
            if (string.IsNullOrWhiteSpace(d)) continue;
            guides.Add(new PaperGuidePath { D = d, IsHole = true });
        }

        return new PaperShape
        {
            Kind = "svg",
            CornerRadiusMm = 0f,
            Svg = outer.ToSvgPathData(),
            SvgIsLabelMm = true,
            Guides = guides.Count > 0 ? guides : null
        };
    }

    private static void DisposeAll(List<Piece> pieces)
    {
        foreach (var piece in pieces)
            piece.Path.Dispose();
    }

    // ---------------------------------------------------------------- 요소 모으기

    /// <summary>그림이 아니라 설명·정의인 요소. 여기 것은 그리지 않는다.</summary>
    private static readonly HashSet<string> SkipTags = new(StringComparer.OrdinalIgnoreCase)
    {
        "defs", "clippath", "mask", "marker", "symbol", "pattern", "filter",
        "style", "title", "desc", "metadata", "script", "foreignobject", "animate", "set"
    };

    private static void Collect(
        XElement element,
        SKMatrix parent,
        SvgStyle inherited,
        Dictionary<string, string> css,
        List<Piece> pieces,
        int depth)
    {
        if (depth > MaxDepth || pieces.Count >= MaxPieces) return;

        var tag = element.Name.LocalName;
        if (SkipTags.Contains(tag)) return;

        var style = ResolveStyle(element, inherited, css, tag);
        if (style.Hidden) return;

        var matrix = Multiply(parent, ParseTransform(element.Attribute("transform")?.Value));

        if (string.Equals(tag, "use", StringComparison.OrdinalIgnoreCase))
        {
            // <use>는 defs의 모양을 참조하는데, 여기서는 defs를 펼치지 않는다.
            EditorLog.Warn("규격 SVG의 <use> 요소는 그리지 않습니다. 모양을 path로 펼쳐 올려 주세요.");
            return;
        }

        var built = BuildElementPath(element, tag);
        if (built is { } shape)
        {
            using var whole = shape.Path;
            whole.Transform(matrix);
            // 한 요소가 여러 외곽선을 담을 수 있다. path 하나에 사각형 여러 개를 넣은 규격이
            // 실제로 쓰이므로, 외곽선 단위로 갈라야 각각을 편집 칸으로 셀 수 있다.
            foreach (var (contour, closed) in SplitContours(whole))
            {
                if (pieces.Count >= MaxPieces)
                {
                    contour.Dispose();
                    continue;
                }
                pieces.Add(new Piece
                {
                    Path = contour,
                    Style = style,
                    Closed = closed,
                    Hint = shape.SingleContourHint
                });
            }
        }

        foreach (var child in element.Elements())
            Collect(child, matrix, style, css, pieces, depth + 1);
    }

    /// <summary>외곽선 단위로 갈라낸다. 닫혔는지(Z) 여부도 같이 돌려준다.</summary>
    private static List<(SKPath Path, bool Closed)> SplitContours(SKPath src)
    {
        var result = new List<(SKPath, bool)>();
        var pts = new SKPoint[4];
        SKPath? cur = null;
        var closed = false;

        using (var it = src.CreateRawIterator())
        {
            SKPathVerb verb;
            while ((verb = it.Next(pts)) != SKPathVerb.Done)
            {
                switch (verb)
                {
                    case SKPathVerb.Move:
                        Flush();
                        cur = new SKPath();
                        cur.MoveTo(pts[0]);
                        closed = false;
                        break;
                    case SKPathVerb.Line:
                        cur?.LineTo(pts[1]);
                        break;
                    case SKPathVerb.Quad:
                        cur?.QuadTo(pts[1], pts[2]);
                        break;
                    case SKPathVerb.Conic:
                        cur?.ConicTo(pts[1], pts[2], it.ConicWeight());
                        break;
                    case SKPathVerb.Cubic:
                        cur?.CubicTo(pts[1], pts[2], pts[3]);
                        break;
                    case SKPathVerb.Close:
                        if (cur is not null)
                        {
                            cur.Close();
                            closed = true;
                        }
                        break;
                }
            }
        }
        Flush();
        return result;

        void Flush()
        {
            if (cur is null) return;
            if (cur.PointCount > 1) result.Add((cur, closed));
            else cur.Dispose();
            cur = null;
        }
    }

    private sealed record ElementShape(SKPath Path, ShapeHint? SingleContourHint);

    /// <summary>요소 하나를 path로 바꾼다. 그릴 수 없는 요소는 null.</summary>
    private static ElementShape? BuildElementPath(XElement el, string tag)
    {
        switch (tag.ToLowerInvariant())
        {
            case "path":
            {
                var d = el.Attribute("d")?.Value;
                if (string.IsNullOrWhiteSpace(d)) return null;
                return new ElementShape(SvgPathParser.Parse(d, 0f, 0f, fitToBounds: false), null);
            }
            case "rect":
            {
                var w = Num(el, "width");
                var h = Num(el, "height");
                if (w <= 0f || h <= 0f) return null;
                var x = Num(el, "x");
                var y = Num(el, "y");
                var rect = new SKRect(x, y, x + w, y + h);
                var rx = Num(el, "rx");
                var ry = Num(el, "ry");
                if (rx <= 0f && ry > 0f) rx = ry;
                if (ry <= 0f && rx > 0f) ry = rx;
                rx = Math.Min(rx, w / 2f);
                ry = Math.Min(ry, h / 2f);
                var path = new SKPath();
                if (rx > 0f && ry > 0f)
                    path.AddRoundRect(new SKRoundRect(rect, rx, ry));
                else
                    path.AddRect(rect);
                return new ElementShape(path,
                    new ShapeHint(rx > 0f ? "roundrect" : "rect", rx, ry, 0f));
            }
            case "circle":
            {
                var r = Num(el, "r");
                if (r <= 0f) return null;
                var path = new SKPath();
                path.AddCircle(Num(el, "cx"), Num(el, "cy"), r);
                return new ElementShape(path, new ShapeHint("ellipse", r, r, r));
            }
            case "ellipse":
            {
                var rx = Num(el, "rx");
                var ry = Num(el, "ry");
                if (rx <= 0f || ry <= 0f) return null;
                var cx = Num(el, "cx");
                var cy = Num(el, "cy");
                var path = new SKPath();
                path.AddOval(new SKRect(cx - rx, cy - ry, cx + rx, cy + ry));
                return new ElementShape(path,
                    new ShapeHint("ellipse", rx, ry, Math.Abs(rx - ry) < 0.0001f ? rx : 0f));
            }
            case "line":
            {
                var path = new SKPath();
                path.MoveTo(Num(el, "x1"), Num(el, "y1"));
                path.LineTo(Num(el, "x2"), Num(el, "y2"));
                return new ElementShape(path, null);
            }
            case "polyline":
            case "polygon":
            {
                var pts = ParsePoints(el.Attribute("points")?.Value);
                if (pts.Count < 2) return null;
                var path = new SKPath();
                path.MoveTo(pts[0]);
                for (var i = 1; i < pts.Count; i++)
                    path.LineTo(pts[i]);
                if (string.Equals(tag, "polygon", StringComparison.OrdinalIgnoreCase))
                    path.Close();
                return new ElementShape(path, null);
            }
            default:
                return null;
        }
    }

    private static List<SKPoint> ParsePoints(string? raw)
    {
        var list = new List<SKPoint>();
        if (string.IsNullOrWhiteSpace(raw)) return list;
        var nums = Regex.Matches(raw, @"[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?");
        for (var i = 0; i + 1 < nums.Count; i += 2)
        {
            if (TryNum(nums[i].Value, out var x) && TryNum(nums[i + 1].Value, out var y))
                list.Add(new SKPoint(x, y));
        }
        return list;
    }

    // ---------------------------------------------------------------- 모양새(style)

    private sealed class SvgStyle
    {
        /// <summary>채움 색(#rrggbb). 없거나 none이면 null.</summary>
        public string? Fill { get; set; }
        /// <summary>선 색(#rrggbb). 없거나 none이면 null.</summary>
        public string? Stroke { get; set; }
        /// <summary>선 굵기(SVG 좌표 단위). 라벨 mm로는 나중에 배율을 곱해 바꾼다.</summary>
        public float? StrokeWidth { get; set; }
        public bool EvenOdd { get; set; }
        public bool Hidden { get; set; }

        public SvgStyle Clone() => new()
        {
            Fill = Fill,
            Stroke = Stroke,
            StrokeWidth = StrokeWidth,
            EvenOdd = EvenOdd,
            Hidden = Hidden
        };
    }

    /// <summary>
    /// 요소의 최종 모양새. 윗 요소에서 물려받은 값에 CSS 규칙 → 속성 → style 속성 순으로 덮는다.
    /// SVG 표준은 fill을 안 적으면 검정으로 채우지만, 여기서는 채우지 않는 것으로 본다.
    /// 용지 규격 SVG는 칼선 그림이라, 표준대로 검정을 채우면 라벨이 통째로 검게 덮인다.
    /// </summary>
    private static SvgStyle ResolveStyle(XElement el, SvgStyle inherited, Dictionary<string, string> css, string tag)
    {
        var style = inherited.Clone();

        if (css.Count > 0)
        {
            if (css.TryGetValue(tag.ToLowerInvariant(), out var byTag))
                ApplyDeclarations(style, byTag);
            var classes = el.Attribute("class")?.Value ?? "";
            foreach (var cls in classes.Split(' ', StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries))
            {
                if (css.TryGetValue("." + cls, out var byClass))
                    ApplyDeclarations(style, byClass);
            }
        }

        foreach (var attr in el.Attributes())
            ApplyDeclaration(style, attr.Name.LocalName, attr.Value);

        ApplyDeclarations(style, el.Attribute("style")?.Value);
        return style;
    }

    private static void ApplyDeclarations(SvgStyle style, string? declarations)
    {
        if (string.IsNullOrWhiteSpace(declarations)) return;
        foreach (var part in declarations.Split(';', StringSplitOptions.RemoveEmptyEntries))
        {
            var colon = part.IndexOf(':');
            if (colon <= 0) continue;
            ApplyDeclaration(style, part[..colon].Trim(), part[(colon + 1)..].Trim());
        }
    }

    private static void ApplyDeclaration(SvgStyle style, string name, string value)
    {
        switch (name.ToLowerInvariant())
        {
            case "fill":
                style.Fill = NormalizeColor(value);
                break;
            case "stroke":
                style.Stroke = NormalizeColor(value);
                break;
            case "stroke-width":
                if (TryNum(value, out var w) && w >= 0f) style.StrokeWidth = w;
                break;
            case "fill-rule":
                style.EvenOdd = value.Trim().Equals("evenodd", StringComparison.OrdinalIgnoreCase);
                break;
            case "display":
                if (value.Trim().Equals("none", StringComparison.OrdinalIgnoreCase)) style.Hidden = true;
                break;
            case "visibility":
                var v = value.Trim();
                if (v.Equals("hidden", StringComparison.OrdinalIgnoreCase)
                    || v.Equals("collapse", StringComparison.OrdinalIgnoreCase)) style.Hidden = true;
                break;
            case "opacity":
                if (TryNum(value, out var o) && o <= 0.001f) style.Hidden = true;
                break;
        }
    }

    /// <summary><c>&lt;style&gt;</c> 안의 간단한 규칙(.cls{...} / tag{...})만 읽는다.</summary>
    private static Dictionary<string, string> ReadCssRules(XElement root)
    {
        var rules = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase);
        foreach (var styleEl in root.DescendantsAndSelf()
                     .Where(e => string.Equals(e.Name.LocalName, "style", StringComparison.OrdinalIgnoreCase)))
        {
            var text = styleEl.Value;
            if (string.IsNullOrWhiteSpace(text)) continue;
            text = Regex.Replace(text, @"/\*.*?\*/", "", RegexOptions.Singleline);
            foreach (Match m in Regex.Matches(text, @"([^{}]+)\{([^{}]*)\}", RegexOptions.Singleline))
            {
                var decls = m.Groups[2].Value.Trim();
                if (decls.Length == 0) continue;
                foreach (var selector in m.Groups[1].Value.Split(',', StringSplitOptions.RemoveEmptyEntries))
                {
                    var key = selector.Trim().ToLowerInvariant();
                    // 자손·자식 선택자까지 흉내낼 생각은 없다. 한 덩이 선택자만 받는다.
                    if (key.Length == 0 || key.Any(char.IsWhiteSpace) || key.Contains('>')) continue;
                    rules[key] = rules.TryGetValue(key, out var prev) ? prev + ";" + decls : decls;
                }
            }
        }
        return rules;
    }

    private static readonly Dictionary<string, string> NamedColors = new(StringComparer.OrdinalIgnoreCase)
    {
        ["black"] = "#000000", ["white"] = "#FFFFFF", ["red"] = "#FF0000",
        ["lime"] = "#00FF00", ["green"] = "#008000", ["blue"] = "#0000FF",
        ["yellow"] = "#FFFF00", ["cyan"] = "#00FFFF", ["aqua"] = "#00FFFF",
        ["magenta"] = "#FF00FF", ["fuchsia"] = "#FF00FF", ["gray"] = "#808080",
        ["grey"] = "#808080", ["silver"] = "#C0C0C0", ["maroon"] = "#800000",
        ["olive"] = "#808000", ["navy"] = "#000080", ["teal"] = "#008080",
        ["purple"] = "#800080", ["orange"] = "#FFA500", ["pink"] = "#FFC0CB",
        ["brown"] = "#A52A2A"
    };

    /// <summary>색 표기를 #rrggbb로 맞춘다. 칠하지 않는 값이면 null.</summary>
    private static string? NormalizeColor(string? raw)
    {
        var text = (raw ?? "").Trim();
        if (text.Length == 0) return null;
        if (text.Equals("none", StringComparison.OrdinalIgnoreCase)
            || text.Equals("transparent", StringComparison.OrdinalIgnoreCase)
            || text.Equals("inherit", StringComparison.OrdinalIgnoreCase)
            || text.StartsWith("url(", StringComparison.OrdinalIgnoreCase))
            return null;

        if (NamedColors.TryGetValue(text, out var named))
            return named;

        if (text.StartsWith('#'))
        {
            var hex = text[1..];
            if (hex.Length == 3 && hex.All(Uri.IsHexDigit))
                return $"#{hex[0]}{hex[0]}{hex[1]}{hex[1]}{hex[2]}{hex[2]}".ToUpperInvariant();
            if ((hex.Length == 6 || hex.Length == 8) && hex.All(Uri.IsHexDigit))
                return text.ToUpperInvariant();
            return DefaultStroke;
        }

        var rgb = Regex.Match(text, @"^rgba?\(([^)]*)\)$", RegexOptions.IgnoreCase);
        if (rgb.Success)
        {
            var parts = rgb.Groups[1].Value.Split(',', StringSplitOptions.RemoveEmptyEntries);
            if (parts.Length >= 3
                && TryNum(parts[0], out var r) && TryNum(parts[1], out var g) && TryNum(parts[2], out var b))
            {
                static int Byte(float n) => (int)Math.Clamp(MathF.Round(n), 0f, 255f);
                return $"#{Byte(r):X2}{Byte(g):X2}{Byte(b):X2}";
            }
        }

        // 모르는 표기는 안 보이게 버리기보다 기본 선 색으로 그려 눈에 띄게 둔다.
        return DefaultStroke;
    }

    // ---------------------------------------------------------------- 행렬

    /// <summary>
    /// transform 속성을 행렬로 바꾼다. SVG는 적어 둔 순서의 역순으로 점에 먹으므로
    /// (t1 t2 라면 t2 가 먼저) 왼쪽부터 차례로 곱해 나가면 된다.
    /// </summary>
    private static SKMatrix ParseTransform(string? raw)
    {
        var m = SKMatrix.CreateIdentity();
        if (string.IsNullOrWhiteSpace(raw)) return m;

        foreach (Match op in Regex.Matches(raw, @"(\w+)\s*\(([^)]*)\)"))
        {
            var args = new List<float>();
            foreach (Match n in Regex.Matches(op.Groups[2].Value, @"[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?"))
            {
                if (TryNum(n.Value, out var value)) args.Add(value);
            }

            var step = op.Groups[1].Value.ToLowerInvariant() switch
            {
                "translate" when args.Count >= 1 =>
                    SKMatrix.CreateTranslation(args[0], args.Count > 1 ? args[1] : 0f),
                "scale" when args.Count >= 1 =>
                    SKMatrix.CreateScale(args[0], args.Count > 1 ? args[1] : args[0]),
                "rotate" when args.Count >= 3 => SKMatrix.CreateRotationDegrees(args[0], args[1], args[2]),
                "rotate" when args.Count >= 1 => SKMatrix.CreateRotationDegrees(args[0]),
                "skewx" when args.Count >= 1 => SKMatrix.CreateSkew(MathF.Tan(args[0] * MathF.PI / 180f), 0f),
                "skewy" when args.Count >= 1 => SKMatrix.CreateSkew(0f, MathF.Tan(args[0] * MathF.PI / 180f)),
                "matrix" when args.Count >= 6 => new SKMatrix
                {
                    ScaleX = args[0], SkewY = args[1], SkewX = args[2],
                    ScaleY = args[3], TransX = args[4], TransY = args[5], Persp2 = 1f
                },
                _ => SKMatrix.CreateIdentity()
            };
            m = Multiply(m, step);
        }
        return m;
    }

    /// <summary>
    /// 행렬 곱 a·b. 점에는 b가 먼저, a가 나중에 먹는다.
    /// SkiaSharp의 Pre/PostConcat 방향을 헷갈릴 일이 없도록 직접 곱한다.
    /// </summary>
    private static SKMatrix Multiply(SKMatrix a, SKMatrix b) => new()
    {
        ScaleX = a.ScaleX * b.ScaleX + a.SkewX * b.SkewY,
        SkewX = a.ScaleX * b.SkewX + a.SkewX * b.ScaleY,
        TransX = a.ScaleX * b.TransX + a.SkewX * b.TransY + a.TransX,
        SkewY = a.SkewY * b.ScaleX + a.ScaleY * b.SkewY,
        ScaleY = a.SkewY * b.SkewX + a.ScaleY * b.ScaleY,
        TransY = a.SkewY * b.TransX + a.ScaleY * b.TransY + a.TransY,
        Persp0 = 0f,
        Persp1 = 0f,
        Persp2 = 1f
    };

    private static float Num(XElement el, string name)
        => TryNum(el.Attribute(name)?.Value, out var v) ? v : 0f;

    /// <summary>길이 표기에서 숫자만 읽는다. px·mm·pt 같은 단위는 떼고 값만 쓴다.</summary>
    private static bool TryNum(string? raw, out float value)
    {
        value = 0f;
        if (string.IsNullOrWhiteSpace(raw)) return false;
        var m = Regex.Match(raw, @"[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?");
        return m.Success
               && float.TryParse(m.Value, NumberStyles.Float, CultureInfo.InvariantCulture, out value);
    }
}
