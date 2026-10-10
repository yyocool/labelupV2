namespace LabelUp.Editor.Models;

public sealed class DataSheet
{
    public string SourceName { get; set; } = "";
    public string SourceKind { get; set; } = "";
    public List<string> Columns { get; set; } = [];
    public List<List<string>> Rows { get; set; } = [];

    /// <summary>
    /// 엑셀·CSV 에서 열 이름으로 써 버린 첫 줄의 원래 값.
    ///
    /// 머리글 없이 첫 줄부터 바로 기록이 적힌 파일을 가져오면 그 기록이 열 이름이 되어
    /// 라벨에서 사라진다. 되살릴 수 있게 원래 값을 들고 있는다.
    /// 열 이름이 파일 안의 줄에서 온 것이 아닌 자료(MDB·직접 작성·변환)는 비어 있다.
    /// </summary>
    public List<string> HeaderTexts { get; set; } = [];

    /// <summary>첫 줄을 자료로도 쓰는지. 켜면 첫 줄이 1행이 되어 라벨이 한 칸 늘어난다.</summary>
    public bool FirstRowIsData { get; set; }

    /// <summary>첫 줄을 자료로 되돌릴 수 있는 자료인지. 파일의 한 줄을 열 이름으로 써 버린 것만 해당한다.</summary>
    public bool CanUseFirstRowAsData => HeaderTexts.Count > 0;

    public int RowCount => Rows.Count;
    public int ColumnCount => Columns.Count;

    /// <summary>
    /// 파일 첫 줄을 자료로 쓸지 바꾼다. 실제로 바뀌었으면 true.
    ///
    /// 열 이름은 건드리지 않는다. 라벨 항목이 열 이름으로 자료를 찾으므로 여기서 이름을 바꾸면
    /// 이미 라벨에 올라간 연결이 모두 끊긴다. 첫 줄은 1행으로 끼워 넣기만 한다.
    /// </summary>
    public bool SetFirstRowIsData(bool on)
    {
        if (!CanUseFirstRowAsData || on == FirstRowIsData) return false;
        if (on)
        {
            var line = HeaderTexts.Take(Columns.Count).ToList();
            while (line.Count < Columns.Count) line.Add("");
            Rows.Insert(0, line);
        }
        else if (Rows.Count > 0 && SameLine(Rows[0], HeaderTexts))
        {
            Rows.RemoveAt(0);
        }
        // 첫 줄이 1행에 그대로 남아 있지 않으면(지웠거나 고쳤다) 지우지 않고 표시만 되돌린다.
        // 사람이 손본 기록을 말없이 없애는 것이 더 나쁘다.
        FirstRowIsData = on;
        return true;
    }

    private static bool SameLine(List<string> row, List<string> header)
    {
        var n = Math.Min(row.Count, header.Count);
        for (var i = 0; i < n; i++)
        {
            if (!string.Equals((row[i] ?? "").Trim(), (header[i] ?? "").Trim(), StringComparison.Ordinal))
                return false;
        }
        return true;
    }

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

    public void AddRow(IEnumerable<string>? values = null)
    {
        var line = values?.ToList() ?? [];
        while (line.Count < Columns.Count) line.Add("");
        Rows.Add(line);
    }

    public string AddColumn(string? name = null)
    {
        var baseName = string.IsNullOrWhiteSpace(name) ? $"열{Columns.Count + 1}" : name.Trim();
        var unique = baseName;
        var n = 2;
        while (Columns.Any(c => string.Equals(c, unique, StringComparison.OrdinalIgnoreCase)))
            unique = $"{baseName}{n++}";
        Columns.Add(unique);
        foreach (var row in Rows)
            row.Add("");
        return unique;
    }

    public void RenameColumn(int col, string name)
    {
        if (col < 0 || col >= Columns.Count) return;
        var next = string.IsNullOrWhiteSpace(name) ? Columns[col] : name.Trim();
        if (Columns.Where((c, i) => i != col).Any(c => string.Equals(c, next, StringComparison.OrdinalIgnoreCase)))
            return;
        Columns[col] = next;
    }

    public void RemoveColumn(int col)
    {
        if (col < 0 || col >= Columns.Count) return;
        Columns.RemoveAt(col);
        foreach (var row in Rows)
        {
            if (col < row.Count)
                row.RemoveAt(col);
        }
    }

    public DataSheet Clone()
    {
        return new DataSheet
        {
            SourceName = SourceName,
            SourceKind = SourceKind,
            Columns = [.. Columns],
            Rows = Rows.Select(r => r.ToList()).ToList(),
            HeaderTexts = [.. HeaderTexts],
            FirstRowIsData = FirstRowIsData
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
