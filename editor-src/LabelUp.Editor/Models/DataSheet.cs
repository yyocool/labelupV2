namespace LabelUp.Editor.Models;

public sealed class DataSheet
{
    public string SourceName { get; set; } = "";
    public string SourceKind { get; set; } = "";
    public List<string> Columns { get; set; } = [];
    public List<List<string>> Rows { get; set; } = [];

    public int RowCount => Rows.Count;
    public int ColumnCount => Columns.Count;

    public string Get(int row, string? column)
    {
        if (string.IsNullOrWhiteSpace(column) || row < 0 || row >= Rows.Count) return "";
        var i = Columns.FindIndex(c => string.Equals(c, column, StringComparison.OrdinalIgnoreCase));
        if (i < 0) return "";
        var line = Rows[row];
        return i < line.Count ? line[i] ?? "" : "";
    }

    public void SetCell(int row, int col, string value)
    {
        if (row < 0 || row >= Rows.Count || col < 0 || col >= Columns.Count) return;
        var line = Rows[row];
        while (line.Count <= col) line.Add("");
        line[col] = value;
    }

    public void RemoveRow(int row)
    {
        if (row < 0 || row >= Rows.Count) return;
        Rows.RemoveAt(row);
    }

    public DataSheet Clone()
    {
        return new DataSheet
        {
            SourceName = SourceName,
            SourceKind = SourceKind,
            Columns = [.. Columns],
            Rows = Rows.Select(r => r.ToList()).ToList()
        };
    }
}

public sealed class VendorPaperMap
{
    public int Version { get; set; } = 1;
    public List<VendorPaperMapEntry> Entries { get; set; } = [];

    public string? Resolve(string vendor, string vendorPaperNo)
    {
        if (string.IsNullOrWhiteSpace(vendorPaperNo)) return null;
        var hit = Entries.FirstOrDefault(e =>
            string.Equals(e.Vendor, vendor, StringComparison.OrdinalIgnoreCase)
            && string.Equals(e.VendorPaperNo, vendorPaperNo, StringComparison.OrdinalIgnoreCase));
        return hit?.OurPaperNo;
    }

    public VendorPaperMap Clone()
    {
        return new VendorPaperMap
        {
            Version = Version,
            Entries = Entries.Select(e => new VendorPaperMapEntry
            {
                Vendor = e.Vendor,
                VendorPaperNo = e.VendorPaperNo,
                OurPaperNo = e.OurPaperNo,
                Note = e.Note
            }).ToList()
        };
    }
}

public sealed class VendorPaperMapEntry
{
    public string Vendor { get; set; } = "anylabel";
    public string VendorPaperNo { get; set; } = "";
    public string OurPaperNo { get; set; } = "";
    public string Note { get; set; } = "";
}

public sealed class UserAsset
{
    public string Id { get; set; } = Guid.NewGuid().ToString("N");
    public string Name { get; set; } = "";
    public string DataUrl { get; set; } = "";
}
