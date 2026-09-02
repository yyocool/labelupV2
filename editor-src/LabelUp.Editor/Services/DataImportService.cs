using System.Globalization;
using System.Text;
using ExcelDataReader;
using LabelUp.Editor.Models;

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
        return ext switch
        {
            ".csv" or ".txt" => ParseCsv(fileName, bytes),
            ".xlsx" or ".xls" => ParseExcel(fileName, bytes),
            _ => throw new NotSupportedException("CSV 또는 Excel(xls/xlsx) 파일만 가져올 수 있습니다.")
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
}
