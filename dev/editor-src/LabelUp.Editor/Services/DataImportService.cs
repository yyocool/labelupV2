using System.Globalization;
using System.Text;
using ExcelDataReader;
using LabelUp.Editor.Models;
using LabelUp.Editor.Vendor;

namespace LabelUp.Editor.Services;

public sealed class DataImportService
{
    static DataImportService()
    {
        Encoding.RegisterProvider(CodePagesEncodingProvider.Instance);
    }

    public DataSheet Parse(string fileName, byte[] bytes)
    {
        var ext = Path.GetExtension(fileName).ToLowerInvariant();
        EditorLog.Info($"자료 가져오기: {fileName} ({bytes.Length} bytes, {ext})");
        return FromBytes(fileName, bytes);
    }

    internal static DataSheet FromBytes(string fileName, byte[] bytes)
    {
        var ext = Path.GetExtension(fileName).ToLowerInvariant();
        return ext switch
        {
            ".csv" or ".txt" => ParseCsv(fileName, bytes),
            ".xlsx" or ".xls" => ParseExcel(fileName, bytes),
            ".mdb" => FromMdb(fileName, bytes),
            _ => throw new NotSupportedException("CSV, Excel(xls/xlsx), Access(mdb) 파일만 가져올 수 있습니다.")
        };
    }

    private static DataSheet ParseCsv(string fileName, byte[] bytes)
    {
        var text = DecodeText(bytes);
        var lines = text.Replace("\r\n", "\n").Replace('\r', '\n')
            .Split('\n', StringSplitOptions.RemoveEmptyEntries);
        if (lines.Length == 0) throw new InvalidDataException("빈 CSV 파일입니다.");

        var sheet = new DataSheet { SourceName = fileName, SourceKind = "csv" };
        sheet.Columns = SplitCsvLine(lines[0]).Select(NormalizeHeader).ToList();
        for (var i = 1; i < lines.Length; i++)
        {
            var cells = SplitCsvLine(lines[i]);
            while (cells.Count < sheet.Columns.Count) cells.Add("");
            sheet.Rows.Add(cells.Take(sheet.Columns.Count).ToList());
        }
        return sheet;
    }

    private static DataSheet ParseExcel(string fileName, byte[] bytes)
    {
        using var ms = new MemoryStream(bytes);
        using var reader = ExcelReaderFactory.CreateReader(ms);
        var ds = reader.AsDataSet(new ExcelDataSetConfiguration
        {
            ConfigureDataTable = _ => new ExcelDataTableConfiguration { UseHeaderRow = true }
        });
        if (ds.Tables.Count == 0) throw new InvalidDataException("시트를 찾을 수 없습니다.");
        var table = ds.Tables[0];
        var sheet = new DataSheet { SourceName = fileName, SourceKind = "xlsx" };
        foreach (System.Data.DataColumn col in table.Columns)
            sheet.Columns.Add(NormalizeHeader(col.ColumnName));
        foreach (System.Data.DataRow row in table.Rows)
        {
            var cells = new List<string>(sheet.Columns.Count);
            for (var i = 0; i < sheet.Columns.Count; i++)
                cells.Add(Convert.ToString(row[i], CultureInfo.InvariantCulture)?.Trim() ?? "");
            if (cells.All(string.IsNullOrWhiteSpace)) continue;
            sheet.Rows.Add(cells);
        }
        return sheet;
    }

    private static string DecodeText(byte[] bytes)
    {
        if (bytes.Length >= 3 && bytes[0] == 0xEF && bytes[1] == 0xBB && bytes[2] == 0xBF)
            return Encoding.UTF8.GetString(bytes, 3, bytes.Length - 3);
        if (bytes.Length >= 2 && bytes[0] == 0xFF && bytes[1] == 0xFE)
            return Encoding.Unicode.GetString(bytes);
        try
        {
            return Encoding.UTF8.GetString(bytes);
        }
        catch
        {
            return Encoding.GetEncoding(949).GetString(bytes);
        }
    }

    private static List<string> SplitCsvLine(string line)
    {
        var result = new List<string>();
        var sb = new StringBuilder();
        var inQuotes = false;
        for (var i = 0; i < line.Length; i++)
        {
            var c = line[i];
            if (c == '"')
            {
                if (inQuotes && i + 1 < line.Length && line[i + 1] == '"')
                {
                    sb.Append('"');
                    i++;
                }
                else inQuotes = !inQuotes;
            }
            else if (c is ',' or '\t' && !inQuotes)
            {
                result.Add(sb.ToString().Trim());
                sb.Clear();
            }
            else sb.Append(c);
        }
        result.Add(sb.ToString().Trim());
        return result;
    }

    private static string NormalizeHeader(string raw)
    {
        var s = (raw ?? "").Trim();
        return string.IsNullOrWhiteSpace(s) ? "열" : s;
    }

    /// <summary>폼텍 DGZ Data/*.mdb 또는 직접 가져온 Jet 4 Access.</summary>
    internal static DataSheet FromMdb(string fileName, byte[] bytes, string? preferredTable = null)
    {
        var db = new Jet4Database(bytes);
        var table = PickMdbTable(db, preferredTable)
            ?? throw new InvalidDataException("MDB에서 사용할 테이블을 찾지 못했습니다.");
        var cols = table.Columns.Where(c => !string.IsNullOrWhiteSpace(c.Name)).ToList();
        if (cols.Count == 0)
            throw new InvalidDataException($"테이블 «{table.Name}»에 열이 없습니다.");

        var sheet = new DataSheet { SourceName = fileName, SourceKind = "mdb" };
        sheet.Columns.AddRange(cols.Select(c => NormalizeHeader(c.Name)));
        foreach (var row in table.Rows)
        {
            var cells = cols.Select(c => FormatMdbCell(row, c)).ToList();
            if (cells.All(string.IsNullOrWhiteSpace)) continue;
            sheet.Rows.Add(cells);
        }
        EditorLog.Info($"MDB 읽기: {fileName} · {table.Name} · {sheet.ColumnCount}열 {sheet.RowCount}행");
        return sheet;
    }

    private static Jet4Table? PickMdbTable(Jet4Database db, string? preferred)
    {
        foreach (var name in new[] { preferred, "테이블1", "Table1", "Table" })
        {
            if (string.IsNullOrWhiteSpace(name)) continue;
            var t = db.TryReadTable(name);
            if (t is { Columns.Count: > 0 }) return t;
        }

        return db.Catalog
            .Where(c => c.IsUserTable)
            .Select(c => db.TryReadTable(c.Name))
            .FirstOrDefault(t => t is { Columns.Count: > 0 });
    }

    private static string FormatMdbCell(Jet4Row row, Jet4Column col)
    {
        if (!row.TryGet(col.Name, out var value) || value is null) return "";
        if (col.Type == Jet4Database.TypeDateTime && value is double oa)
        {
            try { return DateTime.FromOADate(oa).ToString("yyyy-MM-dd", CultureInfo.InvariantCulture); }
            catch { /* 일반 숫자로 표시 */ }
        }
        return row.GetString(col.Name).Trim();
    }
}
