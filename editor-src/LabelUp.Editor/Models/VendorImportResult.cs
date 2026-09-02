namespace LabelUp.Editor.Models;

public sealed class VendorImportResult
{
    public bool Ok { get; set; }
    public string Error { get; set; } = "";
    public string FileName { get; set; } = "";
    public string VendorId { get; set; } = "";
    public string VendorName { get; set; } = "";
    public string PaperNo { get; set; } = "";
    public string PaperName { get; set; } = "";
    public string PaperLayout { get; set; } = "";
    public bool HasData { get; set; }
    public int DataRows { get; set; }
    public List<string> DataColumns { get; set; } = [];
    public List<string> DataPreview { get; set; } = [];
    public Dictionary<string, int> TypeCounts { get; set; } = new(StringComparer.Ordinal);
    public List<VendorImportItem> Items { get; set; } = [];
    public int ObjectCount => Items.Count;
    public LabelDocument? Document { get; set; }
}

public sealed class VendorImportItem
{
    public string Kind { get; set; } = "";
    public string Summary { get; set; } = "";
    public string Geometry { get; set; } = "";
}
