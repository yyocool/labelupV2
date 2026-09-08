namespace LabelUp.Editor.Models;

public sealed class ApiEnvelope<T>
{
    public bool Success { get; set; }
    public T? Data { get; set; }
    public string? Message { get; set; }
}

public sealed class ShopPaperCatalogDto
{
    public List<ShopPaperItem> Items { get; set; } = [];
    public List<ShopPaperCategory> Categories { get; set; } = [];
}

public sealed class ShopPaperCategory
{
    public int Id { get; set; }
    public string Name { get; set; } = "";
}

public sealed class ShopPaperItem
{
    public int Id { get; set; }
    public string Name { get; set; } = "";
    public string Sku { get; set; } = "";
    public string Kind { get; set; } = "label";
    public string? ThumbnailUrl { get; set; }
    public int CategoryId { get; set; }
    public string? CategoryName { get; set; }
    public int? SpecId { get; set; }
    public string? SpecName { get; set; }
    public float WidthMm { get; set; }
    public float HeightMm { get; set; }
    public string? Shape { get; set; }
    public int LabelsPerSheet { get; set; }
    public string? Material { get; set; }
    public string? CompatFormtec { get; set; }
    public string? CompatIlabel { get; set; }
    public string? CompatAnylabel { get; set; }

    public bool HasCompat =>
        CompatFormtecCodes.Count > 0
        || CompatIlabelCodes.Count > 0
        || CompatAnylabelCodes.Count > 0;

    public IReadOnlyList<string> CompatFormtecCodes => SplitCompat(CompatFormtec);
    public IReadOnlyList<string> CompatIlabelCodes => SplitCompat(CompatIlabel);
    public IReadOnlyList<string> CompatAnylabelCodes => SplitCompat(CompatAnylabel);

    public static IReadOnlyList<string> SplitCompat(string? raw)
    {
        if (string.IsNullOrWhiteSpace(raw)) return [];
        var list = new List<string>();
        foreach (var part in raw.Split([',', ';', '\n', '\r', '|'], StringSplitOptions.RemoveEmptyEntries))
        {
            var code = part.Trim();
            if (code.Length == 0) continue;
            if (!list.Exists(x => string.Equals(x, code, StringComparison.OrdinalIgnoreCase)))
                list.Add(code);
        }
        return list;
    }

    public string SizeText
    {
        get
        {
            if (WidthMm > 0 && HeightMm > 0)
                return $"{WidthMm:0.##}×{HeightMm:0.##} mm";
            return string.IsNullOrWhiteSpace(SpecName) ? "-" : SpecName!;
        }
    }
}
