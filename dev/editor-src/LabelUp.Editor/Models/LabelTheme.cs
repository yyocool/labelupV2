using System.Text.Json;

namespace LabelUp.Editor.Models;

public sealed class LabelThemeCatalogDto
{
    public List<LabelThemeItem> Items { get; set; } = [];
    public List<LabelThemeCategory> Categories { get; set; } = [];
}

public sealed class LabelThemeCategory
{
    public string Key { get; set; } = "";
    public string Name { get; set; } = "";
}

public sealed class LabelThemeItem
{
    public int Id { get; set; }
    public string Slug { get; set; } = "";
    public string Name { get; set; } = "";
    public string Category { get; set; } = "";
    public string CategoryKey { get; set; } = "";
    public string CategoryName { get; set; } = "";
    public string? Tags { get; set; }
    public string? Description { get; set; }
    public string Tone { get; set; } = "#7B2840";
    public string? PaperNo { get; set; }
    public float WidthMm { get; set; }
    public float HeightMm { get; set; }
    public string? Shape { get; set; }
    public string? PreviewText { get; set; }
    public string? PreviewSvg { get; set; }

    public string DisplayCategory =>
        !string.IsNullOrWhiteSpace(CategoryName) ? CategoryName
        : !string.IsNullOrWhiteSpace(Category) ? Category
        : CategoryKey;

    public string SizeText => WidthMm > 0 && HeightMm > 0
        ? $"{WidthMm:0.#}×{HeightMm:0.#} mm"
        : "";
}

public sealed class PendingClipartDto
{
    public string? Url { get; set; }
    public string? Title { get; set; }
    public string? Fit { get; set; }
}

public sealed class PendingDocumentDto
{
    public string? Json { get; set; }
    public string? Title { get; set; }
    public string? ProjectId { get; set; }
}

public sealed class PendingVendorDto
{
    public string? FileName { get; set; }
    public string? DataUrl { get; set; }
}

public sealed class LabelThemeDetailDto
{
    public int Id { get; set; }
    public string Slug { get; set; } = "";
    public string Name { get; set; } = "";
    public string? Category { get; set; }
    public string? Tone { get; set; }
    public JsonElement Document { get; set; }
}
