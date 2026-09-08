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
            ".xlsx" or ".xls" => FromExcelILabel(fileName, bytes, null),
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

    /// <summary>
    /// 아이라벨 중복 헤더 규칙: 첫 이름은 그대로, 같은 이름이 반복되면 1,2,… 를 붙인다.
    /// 예: 시리얼 번호 / 시리얼 번호 / 시리얼 번호A / 시리얼 번호 → 시리얼 번호, 시리얼 번호1, 시리얼 번호A, 시리얼 번호2
    /// </summary>
    internal static DataSheet FromExcelILabel(string fileName, byte[] bytes, string? sheetName)
    {
        Encoding.RegisterProvider(CodePagesEncodingProvider.Instance);
        if (bytes is not { Length: > 8 })
            throw new InvalidDataException("엑셀 파일이 비어 있습니다.");

        using var stream = new MemoryStream(bytes);
        using var reader = ExcelReaderFactory.CreateReader(stream);
        var ds = reader.AsDataSet(new ExcelDataSetConfiguration
        {
            ConfigureDataTable = _ => new ExcelDataTableConfiguration { UseHeaderRow = false }
        });
        if (ds.Tables.Count == 0)
            throw new InvalidDataException("시트를 찾을 수 없습니다.");

        var table = PickExcelTable(ds, sheetName);
        if (table.Rows.Count == 0)
            throw new InvalidDataException("엑셀에 행이 없습니다.");

        var rawHeaders = new List<string>(table.Columns.Count);
        for (var i = 0; i < table.Columns.Count; i++)
            rawHeaders.Add(Convert.ToString(table.Rows[0][i], CultureInfo.InvariantCulture)?.Trim() ?? "");
        var headers = UniqueILabelHeaders(rawHeaders);

        var sheet = new DataSheet { SourceName = fileName, SourceKind = "xlsx" };
        sheet.Columns.AddRange(headers);
        for (var r = 1; r < table.Rows.Count; r++)
        {
            var cells = new List<string>(headers.Count);
            for (var i = 0; i < headers.Count; i++)
                cells.Add(Convert.ToString(table.Rows[r][i], CultureInfo.InvariantCulture)?.Trim() ?? "");
            if (cells.All(string.IsNullOrWhiteSpace)) continue;
            sheet.Rows.Add(cells);
        }
        if (sheet.RowCount == 0)
            throw new InvalidDataException("엑셀에 데이터 행이 없습니다.");
        EditorLog.Info($"엑셀 읽기: {fileName} · {table.TableName} · {sheet.ColumnCount}열 {sheet.RowCount}행 · {string.Join(",", sheet.Columns)}");
        return sheet;
    }

    private static System.Data.DataTable PickExcelTable(System.Data.DataSet ds, string? sheetName)
    {
        var want = (sheetName ?? "").Trim().TrimEnd('$');
        if (want.Length > 0)
        {
            foreach (System.Data.DataTable t in ds.Tables)
            {
                if (t.TableName.Equals(want, StringComparison.OrdinalIgnoreCase)
                    || t.TableName.Equals(want + "$", StringComparison.OrdinalIgnoreCase))
                    return t;
            }
        }
        return ds.Tables[0];
    }

    internal static List<string> UniqueILabelHeaders(IReadOnlyList<string> names)
    {
        var seen = new Dictionary<string, int>(StringComparer.OrdinalIgnoreCase);
        var result = new List<string>(names.Count);
        foreach (var raw in names)
        {
            var name = string.IsNullOrWhiteSpace(raw) ? "열" : raw.Trim();
            if (!seen.TryGetValue(name, out var n))
            {
                seen[name] = 0;
                result.Add(name);
                continue;
            }
            n++;
            seen[name] = n;
            result.Add(name + n);
        }
        return result;
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
