using System.Globalization;
using System.Net;
using System.Text;
using System.Text.RegularExpressions;
using System.Xml.Linq;
using LabelUp.Editor.Models;
using LabelUp.Editor.Vendor;

namespace LabelUp.Editor.Services;

/// <summary>
/// 아이라벨 .idf(Jet DB) / XML 을 LabelUp DesignObject 로 변환한다.
/// 기준: md_ilabel. 폼텍·애니라벨 변환과 분리된 파일이다.
/// </summary>
internal static class ILabelImporter
{
    /// <summary>아이라벨 PDF417·Micro PDF417 그리기 높이(0.5in). 저장된 상자 높이와 무관하다.</summary>
    private const float ILabelPdf417HeightMm = 12.7f;


    private const int CommonLabelId = int.MaxValue;

    private static readonly Regex RunRegex = new(
        @"\{&\s*([^,]+?)\s*,\s*([0-9.]+)\s*,\s*([bBiIuUsS]{4})\s*,\s*(-?\d+)\s*\}",
        RegexOptions.Compiled);

    private static readonly Regex FieldRegex = new(
        @"\{@([^}]+)\}",
        RegexOptions.Compiled);

    /// <summary>줄마다 앞에 붙는 문단 표시. 바코드 1.idf의 165개가 모두 {#0,0}이라 값은 쓰지 않는다.</summary>
    private static readonly Regex ParaRegex = new(
        @"\{#[^}]*\}",
        RegexOptions.Compiled);

    public static Func<string, Task<byte[]?>>? FetchSidecarExcel { get; set; }

    internal static string? LastExcelError { get; private set; }

    public static LabelDocument Import(byte[] bytes, string name, PaperCatalog papers, byte[]? excelSidecar = null)
    {
        if (ExternalImportService.LooksLikeXml(bytes))
            return FromXml(bytes, name, papers);
        if (!Jet4Database.LooksLikeJet(bytes))
            throw new InvalidDataException("아이라벨 IDF(Jet DB) 시그니처가 아닙니다.");

        var db = new Jet4Database(bytes);
        var paperRow = db.TryReadTable("Paper")?.Rows.FirstOrDefault()
                       ?? throw new InvalidDataException("IDF Paper 테이블을 읽지 못했습니다.");

        var paper = ReadPaper(paperRow, papers);
        var doc = LabelDocument.CreateBlank(paper);
        doc.Name = name;
        foreach (var cell in doc.Pages[0].Cells)
            cell.Objects.Clear();

        LoadData(db, doc, name, paperRow.GetString("DataSrc"), excelSidecar);

        var factors = db.TryReadTable("Factors");
        if (factors is null || factors.Rows.Count == 0)
        {
            EditorLog.Info("아이라벨: Factors 없음 · 빈 용지로 엽니다.");
            return doc;
        }

        PlaceFactors(doc, paper, factors.Rows);
        if (doc.Data is { RowCount: > 0 })
            doc.EnsurePagesForData();
        LogImportFonts(doc);
        EditorLog.Info(
            $"아이라벨 변환: paper={paper.Columns}x{paper.Rows} label={paper.LabelWidthMm:0.#}x{paper.LabelHeightMm:0.#} " +
            $"factors={factors.Rows.Count} pages={doc.Pages.Count}");
        return doc;
    }

    private static PaperSpec ReadPaper(Jet4Row paperRow, PaperCatalog papers)
    {
        var paperW = (float)paperRow.GetDouble("Width");
        var paperH = (float)paperRow.GetDouble("Height");
        var lw = (float)paperRow.GetDouble("LabelWidth");
        var lh = (float)paperRow.GetDouble("LabelHeight");
        var cols = Math.Max(1, paperRow.GetInt("Cols"));
        var rows = Math.Max(1, paperRow.GetInt("Rows"));
        var left = (float)paperRow.GetDouble("MarginLeft");
        var top = (float)paperRow.GetDouble("MarginTop");
        var hGap = (float)paperRow.GetDouble("PitchHorizen");
        var vGap = (float)paperRow.GetDouble("PitchVertical");
        if (lw < 1) lw = 70;
        if (lh < 1) lh = 36;
        var right = (float)Math.Max(0, paperW - left - cols * lw - Math.Max(0, cols - 1) * hGap);
        var bottom = (float)Math.Max(0, paperH - top - rows * lh - Math.Max(0, rows - 1) * vGap);
        var paperNo = paperRow.GetString("Name");
        var paper = ExternalImportService.ResolvePaper(
            papers, "ilabel", paperNo, lw, lh, cols, rows, paperW, paperH, left, top, right, bottom, hGap, vGap);

        ApplyPaperShape(
            paper,
            paperRow.GetInt("LabelShape"),
            paperRow.GetString("LabelFrame"),
            (float)paperRow.GetDouble("LabelEdgeWidth"),
            (float)paperRow.GetDouble("LabelEdgeHeight"));
        paper.DesignImageUrl = NullIfEmpty(paperRow.GetString("LabelBackground"));
        if (!string.IsNullOrWhiteSpace(paper.DesignImageUrl))
            EditorLog.Info("아이라벨 디자인 배경: " + paper.DesignImageUrl);
        return paper;
    }

    private static void PlaceFactors(LabelDocument doc, PaperSpec paper, IReadOnlyList<Jet4Row> rows)
    {
        var per = Math.Max(1, paper.LabelsPerPage);
        var z = 0;
        var commons = new List<DesignObject>();
        var unique = new Dictionary<string, (DesignObject Obj, int LabelId)>(StringComparer.Ordinal);
        foreach (var row in rows)
        {
            var obj = MapFactor(row, z++);
            if (obj is null) continue;
            var labelId = row.GetInt("LabelId");
            if (labelId == CommonLabelId)
            {
                commons.Add(obj);
                continue;
            }
            if (labelId < 0 || labelId >= per * ExternalImportService.MaxImportPages)
                labelId = 0;
            unique[ObjectKey(obj, labelId)] = (obj, labelId);
        }

        foreach (var (obj, labelId) in unique.Values)
            ExternalImportService.Place(doc, obj, labelId + 1, per);

        if (commons.Count > 0)
        {
            doc.EnsureStructure();
            foreach (var common in commons)
                ExternalImportService.PlaceCommon(doc, common);
            EditorLog.Info($"아이라벨 공통데이터: {commons.Count}개 (LabelId=0x7FFFFFFF)");
        }
    }

    private static string ObjectKey(DesignObject obj, int labelId)
        => $"{labelId}|{(int)obj.Type}|{(int)obj.ShapeKind}|{(int)obj.TextMode}|{obj.X:0.###}|{obj.Y:0.###}|{obj.Width:0.###}|{obj.Height:0.###}|{obj.Fill}|{obj.Stroke}|{obj.StrokeWidth:0.###}|{obj.DashStyle}|{obj.Text}|{obj.BarcodeFormat}|{obj.BarcodeValue}|{obj.BarcodeSupplement}|{obj.BarcodeIsbnCaption}|{obj.IconName}";

    private static void LoadData(Jet4Database db, LabelDocument doc, string name, string dataSrc, byte[]? excelSidecar)
    {
        var table = FindInner(db);
        if (table is { Rows.Count: > 0 })
        {
            var fields = table.Columns.Select(c => c.Name)
                .Where(n => !n.StartsWith("idb_", StringComparison.OrdinalIgnoreCase)
                            && !n.Equals("ID", StringComparison.OrdinalIgnoreCase))
                .ToList();
            if (fields.Count > 0)
            {
                var inner = new DataSheet
                {
                    SourceName = name,
                    SourceKind = string.IsNullOrWhiteSpace(dataSrc) ? table.Name : dataSrc
                };
                inner.Columns.AddRange(fields);
                foreach (var row in table.Rows)
                {
                    var values = fields.Select(f => row.GetString(f)).ToList();
                    if (values.Any(v => !string.IsNullOrWhiteSpace(v)))
                        inner.Rows.Add(values);
                }
                if (inner.RowCount > 0)
                {
                    ApplySheet(doc, inner);
                    return;
                }
            }
        }

        ParseDataSrc(dataSrc, out var excelPath, out var sheetName);
        if (string.IsNullOrWhiteSpace(excelPath))
            return;

        var fileName = ExcelFileName(excelPath);
        var bytes = excelSidecar
                    ?? ReadExcelFromDisk(excelPath, fileName);
        if (bytes is { Length: > 8 } && TryApplyExcel(doc, fileName, bytes, sheetName))
            return;

        doc.Data = new DataSheet
        {
            SourceName = fileName,
            SourceKind = "ilabel-excel:" + sheetName
        };
        EditorLog.Warn($"아이라벨 자료연결: InnerDB 없음 · 외부 엑셀 {fileName} (시트 {sheetName})");
    }

    internal static bool NeedsExcelSidecar(LabelDocument doc)
        => doc.Data is { RowCount: 0 } data
           && !string.IsNullOrWhiteSpace(data.SourceName)
           && data.SourceKind.StartsWith("ilabel-excel", StringComparison.OrdinalIgnoreCase);

    internal static async Task TryAttachSidecarExcelAsync(LabelDocument doc)
    {
        if (!NeedsExcelSidecar(doc) || FetchSidecarExcel is null) return;
        var fileName = doc.Data!.SourceName;
        var sheetName = ExcelSheetOf(doc.Data.SourceKind);
        try
        {
            var bytes = await FetchSidecarExcel(fileName);
            if (bytes is not { Length: > 8 })
            {
                LastExcelError = "엑셀 파일을 받지 못했습니다. 변환 창에서 엑셀을 직접 연결하세요.";
                EditorLog.Warn("아이라벨 외부 엑셀 응답이 비어 있음: " + fileName);
                return;
            }
            EditorLog.Info($"아이라벨 외부 엑셀 수신: {fileName} · {bytes.Length} bytes · sig={bytes[0]:X2}{bytes[1]:X2}");
            TryApplyExcel(doc, fileName, bytes, sheetName);
        }
        catch (Exception ex)
        {
            EditorLog.Warn("아이라벨 외부 엑셀 연결 실패: " + ex.Message);
        }
    }

    internal static bool TryBindExcel(LabelDocument doc, string fileName, byte[] bytes)
    {
        var sheetName = ExcelSheetOf(doc.Data?.SourceKind);
        return TryApplyExcel(doc, fileName, bytes, sheetName);
    }

    internal static IEnumerable<string> ExcelNameTries(string fileName)
    {
        var seen = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
        void Offer(string? n)
        {
            n = (n ?? "").Trim();
            if (n.Length > 0) seen.Add(n);
        }
        Offer(fileName);
        Offer(Regex.Replace(fileName ?? "", @"\s+(\.xlsx?)$", "$1", RegexOptions.IgnoreCase));
        return seen;
    }

    private static string ExcelSheetOf(string? kind)
    {
        var raw = kind ?? "";
        var i = raw.IndexOf(':');
        return i >= 0 && i + 1 < raw.Length ? raw[(i + 1)..] : "Sheet1";
    }

    private static bool TryApplyExcel(LabelDocument doc, string fileName, byte[] bytes, string sheetName)
    {
        LastExcelError = null;
        try
        {
            var sheet = DataImportService.FromExcelILabel(fileName, bytes, sheetName);
            if (ApplySheet(doc, sheet))
                return true;
            LastExcelError = "엑셀에서 데이터 행을 읽지 못했습니다.";
            return false;
        }
        catch (Exception ex)
        {
            LastExcelError = ex.Message;
            EditorLog.Warn($"아이라벨 엑셀 파싱 실패: {fileName} · {ex.GetType().Name}: {ex.Message}");
            return false;
        }
    }

    private static bool ApplySheet(LabelDocument doc, DataSheet sheet)
    {
        if (sheet.Rows.Count > 256)
            sheet.Rows.RemoveRange(256, sheet.Rows.Count - 256);
        if (sheet.RowCount == 0) return false;
        doc.Data = sheet;
        doc.EnsurePagesForData();
        EditorLog.Info($"아이라벨 자료연결: {sheet.SourceKind} · {sheet.ColumnCount}열 {sheet.RowCount}행 · {string.Join(",", sheet.Columns)}");
        return true;
    }

    private static void ParseDataSrc(string? dataSrc, out string path, out string sheet)
    {
        path = "";
        sheet = "Sheet1";
        var raw = (dataSrc ?? "").Trim();
        if (raw.Length == 0 || raw.Equals("InnerDB", StringComparison.OrdinalIgnoreCase))
            return;
        var bar = raw.LastIndexOf('|');
        if (bar >= 0)
        {
            path = raw[..bar].Trim();
            var sh = raw[(bar + 1)..].Trim().TrimEnd('$');
            if (sh.Length > 0) sheet = sh;
        }
        else
            path = raw;
    }

    private static byte[]? ReadExcelFromDisk(string path, string fileName)
    {
        foreach (var candidate in ExcelPathCandidates(path, fileName))
        {
            try
            {
                if (File.Exists(candidate))
                    return File.ReadAllBytes(candidate);
            }
            catch
            {
                /* WASM 등에서는 디스크를 못 연다 */
            }
        }

        foreach (var dir in ExcelSearchDirs(path))
        {
            try
            {
                if (!Directory.Exists(dir)) continue;
                foreach (var f in Directory.GetFiles(dir, "*.xls*"))
                {
                    if (SameExcelName(Path.GetFileName(f), fileName))
                        return File.ReadAllBytes(f);
                }
            }
            catch
            {
                /* 브라우저 WASM */
            }
        }
        return null;
    }

    private static string ExcelFileName(string? path)
    {
        var s = (path ?? "").Replace('\\', '/').Trim();
        var i = s.LastIndexOf('/');
        return i >= 0 ? s[(i + 1)..] : s;
    }

    private static bool SameExcelName(string a, string b)
    {
        static string Norm(string s)
            => string.Concat(ExcelFileName(s).Where(ch => !char.IsWhiteSpace(ch))).ToLowerInvariant();
        return Norm(a) == Norm(b);
    }

    private static IEnumerable<string> ExcelSearchDirs(string path)
    {
        yield return @"C:\LabelupData";
        yield return @"C:\win7share\ilabelData";
        yield return @"Z:\ilabelData";
        var parent = Path.GetDirectoryName(path.Replace('/', '\\'));
        if (!string.IsNullOrWhiteSpace(parent))
            yield return parent;
    }

    private static IEnumerable<string> ExcelPathCandidates(string path, string fileName)
    {
        if (!string.IsNullOrWhiteSpace(path))
        {
            yield return path;
            yield return path.Replace('/', '\\');
        }
        if (string.IsNullOrWhiteSpace(fileName)) yield break;
        yield return Path.Combine(@"C:\LabelupData", fileName);
        yield return Path.Combine(@"C:\win7share\ilabelData", fileName);
        yield return Path.Combine(@"Z:\ilabelData", fileName);
        var mapped = path.Replace(@"Z:\ilabelData", @"C:\win7share\ilabelData", StringComparison.OrdinalIgnoreCase)
            .Replace(@"Z:/ilabelData", @"C:\win7share\ilabelData", StringComparison.OrdinalIgnoreCase);
        if (!string.Equals(mapped, path, StringComparison.OrdinalIgnoreCase))
            yield return mapped;
    }

    private static Jet4Table? FindInner(Jet4Database db)
    {
        foreach (var n in new[] { "InnerDB", "Table1", "Table" })
        {
            var t = db.TryReadTable(n);
            if (t is { Rows.Count: > 0 } && t.Columns.Any(c =>
                    !c.Name.StartsWith("idb_", StringComparison.OrdinalIgnoreCase)
                    && !c.Name.Equals("ID", StringComparison.OrdinalIgnoreCase)))
                return t;
        }
        return db.Catalog
            .Where(c => c.IsUserTable && c.Name is not ("Paper" or "Factors"))
            .Select(c => db.TryReadTable(c.Name))
            .FirstOrDefault(t => t is { Rows.Count: > 0 });
    }

    private static DesignObject? MapFactor(Jet4Row row, int z)
    {
        var type = row.GetInt("Type");
        var shape = row.GetInt("Shape");
        var x = (float)row.GetDouble("Left");
        var y = (float)row.GetDouble("Top");
        var w = (float)row.GetDouble("Width");
        var h = (float)row.GetDouble("Height");
        if (w < 0.2f && type != 8) w = 10;
        if (h < 0.2f && type != 8) h = 6;
        var cont = row.GetString("Cont");
        var fillOn = row.GetBool("Fill");
        var back = row.GetInt("BackColor");
        var fore = row.GetInt("ForeColor");

        DesignObject obj = type switch
        {
            1 => MakeText(x, y, w, h, cont),
            2 => MakeImage(x, y, w, h, row.GetBytes("Image")),
            3 => MakeBarcode(x, y, w, h, cont, fore, back, fillOn, row.GetBytes("Image")),
            4 => MakeWordArt(x, y, w, h, cont, row.GetInt("Attribute"), fore, back, fillOn),
            5 or 6 or 7 or 8 or 9 or 13 => MakeShape(type, shape, x, y),
            14 => MakeTable(x, y, w, h, cont),
            16 => MakeIcon(x, y, w, h, cont),
            _ => FallbackText(x, y, w, h, type, cont)
        };

        obj.Width = type == 8 ? Math.Max(0.3f, w) : Math.Max(0.4f, w);
        // 바코드(Type=3)는 MakeBarcode가 정한 높이를 지운다. 아이라벨 PDF417은 상자보다 높게 그려진다.
        obj.Height = type switch
        {
            8 => Math.Max(0.3f, h),
            3 => Math.Max(0.4f, obj.Height),
            _ => Math.Max(0.4f, h)
        };
        obj.ZIndex = z;
        obj.Locked = row.GetBool("Lock");
        obj.Rotation = (float)row.GetDouble("Rotate");

        var thick = row.GetDouble("LineThickness");
        if (thick > 0)
        {
            obj.StrokeWidth = (float)thick;
            if (obj.Type == ObjectType.Table)
                obj.TableBorderWidth = (float)thick;
        }

        ApplyColors(obj, type, fillOn, back, fore);
        if (type == 1)
            obj.VerticalAlign = "top";
        if (DesignObject.IsShape(obj.Type) || obj.Type is ObjectType.Table or ObjectType.Icon)
            obj.DashStyle = Math.Clamp(row.GetInt("DashStyle"), 0, 4);
        // Frame은 Jet에서 Boolean이 아니라 빈 Binary로 들어오는 경우가 많다.
        // 값이 명시적으로 False일 때만 선을 끈다. 비어 있으면 LineThickness를 유지한다.
        if (TryReadExplicitBool(row, "Frame", out var frameOn) && !frameOn
            && (DesignObject.IsShape(obj.Type) || obj.Type == ObjectType.Table))
            obj.StrokeWidth = 0;
        return obj;
    }

    private static bool TryReadExplicitBool(Jet4Row row, string name, out bool value)
    {
        value = false;
        if (!row.TryGet(name, out var raw) || raw is null)
            return false;
        switch (raw)
        {
            case bool b:
                value = b;
                return true;
            case byte or sbyte or short or ushort or int or uint or long:
                value = Convert.ToInt64(raw) != 0;
                return true;
            case string s:
                if (string.IsNullOrWhiteSpace(s))
                    return false;
                if (IsTrue(s))
                {
                    value = true;
                    return true;
                }
                if (s.Equals("False", StringComparison.OrdinalIgnoreCase) || s == "0")
                    return true;
                return false;
            case byte[] buf:
                if (buf.Length == 0)
                    return false;
                if (buf.Length == 1)
                {
                    value = buf[0] != 0;
                    return true;
                }
                var text = DecodeJetTextSafe(buf);
                if (string.IsNullOrWhiteSpace(text))
                    return false;
                if (IsTrue(text))
                {
                    value = true;
                    return true;
                }
                if (text.Equals("False", StringComparison.OrdinalIgnoreCase) || text == "0")
                    return true;
                return false;
            default:
                return false;
        }
    }

    private static string DecodeJetTextSafe(byte[] buf)
    {
        try
        {
            return Encoding.Unicode.GetString(buf).Trim('\0').Trim();
        }
        catch
        {
            return "";
        }
    }

    private static DesignObject FallbackText(float x, float y, float w, float h, int type, string cont)
    {
        EditorLog.Warn($"아이라벨 미지원 Type={type} · 텍스트로 엽니다.");
        return MakeText(x, y, w, h, StripCont(cont));
    }

    private static void ApplyColors(DesignObject obj, int type, bool fillOn, int back, int fore)
    {
        var stroke = ArgbToCss(fore == 0 ? -16777216 : fore);
        var transparent = !fillOn || back == -1;
        var fill = transparent ? "transparent" : ArgbToCss(back);

        if (DesignObject.IsShape(obj.Type) || obj.Type == ObjectType.Table)
        {
            obj.Fill = fill;
            obj.BackgroundFill = fill;
            obj.BackgroundTransparent = transparent;
            obj.Stroke = stroke;
            return;
        }

        if (obj.Type == ObjectType.Icon)
        {
            // 아이라벨 배경(바탕)=우리 채우기, 아이라벨 선색=우리 선. 투명이면 채우기 없이 선만 그린다.
            obj.Fill = fill;
            obj.Stroke = stroke;
            obj.BackgroundFill = fill;
            obj.BackgroundTransparent = transparent;
            if (obj.StrokeWidth <= 0)
                obj.StrokeWidth = 0.3f;
            return;
        }

        if (obj.Type is ObjectType.Barcode or ObjectType.Qr)
        {
            obj.Fill = stroke;
            obj.BackgroundFill = fill;
            obj.BackgroundTransparent = transparent;
            return;
        }

        if (obj.Type != ObjectType.Text) return;
        if (type == 4)
        {
            obj.Fill = stroke;
            obj.BackgroundFill = fill;
            obj.BackgroundTransparent = transparent;
            return;
        }

        // Type=1 글자색은 Cont run({&...,Color})이 기준. ForeColor로 덮어쓰지 않는다.
        obj.BackgroundFill = "transparent";
        obj.BackgroundTransparent = true;
        if (!HasTextInk(obj.Fill))
            obj.Fill = HasTextInk(stroke) ? stroke : "#000000";
    }

    private static bool HasTextInk(string? css)
        => !string.IsNullOrWhiteSpace(css)
           && css is not ("transparent" or "none" or "#7B2840" or "#2E2A27" or "#FFFFFF" or "#ffffff");

    /// <summary>Type=1. Cont = {#0,0}{&amp;Font,Size,BIUS,Color}run… / 자료연결 {@필드명}</summary>
    private static DesignObject MakeText(float x, float y, float w, float h, string cont)
    {
        var o = DesignObject.CreateDefault(ObjectType.Text, x, y);
        o.Width = w;
        o.Height = h;
        o.TextAlign = "left";
        o.VerticalAlign = "top";
        o.LineHeight = 1f;
        o.StrokeWidth = 0;
        o.BackgroundTransparent = true;
        o.BackgroundFill = "transparent";
        // 아이라벨 박스는 GDI 글자폭에 딱 맞게 저장된다. 자동 1.5% 여백은 빼지 않는다.
        o.TextPaddingXMm = 0.02f;

        var parsed = ParseCont(cont);
        if (parsed.DataBound)
        {
            o.DataBound = true;
            o.DataColumn = parsed.DataColumn;
            o.Text = $"[{parsed.DataColumn}]";
        }
        else
        {
            o.Text = parsed.Plain;
        }

        if (parsed.Runs.Count > 0)
        {
            var ink = parsed.Runs.FirstOrDefault(r => r.FromCont) ?? parsed.Runs[0];
            ApplyRun(o, ink);
            o.TextMode = TextMode.Normal;
            o.RichText = ToParagraphs(parsed);
        }

        o.VerticalAlign = "top";
        var pt = parsed.Runs.Count > 0 ? parsed.Runs.Max(r => r.FontSizePt) : 9f;
        if (pt <= 0) pt = 9f;
        var lineMm = FontCatalog.FromPt(pt);
        // 한 줄 높이 박스는 줄바꿈하지 않는다. 대체 글꼴이 넓으면 "BM P" / "지" 잘림이 생긴다.
        o.TextWrap = h >= lineMm * 1.45f ? "char" : "none";
        EditorLog.Info($"아이라벨 텍스트: family={o.FontFamily} pt={pt} wrap={o.TextWrap} box={w:0.#}x{h:0.#}");
        return o;
    }

    /// <summary>Type=4. Cont=글꼴,크기,굵게,이탤릭,밑줄,외곽선,세로,그림자,좌우반전:텍스트</summary>
    private static DesignObject MakeWordArt(
        float x, float y, float w, float h, string cont, int attribute, int fore, int back, bool fillOn)
    {
        var o = DesignObject.CreateDefault(ObjectType.Text, x, y);
        o.Width = w;
        o.Height = h;
        o.TextMode = TextMode.WordArt;
        o.TextAlign = "center";
        o.VerticalAlign = "middle";
        o.TextWrap = "none";
        o.StrokeWidth = 0;
        o.WordArtStyle = MapWordArtAttribute(attribute);
        // 아이라벨 각도는 Rotate(객체 회전). 워드아트 굽힘 기본 30은 폼텍용이라 넣지 않는다.
        o.WordArtBend = 0;
        o.Fill = ArgbToCss(fore == 0 ? -16777216 : fore);
        o.BackgroundTransparent = !fillOn || back == -1;
        o.BackgroundFill = o.BackgroundTransparent ? "transparent" : ArgbToCss(back);

        var raw = cont ?? "";
        var colon = raw.IndexOf(':');
        var head = colon >= 0 ? raw[..colon] : raw;
        o.Text = colon >= 0 ? raw[(colon + 1)..] : StripCont(raw);
        var parts = head.Split(',');
        if (parts.Length >= 2)
        {
            FontCatalog.ApplyImportedFamily(o, parts[0].Trim());
            if (float.TryParse(parts[1].Trim(), NumberStyles.Float, CultureInfo.InvariantCulture, out var fs) && fs > 0)
                o.FontSize = FontCatalog.FromPt(fs);
        }
        if (parts.Length >= 6)
        {
            o.Bold = IsTrue(parts[2]);
            o.Italic = IsTrue(parts[3]);
            o.Underline = IsTrue(parts[4]);
            o.Outline = IsTrue(parts[5]);
        }
        if (parts.Length >= 7 && IsTrue(parts[6]))
            o.TextDirection = "vertical";
        if (parts.Length >= 8)
            o.Shadow = IsTrue(parts[7]);
        if (parts.Length >= 9)
            o.FlipHorizontal = IsTrue(parts[8]);
        return o;
    }

    /// <summary>
    /// 아이라벨 Type=4 Attribute. 워드아트.idf 텍스트가 모양을 설명한다.
    /// 0보통 11늘리기 12점점크게 13점점작게 14위로크게 15위로작게 16둥글게 17스마일 18원.
    /// </summary>
    private static WordArtStyle MapWordArtAttribute(int attribute) => attribute switch
    {
        11 => WordArtStyle.Stretch,
        12 => WordArtStyle.GrowRight,
        13 => WordArtStyle.ShrinkRight,
        14 => WordArtStyle.TopWide,
        15 => WordArtStyle.TopNarrow,
        16 => WordArtStyle.Rounded,
        17 => WordArtStyle.Smile,
        18 => WordArtStyle.Circle,
        _ => WordArtStyle.None
    };

    private static DesignObject MakeImage(float x, float y, float w, float h, byte[]? bytes)
    {
        var o = DesignObject.CreateDefault(ObjectType.Image, x, y);
        o.Width = w;
        o.Height = h;
        o.ImageFit = "contain";
        if (bytes is { Length: > 8 })
            o.ImageData = ExternalImportService.ToDataUrl(bytes, DetectImageMime(bytes));
        return o;
    }

    /// <summary>Type=3. Cont = Type|Option:Value;...|Data</summary>
    private static DesignObject MakeBarcode(
        float x, float y, float w, float h, string cont, int fore, int back, bool fillOn, byte[]? logo)
    {
        SplitBarcodeCont(cont, out var typeName, out var options, out var data);
        var format = MapBarcodeFormat(typeName);
        if (LooksLikeILabelItf14(typeName))
            format = "ITF_14";
        var o = DesignObject.CreateDefault(
            ExternalImportService.Is2dBarcode(format) ? ObjectType.Qr : ObjectType.Barcode, x, y);
        o.Width = w;
        o.Height = h;
        o.BarcodeFormat = format;
        o.BarcodeValue = data;
        o.BarcodeVendor = "ilabel";
        o.Fill = ArgbToCss(fore == 0 ? -16777216 : fore);
        o.BackgroundTransparent = !fillOn || back == -1;
        o.BackgroundFill = o.BackgroundTransparent ? "transparent" : ArgbToCss(back);
        o.StrokeWidth = 0;
        // 1D는 DrawCaption이 없어도 아래에 값을 쓴다(아이라벨 Numly·Optical 실측).
        // QR/DataMatrix/PDF417은 캡션을 쓰지 않는다.
        o.BarcodeShowText = !ExternalImportService.Is2dBarcode(format);

        string? captionFont = null;
        float? captionPt = null;
        var opt = ParseOptions(options);
        foreach (var (key, val) in opt)
        {
            if (key.Equals("DrawCaption", StringComparison.OrdinalIgnoreCase))
                o.BarcodeShowText = IsTrue(val);
            else if (key.Equals("ISBNAutoCaption", StringComparison.OrdinalIgnoreCase) && IsTrue(val))
            {
                o.BarcodeIsbnCaption = true;
                o.BarcodeShowText = true;
            }
            else if (key.Equals("ShowStartStop", StringComparison.OrdinalIgnoreCase))
                o.BarcodeShowStartEnd = IsTrue(val);
            else if (key.Equals("FontName", StringComparison.OrdinalIgnoreCase) && val.Length > 0)
                captionFont = val;
            else if (key.Equals("FontSize", StringComparison.OrdinalIgnoreCase)
                     && float.TryParse(val, NumberStyles.Float, CultureInfo.InvariantCulture, out var fs) && fs > 0)
                captionPt = fs;
            else if (key.Equals("QRErrorCorrectionLevel", StringComparison.OrdinalIgnoreCase))
                o.QrEcc = MapQrEcc(val);
            // QRVersion 0은 자동이다. 1~40이면 자료가 남더라도 그 크기로 그린다(실측: 4→33칸, 10→57칸).
            else if (key.Equals("QRVersion", StringComparison.OrdinalIgnoreCase)
                     && int.TryParse(val, NumberStyles.Integer, CultureInfo.InvariantCulture, out var qv)
                     && qv is > 0 and <= 40)
                o.QrVersion = qv;
            else if (key.Equals("QRCodeLogo", StringComparison.OrdinalIgnoreCase) && val.Trim().Length > 0)
            {
                // 옵션 값은 아이라벨 PC의 파일 경로라 못 쓴다. 그림은 Factors 행 Image 열에 들어 있다.
                if (logo is { Length: > 8 })
                    o.QrLogoData = ExternalImportService.ToDataUrl(logo, DetectImageMime(logo));
                else
                    EditorLog.Warn($"아이라벨 QR 로고 없음: {val}");
            }
            else if (key.Equals("SupplementValue", StringComparison.OrdinalIgnoreCase)
                     && val.Trim().Length > 0)
                o.BarcodeSupplement = new string(val.Where(char.IsAsciiDigit).ToArray());
        }

        // FontName은 바코드/QR 캡션 글꼴이다. 텍스트 항목 글꼴이 아니다.
        if (o.BarcodeShowText || o.BarcodeIsbnCaption)
        {
            if (!string.IsNullOrWhiteSpace(captionFont))
                FontCatalog.ApplyImportedFamily(o, captionFont);
            if (captionPt is > 0)
                o.FontSize = FontCatalog.FromPt(captionPt.Value);
        }

        if (format == "CODABAR")
            NormalizeILabelCodabar(o, opt, data);
        if (format == "CODE_128")
            NormalizeILabelCode128(o, opt);
        if (format == "CODE_39")
            NormalizeILabelCode39(o, opt, data);
        if (format == "PZN")
            NormalizeILabelPzn(o, opt, data);
        if (format == "DATA_MATRIX")
            NormalizeILabelDataMatrix(o, opt);
        if (format is "ITF_14" or "ITF" or "ITF_6" or "ITF_16")
            NormalizeILabelItf(o, opt, data);
        if (format == "JAN_13")
            NormalizeILabelJan13(o, data);
        if (format == "KOREAN_POST")
            o.BarcodeShowText = false;
        // PLANET은 1D 중 유일하게 IDF 옵션에 DrawCaption 키가 없다(같은 우편 계열인 POSTNET은 있다).
        // 아이라벨에 캡션 기능 자체가 없는 타입이므로 항상 막대만 그린다.
        if (format == "PLANET")
            o.BarcodeShowText = false;
        if (format is "PDF_417" or "PDF_417_TRUNC" or "MICRO_PDF417")
            NormalizeILabelPdf417(o, opt, format);
        // 아이라벨 PDF417·Micro는 가로만 상자를 따르고 높이는 0.5in(12.7mm)로 그려 상자를 넘는다.
        // L57(PDF417 12345) 12.53mm, L58(Micro 12345) 12.72mm 실측. 잘리지 않게 상자를 늘린다.
        if (format is "PDF_417" or "PDF_417_TRUNC" or "MICRO_PDF417"
            && o.Height < ILabelPdf417HeightMm)
            o.Height = ILabelPdf417HeightMm;

        EditorLog.Info($"아이라벨 바코드: type={typeName} → {format} value={o.BarcodeValue} show={o.BarcodeShowText} supplement={o.BarcodeSupplement} captionFont={captionFont}");
        return o;
    }

    /// <summary>
    /// 아이라벨 Codabar를 우리 인코더가 그대로 그릴 문자열로 맞춘다.
    /// 폼텍 인코더(A+값+Mod16+A, 굵기 1:2)는 바꾸지 않는다.
    /// 알고리즘 0은 문서의 Modulo9가 아니라 시작/종료 포함 Mod10(실측 123456789→7)이다.
    /// </summary>
    private static void NormalizeILabelCodabar(DesignObject o, Dictionary<string, string> opt, string data)
    {
        var payload = StripCodabarGuards(data);
        if (payload.Length == 0) payload = "0";
        var start = CodabarGuard(opt, "CodabarStartSymbol", 'A');
        var stop = CodabarGuard(opt, "CodabarStopSymbol", 'A');
        var addCheck = IsTrue(opt.GetValueOrDefault("AddChecksum"));
        var checkOnCaption = IsTrue(opt.GetValueOrDefault("AddChecksumToCaption"));
        _ = int.TryParse(opt.GetValueOrDefault("CodabarChecksumAlgorithm"), NumberStyles.Integer,
            CultureInfo.InvariantCulture, out var algo);

        var body = payload;
        var check = '\0';
        if (addCheck)
        {
            check = ILabelCodabarCheck(start, payload, stop, algo);
            body += check;
        }

        o.BarcodeVendor = "ilabel";
        o.BarcodeValue = $"{start}{body}{stop}";
        o.Text = checkOnCaption && check != '\0' ? payload + check : payload;
        EditorLog.Info(
            $"아이라벨 Codabar 변환: data={data} → encode={o.BarcodeValue} caption={o.Text} algo={algo}");
    }

    private static char CodabarGuard(Dictionary<string, string> opt, string key, char fallback)
    {
        if (!opt.TryGetValue(key, out var raw)
            || !int.TryParse(raw, NumberStyles.Integer, CultureInfo.InvariantCulture, out var n))
            return fallback;
        return n switch
        {
            1 => 'B',
            2 => 'C',
            3 => 'D',
            _ => 'A'
        };
    }

    /// <summary>
    /// 아이라벨 Code 39 체크를 우리 인코더가 그대로 그릴 문자로 붙인다.
    /// 폼텍 Code 39(N:W=1:2, 값 그대로, 시작/종료 *)는 바꾸지 않는다.
    /// 체크는 ISO/IEC 16388 Mod 43(실측 123456789→2, 막대 60개).
    /// </summary>
    private static void NormalizeILabelCode39(DesignObject o, Dictionary<string, string> opt, string data)
    {
        var payload = (data ?? "").Trim().ToUpperInvariant();
        if (payload.Length == 0) payload = "0";
        var addCheck = IsTrue(opt.GetValueOrDefault("AddChecksum"));
        var checkOnCaption = IsTrue(opt.GetValueOrDefault("AddChecksumToCaption"));
        var check = '\0';
        if (addCheck)
        {
            check = ILabelCode39Check(payload);
            if (check != '\0')
                payload += check;
        }

        o.BarcodeVendor = "ilabel";
        o.BarcodeValue = payload;
        o.Text = checkOnCaption || check == '\0'
            ? payload
            : payload[..^1];
        EditorLog.Info($"아이라벨 Code39 변환: data={data} → encode={o.BarcodeValue} caption={o.Text}");
    }

    /// <summary>
    /// 아이라벨 PZN. 실측(1234567): 막대는 Code 39 `-12345678`(11글자·109요소·N:W=1:3),
    /// 캡션은 `PZN - 12345678`. 막대의 `-`는 IFA 식별자라 캡션에는 그대로 쓰지 않는다.
    /// </summary>
    private static void NormalizeILabelPzn(DesignObject o, Dictionary<string, string> opt, string data)
    {
        var digits = new string((data ?? "").Where(char.IsAsciiDigit).ToArray());
        if (digits.Length == 0) return;

        var check = IsTrue(opt.GetValueOrDefault("AddChecksum")) ? IfaPznCheckDigit(digits) : -1;
        var body = check >= 0 ? digits + (char)('0' + check) : digits;
        var caption = check >= 0 && !IsTrue(opt.GetValueOrDefault("AddChecksumToCaption"))
            ? digits
            : body;

        o.BarcodeVendor = "ilabel";
        o.BarcodeValue = "-" + body;
        o.Text = $"PZN - {caption}";
        EditorLog.Info($"아이라벨 PZN 변환: data={data} → encode={o.BarcodeValue} caption={o.Text}");
    }

    /// <summary>IFA mod-11 체크. 7자리는 가중 1..7, 6자리는 2..7. 나머지가 10이면 무효.</summary>
    private static int IfaPznCheckDigit(string digits)
    {
        if (digits.Length is not (6 or 7)) return -1;
        var firstWeight = digits.Length == 7 ? 1 : 2;
        var sum = 0;
        for (var i = 0; i < digits.Length; i++)
            sum += (digits[i] - '0') * (firstWeight + i);
        var check = sum % 11;
        return check == 10 ? -1 : check;
    }

    private const string Code39Alphabet = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%";

    private static char ILabelCode39Check(string payload)
    {
        var sum = 0;
        foreach (var ch in payload)
        {
            var i = Code39Alphabet.IndexOf(ch);
            if (i < 0) return '\0';
            sum += i;
        }
        return Code39Alphabet[sum % 43];
    }

    /// <summary>
    /// 아이라벨 ITF 체크를 값에 붙인다. 폼텍 ITF(N:W=1:2)는 바꾸지 않는다.
    /// 9자리 123456789, 체크 없음 → 인코더가 짝수로 0123456789 (막대 29개).
    /// </summary>
    private static void NormalizeILabelItf(DesignObject o, Dictionary<string, string> opt, string data)
    {
        var payload = new string((data ?? "").Where(char.IsAsciiDigit).ToArray());
        if (payload.Length == 0) payload = "0";
        var addCheck = OptTrue(opt, "AddChecksum");
        var checkOnCaption = OptTrue(opt, "AddChecksumToCaption");
        var check = '\0';
        if (addCheck)
        {
            check = ItfMod10Check(payload);
            payload += check;
        }

        o.BarcodeVendor = "ilabel";
        o.BarcodeValue = payload;
        o.Text = checkOnCaption || check == '\0' ? payload : payload[..^1];
        if (checkOnCaption)
            o.QrKind = "CHECK_CAPTION";
        EditorLog.Info($"아이라벨 ITF 변환: data={data} → encode={o.BarcodeValue} caption={o.Text} checkCaption={checkOnCaption}");
    }

    /// <summary>
    /// 아이라벨 JAN-13. 10자리 1234567890 → 49 + 값 + EAN 체크 = 4912345678904.
    /// 폼텍 JAN은 바꾸지 않는다.
    /// </summary>
    private static void NormalizeILabelJan13(DesignObject o, string data)
    {
        var d = new string((data ?? "").Where(char.IsAsciiDigit).ToArray());
        o.BarcodeVendor = "ilabel";
        o.BarcodeValue = ToILabelJan13Digits(d);
        EditorLog.Info($"아이라벨 JAN-13 변환: data={data} → {o.BarcodeValue}");
    }

    internal static string ToILabelJan13Digits(string digits)
    {
        if (digits.Length >= 13)
            return digits[..13];
        if (digits.Length == 12)
            return digits;
        if (digits.Length == 10)
            return "49" + digits;
        if (digits.Length is > 0 and < 10)
            return "49" + digits.PadLeft(10, '0');
        return digits;
    }

    private static char ItfMod10Check(string digits)
    {
        var sum = 0;
        for (var i = 0; i < digits.Length; i++)
        {
            var n = digits[digits.Length - 1 - i] - '0';
            sum += i % 2 == 0 ? n * 3 : n;
        }
        return (char)('0' + (10 - sum % 10) % 10);
    }

    /// <summary>
    /// 아이라벨 PDF417 ECC·최소 열. Micro는 타입만 유지하고 전용 인코더가 그린다.
    /// Macro(FileID/Segment)는 PDF417 옵션이지 별도 타입이 아니다.
    /// </summary>
    private static void NormalizeILabelPdf417(DesignObject o, Dictionary<string, string> opt, string format)
    {
        o.BarcodeVendor = "ilabel";
        var ecc = opt.GetValueOrDefault("PDF417ErrorCorrectionLevel", "").Trim();
        o.QrEcc = ecc.Length == 0 || ecc == "-1" ? "AUTO" : ecc;
        if (int.TryParse(opt.GetValueOrDefault("PDF417MinimumColumnCount"), NumberStyles.Integer,
                CultureInfo.InvariantCulture, out var cols) && cols > 0)
            o.QrKind = "COL:" + cols;
        EditorLog.Info($"아이라벨 PDF417: type={format} ecc={o.QrEcc} cols={o.QrKind} value={o.BarcodeValue}");
    }

    /// <summary>
    /// 아이라벨 DataMatrix 크기·압축을 QrKind/QrEcc에 담아 우리 ZXing 옵션으로 그린다.
    /// 폼텍 DataMatrix(정사각 최소 크기)는 바꾸지 않는다.
    /// Size 1=Auto는 압축 용량으로 최소 ECC200(Binary 16×16, EDIFACT 8×32). 0=AutoSquare.
    /// </summary>
    private static void NormalizeILabelDataMatrix(DesignObject o, Dictionary<string, string> opt)
    {
        o.BarcodeVendor = "ilabel";
        var size = 0;
        if (opt.TryGetValue("DataMatrixSize", out var sizeRaw))
            _ = int.TryParse(sizeRaw, NumberStyles.Integer, CultureInfo.InvariantCulture, out size);
        var compact = -1;
        if (opt.TryGetValue("DataMatrixCompactionMode", out var compactRaw))
            _ = int.TryParse(compactRaw, NumberStyles.Integer, CultureInfo.InvariantCulture, out compact);
        o.QrKind = ILabelDataMatrixSizeToken(size);
        o.QrEcc = compact switch
        {
            0 => "ASCII",
            1 => "C40",
            2 => "TEXT",
            3 => "X12",
            4 => "EDIFACT",
            5 => "BINARY",
            _ => "AUTO"
        };
        EditorLog.Info($"아이라벨 DataMatrix: size={size}→{o.QrKind} compact={compact}→{o.QrEcc} value={o.BarcodeValue}");
    }

    /// <summary>아이라벨 DataMatrixSize ItemIndex. 0=AutoSquare, 1=Auto, 2부터 고정 크기.</summary>
    private static readonly (int W, int H)[] ILabelDataMatrixSizes =
    [
        (0, 0), (0, 0),
        (8, 18), (8, 32), (10, 10), (12, 12), (12, 26), (12, 36),
        (14, 14), (16, 16), (16, 36), (16, 48), (18, 18), (20, 20),
        (22, 22), (24, 24), (26, 26), (32, 32), (36, 36), (40, 40),
        (44, 44), (48, 48), (52, 52), (64, 64), (72, 72), (80, 80),
        (88, 88), (96, 96), (104, 104), (120, 120)
    ];

    private static string ILabelDataMatrixSizeToken(int index)
    {
        if (index <= 0) return "AUTOSQ";
        if (index == 1) return "AUTO";
        if (index >= ILabelDataMatrixSizes.Length) return "AUTO";
        var (w, h) = ILabelDataMatrixSizes[index];
        return w <= 0 ? "AUTO" : $"{w}x{h}";
    }

    /// <summary>
    /// 아이라벨 Code128Alphabet을 QrKind에 담아 우리 Code 128 표로 그린다.
    /// Auto(-1)는 숫자면 Set C(실측 28막대). A/B는 고정 세트. 폼텍 CODE_128은 ZXing 유지.
    /// </summary>
    private static void NormalizeILabelCode128(DesignObject o, Dictionary<string, string> opt)
    {
        o.BarcodeVendor = "ilabel";
        if (!opt.TryGetValue("Code128Alphabet", out var raw)
            || !int.TryParse(raw, NumberStyles.Integer, CultureInfo.InvariantCulture, out var n))
        {
            o.QrKind = "AUTO";
            return;
        }
        o.QrKind = n switch
        {
            0 => "A",
            1 => "B",
            2 => "C",
            _ => "AUTO"
        };
        EditorLog.Info($"아이라벨 Code128 세트: alphabet={n} → {o.QrKind} value={o.BarcodeValue}");
    }

    private static char ILabelCodabarCheck(char start, string payload, char stop, int algo)
    {
        const string alph = "0123456789-$:/.+ABCD";
        var sum = 0;
        foreach (var ch in $"{start}{payload}{stop}")
        {
            var i = alph.IndexOf(ch);
            if (i >= 0) sum += i;
        }
        if (algo == 1)
            return alph[(16 - (sum % 16)) % 16];
        return alph[sum % 10];
    }

    private static string StripCodabarGuards(string? data)
    {
        var s = (data ?? "").Trim();
        static bool Guard(char c) => c is >= 'A' and <= 'D' or >= 'a' and <= 'd';
        if (s.Length >= 2 && Guard(s[0]) && Guard(s[^1]))
            return s[1..^1];
        return s;
    }

    /// <summary>Type=14. Cont = 모드:열너비,...:행높이,...</summary>
    private static DesignObject MakeTable(float x, float y, float w, float h, string cont)
    {
        var o = DesignObject.CreateDefault(ObjectType.Table, x, y);
        o.Width = w;
        o.Height = h;
        o.TableCells.Clear();
        if (TryParseTableCont(cont, out var cols, out var rows))
        {
            o.TableCols = Math.Clamp(cols.Count, 1, 40);
            o.TableRows = Math.Clamp(rows.Count, 1, 40);
        }
        o.EnsureTableSize();
        for (var i = 0; i < o.TableCells.Count; i++)
            o.TableCells[i] = "";
        return o;
    }

    /// <summary>Type=16 Shape=7. Cont = Font Awesome 아이콘 이름.</summary>
    private static DesignObject MakeIcon(float x, float y, float w, float h, string cont)
    {
        var o = DesignObject.CreateDefault(ObjectType.Icon, x, y);
        o.Width = w;
        o.Height = h;
        o.IconName = IconNameFromCont(cont);
        o.Text = o.IconName;
        if (FontAwesomeCatalog.TryResolve(o.IconName, out var d))
            o.Svg = d;
        return o;
    }

    private static DesignObject MakeShape(int type, int shape, float x, float y)
        => DesignObject.CreateShape(MapShapeKind(type, shape), x, y);

    private static ShapeKind MapShapeKind(int type, int shape) => (type, shape) switch
    {
        (5, _) => ShapeKind.Rect,
        (6, _) => ShapeKind.RoundRect,
        (7, _) => ShapeKind.Circle,
        (8, _) => ShapeKind.Line,
        (9, _) => ShapeKind.Arc,
        (13, _) => ShapeKind.Triangle,
        _ => ShapeKind.Rect
    };

    private static ContParse ParseCont(string? cont)
    {
        var result = new ContParse();
        if (string.IsNullOrEmpty(cont))
            return result;

        var field = FieldRegex.Match(cont);
        if (field.Success)
        {
            result.DataBound = true;
            result.DataColumn = field.Groups[1].Value.Trim();
        }

        // {#a,b}는 첫 줄만이 아니라 줄마다 앞에 붙는 문단 표시다. 전부 지운다.
        var body = ParaRegex.Replace(cont, "");

        var last = 0;
        foreach (Match m in RunRegex.Matches(body))
        {
            if (m.Index > last)
                AppendPlain(result, body[last..m.Index], null);

            var run = new ContRun
            {
                FromCont = true,
                FontFamily = m.Groups[1].Value.Trim(),
                FontSizePt = float.TryParse(m.Groups[2].Value, NumberStyles.Float, CultureInfo.InvariantCulture, out var fs)
                    ? fs : 9f,
                Fill = ArgbToCss(int.TryParse(m.Groups[4].Value, NumberStyles.Integer, CultureInfo.InvariantCulture, out var c)
                    ? c : -16777216)
            };
            ApplyBius(run, m.Groups[3].Value);
            last = m.Index + m.Length;
            var next = RunRegex.Match(body, last);
            var textEnd = next.Success ? next.Index : body.Length;
            var text = last < textEnd ? body[last..textEnd] : "";
            last = textEnd;
            AppendPlain(result, text, run);
        }

        if (last < body.Length)
            AppendPlain(result, body[last..], null);

        result.Plain = FieldRegex.Replace(result.Plain, m => m.Groups[1].Value);
        return result;
    }

    private static void AppendPlain(ContParse result, string text, ContRun? style)
    {
        if (string.IsNullOrEmpty(text))
            return;
        result.Plain += text;
        var run = style ?? new ContRun();
        run.Text = FieldRegex.Replace(text, m => $"[{m.Groups[1].Value.Trim()}]");
        result.Runs.Add(run);
    }

    private static void ApplyBius(ContRun run, string bius)
    {
        run.Bold = bius.Length >= 1 && char.IsUpper(bius[0]);
        run.Italic = bius.Length >= 2 && char.IsUpper(bius[1]);
        run.Underline = bius.Length >= 3 && char.IsUpper(bius[2]);
        run.Strikeout = bius.Length >= 4 && char.IsUpper(bius[3]);
    }

    private static void ApplyRun(DesignObject obj, ContRun run)
    {
        if (!string.IsNullOrWhiteSpace(run.FontFamily))
            FontCatalog.ApplyImportedFamily(obj, run.FontFamily);
        if (run.FontSizePt > 0)
            obj.FontSize = FontCatalog.FromPt(run.FontSizePt);
        obj.Bold = run.Bold;
        obj.Italic = run.Italic;
        obj.Underline = run.Underline;
        obj.Strikeout = run.Strikeout;
        obj.Fill = run.Fill;
    }

    private static void LogImportFonts(LabelDocument doc)
    {
        var texts = new SortedSet<string>(StringComparer.OrdinalIgnoreCase);
        var captions = new SortedSet<string>(StringComparer.OrdinalIgnoreCase);
        foreach (var obj in doc.Pages.SelectMany(p => p.Cells).SelectMany(c => c.Objects))
        {
            if (obj.Type == ObjectType.Text)
            {
                foreach (var fam in RichTextModel.TextFamilies(obj))
                    texts.Add(fam);
            }
            else if (obj.Type is ObjectType.Barcode or ObjectType.Qr && obj.BarcodeShowText
                     && !string.IsNullOrWhiteSpace(obj.FontFamily))
            {
                captions.Add(obj.FontFamily);
            }
        }
        EditorLog.Info(
            $"아이라벨 글꼴: 텍스트=[{string.Join(", ", texts)}] 바코드캡션=[{string.Join(", ", captions)}]");
    }

    private static List<TextParagraph> ToParagraphs(ContParse parsed)
    {
        var fallback = parsed.Runs
            .FirstOrDefault(r => r.FromCont && !string.IsNullOrWhiteSpace(r.FontFamily))
            ?.FontFamily ?? "Pretendard";
        var paragraphs = new List<TextParagraph> { new() { Align = "left" } };
        foreach (var run in parsed.Runs)
        {
            var lines = (run.Text ?? "").Replace("\r\n", "\n").Replace('\r', '\n').Split('\n');
            for (var i = 0; i < lines.Length; i++)
            {
                if (i > 0)
                    paragraphs.Add(new TextParagraph { Align = "left" });
                if (lines[i].Length == 0 && i + 1 < lines.Length)
                    continue;
                paragraphs[^1].Spans.Add(new TextSpan
                {
                    Text = lines[i],
                    FontFamily = FontCatalog.CanonicalId(
                        string.IsNullOrWhiteSpace(run.FontFamily) ? fallback : run.FontFamily),
                    FontSize = FontCatalog.FromPt(run.FontSizePt > 0 ? run.FontSizePt : 9f),
                    Fill = run.Fill,
                    Bold = run.Bold,
                    Italic = run.Italic,
                    Underline = run.Underline,
                    Strikeout = run.Strikeout
                });
            }
        }
        return paragraphs;
    }

    private static void SplitBarcodeCont(string? cont, out string type, out string options, out string data)
    {
        type = cont ?? "";
        options = "";
        data = "";
        var first = type.IndexOf('|');
        if (first < 0) return;
        var rest = type[(first + 1)..];
        type = type[..first];
        var second = rest.IndexOf('|');
        if (second >= 0)
        {
            options = rest[..second];
            data = rest[(second + 1)..];
        }
        else
        {
            options = rest;
        }
    }

    private static Dictionary<string, string> ParseOptions(string options)
    {
        var map = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase);
        if (string.IsNullOrWhiteSpace(options))
            return map;
        foreach (var part in options.Split(';', StringSplitOptions.RemoveEmptyEntries))
        {
            var colon = part.IndexOf(':');
            if (colon < 0)
            {
                map[part.Trim()] = "";
                continue;
            }
            map[part[..colon].Trim()] = part[(colon + 1)..].Trim();
        }
        return map;
    }

    /// <summary>아이라벨 저장 타입명 → 에디터 BarcodeFormat. 폼텍 MapBarcode 를 쓰지 않는다.</summary>
    private static string MapBarcodeFormat(string? raw)
    {
        var key = (raw ?? "").Trim();
        if (key.Equals("GS1-128", StringComparison.OrdinalIgnoreCase))
            key = "EAN-128";
        foreach (var open in new[] { '(', '（' })
        {
            var paren = key.IndexOf(open);
            if (paren > 0)
            {
                key = key[..paren].Trim();
                break;
            }
        }
        var n = key.Replace("-", "_").Replace(" ", "_").Replace("/", "_").ToUpperInvariant();
        if (n.Contains("ITF_14") || n.Contains("EAN_14") || n is "ITF14" or "EAN14")
            return "ITF_14";
        return n switch
        {
            "QR" or "QR_CODE" or "QRCODE" => "QR_CODE",
            "DATAMATRIX" or "DATA_MATRIX" => "DATA_MATRIX",
            "MICRO_PDF417" or "MICRO_PDF_417" => "MICRO_PDF417",
            "PDF417_TRUNCATED" or "PDF_417_TRUNCATED" or "PDF_417_TRUNC" => "PDF_417_TRUNC",
            "PDF417" or "PDF_417" or "MACRO_PDF417" => "PDF_417",
            "AZTEC" => "AZTEC",
            "ISBN" or "BOOKLAND" or "BOOKLAND_EAN" or "BOOK_LAND" => "ISBN",
            "EAN_13" or "EAN13" => "EAN_13",
            "JAN_13" or "JAN13" => "JAN_13",
            "EAN_8" or "EAN8" => "EAN_8",
            "EAN_5" or "EAN5" => "EAN_5",
            "EAN_2" or "EAN2" => "EAN_2",
            "UPC_A" or "UPCA" => "UPC_A",
            "UPC_E" or "UPCE" => "UPC_E",
            "CODE_39" or "CODE39" => "CODE_39",
            "CODE_93" or "CODE93" => "CODE_93",
            "CODE_128" or "CODE128" => "CODE_128",
            "EAN_128" or "EAN128" or "GS1_128" => "EAN_128",
            "ITF_14" or "EAN_14" or "ITF14" or "EAN14" => "ITF_14",
            "CODABAR" => "CODABAR",
            "PZN" => "PZN",
            "POSTNET" => "POSTNET",
            "PLANET" => "PLANET",
            "KOREAN_POSTCODE" or "KOREAN_POST" => "KOREAN_POST",
            "OPTICAL_PRODUCT" or "OPC" => "OPC",
            "NUMLY" or "ESBN" or "ESN" or "NUMLY_NUMBER" => "NUMLY",
            _ => BarcodeCatalog.Find(n) is not null ? n : "CODE_128"
        };
    }

    private static string MapQrEcc(string raw) => raw.Trim() switch
    {
        "0" => "L",
        "2" => "Q",
        "3" => "H",
        _ => "M"
    };

    private static bool TryParseTableCont(string? cont, out List<float> cols, out List<float> rows)
    {
        cols = [];
        rows = [];
        if (string.IsNullOrWhiteSpace(cont))
            return false;
        var parts = cont.Split(':');
        if (parts.Length < 3)
            return false;
        cols.AddRange(ParsePositiveList(parts[1]));
        rows.AddRange(ParsePositiveList(parts[2]));
        return cols.Count > 0 && rows.Count > 0;
    }

    private static IEnumerable<float> ParsePositiveList(string csv)
    {
        foreach (var token in csv.Split(',', StringSplitOptions.RemoveEmptyEntries))
        {
            if (float.TryParse(token.Trim(), NumberStyles.Float, CultureInfo.InvariantCulture, out var v) && v > 0)
                yield return v;
        }
    }

    private static string IconNameFromCont(string? cont)
    {
        var s = (cont ?? "").Trim();
        var i = 0;
        while (i < s.Length && (char.IsLetterOrDigit(s[i]) || s[i] is '-' or '_' or ' '))
            i++;
        return i > 0 ? s[..i].Trim() : s;
    }

    private static string DetectImageMime(byte[] bytes)
    {
        if (bytes.Length >= 8 && bytes[0] == 0x89 && bytes[1] == 0x50) return "image/png";
        if (bytes.Length >= 3 && bytes[0] == 0xFF && bytes[1] == 0xD8) return "image/jpeg";
        if (bytes.Length >= 3 && bytes[0] == (byte)'G' && bytes[1] == (byte)'I') return "image/gif";
        if (bytes.Length >= 2 && bytes[0] == (byte)'B' && bytes[1] == (byte)'M') return "image/bmp";
        return "image/png";
    }

    private static string StripCont(string? cont)
    {
        if (string.IsNullOrEmpty(cont)) return "";
        var s = Regex.Replace(cont, @"\{[#&@][^}]*\}", "");
        return WebUtility.HtmlDecode(s).Trim();
    }

    private static string? NullIfEmpty(string s) => string.IsNullOrWhiteSpace(s) ? null : s;

    private static bool IsTrue(string? raw)
        => raw is not null && (raw.Trim().Equals("True", StringComparison.OrdinalIgnoreCase) || raw.Trim() == "1");

    private static bool OptTrue(Dictionary<string, string> opt, string key)
        => opt.TryGetValue(key, out var val) && IsTrue(val);

    private static bool LooksLikeILabelItf14(string? raw)
    {
        var k = (raw ?? "").ToUpperInvariant();
        return k.Contains("ITF-14") || k.Contains("ITF_14") || k.Contains("EAN-14") || k.Contains("EAN_14");
    }

    private static string ArgbToCss(int argb)
    {
        if (argb == -1) return "transparent";
        var u = unchecked((uint)argb);
        return $"#{(u >> 16) & 0xFF:X2}{(u >> 8) & 0xFF:X2}{u & 0xFF:X2}";
    }

    private static LabelDocument FromXml(byte[] bytes, string name, PaperCatalog papers)
    {
        XDocument xml;
        try
        {
            using var ms = new MemoryStream(bytes);
            xml = XDocument.Load(ms);
        }
        catch (Exception ex)
        {
            throw new InvalidDataException("아이라벨 XML을 읽지 못했습니다: " + ex.Message);
        }

        var root = xml.Root ?? throw new InvalidDataException("아이라벨 XML 루트가 없습니다.");
        var paperEl = root.Descendants().FirstOrDefault(e => e.Name.LocalName.Equals("Paper", StringComparison.OrdinalIgnoreCase));
        float lw = Attr(paperEl, "LabelWidth", 70), lh = Attr(paperEl, "LabelHeight", 36);
        var cols = (int)Attr(paperEl, "Cols", 1);
        var rows = (int)Attr(paperEl, "Rows", 1);
        var paper = ExternalImportService.ResolvePaper(
            papers, "ilabel", AttrStr(paperEl, "Name"), lw, lh, cols, rows,
            Attr(paperEl, "Width", 210), Attr(paperEl, "Height", 297),
            Attr(paperEl, "MarginLeft", -1), Attr(paperEl, "MarginTop", -1),
            -1, -1, Attr(paperEl, "PitchHorizen", -1), Attr(paperEl, "PitchVertical", -1));
        var bgUrl = AttrStr(paperEl, "LabelBackground");
        if (!string.IsNullOrWhiteSpace(bgUrl))
            paper.DesignImageUrl = bgUrl;
        ApplyPaperShape(
            paper,
            (int)Attr(paperEl, "LabelShape", 0),
            AttrStr(paperEl, "LabelFrame") ?? "",
            Attr(paperEl, "LabelEdgeWidth", 0),
            Attr(paperEl, "LabelEdgeHeight", 0));
        var doc = LabelDocument.CreateBlank(paper);
        doc.Name = name;
        foreach (var cell in doc.Pages[0].Cells)
            cell.Objects.Clear();

        var per = Math.Max(1, paper.LabelsPerPage);
        var z = 0;
        foreach (var el in root.Descendants().Where(e => e.Name.LocalName.Equals("Factor", StringComparison.OrdinalIgnoreCase)
                                                       || e.Name.LocalName.Equals("Object", StringComparison.OrdinalIgnoreCase)))
        {
            var row = new Dictionary<string, object?>(StringComparer.OrdinalIgnoreCase);
            foreach (var a in el.Attributes())
                row[a.Name.LocalName] = a.Value;
            foreach (var c in el.Elements())
                row[c.Name.LocalName] = c.Value;
            var fake = new Jet4Row(row);
            var obj = MapFactor(fake, z++);
            if (obj is null) continue;
            var labelId = fake.GetInt("LabelId");
            if (labelId == CommonLabelId)
                ExternalImportService.PlaceCommon(doc, obj);
            else
            {
                if (labelId < 0 || labelId >= per * ExternalImportService.MaxImportPages)
                    labelId = 0;
                ExternalImportService.Place(doc, obj, labelId + 1, per);
            }
        }

        if (doc.Pages.All(p => p.Cells.All(c => c.Objects.Count == 0)))
        {
            var t = DesignObject.CreateDefault(ObjectType.Text, paper.LabelWidthMm * 0.1f, paper.LabelHeightMm * 0.3f);
            t.Text = name;
            doc.Pages[0].Cells[0].Objects.Add(t);
            EditorLog.Warn("아이라벨 XML에서 객체를 찾지 못해 빈 용지로 엽니다.");
        }

        if (doc.Data is { RowCount: > 0 })
            doc.EnsurePagesForData();
        return doc;
    }

    /// <summary>
    /// 아이라벨 용지 칼선. SVG가 있으면 특수 모양, LabelShape=2는 원,
    /// 그 외는 LabelEdgeWidth/Height(mm)를 모서리 반경으로 쓴다.
    /// 파선 가이드는 파일 객체가 아니라 아이라벨 UI가 칼선에 그리는 표시다.
    /// </summary>
    private static void ApplyPaperShape(PaperSpec paper, int labelShape, string? frame, float edgeW, float edgeH)
    {
        if (!string.IsNullOrWhiteSpace(frame) && frame.Contains("<svg", StringComparison.OrdinalIgnoreCase))
        {
            paper.Shape.Kind = "svg";
            paper.Shape.Svg = frame;
            return;
        }

        if (labelShape == 2)
        {
            paper.Shape.Kind = "ellipse";
            return;
        }

        var radius = Math.Max(edgeW, edgeH);
        if (radius <= 0.05f)
        {
            paper.Shape.Kind = "rect";
            return;
        }

        var cap = Math.Min(paper.LabelWidthMm, paper.LabelHeightMm) * 0.5f;
        paper.Shape.Kind = "roundrect";
        paper.Shape.CornerRadiusMm = Math.Min(radius, cap);
        EditorLog.Info($"아이라벨 칼선: roundrect r={paper.Shape.CornerRadiusMm:0.##}mm (LabelEdge)");
    }

    private static float Attr(XElement? el, string name, float fallback)
    {
        var v = el?.Attribute(name)?.Value ?? el?.Element(name)?.Value;
        return float.TryParse(v, NumberStyles.Float, CultureInfo.InvariantCulture, out var n) ? n : fallback;
    }

    private static string? AttrStr(XElement? el, string name)
        => el?.Attribute(name)?.Value ?? el?.Element(name)?.Value;

    private sealed class ContParse
    {
        public string Plain { get; set; } = "";
        public bool DataBound { get; set; }
        public string? DataColumn { get; set; }
        public List<ContRun> Runs { get; } = [];
    }

    private sealed class ContRun
    {
        public bool FromCont { get; set; }
        public string Text { get; set; } = "";
        public string FontFamily { get; set; } = "굴림";
        public float FontSizePt { get; set; } = 9f;
        public bool Bold { get; set; }
        public bool Italic { get; set; }
        public bool Underline { get; set; }
        public bool Strikeout { get; set; }
        public string Fill { get; set; } = "#000000";
    }
}
