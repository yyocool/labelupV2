using System.Globalization;
using System.IO.Compression;
using System.Net;
using System.Text;
using System.Text.RegularExpressions;
using System.Xml.Linq;
using LabelUp.Editor.Models;
using LabelUp.Editor.Vendor;

namespace LabelUp.Editor.Services;

/// <summary>
/// 애니라벨 .lbl / 폼텍 .dgz·.dgf / 아이라벨 .idf 를 LabelUp 문서로 변환한다.
/// WPF 분석기(AniLabelDesignParser / FormtecDgfAnalyzer / ILabelIdfAnalyzer)와 같은 매핑을 쓴다.
/// </summary>
public sealed class ExternalImportService(PaperCatalog papers)
{
    public static readonly string[] VendorExtensions = [".lbl", ".idf", ".xml", ".dgz", ".dgf", ".fmt", ".fdx", ".zip"];

    public static bool IsVendorFileName(string? fileName)
    {
        var ext = Path.GetExtension(fileName ?? "").ToLowerInvariant();
        return VendorExtensions.Contains(ext);
    }

    public LabelDocument Import(string fileName, byte[] bytes)
    {
        var result = Analyze(fileName, bytes);
        if (!result.Ok || result.Document is null)
            throw new InvalidDataException(string.IsNullOrWhiteSpace(result.Error) ? "타사 포맷을 변환하지 못했습니다." : result.Error);
        return result.Document;
    }

    public VendorImportResult Analyze(string fileName, byte[] bytes)
    {
        Encoding.RegisterProvider(CodePagesEncodingProvider.Instance);
        var result = new VendorImportResult { FileName = fileName };
        try
        {
            if (bytes is not { Length: > 16 })
                throw new InvalidDataException("파일이 비어 있거나 너무 짧습니다.");

            var ext = Path.GetExtension(fileName).ToLowerInvariant();
            var payload = UnwrapContainer(bytes, out var innerName);
            var name = Path.GetFileNameWithoutExtension(string.IsNullOrWhiteSpace(innerName) ? fileName : innerName);
            var vendor = DetectVendor(payload, ext, innerName);
            result.VendorId = vendor;
            result.VendorName = VendorTitle(vendor);
            EditorLog.Info($"타사 포맷 분석: {fileName} → {vendor} ({payload.Length} bytes)");

            var doc = vendor switch
            {
                "ilabel" => ILabelImporter.Import(payload, name, papers),
                "anylabel" => AniLabelImporter.Import(payload, name, papers),
                "formtec" => FormtecImporter.Import(payload, name, papers),
                _ => throw new NotSupportedException("지원 포맷: 애니라벨 .lbl, 폼텍 .dgz/.dgf, 아이라벨 .idf")
            };
            doc.EnsureStructure();
            FillReport(result, doc);
            result.Ok = true;
            EditorLog.Info($"변환 분석 완료: {doc.Name} objects={result.ObjectCount} data={result.DataRows}");
            return result;
        }
        catch (Exception ex)
        {
            EditorLog.Error("타사 포맷 분석 실패", ex);
            result.Ok = false;
            result.Error = ex.Message;
            if (string.IsNullOrWhiteSpace(result.VendorName) && !string.IsNullOrWhiteSpace(result.VendorId))
                result.VendorName = VendorTitle(result.VendorId);
            return result;
        }
    }

    internal static string VendorTitle(string vendor) => vendor switch
    {
        "anylabel" => "애니라벨",
        "ilabel" => "아이라벨",
        "formtec" => "폼텍 디자인프로",
        _ => vendor
    };

    private static void FillReport(VendorImportResult result, LabelDocument doc)
    {
        result.Document = doc;
        result.PaperNo = doc.Paper.PaperNo;
        result.PaperName = doc.Paper.Name;
        result.PaperLayout =
            $"{doc.Paper.Columns}열 × {doc.Paper.Rows}행 ({doc.Paper.LabelsPerPage}칸) · " +
            $"라벨 {doc.Paper.LabelWidthMm:0.#}×{doc.Paper.LabelHeightMm:0.#} mm · " +
            $"용지 {doc.Paper.PaperWidthMm:0.#}×{doc.Paper.PaperHeightMm:0.#} mm";
        if (doc.Data is { RowCount: > 0, Columns.Count: > 0 } data)
        {
            result.HasData = true;
            result.DataRows = data.RowCount;
            result.DataColumns = [.. data.Columns];
            var take = Math.Min(3, data.RowCount);
            for (var r = 0; r < take; r++)
            {
                var cells = data.Columns.Select(c => data.Get(r, c)).Where(s => !string.IsNullOrWhiteSpace(s));
                result.DataPreview.Add(string.Join(" · ", cells));
            }
        }

        foreach (var obj in doc.Pages.SelectMany(p => p.Cells).SelectMany(c => c.Objects))
        {
            var kind = KindName(obj);
            result.TypeCounts[kind] = result.TypeCounts.TryGetValue(kind, out var n) ? n + 1 : 1;
            result.Items.Add(new VendorImportItem
            {
                Kind = kind,
                Summary = ItemSummary(obj),
                Geometry = $"{obj.X:0.#},{obj.Y:0.#} · {obj.Width:0.#}×{obj.Height:0.#} mm"
            });
        }
    }

    private static string KindName(DesignObject obj)
    {
        if (obj.DataBound) return "자료연결";
        return obj.Type switch
        {
            ObjectType.Text => obj.TextMode switch
            {
                TextMode.WordArt => "워드아트",
                TextMode.Extended => "확장문자열",
                TextMode.Custom => "사용자정의문자열",
                _ => "텍스트"
            },
            ObjectType.Table => "표",
            ObjectType.Barcode => "바코드",
            ObjectType.Qr => "QR/2D",
            ObjectType.Image => "이미지",
            ObjectType.Clipart => "클립아트",
            ObjectType.Icon => "아이콘",
            _ when DesignObject.IsShape(obj.Type) => "도형",
            _ => obj.Type.ToString()
        };
    }

    private static string ItemSummary(DesignObject obj)
    {
        if (obj.DataBound) return string.IsNullOrWhiteSpace(obj.DataColumn) ? obj.Text : $"[{obj.DataColumn}] {obj.Text}";
        if (obj.Type is ObjectType.Barcode or ObjectType.Qr)
            return $"{obj.BarcodeFormat} · {obj.BarcodeValue}";
        if (obj.Type == ObjectType.Table)
            return $"{obj.TableRows}행 × {obj.TableCols}열";
        if (DesignObject.IsShape(obj.Type))
            return obj.ShapeKind.ToString();
        if (!string.IsNullOrWhiteSpace(obj.Text))
            return obj.Text.Length > 40 ? obj.Text[..40] + "…" : obj.Text;
        if (!string.IsNullOrWhiteSpace(obj.IconName)) return obj.IconName;
        return obj.Type.ToString();
    }

    private static string DetectVendor(byte[] data, string ext, string innerName)
    {
        var innerExt = Path.GetExtension(innerName).ToLowerInvariant();
        if (Jet4Database.LooksLikeJet(data) || ext is ".idf" || innerExt is ".idf") return "ilabel";
        if (LooksLikeLbl(data) || ext is ".lbl" || innerExt is ".lbl") return "anylabel";
        if (LooksLikeXml(data) && (ext is ".xml" || innerExt is ".xml")) return "ilabel";
        if (LooksLikeDgf(data) || ext is ".dgf" or ".dgz" or ".fmt" or ".fdx" || innerExt is ".dgf" or ".fmt" or ".fdx")
            return "formtec";
        if (LooksLikeXml(data)) return "ilabel";
        throw new NotSupportedException("지원 포맷: 애니라벨 .lbl, 폼텍 .dgz/.dgf, 아이라벨 .idf");
    }

    internal static bool LooksLikeLbl(byte[] data)
    {
        if (data.Length < 40) return false;
        var head = Encoding.Unicode.GetString(data, 0, Math.Min(80, data.Length));
        return head.Contains("Printec", StringComparison.OrdinalIgnoreCase)
               || head.Contains("Label Maker", StringComparison.OrdinalIgnoreCase);
    }

    internal static bool LooksLikeDgf(byte[] data)
        => data.Length > 80 && FindMagic(data, 0xB8, 0x01, 512 * 1024) >= 0;

    internal static bool LooksLikeXml(byte[] data)
    {
        var i = 0;
        if (data.Length >= 3 && data[0] == 0xEF && data[1] == 0xBB && data[2] == 0xBF) i = 3;
        while (i < data.Length && data[i] <= 32) i++;
        return i < data.Length && data[i] == (byte)'<';
    }

    private static byte[] UnwrapContainer(byte[] bytes, out string innerName)
    {
        innerName = "";
        if (bytes.Length < 4 || bytes[0] != 0x50 || bytes[1] != 0x4B)
            return bytes;
        try
        {
            using var ms = new MemoryStream(bytes);
            using var zip = new ZipArchive(ms, ZipArchiveMode.Read, leaveOpen: false);
            ZipArchiveEntry? best = null;
            best = zip.Entries.FirstOrDefault(e =>
                       e.FullName.Replace('\\', '/').Contains("Design/", StringComparison.OrdinalIgnoreCase)
                       && e.FullName.EndsWith(".dgf", StringComparison.OrdinalIgnoreCase))
                   ?? zip.Entries.FirstOrDefault(e => e.FullName.EndsWith(".dgf", StringComparison.OrdinalIgnoreCase))
                   ?? zip.Entries.FirstOrDefault(e => e.FullName.EndsWith(".idf", StringComparison.OrdinalIgnoreCase))
                   ?? zip.Entries.FirstOrDefault(e => e.FullName.EndsWith(".lbl", StringComparison.OrdinalIgnoreCase))
                   ?? zip.Entries.FirstOrDefault(e => e.FullName.EndsWith(".xml", StringComparison.OrdinalIgnoreCase))
                   ?? zip.Entries.FirstOrDefault(e => e.FullName.EndsWith(".fmt", StringComparison.OrdinalIgnoreCase)
                                                   || e.FullName.EndsWith(".fdx", StringComparison.OrdinalIgnoreCase));
            best ??= zip.Entries.FirstOrDefault(e => e.Length > 32);
            if (best is null) return bytes;
            innerName = best.Name;
            using var s = best.Open();
            using var outMs = new MemoryStream();
            s.CopyTo(outMs);
            return outMs.ToArray();
        }
        catch (Exception ex)
        {
            EditorLog.Warn("압축 해제 실패: " + ex.Message);
            return bytes;
        }
    }

    internal static int FindMagic(byte[] data, byte a, byte b, int maxScan = 0)
    {
        var last = data.Length - 1;
        if (maxScan > 0) last = Math.Min(last, maxScan);
        for (var i = 0; i < last; i++)
            if (data[i] == a && data[i + 1] == b) return i;
        return -1;
    }

    internal static int FindU32(byte[] data, int start, int end, uint value)
    {
        end = Math.Min(end, data.Length - 3);
        for (var i = start; i < end; i++)
            if (BitConverter.ToUInt32(data, i) == value) return i;
        return -1;
    }

    internal static PaperSpec ResolvePaper(
        PaperCatalog papers, string vendor, string? paperNo,
        float labelW, float labelH, int cols, int rows,
        float paperW = 0, float paperH = 0,
        float left = -1, float top = -1, float right = -1, float bottom = -1,
        float hGap = -1, float vGap = -1)
    {
        var mapped = papers.MapVendor(vendor, paperNo ?? "") ?? paperNo;
        var paper = papers.Find(mapped ?? "")
                    ?? papers.Papers.FirstOrDefault(p =>
                           Math.Abs(p.LabelWidthMm - labelW) < 0.4f && Math.Abs(p.LabelHeightMm - labelH) < 0.4f)
                    ?? PaperSpec.CreateDefault();
        paper = paper.Clone();
        if (labelW > 1) paper.LabelWidthMm = labelW;
        if (labelH > 1) paper.LabelHeightMm = labelH;
        if (cols > 0) paper.Columns = cols;
        if (rows > 0) paper.Rows = rows;
        if (paperW > 20) paper.PaperWidthMm = paperW;
        if (paperH > 20) paper.PaperHeightMm = paperH;
        if (hGap >= 0) paper.HGapMm = hGap;
        if (vGap >= 0) paper.VGapMm = vGap;
        if (left >= 0 && top >= 0)
        {
            paper.LeftMarginMm = left;
            paper.TopMarginMm = top;
            paper.RightMarginMm = right >= 0 ? right : paper.RightMarginMm;
            paper.BottomMarginMm = bottom >= 0 ? bottom : paper.BottomMarginMm;
        }
        else
        {
            paper.RecalcMarginsFromGaps();
        }
        if (!string.IsNullOrWhiteSpace(paperNo)) paper.PaperNo = mapped ?? paper.PaperNo;
        return paper;
    }

    internal const int MaxImportPages = 24;

    internal static void Place(LabelDocument doc, DesignObject obj, int globalLabelIndex, int per)
    {
        per = Math.Max(1, per);
        var idx = Math.Max(1, globalLabelIndex) - 1;
        var pageIndex = idx / per;
        var cellIndex = idx % per;
        if (pageIndex < 0 || pageIndex >= MaxImportPages)
        {
            pageIndex = 0;
            cellIndex = 0;
        }
        while (doc.Pages.Count <= pageIndex)
            doc.AddPage();
        doc.Pages[pageIndex].EnsureCellCount(per);
        doc.Pages[pageIndex].Cells[cellIndex].Objects.Add(obj);
    }

    internal static void PlaceCommon(LabelDocument doc, DesignObject obj)
    {
        foreach (var page in doc.Pages)
        foreach (var cell in page.Cells)
        {
            var copy = obj.Clone();
            copy.Id = Guid.NewGuid().ToString("N");
            cell.Objects.Add(copy);
        }
    }

    internal static string ToDataUrl(byte[] bytes, string mime)
        => $"data:{mime};base64,{Convert.ToBase64String(bytes)}";

    internal static string? FindImageDataUrl(byte[] data, int start, int end)
    {
        end = Math.Min(end, data.Length);
        const int maxImage = 8 * 1024 * 1024;
        if (start + 0x55 < end)
        {
            var structured = FormtecImageFallback(data, start, end);
            if (structured is not null) return structured;
        }
        for (var i = start; i + 8 < end && i < start + 4096; i++)
        {
            if (data[i] == 0x89 && data[i + 1] == 0x50 && data[i + 2] == 0x4E)
            {
                var take = Math.Min(maxImage, end - i);
                return ToDataUrl(data.AsSpan(i, take).ToArray(), "image/png");
            }
            if (data[i] == 0xFF && data[i + 1] == 0xD8 && data[i + 2] == 0xFF)
            {
                var limit = Math.Min(end, i + maxImage);
                var eoi = limit;
                for (var j = i + 2; j + 1 < limit; j++)
                    if (data[j] == 0xFF && data[j + 1] == 0xD9) { eoi = j + 2; break; }
                return ToDataUrl(data.AsSpan(i, eoi - i).ToArray(), "image/jpeg");
            }
            if (data[i] == (byte)'B' && data[i + 1] == (byte)'M' && i + 6 < end)
            {
                var len = BitConverter.ToInt32(data, i + 2);
                if (len is > 54 and < 8_000_000 && i + len <= data.Length)
                    return ToDataUrl(data.AsSpan(i, len).ToArray(), "image/bmp");
            }
            if (data[i] == (byte)'G' && data[i + 1] == (byte)'I' && data[i + 2] == (byte)'F')
                return ToDataUrl(data.AsSpan(i, Math.Min(end, i + 2_000_000) - i).ToArray(), "image/gif");
        }
        return null;
    }

    private static string? FormtecImageFallback(byte[] data, int start, int end)
    {
        if (start + 0x55 > end) return null;
        if (start + 0x53 + 4 <= data.Length && data[start + 0x51] == (byte)'B' && data[start + 0x52] == (byte)'M')
        {
            var len = BitConverter.ToInt32(data, start + 0x53);
            if (len is > 54 and < 8_000_000 && start + 0x51 + len <= data.Length)
                return ToDataUrl(data.AsSpan(start + 0x51, len).ToArray(), "image/bmp");
        }
        var dataLen = BitConverter.ToInt32(data, start + 0x51);
        var off = start + 0x55;
        if (dataLen is > 16 and < 8_000_000 && off + dataLen <= data.Length)
        {
            var slice = data.AsSpan(off, dataLen);
            if (slice[0] == 0x89 && slice[1] == 0x50) return ToDataUrl(slice.ToArray(), "image/png");
            if (slice[0] == 0xFF && slice[1] == 0xD8) return ToDataUrl(slice.ToArray(), "image/jpeg");
            if (slice[0] == (byte)'G' && slice[1] == (byte)'I') return ToDataUrl(slice.ToArray(), "image/gif");
        }
        return null;
    }

    internal static string MapBarcode(string? raw)
    {
        var key = (raw ?? "").Replace("-", "_").Replace(" ", "_").Replace("/", "_").ToUpperInvariant();
        if (key.Contains("QR")) return "QR_CODE";
        if (key.Contains("DATA") && key.Contains("MATRIX")) return "DATA_MATRIX";
        if (key.Contains("MICRO") && key.Contains("PDF")) return "MICRO_PDF417";
        if (key.Contains("PDF")) return "PDF_417";
        if (key.Contains("AZTEC")) return "AZTEC";
        if (key.Contains("EAN_13") || key.Contains("EAN13") || key.Contains("JAN_13") || key.Contains("JAN13") || key.Contains("ISBN")) return "EAN_13";
        if (key.Contains("EAN_8") || key.Contains("EAN8") || key.Contains("JAN_8")) return "EAN_8";
        if (key.Contains("UPC_A") || key.Contains("UPCA")) return "UPC_A";
        if (key.Contains("UPC_E")) return "UPC_E";
        if (key.Contains("CODE_39") || key.Contains("CODE39")) return "CODE_39";
        if (key.Contains("CODE_93") || key.Contains("CODE93")) return "CODE_93";
        if (key.Contains("CODE_128") || key.Contains("CODE128") || key.Contains("EAN_128") || key.Contains("GS1")) return "CODE_128";
        if (key.Contains("ITF_14") || key.Contains("EAN_14")) return "ITF_14";
        if (key.Contains("ITF") || key.Contains("I25") || key.Contains("2_5") || key.Contains("2/5")) return "ITF";
        if (key.Contains("CODABAR")) return "CODABAR";
        if (key.Contains("POST")) return "KOREAN_POST";
        return BarcodeCatalog.Find(key) is not null ? key : "CODE_128";
    }

    internal static bool Is2dBarcode(string format)
        => format is "QR_CODE" or "DATA_MATRIX" or "PDF_417" or "PDF_417_TRUNC" or "MICRO_PDF417" or "AZTEC";

    internal static List<string> ExtractPrintable(byte[] data, int start, int end, int minLen = 2)
    {
        var list = new List<string>();
        var sb = new StringBuilder();
        end = Math.Min(end, data.Length);
        for (var i = start; i + 1 < end; i += 2)
        {
            var ch = (char)(data[i] | (data[i + 1] << 8));
            if (ch is >= ' ' and <= '~' or >= '가' and <= '힣' or >= 'ㄱ' and <= 'ㅣ' or >= (char)0x4E00 and <= (char)0x9FFF)
                sb.Append(ch);
            else
            {
                if (sb.Length >= minLen) list.Add(sb.ToString());
                sb.Clear();
            }
        }
        if (sb.Length >= minLen) list.Add(sb.ToString());
        return list.Where(s => s.Length <= 200).ToList();
    }

    internal static string DecodeAnsi(ReadOnlySpan<byte> raw)
    {
        try { return Encoding.GetEncoding(949).GetString(raw).TrimEnd('\0').Trim(); }
        catch { return Encoding.UTF8.GetString(raw).TrimEnd('\0').Trim(); }
    }

    internal static bool TryGeom(byte[] data, int pos, int limit, out byte type, out float x, out float y, out float w, out float h)
    {
        type = 0; x = y = w = h = 0;
        if (pos + 41 > limit) return false;
        type = data[pos];
        try
        {
            var xd = Extended80.ReadStandard(data.AsSpan(pos + 1, 10));
            var yd = Extended80.ReadStandard(data.AsSpan(pos + 11, 10));
            var wd = Extended80.ReadStandard(data.AsSpan(pos + 21, 10));
            var hd = Extended80.ReadStandard(data.AsSpan(pos + 31, 10));
            if (double.IsNaN(xd) || double.IsInfinity(wd) || wd is < 0.05 or > 400 || hd is < 0.05 or > 400
                || xd is < -8 or > 500 || yd is < -8 or > 500)
                return false;
            x = (float)xd; y = (float)yd; w = (float)wd; h = (float)hd;
            return true;
        }
        catch { return false; }
    }

    internal static DesignObject Shape(ShapeKind kind, float x, float y, float w, float h)
    {
        var o = DesignObject.CreateShape(kind, x, y);
        o.Width = w;
        o.Height = h;
        return o;
    }

    internal static DesignObject TextAt(float x, float y, float w, float h, string text, bool dataBound = false, string? column = null)
    {
        var o = DesignObject.CreateDefault(ObjectType.Text, x, y);
        o.Width = w; o.Height = h;
        o.Text = string.IsNullOrWhiteSpace(text) ? "텍스트" : text;
        o.TextAlign = "left";
        o.FontSize = Math.Clamp(h * 0.5f, 2.2f, 10f);
        if (dataBound)
        {
            o.DataBound = true;
            o.DataColumn = column ?? text.Trim('[', ']', '{', '}', '@');
            if (!o.Text.Contains('[')) o.Text = $"[{o.DataColumn}]";
        }
        return o;
    }
}

internal static class AniLabelImporter
{
    private const byte TypeData = 0x00;
    private const byte TypeRect = 0x04;
    private const byte TypeEllipse = 0x05;
    private const byte TypeBarcode = 0x07;
    private const byte TypeImage = 0x09;
    private const byte TypeText = 0x1A;
    private const byte TypeBarcode1D = 0x1B;
    private const byte TypeBarcode2D = 0x1C;
    private static readonly byte[] Footer = [0x64, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00];
    private static readonly byte[] RtfTable = Encoding.Unicode.GetBytes("RTF TABLE");

    public static LabelDocument Import(byte[] fileBytes, string name, PaperCatalog papers)
    {
        if (!ExternalImportService.LooksLikeLbl(fileBytes))
            throw new InvalidDataException("애니라벨 LBL 시그니처(Printec Label Maker)가 아닙니다.");

        var design = Inflate(fileBytes);
        var pos = 0;
        var strings = ReadHeaderStrings(design, ref pos);
        var paperNo = strings.FirstOrDefault(s => s.StartsWith('V') && s.Length is >= 4 and <= 8);

        float pw = 210, ph = 297, lw = 70, lh = 36, left = 0, top = 0, right = 0, bottom = 0, hg = 0, vg = 0;
        var cols = 0;
        var rows = 0;
        if (TryPaperLayout(design, pos, out var layoutEnd, out pw, out ph, out cols, out rows, out left, out top, out right, out bottom, out hg, out vg))
        {
            pos = layoutEnd;
            var gapsW = hg * Math.Max(0, cols - 1);
            var gapsH = vg * Math.Max(0, rows - 1);
            if (cols > 0) lw = (float)((pw - left - right - gapsW) / cols);
            if (rows > 0) lh = (float)((ph - top - bottom - gapsH) / rows);
        }

        var paper = ExternalImportService.ResolvePaper(papers, "anylabel", paperNo, lw, lh, cols, rows, pw, ph, left, top, right, bottom, hg, vg);
        var doc = LabelDocument.CreateBlank(paper);
        doc.Name = name;
        foreach (var cell in doc.Pages[0].Cells)
            cell.Objects.Clear();

        var per = Math.Max(1, paper.LabelsPerPage);
        var linked = new List<(int Global, string Field, string Value)>();
        var z = 0;
        while (TryReadSection(design, ref pos, per, out var globalIdx, out var sectionEnd))
        {
            var cursor = pos;
            if (cursor + 2 <= sectionEnd && design[cursor] == 0x2D && design[cursor + 1] == 0x01)
                cursor += 2;
            while (cursor + 41 <= sectionEnd)
            {
                if (!ExternalImportService.TryGeom(design, cursor, sectionEnd, out var type, out var x, out var y, out var w, out var h)
                    || !IsType(type))
                {
                    cursor++;
                    continue;
                }

                var payloadStart = cursor + 41;
                var payloadEnd = NextObject(design, payloadStart, sectionEnd) ?? sectionEnd;
                var obj = Map(design, cursor, payloadStart, payloadEnd, type, x, y, w, h, ref linked, globalIdx);
                if (obj is not null)
                {
                    obj.ZIndex = ++z;
                    ExternalImportService.Place(doc, obj, (int)globalIdx, per);
                }
                cursor = payloadEnd;
            }
            pos = sectionEnd;
        }

        if (doc.Pages.All(p => p.Cells.All(c => c.Objects.Count == 0)))
            ScanLoose(design, doc, per, ref linked, ref z);

        if (linked.Count > 0)
        {
            var fields = linked.Select(t => t.Field).Where(s => s.Length > 0).Distinct(StringComparer.OrdinalIgnoreCase).ToList();
            if (fields.Count > 0)
            {
                var sheet = new DataSheet { SourceName = name, SourceKind = "lbl" };
                sheet.Columns.AddRange(fields);
                foreach (var group in linked.GroupBy(t => t.Global).OrderBy(g => g.Key))
                {
                    var row = fields.Select(f => group.LastOrDefault(t => string.Equals(t.Field, f, StringComparison.OrdinalIgnoreCase)).Value ?? "").ToList();
                    if (row.Any(v => !string.IsNullOrWhiteSpace(v)))
                        sheet.Rows.Add(row);
                }
                if (sheet.RowCount > 0)
                {
                    doc.Data = sheet;
                    doc.EnsurePagesForData();
                }
            }
        }

        if (doc.Pages.All(p => p.Cells.All(c => c.Objects.Count == 0)))
        {
            var t = DesignObject.CreateDefault(ObjectType.Text, paper.LabelWidthMm * 0.1f, paper.LabelHeightMm * 0.3f);
            t.Text = name;
            doc.Pages[0].Cells[0].Objects.Add(t);
        }

        return doc;
    }

    private static void ScanLoose(
        byte[] design, LabelDocument doc, int per,
        ref List<(int Global, string Field, string Value)> linked, ref int z)
    {
        var last = Math.Min(design.Length - 41, 800_000);
        for (var i = 0; i < last && z < 80; i++)
        {
            if (!IsType(design[i])) continue;
            if (!ExternalImportService.TryGeom(design, i, design.Length, out var type, out var x, out var y, out var w, out var h))
                continue;
            var end = Math.Min(design.Length, i + 8000);
            var obj = Map(design, i, i + 41, end, type, x, y, w, h, ref linked, 1);
            if (obj is null) continue;
            obj.ZIndex = ++z;
            ExternalImportService.Place(doc, obj, 1, per);
            i += 40;
        }
    }

    private static bool IsType(byte type)
        => type is TypeData or TypeRect or TypeEllipse or TypeBarcode or TypeImage or TypeText or TypeBarcode1D or TypeBarcode2D;

    private static DesignObject? Map(
        byte[] data, int geom, int start, int end, byte type, float x, float y, float w, float h,
        ref List<(int Global, string Field, string Value)> linked, uint globalIdx)
    {
        switch (type)
        {
            case TypeRect:
                return ExternalImportService.Shape(ShapeKind.Rect, x, y, w, h);
            case TypeEllipse:
                return ExternalImportService.Shape(ShapeKind.Ellipse, x, y, w, h);
            case TypeImage:
                var img = DesignObject.CreateDefault(ObjectType.Image, x, y);
                img.Width = w; img.Height = h;
                img.ImageData = ExternalImportService.FindImageDataUrl(data, start, end);
                return img;
            case TypeBarcode:
            case TypeBarcode1D:
            case TypeBarcode2D:
                var strings = ExternalImportService.ExtractPrintable(data, start, end, 1);
                var value = ReadLengthPrefixedAscii(data, start, end) ?? strings.LastOrDefault(s => s.Any(char.IsLetterOrDigit)) ?? "12345678";
                var format = ExternalImportService.MapBarcode(strings.FirstOrDefault(s => s.Length < 28 && !s.Equals(value, StringComparison.Ordinal)));
                if (type == TypeBarcode2D) format = strings.Any(s => s.Contains("QR", StringComparison.OrdinalIgnoreCase)) ? "QR_CODE" : format;
                var is2d = type == TypeBarcode2D || ExternalImportService.Is2dBarcode(format);
                var bar = DesignObject.CreateDefault(is2d ? ObjectType.Qr : ObjectType.Barcode, x, y);
                bar.Width = w; bar.Height = h;
                bar.BarcodeValue = value;
                bar.BarcodeFormat = format;
                return bar;
            case TypeData:
                var rec = ReadLinked(data, start, end);
                var field = rec.Field;
                var text = rec.Value;
                if (string.IsNullOrWhiteSpace(text) && string.IsNullOrWhiteSpace(field)) return null;
                linked.Add(((int)globalIdx, field, text));
                return ExternalImportService.TextAt(x, y, w, h, string.IsNullOrWhiteSpace(text) ? field : text, true, field);
            case TypeText:
                var hasDigits = TryTableDigits(data, start, end, out var tc, out var tr);
                if (hasDigits || ContainsRtfTable(data, start, end))
                {
                    var table = DesignObject.CreateDefault(ObjectType.Table, x, y);
                    table.Width = w; table.Height = h;
                    table.TableCols = tc > 0 ? tc : 2;
                    table.TableRows = tr > 0 ? tr : 2;
                    table.EnsureTableSize();
                    return table;
                }
                var body = CollectText(data, start, end);
                if (string.IsNullOrWhiteSpace(body)) return null;
                return ExternalImportService.TextAt(x, y, w, h, body);
            default:
                return null;
        }
    }

    private static bool ContainsRtfTable(byte[] data, int start, int end)
    {
        var last = Math.Min(end, data.Length) - RtfTable.Length;
        for (var i = start; i <= last; i++)
            if (data.AsSpan(i, RtfTable.Length).SequenceEqual(RtfTable))
                return true;
        return false;
    }

    private static bool TryTableDigits(byte[] data, int start, int end, out int cols, out int rows)
    {
        cols = 0; rows = 0;
        var digits = new List<int>();
        var last = Math.Min(end, data.Length) - 16;
        for (var i = start; i <= last && digits.Count < 2; i++)
        {
            if (BitConverter.ToUInt64(data, i) != 0x64) continue;
            var ch = data[i + 8];
            if (ch is < (byte)'1' or > (byte)'9') continue;
            if (data[i + 9] != 0 || data[i + 10] != 0x04 || data[i + 11] != 0x0C) continue;
            digits.Add(ch - '0');
        }
        if (digits.Count < 2) return false;
        cols = digits[0];
        rows = digits[1];
        return true;
    }

    private static string CollectText(byte[] data, int start, int end)
    {
        var chars = new List<(int Off, char Ch)>();
        for (var i = start; i + 13 < end && i < start + 8000; i++)
        {
            var code = BitConverter.ToUInt16(data, i);
            if (code is not (9 or 10 or 13 or (>= 0x20 and <= 0x7E) or (>= 0xAC00 and <= 0xD7A3) or (>= 0x3130 and <= 0x318F) or (>= 0x4E00 and <= 0x9FFF)))
                continue;
            var size = BitConverter.ToUInt32(data, i + 4);
            var zero = BitConverter.ToUInt32(data, i + 8);
            if (zero > 16 || size is < 1 or > 200) continue;
            chars.Add((i, (char)code));
            i += 12;
        }
        if (chars.Count == 0) return "";
        if (chars.Count == 1) return chars[0].Ch.ToString();
        var best = new List<char>();
        for (var s = 0; s < chars.Count; s++)
        {
            var run = new List<char> { chars[s].Ch };
            var last = chars[s].Off;
            for (var i = s + 1; i < chars.Count; i++)
            {
                var delta = chars[i].Off - last;
                if (delta is >= 200 and <= 400)
                {
                    run.Add(chars[i].Ch);
                    last = chars[i].Off;
                }
            }
            if (run.Count > best.Count) best = run;
        }
        return new string(best.Count > 0 ? best.ToArray() : chars.Select(c => c.Ch).ToArray()).Trim();
    }

    private static (string Field, string Value) ReadLinked(byte[] data, int start, int end)
    {
        var marker = ExternalImportService.FindU32(data, start, Math.Min(end, start + 160), 0x00002711);
        if (marker < 0) return ("", ExternalImportService.ExtractPrintable(data, start, end, 1).FirstOrDefault() ?? "");
        string value = "", field = "";
        foreach (var extra in new[] { 0, 1 })
        {
            var lenAt = marker + 20 + extra;
            if (lenAt + 4 > end) continue;
            var len = BitConverter.ToInt32(data, lenAt);
            if (len is < 0 or > 512 || lenAt + 4 + len > end) continue;
            value = len == 0 ? "" : Encoding.Latin1.GetString(data, lenAt + 4, len);
            var from = lenAt + 4 + len;
            for (var i = from; i + 8 < end && i < from + 96; i++)
            {
                var n = BitConverter.ToInt32(data, i);
                if (n is < 4 or > 16 || i + 4 + n > end) continue;
                if (data[i + 4] != (byte)'T' || data[i + 5] != (byte)'e') continue;
                field = Encoding.ASCII.GetString(data, i + 4, n);
                return (field, value);
            }
        }
        return (field, value);
    }

    private static string? ReadLengthPrefixedAscii(byte[] data, int start, int end)
    {
        for (var i = start; i + 8 < end && i < start + 400; i++)
        {
            var n = BitConverter.ToInt32(data, i);
            if (n is < 1 or > 80 || i + 4 + n > end) continue;
            var slice = data.AsSpan(i + 4, n);
            if (slice.ToArray().All(b => b is >= 0x20 and <= 0x7E))
                return Encoding.ASCII.GetString(slice);
        }
        return null;
    }

    private static int? NextObject(byte[] data, int from, int sectionEnd)
    {
        var last = sectionEnd - 41;
        for (var i = from + 8; i <= last; i++)
        {
            if (i >= 8 && !data.AsSpan(i - 8, 8).SequenceEqual(Footer))
                continue;
            if (ExternalImportService.TryGeom(data, i, sectionEnd, out var type, out _, out _, out _, out _) && IsType(type))
                return i;
        }
        for (var i = from + 8; i <= last; i++)
        {
            if (ExternalImportService.TryGeom(data, i, sectionEnd, out var type, out _, out _, out _, out _) && IsType(type))
                return i;
        }
        return null;
    }

    private static bool TryReadSection(byte[] data, ref int pos, int per, out uint globalIdx, out int sectionEnd)
    {
        globalIdx = 0;
        sectionEnd = pos;
        var max = (uint)Math.Max(64, per * 256);
        var limit = Math.Min(data.Length - 11, pos + 1_500_000);
        for (var i = pos; i <= limit; i++)
        {
            var idx = BitConverter.ToUInt32(data, i);
            var len = BitConverter.ToInt32(data, i + 4);
            if (idx is < 1 || idx > max || len < 44 || i + 8 + len > data.Length) continue;
            if (data[i + 8] != 0x2D || data[i + 9] != 0x01) continue;
            pos = i + 8;
            globalIdx = idx;
            sectionEnd = i + 8 + len;
            return true;
        }
        return false;
    }

    private static List<string> ReadHeaderStrings(byte[] data, ref int pos)
    {
        var strings = new List<string>();
        while (pos + 4 <= data.Length && strings.Count < 12)
        {
            if (TryPaperLayout(data, pos, out _, out _, out _, out _, out _, out _, out _, out _, out _, out _, out _))
                break;
            if (data[pos] == 0) { pos++; continue; }
            var n = BitConverter.ToInt32(data, pos);
            if (n is < 1 or > 200 || pos + 4 + n > data.Length)
            {
                pos++;
                continue;
            }
            var s = ExternalImportService.DecodeAnsi(data.AsSpan(pos + 4, n));
            if (s.Length == 0) { pos++; continue; }
            strings.Add(s);
            pos += 4 + n;
        }
        return strings;
    }

    private static bool TryPaperLayout(
        byte[] data, int start,
        out int end, out float pw, out float ph, out int cols, out int rows,
        out float left, out float top, out float right, out float bottom, out float hg, out float vg)
    {
        end = start; pw = ph = left = top = right = bottom = hg = vg = 0; cols = rows = 0;
        if (start < 0 || start + 116 > data.Length) return false;
        try
        {
            pw = (float)Extended80.ReadStandard(data.AsSpan(start, 10));
            ph = (float)Extended80.ReadStandard(data.AsSpan(start + 10, 10));
            cols = (int)BitConverter.ToUInt32(data, start + 48);
            rows = (int)BitConverter.ToUInt32(data, start + 52);
            left = (float)Extended80.ReadStandard(data.AsSpan(start + 56, 10));
            top = (float)Extended80.ReadStandard(data.AsSpan(start + 66, 10));
            right = (float)Extended80.ReadStandard(data.AsSpan(start + 76, 10));
            bottom = (float)Extended80.ReadStandard(data.AsSpan(start + 86, 10));
            hg = (float)Extended80.ReadStandard(data.AsSpan(start + 96, 10));
            vg = (float)Extended80.ReadStandard(data.AsSpan(start + 106, 10));
        }
        catch { return false; }
        if (cols is < 1 or > 50 || rows is < 1 or > 50 || pw is < 20 or > 2000 || ph is < 20 or > 2000)
            return false;
        end = start + 116;
        return true;
    }

    private static byte[] Inflate(byte[] fileBytes)
    {
        var jpeg = -1;
        for (var i = 0; i + 3 < fileBytes.Length; i++)
        {
            if (fileBytes[i] == 0xFF && fileBytes[i + 1] == 0xD8 && fileBytes[i + 2] == 0xFF)
            { jpeg = i; break; }
        }
        if (jpeg < 4) throw new InvalidDataException("애니라벨 LBL JPEG 미리보기를 찾지 못했습니다.");
        var jpegLen = BitConverter.ToInt32(fileBytes, jpeg - 4);
        if (jpegLen < 20 || jpeg + jpegLen > fileBytes.Length)
            throw new InvalidDataException("애니라벨 LBL JPEG 길이가 올바르지 않습니다.");
        if (fileBytes[jpeg + jpegLen - 2] != 0xFF || fileBytes[jpeg + jpegLen - 1] != 0xD9)
            throw new InvalidDataException("애니라벨 LBL JPEG 종료 마커가 일치하지 않습니다.");
        var rest = jpeg + jpegLen;
        if (rest + 6 > fileBytes.Length)
            throw new InvalidDataException("LBL zlib 설계 블록이 없습니다.");
        var uncompressed = BitConverter.ToInt32(fileBytes, rest + 1);
        var zlib = fileBytes.AsSpan(rest + 5).ToArray();
        if (zlib.Length < 2 || zlib[0] != 0x78)
            throw new InvalidDataException("LBL zlib 시그니처가 없습니다.");
        using var input = new MemoryStream(zlib);
        using var zs = new ZLibStream(input, CompressionMode.Decompress);
        using var output = new MemoryStream(uncompressed > 0 ? uncompressed : 4096);
        zs.CopyTo(output);
        return output.ToArray();
    }
}

internal static class FormtecImporter
{
    public static LabelDocument Import(byte[] dgf, string name, PaperCatalog papers)
    {
        var header = ExtractHeader(dgf);
        var paperNo = header.FirstOrDefault(s => Regex.IsMatch(s, @"^\d{3,5}$"));
        float pw = 210, ph = 297, lw = 70, lh = 36, left = -1, top = -1, right = -1, bottom = -1, hg = -1, vg = -1;
        var cols = 0;
        var rows = 0;
        if (!TryLabelsBlock(dgf, out pw, out ph, out cols, out rows, out lw, out lh, out left, out top, out right, out bottom, out hg, out vg))
            TryPaper(dgf, out pw, out ph, out cols, out rows, out lw, out lh);
        var paper = ExternalImportService.ResolvePaper(papers, "formtec", paperNo, lw, lh, cols, rows, pw, ph, left, top, right, bottom, hg, vg);
        var doc = LabelDocument.CreateBlank(paper);
        doc.Name = name;
        foreach (var cell in doc.Pages[0].Cells)
            cell.Objects.Clear();

        var per = Math.Max(1, paper.LabelsPerPage);
        var z = 0;
        if (!ReadLegacySections(dgf, doc, per, ref z))
            ScanLoose(dgf, doc, ref z);

        if (doc.Pages[0].Cells[0].Objects.Count == 0)
        {
            var t = DesignObject.CreateDefault(ObjectType.Text, paper.LabelWidthMm * 0.1f, paper.LabelHeightMm * 0.3f);
            t.Text = name;
            doc.Pages[0].Cells[0].Objects.Add(t);
        }

        return doc;
    }

    private static bool ReadLegacySections(byte[] data, LabelDocument doc, int per, ref int z)
    {
        var found = false;
        var last = Math.Min(data.Length - 10, 2 * 1024 * 1024);
        for (var i = 0; i <= last; i++)
        {
            if (data[i + 8] != 0xB8 || data[i + 9] != 0x01) continue;
            var type = data[i + 10];
            if (!IsType(type)) continue;
            var blockLen = BitConverter.ToUInt32(data, i + 4);
            if (blockLen is < 43 or > 400_000) continue;
            if (i + 8L + blockLen > data.Length) continue;
            var start = i + 10;
            var end = (int)(i + 8L + blockLen);
            var added = ReadObjectRun(data, start, end, doc, per, 1, ref z);
            if (added > 0)
            {
                found = true;
                i = end - 1;
            }
            if (z >= 120) break;
        }
        return found;
    }

    private static void ScanLoose(byte[] data, LabelDocument doc, ref int z)
    {
        var pos = ExternalImportService.FindMagic(data, 0xB8, 0x01, 512 * 1024);
        if (pos < 0) pos = 0;
        else pos += 2;
        var end = Math.Min(data.Length, pos + 1_200_000);
        var miss = 0;
        while (pos + 41 < end && z < 120)
        {
            if (pos + 1 < end && data[pos] == 0xB8 && data[pos + 1] == 0x01)
            {
                pos += 2;
                miss = 0;
                continue;
            }
            if (!ExternalImportService.TryGeom(data, pos, end, out var type, out var x, out var y, out var w, out var h)
                || !IsType(type))
            {
                pos += ++miss > 80 ? 64 : 1;
                continue;
            }
            miss = 0;
            var length = GuessLength(data, pos, end, type);
            var payloadEnd = (int)Math.Min(end, pos + Math.Max(41, length));
            var obj = Map(data, pos, pos + 41, payloadEnd, type, x, y, w, h);
            if (obj is not null)
            {
                obj.ZIndex = ++z;
                doc.Pages[0].Cells[0].Objects.Add(obj);
            }
            pos = payloadEnd > pos + 41 ? payloadEnd : pos + 41;
        }
    }

    private static int ReadObjectRun(byte[] data, int start, int end, LabelDocument doc, int per, int labelKey, ref int z)
    {
        var added = 0;
        var pos = start;
        var miss = 0;
        end = Math.Min(end, start + 400_000);
        while (pos + 41 < end && z < 120)
        {
            if (pos + 1 < end && data[pos] == 0xB8 && data[pos + 1] == 0x01)
            {
                pos += 2;
                miss = 0;
                continue;
            }
            if (!ExternalImportService.TryGeom(data, pos, end, out var type, out var x, out var y, out var w, out var h)
                || !IsType(type))
            {
                pos += ++miss > 80 ? 64 : 1;
                continue;
            }
            miss = 0;
            var length = GuessLength(data, pos, end, type);
            var payloadEnd = (int)Math.Min(end, pos + Math.Max(41, length));
            var obj = Map(data, pos, pos + 41, payloadEnd, type, x, y, w, h);
            if (obj is not null)
            {
                obj.ZIndex = ++z;
                ExternalImportService.Place(doc, obj, Math.Clamp(labelKey, 1, per), per);
                added++;
            }
            pos = payloadEnd > pos + 41 ? payloadEnd : pos + 41;
        }
        return added;
    }

    private static bool TryLabelsBlock(
        byte[] data,
        out float pw, out float ph, out int cols, out int rows, out float lw, out float lh,
        out float left, out float top, out float right, out float bottom, out float hg, out float vg)
    {
        pw = ph = lw = lh = 0; cols = rows = 0;
        left = top = right = bottom = hg = vg = -1;
        var needle = "Labels"u8;
        var labels = -1;
        var searchEnd = Math.Min(data.Length, 256 * 1024);
        for (var i = 0; i + needle.Length <= searchEnd; i++)
        {
            if (data.AsSpan(i, needle.Length).SequenceEqual(needle))
            { labels = i; break; }
        }
        if (labels < 0 || labels + 0x76 + 10 > data.Length) return false;
        try
        {
            pw = (float)Extended80.ReadStandard(data.AsSpan(labels + 0x0C, 10));
            ph = (float)Extended80.ReadStandard(data.AsSpan(labels + 0x16, 10));
            cols = (int)BitConverter.ToUInt32(data, labels + 0x3C);
            rows = (int)BitConverter.ToUInt32(data, labels + 0x40);
            left = (float)Extended80.ReadStandard(data.AsSpan(labels + 0x44, 10));
            top = (float)Extended80.ReadStandard(data.AsSpan(labels + 0x4E, 10));
            right = (float)Extended80.ReadStandard(data.AsSpan(labels + 0x58, 10));
            bottom = (float)Extended80.ReadStandard(data.AsSpan(labels + 0x62, 10));
            hg = (float)Extended80.ReadStandard(data.AsSpan(labels + 0x6C, 10));
            vg = (float)Extended80.ReadStandard(data.AsSpan(labels + 0x76, 10));
            if (cols is < 1 or > 50 || rows is < 1 or > 50 || pw is < 20 or > 2000 || ph is < 20 or > 2000)
                return false;
            lw = (pw - left - right - hg * Math.Max(0, cols - 1)) / cols;
            lh = (ph - top - bottom - vg * Math.Max(0, rows - 1)) / rows;
            return lw is >= 1 and <= 400 && lh is >= 1 and <= 400;
        }
        catch { return false; }
    }

    private static bool IsType(byte type) => type is
        0x00 or 0x04 or 0x05 or 0x06 or 0x07 or 0x08 or 0x09 or 0x0A or 0x0B
        or 0x0E or 0x0F or 0x10 or 0x16 or 0x18;

    private static long GuessLength(byte[] data, int start, long pageEnd, byte type)
    {
        var available = pageEnd - start;
        if (FormtecRecords.TryObjectLength(data, start, (int)pageEnd, type, out var exact) && exact > 41)
            return exact;
        switch (type)
        {
            case 0x04: return 95 <= available ? 95 : 41;
            case 0x05: return 75 <= available ? 75 : 41;
            case 0x0E: return Math.Min(120, available);
            case 0x0F:
                if (available < 0x53) return 41;
                var rows = BitConverter.ToUInt32(data, start + 0x4B);
                var columns = BitConverter.ToUInt32(data, start + 0x4F);
                if (rows > 200 || columns > 200) return 83;
                return Math.Min(available, 83 + 16L * (rows + columns));
            default:
                var next = start + 41;
                var scanCap = type is 0x09 or 0x18 or 0x16 ? start + 80 : start + 4_000;
                while (next + 41 < pageEnd)
                {
                    if (data[next] == 0xB8 && next + 1 < pageEnd && data[next + 1] == 0x01)
                        return next - start;
                    if (ExternalImportService.TryGeom(data, next, (int)pageEnd, out var t, out _, out _, out _, out _) && IsType(t))
                        return next - start;
                    next++;
                    if (next > scanCap) break;
                }
                return Math.Min(available, type is 0x09 or 0x18 ? available : 4000);
        }
    }

    private static DesignObject? Map(byte[] data, int geom, int start, int end, byte type, float x, float y, float w, float h)
    {
        var strings = ExternalImportService.ExtractPrintable(data, start, Math.Min(end, start + 4000), 1);
        switch (type)
        {
            case 0x04: return ExternalImportService.Shape(ShapeKind.Rect, x, y, w, h);
            case 0x05: return ExternalImportService.Shape(ShapeKind.Ellipse, x, y, w, h);
            case 0x0E: return ExternalImportService.Shape(ShapeKind.RoundRect, x, y, w, h);
            case 0x09:
            case 0x18:
                var img = DesignObject.CreateDefault(type == 0x18 ? ObjectType.Clipart : ObjectType.Image, x, y);
                img.Width = w; img.Height = h;
                FormtecRecords.Apply(img, data, geom, end, type);
                if (string.IsNullOrEmpty(img.ImageData))
                    img.ImageData = ExternalImportService.FindImageDataUrl(data, geom, end);
                return img;
            case 0x07:
            case 0x08:
            case 0x10:
                var format = ExternalImportService.MapBarcode(strings.FirstOrDefault());
                if (type == 0x10 && !ExternalImportService.Is2dBarcode(format)) format = "QR_CODE";
                var bar = DesignObject.CreateDefault(ExternalImportService.Is2dBarcode(format) ? ObjectType.Qr : ObjectType.Barcode, x, y);
                bar.Width = w; bar.Height = h;
                bar.BarcodeValue = ReadAsciiPrefixed(data, start, end) ?? strings.LastOrDefault(s => s.Length is >= 1 and <= 80) ?? "12345678";
                bar.BarcodeFormat = format;
                if (type == 0x10)
                    FormtecRecords.Apply(bar, data, geom, end, type);
                return bar;
            case 0x0F:
                var table = DesignObject.CreateDefault(ObjectType.Table, x, y);
                table.Width = w; table.Height = h;
                if (geom + 0x53 <= data.Length)
                {
                    var tr = (int)BitConverter.ToUInt32(data, geom + 0x4B);
                    var tc = (int)BitConverter.ToUInt32(data, geom + 0x4F);
                    if (tr is >= 1 and <= 40) table.TableRows = tr;
                    if (tc is >= 1 and <= 40) table.TableCols = tc;
                }
                if (strings.Count > 0) table.TableCells = strings.Take(table.TableRows * table.TableCols).ToList();
                table.EnsureTableSize();
                return table;
            case 0x00:
            case 0x06:
            case 0x0A:
            case 0x0B:
            case 0x16:
                var text = type switch
                {
                    0x0A or 0x0B or 0x16 or 0x00 => null,
                    _ => ReadDgfText(data, start, end)
                } ?? strings.FirstOrDefault();
                var o = ExternalImportService.TextAt(x, y, w, h, text ?? "", type == 0x06, type == 0x06 ? (text ?? "").Trim('[', ']') : null);
                FormtecRecords.Apply(o, data, geom, end, type);
                if (string.IsNullOrWhiteSpace(o.Text))
                {
                    if (string.IsNullOrWhiteSpace(text)) return null;
                    o.Text = text;
                }
                if (type == 0x0A) o.TextMode = TextMode.WordArt;
                if (type == 0x0B) o.TextMode = TextMode.Custom;
                if (type == 0x16) o.TextMode = TextMode.Extended;
                return o;
            default:
                return null;
        }
    }

    private static string? ReadAsciiPrefixed(byte[] data, int start, int end)
    {
        for (var i = start; i + 8 < end && i < start + 300; i++)
        {
            var n = (int)BitConverter.ToUInt32(data, i);
            if (n is < 1 or > 80 || i + 4 + n > end) continue;
            var slice = data.AsSpan(i + 4, n);
            if (slice.ToArray().All(b => b is >= 0x20 and <= 0x7E))
                return Encoding.ASCII.GetString(slice);
        }
        return null;
    }

    private static string? ReadDgfText(byte[] data, int start, int end)
    {
        for (var i = start; i + 8 < end && i < start + 800; i++)
        {
            if (BitConverter.ToUInt32(data, i) != 0x00002711) continue;
            var n = BitConverter.ToUInt32(data, i + 4);
            if (n is 0 or > 2000) continue;
            var p = i + 8;
            if (p + n * 2 > data.Length) continue;
            return Encoding.Unicode.GetString(data, p, (int)n * 2).Trim('\0', ' ');
        }
        return null;
    }

    private static List<string> ExtractHeader(byte[] dgf)
    {
        var results = new List<string>();
        var limit = Math.Min(dgf.Length, 1600);
        for (var i = 0; i + 4 < limit && results.Count < 12; i++)
        {
            var len = (int)BitConverter.ToUInt32(dgf, i);
            if (len is < 1 or > 128 || i + 4 + len > limit) continue;
            var s = ExternalImportService.DecodeAnsi(dgf.AsSpan(i + 4, len));
            if (s.Length < 1 || results.Contains(s)) continue;
            results.Add(s);
            i += 3 + len;
        }
        return results;
    }

    private static void TryPaper(byte[] data, out float pw, out float ph, out int cols, out int rows, out float lw, out float lh)
    {
        pw = 210; ph = 297; cols = 0; rows = 0; lw = 70; lh = 36;
        for (var i = 0; i + 116 < Math.Min(data.Length, 0x400); i++)
        {
            try
            {
                var w = Extended80.ReadStandard(data.AsSpan(i, 10));
                var h = Extended80.ReadStandard(data.AsSpan(i + 10, 10));
                var c = (int)BitConverter.ToUInt32(data, i + 48);
                var r = (int)BitConverter.ToUInt32(data, i + 52);
                if (c is < 1 or > 50 || r is < 1 or > 50 || w is < 20 or > 2000 || h is < 20 or > 2000) continue;
                var left = Extended80.ReadStandard(data.AsSpan(i + 56, 10));
                var top = Extended80.ReadStandard(data.AsSpan(i + 66, 10));
                var right = Extended80.ReadStandard(data.AsSpan(i + 76, 10));
                var bottom = Extended80.ReadStandard(data.AsSpan(i + 86, 10));
                var hg = Extended80.ReadStandard(data.AsSpan(i + 96, 10));
                var vg = Extended80.ReadStandard(data.AsSpan(i + 106, 10));
                pw = (float)w; ph = (float)h; cols = c; rows = r;
                lw = (float)((w - left - right - hg * Math.Max(0, c - 1)) / c);
                lh = (float)((h - top - bottom - vg * Math.Max(0, r - 1)) / r);
                if (lw is >= 1 and <= 400 && lh is >= 1 and <= 400) return;
            }
            catch { /* next */ }
        }
    }
}

internal static class ILabelImporter
{
    private const int CommonLabelId = int.MaxValue;

    public static LabelDocument Import(byte[] bytes, string name, PaperCatalog papers)
    {
        if (ExternalImportService.LooksLikeXml(bytes))
            return FromXml(bytes, name, papers);
        if (!Jet4Database.LooksLikeJet(bytes))
            throw new InvalidDataException("아이라벨 IDF(Jet DB) 시그니처가 아닙니다.");

        var db = new Jet4Database(bytes);
        var paperRow = db.TryReadTable("Paper")?.Rows.FirstOrDefault()
                       ?? throw new InvalidDataException("IDF Paper 테이블을 읽지 못했습니다.");

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
        if (!string.IsNullOrWhiteSpace(paperRow.GetString("LabelFrame")))
        {
            paper.Shape.Kind = "svg";
            paper.Shape.Svg = paperRow.GetString("LabelFrame");
        }
        paper.DesignImageUrl = NullIfEmpty(paperRow.GetString("LabelBackground"));

        var doc = LabelDocument.CreateBlank(paper);
        doc.Name = name;
        foreach (var cell in doc.Pages[0].Cells)
            cell.Objects.Clear();

        LoadData(db, doc, name, paperRow.GetString("DataSrc"));

        var factors = db.TryReadTable("Factors");
        if (factors is null || factors.Rows.Count == 0)
            return doc;

        var per = Math.Max(1, paper.LabelsPerPage);
        var z = 0;
        var commons = new List<DesignObject>();
        foreach (var row in factors.Rows)
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
            ExternalImportService.Place(doc, obj, labelId + 1, per);
        }

        if (commons.Count > 0)
        {
            doc.EnsureStructure();
            foreach (var common in commons)
                ExternalImportService.PlaceCommon(doc, common);
        }

        return doc;
    }

    private static void LoadData(Jet4Database db, LabelDocument doc, string name, string dataSrc)
    {
        var table = FindInner(db);
        if (table is null || table.Rows.Count == 0) return;
        var fields = table.Columns.Select(c => c.Name)
            .Where(n => !n.StartsWith("idb_", StringComparison.OrdinalIgnoreCase) && !n.Equals("ID", StringComparison.OrdinalIgnoreCase))
            .ToList();
        if (fields.Count == 0) return;
        var sheet = new DataSheet
        {
            SourceName = name,
            SourceKind = string.IsNullOrWhiteSpace(dataSrc) ? table.Name : dataSrc
        };
        sheet.Columns.AddRange(fields);
        foreach (var row in table.Rows)
        {
            var values = fields.Select(f => row.GetString(f)).ToList();
            if (values.Any(v => !string.IsNullOrWhiteSpace(v)))
                sheet.Rows.Add(values);
        }
        if (sheet.RowCount == 0) return;
        if (sheet.Rows.Count > 256)
            sheet.Rows.RemoveRange(256, sheet.Rows.Count - 256);
        doc.Data = sheet;
        doc.EnsurePagesForData();
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
            3 => MakeBarcode(x, y, w, h, cont),
            5 => DesignObject.CreateShape(ShapeKind.Rect, x, y),
            6 => DesignObject.CreateShape(ShapeKind.RoundRect, x, y),
            7 => DesignObject.CreateShape(ShapeKind.Triangle, x, y),
            8 => DesignObject.CreateShape(ShapeKind.Line, x, y),
            9 or 13 => DesignObject.CreateShape(ShapeKind.Ellipse, x, y),
            14 => MakeTable(x, y, w, h, cont),
            16 => MakeIcon(x, y, w, h, cont),
            _ => MakeText(x, y, w, h, StripCont(cont))
        };
        obj.Width = Math.Max(0.4f, w);
        obj.Height = Math.Max(0.4f, h);
        obj.ZIndex = z;
        obj.Rotation = (float)row.GetDouble("Rotate");
        var thick = row.GetDouble("LineThickness");
        if (thick > 0) obj.StrokeWidth = (float)thick;
        if (DesignObject.IsShape(obj.Type) || obj.Type == ObjectType.Table)
        {
            var transparent = !fillOn || back == -1 || back == 16777215;
            obj.Fill = transparent ? "transparent" : ArgbToCss(back);
            obj.BackgroundFill = obj.Fill;
            obj.BackgroundTransparent = transparent;
            obj.Stroke = ArgbToCss(fore == 0 ? -16777216 : fore);
        }
        else if (obj.Type == ObjectType.Text)
        {
            obj.Fill = ArgbToCss(fore == 0 ? -16777216 : fore);
        }
        return obj;
    }

    private static DesignObject MakeText(float x, float y, float w, float h, string cont)
    {
        var o = DesignObject.CreateDefault(ObjectType.Text, x, y);
        o.Width = w; o.Height = h;
        var field = Regex.Match(cont ?? "", @"\{@([^}]+)\}");
        var run = Regex.Match(cont ?? "", @"\{&([^,]+),(\d+),([bBiIuUsS]{4}),(-?\d+)\}");
        if (field.Success)
        {
            o.DataBound = true;
            o.DataColumn = field.Groups[1].Value.Trim();
            o.Text = $"[{o.DataColumn}]";
        }
        else
        {
            o.Text = StripCont(cont);
        }
        if (run.Success)
        {
            if (float.TryParse(run.Groups[2].Value, NumberStyles.Integer, CultureInfo.InvariantCulture, out var fs))
                o.FontSize = Math.Clamp(fs * 0.35f, 2f, 14f);
            var bius = run.Groups[3].Value;
            o.Bold = bius.Length >= 1 && char.IsUpper(bius[0]);
            o.Italic = bius.Length >= 2 && char.IsUpper(bius[1]);
            o.Underline = bius.Length >= 3 && char.IsUpper(bius[2]);
            o.Strikeout = bius.Length >= 4 && char.IsUpper(bius[3]);
            o.FontFamily = run.Groups[1].Value.Trim();
            if (int.TryParse(run.Groups[4].Value, out var color))
                o.Fill = ArgbToCss(color);
        }
        o.TextAlign = "left";
        return o;
    }

    private static DesignObject MakeImage(float x, float y, float w, float h, byte[]? bytes)
    {
        var o = DesignObject.CreateDefault(ObjectType.Image, x, y);
        o.Width = w; o.Height = h;
        if (bytes is { Length: > 8 })
        {
            var mime = bytes[0] == 0x89 ? "image/png" : bytes[0] == 0xFF ? "image/jpeg" : "image/bmp";
            o.ImageData = ExternalImportService.ToDataUrl(bytes, mime);
        }
        return o;
    }

    private static DesignObject MakeBarcode(float x, float y, float w, float h, string cont)
    {
        var type = cont ?? "";
        var options = "";
        var data = "";
        var first = type.IndexOf('|');
        if (first >= 0)
        {
            var rest = type[(first + 1)..];
            type = type[..first];
            var second = rest.IndexOf('|');
            if (second >= 0)
            {
                options = rest[..second];
                data = rest[(second + 1)..];
            }
            else options = rest;
        }
        var format = ExternalImportService.MapBarcode(type);
        var o = DesignObject.CreateDefault(ExternalImportService.Is2dBarcode(format) ? ObjectType.Qr : ObjectType.Barcode, x, y);
        o.Width = w; o.Height = h;
        o.BarcodeFormat = format;
        o.BarcodeValue = data;
        foreach (var part in options.Split(';', StringSplitOptions.RemoveEmptyEntries))
        {
            var colon = part.IndexOf(':');
            if (colon < 0) continue;
            var key = part[..colon].Trim();
            var val = part[(colon + 1)..];
            if (key.Equals("DrawCaption", StringComparison.OrdinalIgnoreCase))
                o.BarcodeShowText = val.Equals("True", StringComparison.OrdinalIgnoreCase) || val == "1";
        }
        return o;
    }

    private static DesignObject MakeTable(float x, float y, float w, float h, string cont)
    {
        var o = DesignObject.CreateDefault(ObjectType.Table, x, y);
        o.Width = w; o.Height = h;
        var parts = (cont ?? "").Split(':');
        if (parts.Length >= 3)
        {
            o.TableCols = Math.Clamp(parts[1].Split(',', StringSplitOptions.RemoveEmptyEntries).Length, 1, 40);
            o.TableRows = Math.Clamp(parts[2].Split(',', StringSplitOptions.RemoveEmptyEntries).Length, 1, 40);
        }
        o.EnsureTableSize();
        return o;
    }

    private static DesignObject MakeIcon(float x, float y, float w, float h, string cont)
    {
        var o = DesignObject.CreateDefault(ObjectType.Icon, x, y);
        o.Width = w; o.Height = h;
        o.IconName = (cont ?? "").Trim();
        o.Text = o.IconName;
        return o;
    }

    private static string StripCont(string? cont)
    {
        if (string.IsNullOrEmpty(cont)) return "";
        var s = Regex.Replace(cont, @"\{[#&@][^}]*\}", "");
        return WebUtility.HtmlDecode(s).Trim();
    }

    private static string? NullIfEmpty(string s) => string.IsNullOrWhiteSpace(s) ? null : s;

    private static string ArgbToCss(int argb)
    {
        if (argb == -1) return "transparent";
        var u = unchecked((uint)argb);
        return $"#{u & 0xFF:X2}{(u >> 8) & 0xFF:X2}{(u >> 16) & 0xFF:X2}";
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

        return doc;
    }

    private static float Attr(XElement? el, string name, float fallback)
    {
        var v = el?.Attribute(name)?.Value ?? el?.Element(name)?.Value;
        return float.TryParse(v, NumberStyles.Float, CultureInfo.InvariantCulture, out var n) ? n : fallback;
    }

    private static string? AttrStr(XElement? el, string name)
        => el?.Attribute(name)?.Value ?? el?.Element(name)?.Value;
}
