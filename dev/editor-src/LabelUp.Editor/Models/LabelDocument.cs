using System.Text.Json;
using System.Text.Json.Serialization;

namespace LabelUp.Editor.Models;

public sealed class LabelCell
{
    public int Index { get; set; }
    public List<DesignObject> Objects { get; set; } = [];

    public LabelCell Clone() => new()
    {
        Index = Index,
        Objects = Objects.Select(o => o.Clone()).ToList()
    };

    public IEnumerable<DesignObject> OrderedObjects()
        => Objects.Where(o => o.Visible).OrderBy(o => o.ZIndex).ThenBy(o => Objects.IndexOf(o));
}

public sealed class LabelPage
{
    public int Index { get; set; }
    public List<LabelCell> Cells { get; set; } = [];

    public LabelPage Clone() => new()
    {
        Index = Index,
        Cells = Cells.Select(c => c.Clone()).ToList()
    };

    public static LabelPage Create(int index, int cellCount, IReadOnlyList<DesignObject>? prototype = null)
    {
        var page = new LabelPage { Index = index };
        for (var i = 0; i < Math.Max(1, cellCount); i++)
        {
            var cell = new LabelCell { Index = i };
            if (prototype is { Count: > 0 } && i == 0)
            {
                foreach (var obj in prototype)
                {
                    var copy = obj.Clone();
                    copy.Id = Guid.NewGuid().ToString("N");
                    cell.Objects.Add(copy);
                }
            }
            page.Cells.Add(cell);
        }
        return page;
    }

    public void EnsureCellCount(int count)
    {
        count = Math.Max(1, count);
        while (Cells.Count < count)
            Cells.Add(new LabelCell { Index = Cells.Count });
        if (Cells.Count > count)
            Cells.RemoveRange(count, Cells.Count - count);
        for (var i = 0; i < Cells.Count; i++)
            Cells[i].Index = i;
    }
}

public sealed class LabelDocument
{
    public int Version { get; set; } = 2;
    public string Format { get; set; } = "labelup";
    public string Name { get; set; } = "새 라벨 디자인";
    public string Background { get; set; } = "#FFFFFF";
    public PaperSpec Paper { get; set; } = PaperSpec.CreateDefault();
    public List<LabelPage> Pages { get; set; } = [];
    public DataSheet? Data { get; set; }
    public float PrintOffsetXMm { get; set; }
    public float PrintOffsetYMm { get; set; }
    /// <summary>변환 출처. formtec / anylabel / ilabel 등. 바코드 기본 인코딩에 쓴다.</summary>
    public string? SourceVendor { get; set; }

    [JsonIgnore]
    public float WidthMm
    {
        get => Paper.LabelWidthMm;
        set => Paper.LabelWidthMm = value;
    }

    [JsonIgnore]
    public float HeightMm
    {
        get => Paper.LabelHeightMm;
        set => Paper.LabelHeightMm = value;
    }

    public LabelDocument Clone()
    {
        return new LabelDocument
        {
            Version = Version,
            Format = Format,
            Name = Name,
            Background = Background,
            Paper = Paper.Clone(),
            Pages = Pages.Select(p => p.Clone()).ToList(),
            Data = Data?.Clone(),
            PrintOffsetXMm = PrintOffsetXMm,
            PrintOffsetYMm = PrintOffsetYMm,
            SourceVendor = SourceVendor,
        };
    }

    public LabelCell GetCell(int pageIndex, int cellIndex)
    {
        EnsureStructure();
        pageIndex = Math.Clamp(pageIndex, 0, Pages.Count - 1);
        var page = Pages[pageIndex];
        cellIndex = Math.Clamp(cellIndex, 0, page.Cells.Count - 1);
        return page.Cells[cellIndex];
    }

    public int GlobalIndex(int pageIndex, int cellIndex)
        => pageIndex * Math.Max(1, Paper.LabelsPerPage) + cellIndex;

    public void EnsureStructure()
    {
        var per = Math.Max(1, Paper.LabelsPerPage);
        if (Pages.Count == 0)
            Pages.Add(LabelPage.Create(0, per));
        for (var i = 0; i < Pages.Count; i++)
        {
            Pages[i].Index = i;
            Pages[i].EnsureCellCount(per);
            foreach (var obj in Pages[i].Cells.SelectMany(c => c.Objects))
                TextModes.Unify(obj);
        }
    }

    /// <summary>용지를 바꿀 때 만들 수 있는 최대 페이지 수. 1칸짜리 용지로 바꿔도 여기서 멈춘다.</summary>
    public const int MaxPagesOnPaperChange = 300;

    /// <summary>라벨 상자 밖으로 완전히 벗어나 화면에 그려지지 않는 항목인지.</summary>
    public static bool IsOutsideLabel(DesignObject obj, float widthMm, float heightMm)
        => obj.X >= widthMm || obj.Y >= heightMm
           || obj.X + obj.Width <= 0f || obj.Y + obj.Height <= 0f;

    /// <summary>
    /// 용지를 바꾼다. 칸 내용은 전역 라벨 번호를 그대로 유지한다(1번 라벨 내용은 새 용지의 1번 라벨로).
    /// 페이지당 칸 수가 달라지면 내용이 들어 있는 마지막 칸까지 담을 만큼 페이지를 다시 만든다.
    ///
    /// <paramref name="scaleObjects"/> 가 true 면 라벨 크기가 바뀐 비율만큼 항목의 위치·크기·글자도 함께 바꾼다.
    /// false 면 위치를 그대로 두므로, 라벨이 작아지면 밖으로 나간 부분은 그려지지 않는다.
    ///
    /// <paramref name="dropHiddenObjects"/> 가 true 면 바꾸기 전 라벨 밖에 있어 보이지 않던 항목을 지운다.
    /// 그대로 두면 새 라벨이 더 클 때 없던 항목이 튀어나온 것처럼 보인다.
    /// </summary>
    public void ApplyPaper(
        PaperSpec paper,
        bool keepDesign = true,
        bool scaleObjects = false,
        bool dropHiddenObjects = false)
    {
        var oldWidth = Paper.LabelWidthMm;
        var oldHeight = Paper.LabelHeightMm;
        var kept = keepDesign
            ? Pages.SelectMany(p => p.Cells).Select(c => c.Objects).ToList()
            : [];
        var oldPageCount = Math.Max(1, Pages.Count);

        if (dropHiddenObjects && oldWidth > 0.01f && oldHeight > 0.01f)
        {
            foreach (var objects in kept)
                objects.RemoveAll(obj => IsOutsideLabel(obj, oldWidth, oldHeight));
        }

        Paper = paper.Clone();
        var perPage = Math.Max(1, paper.LabelsPerPage);

        if (scaleObjects && oldWidth > 0.01f && oldHeight > 0.01f)
        {
            var sx = paper.LabelWidthMm / oldWidth;
            var sy = paper.LabelHeightMm / oldHeight;
            if (Math.Abs(sx - 1f) > 0.001f || Math.Abs(sy - 1f) > 0.001f)
            {
                foreach (var obj in kept.SelectMany(objects => objects))
                    ScaleObject(obj, sx, sy);
            }
        }

        var filled = kept.FindLastIndex(objects => objects.Count > 0) + 1;
        var pageCount = filled > 0
            ? Math.Clamp((filled + perPage - 1) / perPage, 1, MaxPagesOnPaperChange)
            : Math.Clamp(oldPageCount, 1, MaxPagesOnPaperChange);

        Pages.Clear();
        for (var i = 0; i < pageCount; i++)
        {
            var page = LabelPage.Create(i, perPage);
            for (var c = 0; c < page.Cells.Count; c++)
            {
                var global = i * perPage + c;
                if (global < filled)
                    page.Cells[c].Objects = kept[global];
            }
            Pages.Add(page);
        }
    }

    /// <summary>
    /// 라벨 크기가 바뀐 비율만큼 항목을 줄이고 늘린다.
    /// 글자와 선 굵기는 찌그러져 보이지 않도록 가로·세로 중 작은 배율을 쓴다.
    /// </summary>
    private static void ScaleObject(DesignObject obj, float sx, float sy)
    {
        var uniform = Math.Min(sx, sy);
        obj.X *= sx;
        obj.Y *= sy;
        obj.Width *= sx;
        obj.Height *= sy;
        obj.FontSize *= uniform;
        obj.StrokeWidth *= uniform;
        obj.LetterSpacing *= uniform;
        obj.TextPaddingXMm *= sx;
        obj.TableBorderWidth *= uniform;
        if (obj.RichText is { Count: > 0 })
        {
            foreach (var span in obj.RichText.SelectMany(p => p.Spans))
                span.FontSize *= uniform;
        }
    }

    public LabelPage AddPage(IReadOnlyList<DesignObject>? prototype = null)
    {
        EnsureStructure();
        var page = LabelPage.Create(Pages.Count, Paper.LabelsPerPage, prototype);
        Pages.Add(page);
        return page;
    }

    /// <summary>페이지 삭제. 마지막 한 장은 남긴다.</summary>
    public bool RemovePage(int pageIndex)
    {
        EnsureStructure();
        if (Pages.Count <= 1 || pageIndex < 0 || pageIndex >= Pages.Count)
            return false;
        Pages.RemoveAt(pageIndex);
        for (var i = 0; i < Pages.Count; i++)
            Pages[i].Index = i;
        return true;
    }

    public void ApplyDesignToPage(int pageIndex, IReadOnlyList<DesignObject> prototype)
    {
        EnsureStructure();
        if (pageIndex < 0 || pageIndex >= Pages.Count) return;
        var page = Pages[pageIndex];
        foreach (var cell in page.Cells)
        {
            cell.Objects = prototype.Select(o =>
            {
                var c = o.Clone();
                c.Id = Guid.NewGuid().ToString("N");
                return c;
            }).ToList();
        }
    }

    public void ApplyDesignToAll(IReadOnlyList<DesignObject> prototype)
    {
        EnsureStructure();
        foreach (var page in Pages)
        {
            foreach (var cell in page.Cells)
            {
                cell.Objects = prototype.Select(o =>
                {
                    var c = o.Clone();
                    c.Id = Guid.NewGuid().ToString("N");
                    return c;
                }).ToList();
            }
        }
    }

    public string ObjectsFingerprint()
    {
        EnsureStructure();
        var sum = 0f;
        var count = 0;
        var style = 0;
        foreach (var page in Pages)
        foreach (var cell in page.Cells)
        foreach (var o in cell.Objects)
        {
            sum += o.X + o.Y + o.Width + o.Height + o.Rotation + o.ZIndex + o.StrokeWidth + o.Opacity
                   + (o.Text?.Length ?? 0) + (o.BarcodeValue?.Length ?? 0) + (o.ImageData?.Length ?? 0);
            style = HashCode.Combine(style, o.Fill, o.Stroke, o.Visible, o.ShapeKind, o.DashStyle, o.Locked, o.TextWrap);
            style = HashCode.Combine(style, o.FlipHorizontal, o.FlipVertical);
            style = HashCode.Combine(style, o.GradientEnd, o.GradientDirection, o.GradientPrecision);
            style = HashCode.Combine(style, o.Text, o.BarcodeValue, o.FontFamily);
            count++;
        }

        var shape = Paper.Shape;
        var shapeKey = HashCode.Combine(
            shape.Kind,
            shape.Svg,
            shape.GuideSvg,
            shape.Guides?.Count ?? 0,
            shape.SvgIsLabelMm,
            Paper.PaperNo);
        shapeKey = HashCode.Combine(shapeKey, Paper.LabelWidthMm, Paper.LabelHeightMm, Name);
        return $"{count}|{sum:0.##}|{style:X8}|{Background}|{Data?.RowCount ?? 0}|{shapeKey:X8}";
    }

    public void EnsurePageCount(int count)
    {
        EnsureStructure();
        count = Math.Max(1, count);
        while (Pages.Count < count)
            AddPage();
    }

    public int EnsureCustomSerialLabels(IReadOnlyList<DesignObject> prototype)
    {
        EnsureStructure();
        var total = CustomTextFormats.TotalLabels(prototype);
        if (total <= 0) return 0;
        var per = Math.Max(1, Paper.LabelsPerPage);
        var pages = Math.Max(1, (int)Math.Ceiling(total / (double)per));
        while (Pages.Count < pages)
            AddPage();
        while (Pages.Count > pages)
            Pages.RemoveAt(Pages.Count - 1);
        for (var i = 0; i < Pages.Count; i++)
            Pages[i].Index = i;

        var copies = prototype.Select(o => o.Clone()).ToList();
        var index = 0;
        foreach (var page in Pages)
        {
            page.EnsureCellCount(per);
            foreach (var cell in page.Cells)
            {
                if (index < total)
                {
                    cell.Objects = copies.Select(o =>
                    {
                        var c = o.Clone();
                        c.Id = Guid.NewGuid().ToString("N");
                        return c;
                    }).ToList();
                }
                else
                    cell.Objects.Clear();
                index++;
            }
        }
        return total;
    }

    public IEnumerable<string> BoundColumnNames()
        => Pages.SelectMany(p => p.Cells).SelectMany(c => c.Objects)
            .Where(o => o.DataBound && !string.IsNullOrWhiteSpace(o.DataColumn))
            .Select(o => o.DataColumn!.Trim())
            .Distinct(StringComparer.OrdinalIgnoreCase);

    public bool HasBoundColumn(string? column)
        => !string.IsNullOrWhiteSpace(column)
           && BoundColumnNames().Any(c => string.Equals(c, column, StringComparison.OrdinalIgnoreCase));

    /// <summary>변환 파일에 표는 없어도 객체에 연결된 열 이름은 패널에 보여 준다.</summary>
    public void EnsureDataFromBoundColumns()
    {
        EnsureStructure();
        var cols = BoundColumnNames().ToList();
        if (cols.Count == 0) return;
        Data ??= new DataSheet { SourceName = "변환 데이터", SourceKind = "bound" };
        foreach (var col in cols)
        {
            if (!Data.Columns.Any(c => string.Equals(c, col, StringComparison.OrdinalIgnoreCase)))
                Data.AddColumn(col);
        }
    }

    /// <summary>
    /// 데이터 행 수만큼 라벨을 만들고, 같은 디자인을 1행→1칸 순으로 넣는다.
    /// prototype이 있으면 그 칸을 기준으로 하고, 없으면 객체가 있는 첫 칸을 쓴다.
    /// </summary>
    public void EnsurePagesForData(IReadOnlyList<DesignObject>? prototype = null)
    {
        EnsureStructure();
        var rows = Data?.RowCount ?? 0;
        if (rows <= 0) return;
        var per = Math.Max(1, Paper.LabelsPerPage);
        var need = Math.Max(1, (int)Math.Ceiling(rows / (double)per));

        List<DesignObject>? copies = null;
        if (prototype is { Count: > 0 })
            copies = prototype.Select(o => o.Clone()).ToList();
        else
        {
            foreach (var cell in Pages.SelectMany(p => p.Cells))
            {
                if (cell.Objects.Count == 0) continue;
                copies = cell.Objects.Select(o => o.Clone()).ToList();
                break;
            }
        }

        while (Pages.Count < need)
            AddPage();
        while (Pages.Count > need)
            Pages.RemoveAt(Pages.Count - 1);
        for (var i = 0; i < Pages.Count; i++)
            Pages[i].Index = i;

        if (copies is not { Count: > 0 }) return;

        var index = 0;
        foreach (var page in Pages)
        {
            page.EnsureCellCount(per);
            foreach (var cell in page.Cells)
            {
                if (index < rows)
                {
                    cell.Objects = copies.Select(o =>
                    {
                        var c = o.Clone();
                        c.Id = Guid.NewGuid().ToString("N");
                        return c;
                    }).ToList();
                }
                else
                    cell.Objects.Clear();
                index++;
            }
        }
    }

    public static LabelDocument CreateBlank(PaperSpec? paper = null)
    {
        paper ??= PaperSpec.CreateDefault();
        var doc = new LabelDocument
        {
            Paper = paper.Clone(),
            Background = paper.LabelColor
        };
        var sample = DesignObject.CreateDefault(ObjectType.Text, paper.LabelWidthMm * 0.12f, paper.LabelHeightMm * 0.28f);
        sample.Width = paper.LabelWidthMm * 0.76f;
        sample.Height = paper.LabelHeightMm * 0.44f;
        sample.Fill = "#7B2840";
        sample.Text = "라벨업";
        sample.Bold = true;
        sample.FontSize = Math.Clamp(paper.LabelHeightMm * 0.28f, 3.5f, 9f);
        sample.ZIndex = 1;
        doc.Pages.Add(LabelPage.Create(0, paper.LabelsPerPage, [sample]));
        return doc;
    }

    public static LabelDocument CreateBlank(float widthMm, float heightMm)
    {
        var paper = PaperSpec.CreateDefault();
        paper.LabelWidthMm = widthMm;
        paper.LabelHeightMm = heightMm;
        paper.Columns = 1;
        paper.Rows = 1;
        paper.PaperWidthMm = Math.Max(widthMm, 210f);
        paper.PaperHeightMm = Math.Max(heightMm, 297f);
        paper.RecalcMarginsFromGaps();
        paper.PaperNo = "CUSTOM";
        paper.Name = $"{widthMm:0.#}×{heightMm:0.#} mm";
        return CreateBlank(paper);
    }
}

public static class LabelDocumentJson
{
    public static readonly JsonSerializerOptions Options = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
        PropertyNameCaseInsensitive = true,
        WriteIndented = true,
        Converters = { new JsonStringEnumConverter(JsonNamingPolicy.CamelCase) },
        DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull
    };

    public static readonly JsonSerializerOptions Compact = new()
    {
        PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
        PropertyNameCaseInsensitive = true,
        WriteIndented = false,
        Converters = { new JsonStringEnumConverter(JsonNamingPolicy.CamelCase) },
        DefaultIgnoreCondition = JsonIgnoreCondition.WhenWritingNull
    };

    public static LabelDocument Parse(string json)
    {
        using var doc = JsonDocument.Parse(json);
        var root = doc.RootElement;
        if (root.TryGetProperty("document", out var inner))
            root = inner;

        if (root.TryGetProperty("pages", out _))
        {
            var parsed = JsonSerializer.Deserialize<LabelDocument>(root.GetRawText(), Options)
                         ?? LabelDocument.CreateBlank();
            parsed.EnsureStructure();
            return parsed;
        }

        return MigrateV1(root);
    }

    private static LabelDocument MigrateV1(JsonElement root)
    {
        var name = root.TryGetProperty("Name", out var n) ? n.GetString()
            : root.TryGetProperty("name", out var n2) ? n2.GetString()
            : "새 라벨 디자인";
        var w = GetFloat(root, "WidthMm", "widthMm", 50f);
        var h = GetFloat(root, "HeightMm", "heightMm", 30f);
        var bg = root.TryGetProperty("Background", out var b) ? b.GetString()
            : root.TryGetProperty("background", out var b2) ? b2.GetString()
            : "#FFFFFF";
        var objects = new List<DesignObject>();
        if (root.TryGetProperty("Objects", out var arr) || root.TryGetProperty("objects", out arr))
        {
            foreach (var el in arr.EnumerateArray())
            {
                var o = JsonSerializer.Deserialize<DesignObject>(el.GetRawText(), Options);
                if (o != null) objects.Add(o);
            }
        }

        var migrated = LabelDocument.CreateBlank(w, h);
        migrated.Name = string.IsNullOrWhiteSpace(name) ? migrated.Name : name!;
        migrated.Background = bg ?? "#FFFFFF";
        if (objects.Count > 0)
            migrated.Pages[0].Cells[0].Objects = objects;
        return migrated;
    }

    private static float GetFloat(JsonElement root, string a, string b, float fallback)
    {
        if (root.TryGetProperty(a, out var x) && x.TryGetSingle(out var va)) return va;
        if (root.TryGetProperty(b, out var y) && y.TryGetSingle(out var vb)) return vb;
        return fallback;
    }

    public static string Serialize(LabelDocument document, bool indent = true)
    {
        document.EnsureStructure();
        var payload = new
        {
            format = "labelup",
            version = document.Version,
            savedAt = DateTime.UtcNow.ToString("o"),
            document
        };
        return JsonSerializer.Serialize(payload, indent ? Options : Compact);
    }
}
