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
            PrintOffsetYMm = PrintOffsetYMm
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
        }
    }

    public void ApplyPaper(PaperSpec paper, bool keepDesign = true)
    {
        var prototype = keepDesign && Pages.Count > 0 && Pages[0].Cells.Count > 0
            ? Pages[0].Cells[0].Objects.Select(o => o.Clone()).ToList()
            : null;
        var pageCount = Math.Max(1, Pages.Count);
        Paper = paper.Clone();
        Pages.Clear();
        for (var i = 0; i < pageCount; i++)
            Pages.Add(LabelPage.Create(i, paper.LabelsPerPage, i == 0 ? prototype : null));
    }

    public LabelPage AddPage(IReadOnlyList<DesignObject>? prototype = null)
    {
        EnsureStructure();
        var page = LabelPage.Create(Pages.Count, Paper.LabelsPerPage, prototype);
        Pages.Add(page);
        return page;
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
            style = HashCode.Combine(style, o.Fill, o.Stroke, o.Visible, o.ShapeKind, o.Locked, o.TextWrap);
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

    public void EnsurePagesForData()
    {
        EnsureStructure();
        var rows = Data?.RowCount ?? 0;
        if (rows <= 0) return;
        var per = Math.Max(1, Paper.LabelsPerPage);
        var need = Math.Max(1, (int)Math.Ceiling(rows / (double)per));
        while (Pages.Count < need)
            AddPage();
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
