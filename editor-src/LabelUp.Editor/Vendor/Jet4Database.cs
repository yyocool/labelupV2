using System.Globalization;
using System.IO;
using System.Text;

namespace LabelUp.Editor.Vendor;

/// <summary>
/// Microsoft Jet 4 (Access 2000) MDB 읽기. ACE/Jet OLEDB 없이 페이지를 직접 해석한다.
/// 기준: mdbtools HACKING.md / Jet4 상수.
/// </summary>
internal sealed class Jet4Database
{
    public const int PageSize = 4096;
    private const int RowCountOffset = 0x0C;
    private const int OffsetMask = 0x1FFF;
    private const byte PageData = 0x01;
    private const byte PageTdef = 0x02;
    private const byte PageUsage = 0x05;

    public const byte TypeBool = 0x01;
    public const byte TypeByte = 0x02;
    public const byte TypeInt = 0x03;
    public const byte TypeLong = 0x04;
    public const byte TypeMoney = 0x05;
    public const byte TypeFloat = 0x06;
    public const byte TypeDouble = 0x07;
    public const byte TypeDateTime = 0x08;
    public const byte TypeBinary = 0x09;
    public const byte TypeText = 0x0A;
    public const byte TypeOle = 0x0B;
    public const byte TypeMemo = 0x0C;
    public const byte TypeRepId = 0x0F;
    public const byte TypeNumeric = 0x10;

    private readonly byte[] _file;
    private readonly uint _dbKey;

    public IReadOnlyList<Jet4CatalogEntry> Catalog { get; }

    public static bool LooksLikeJet(byte[] data)
        => data.Length >= 20
           && data[0] == 0x00
           && Encoding.ASCII.GetString(data, 4, 15) == "Standard Jet DB";

    public Jet4Database(byte[] fileBytes)
    {
        if (!LooksLikeJet(fileBytes))
        {
            throw new InvalidDataException("Microsoft Jet DB 시그니처가 아닙니다.");
        }

        if (fileBytes.Length < PageSize)
        {
            throw new InvalidDataException("Jet DB 페이지가 너무 짧습니다.");
        }

        _file = fileBytes;
        var header = _file.AsSpan(0, PageSize).ToArray();
        Rc4.Apply([0xC7, 0xDA, 0x39, 0x6B], header.AsSpan(0x18, 128));
        var jetVer = header[0x14];
        if (jetVer is not (0x01 or 0x02 or 0x03 or 0x04 or 0x05 or 0x06))
        {
            throw new InvalidDataException($"지원하지 않는 Jet 버전: 0x{jetVer:X2}");
        }

        _dbKey = BitConverter.ToUInt32(header, 0x3E);
        Catalog = ReadCatalog();
    }

    public Jet4Table ReadTable(string name)
    {
        var entry = Catalog.FirstOrDefault(c =>
            string.Equals(c.Name, name, StringComparison.OrdinalIgnoreCase));
        if (entry is null)
        {
            throw new InvalidDataException($"테이블을 찾지 못했습니다: {name}");
        }

        return ReadTable(entry);
    }

    public Jet4Table? TryReadTable(string name)
    {
        var entry = Catalog.FirstOrDefault(c =>
            string.Equals(c.Name, name, StringComparison.OrdinalIgnoreCase));
        return entry is null ? null : ReadTable(entry);
    }

    public Jet4Table ReadTable(Jet4CatalogEntry entry)
    {
        var tdef = ReadPage(entry.TablePage);
        if (tdef[0] != PageTdef)
        {
            throw new InvalidDataException($"페이지 {entry.TablePage}가 TDEF가 아닙니다.");
        }

        var pages = new List<byte[]> { tdef };
        var next = BitConverter.ToInt32(tdef, 4);
        var guard = 0;
        while (next > 0 && guard++ < 64)
        {
            var pg = ReadPage((uint)next);
            pages.Add(pg);
            next = BitConverter.ToInt32(pg, 4);
        }

        var cursor = new TdefCursor(pages);
        var numRows = cursor.ReadInt32(16);
        var numVarCols = cursor.ReadInt16(43);
        var numCols = cursor.ReadInt16(45);
        var numIdx = cursor.ReadInt32(47);
        var numRealIdx = cursor.ReadInt32(51);
        var usagePtr = cursor.ReadInt32(55);
        _ = numIdx;

        if (numCols is < 0 or > 1024)
        {
            throw new InvalidDataException($"비정상 컬럼 수: {numCols}");
        }

        var pos = 63 + numRealIdx * 12;
        var columns = new List<Jet4Column>(numCols);
        for (var i = 0; i < numCols; i++)
        {
            var col = cursor.ReadBytes(ref pos, 25);
            columns.Add(new Jet4Column
            {
                Type = col[0],
                ColNum = col[5],
                VarColNum = BitConverter.ToUInt16(col, 7),
                RowColNum = BitConverter.ToUInt16(col, 9),
                IsFixed = (col[15] & 0x01) != 0,
                FixedOffset = BitConverter.ToUInt16(col, 21),
                Size = col[0] == TypeBool ? (ushort)0 : BitConverter.ToUInt16(col, 23)
            });
        }

        foreach (var col in columns)
        {
            var nameLen = cursor.ReadInt16(ref pos);
            if (nameLen is < 0 or > 512)
            {
                col.Name = $"Col{col.ColNum}";
                continue;
            }

            var raw = cursor.ReadBytes(ref pos, nameLen);
            col.Name = DecodeUcs2(raw);
        }

        columns.Sort((a, b) => a.ColNum.CompareTo(b.ColNum));
        var usage = ReadUsagePages(usagePtr, entry.TablePage);
        var rows = ReadRows(entry.TablePage, columns, usage, numVarCols);
        return new Jet4Table
        {
            Name = entry.Name,
            TablePage = entry.TablePage,
            DeclaredRowCount = numRows,
            Columns = columns,
            Rows = rows
        };
    }

    public byte[] ReadPage(uint page)
    {
        var offset = (long)page * PageSize;
        if (offset < 0 || offset + PageSize > _file.Length)
        {
            throw new InvalidDataException($"페이지 {page}가 파일 범위를 벗어났습니다.");
        }

        var buf = new byte[PageSize];
        Buffer.BlockCopy(_file, (int)offset, buf, 0, PageSize);
        if (page != 0 && _dbKey != 0)
        {
            var key = _dbKey ^ page;
            Rc4.Apply(
            [
                (byte)(key & 0xFF),
                (byte)((key >> 8) & 0xFF),
                (byte)((key >> 16) & 0xFF),
                (byte)((key >> 24) & 0xFF)
            ], buf);
        }

        return buf;
    }

    private List<Jet4CatalogEntry> ReadCatalog()
    {
        var msys = new Jet4CatalogEntry
        {
            Name = "MSysObjects",
            ObjectType = 1,
            TablePage = 2
        };
        var table = ReadTable(msys);
        var list = new List<Jet4CatalogEntry>();
        foreach (var row in table.Rows)
        {
            var type = row.GetInt("Type") & 0x7F;
            var flags = row.GetInt("Flags");
            var id = row.GetInt("Id");
            var name = row.GetString("Name");
            if (string.IsNullOrEmpty(name))
            {
                continue;
            }

            list.Add(new Jet4CatalogEntry
            {
                Name = name,
                ObjectType = type,
                Flags = flags,
                TablePage = (uint)(id & 0x00FFFFFF)
            });
        }

        return list;
    }

    private List<uint> ReadUsagePages(int pgRow, uint tablePage)
    {
        var pages = new List<uint>();
        if (!TryFindPgRow(pgRow, out var map, out var start, out var len) || len < 1)
        {
            return ScanDataPages(tablePage);
        }

        var type = map[start];
        if (type == 0 && start + 5 <= map.Length)
        {
            var pageStart = (uint)BitConverter.ToInt32(map, start + 1);
            var bitOff = start + 5;
            var page = pageStart;
            for (var i = bitOff; i < start + len && i < map.Length; i++)
            {
                var bits = map[i];
                for (var b = 0; b < 8; b++)
                {
                    if ((bits & (1 << b)) != 0)
                    {
                        pages.Add(page);
                    }

                    page++;
                }
            }
        }
        else if (type == 1)
        {
            var pos = start + 1;
            var end = Math.Min(map.Length, start + len);
            while (pos + 4 <= end)
            {
                var mapPage = BitConverter.ToInt32(map, pos);
                pos += 4;
                if (mapPage <= 0)
                {
                    continue;
                }

                try
                {
                    var pg = ReadPage((uint)mapPage);
                    if (pg[0] != PageUsage)
                    {
                        continue;
                    }

                    uint page = 0;
                    for (var i = 4; i < pg.Length; i++)
                    {
                        var bits = pg[i];
                        for (var b = 0; b < 8; b++)
                        {
                            if ((bits & (1 << b)) != 0)
                            {
                                pages.Add(page);
                            }

                            page++;
                        }
                    }
                }
                catch (InvalidDataException)
                {
                    // 잘못된 usage page는 건너뜀
                }
            }
        }

        if (pages.Count == 0)
        {
            return ScanDataPages(tablePage);
        }

        return pages;
    }

    private List<uint> ScanDataPages(uint tablePage)
    {
        var pages = new List<uint>();
        var max = _file.Length / PageSize;
        for (uint p = 1; p < max; p++)
        {
            try
            {
                var buf = ReadPage(p);
                if (buf[0] == PageData && BitConverter.ToUInt32(buf, 4) == tablePage)
                {
                    pages.Add(p);
                }
            }
            catch (InvalidDataException)
            {
                // 페이지 읽기 실패는 무시
            }
        }

        return pages;
    }

    private List<Jet4Row> ReadRows(uint tablePage, List<Jet4Column> columns, List<uint> usage, int numVarCols)
    {
        var rows = new List<Jet4Row>();
        foreach (var pgNum in usage)
        {
            byte[] page;
            try
            {
                page = ReadPage(pgNum);
            }
            catch (InvalidDataException)
            {
                continue;
            }

            if (page[0] != PageData || BitConverter.ToUInt32(page, 4) != tablePage)
            {
                continue;
            }

            var rowCount = BitConverter.ToUInt16(page, RowCountOffset);
            for (var r = 0; r < rowCount && r < 1000; r++)
            {
                if (!TryGetRow(page, r, out var start, out var size))
                {
                    continue;
                }

                if ((start & 0x4000) != 0)
                {
                    continue;
                }

                start &= OffsetMask;
                if (start + size > page.Length || size <= 0)
                {
                    continue;
                }

                var row = CrackRow(page.AsSpan(start, size), columns, numVarCols);
                if (row is not null)
                {
                    rows.Add(row);
                }
            }
        }

        return rows;
    }

    private Jet4Row? CrackRow(ReadOnlySpan<byte> row, List<Jet4Column> columns, int tableVarCols)
    {
        if (row.Length < 4)
        {
            return null;
        }

        var numCols = BitConverter.ToUInt16(row);
        if (numCols == 0 || numCols > 1024)
        {
            return null;
        }

        var nullMaskLen = (numCols + 7) / 8;
        if (row.Length < 2 + nullMaskLen)
        {
            return null;
        }

        var nullMask = row[^nullMaskLen..].ToArray();
        var varEnds = new List<(int Start, int End)>();
        if (tableVarCols > 0 && row.Length >= 2 + nullMaskLen + 2)
        {
            var varLen = BitConverter.ToUInt16(row.Slice(row.Length - nullMaskLen - 2, 2));
            if (varLen > 0 && varLen < 1024)
            {
                var tableBytes = varLen * 2 + 4;
                if (row.Length >= nullMaskLen + tableBytes)
                {
                    var tableStart = row.Length - nullMaskLen - tableBytes;
                    var eod = BitConverter.ToUInt16(row.Slice(tableStart, 2));
                    var prev = eod;
                    for (var i = varLen - 1; i >= 0; i--)
                    {
                        var off = BitConverter.ToUInt16(row.Slice(tableStart + 2 + (varLen - 1 - i) * 2, 2));
                        var end = prev;
                        if (off <= end && end <= row.Length)
                        {
                            varEnds.Insert(0, (off, end));
                        }
                        else
                        {
                            varEnds.Insert(0, (0, 0));
                        }

                        prev = off;
                    }
                }
            }
        }

        var values = new Dictionary<string, object?>(StringComparer.OrdinalIgnoreCase);
        foreach (var col in columns)
        {
            var isNull = IsNull(nullMask, col.ColNum + 1);
            if (col.Type == TypeBool)
            {
                values[col.Name] = !isNull;
                continue;
            }

            if (isNull)
            {
                values[col.Name] = null;
                continue;
            }

            try
            {
                values[col.Name] = ReadValue(row, col, varEnds);
            }
            catch (Exception)
            {
                values[col.Name] = null;
            }
        }

        return new Jet4Row(values);
    }

    private object? ReadValue(ReadOnlySpan<byte> row, Jet4Column col, List<(int Start, int End)> varEnds)
    {
        if (col.IsFixed && col.Type is not (TypeText or TypeBinary or TypeMemo or TypeOle))
        {
            var off = 2 + col.FixedOffset;
            return ReadFixed(row, off, col);
        }

        if (col.VarColNum < varEnds.Count)
        {
            var (start, end) = varEnds[col.VarColNum];
            if (end < start || end > row.Length)
            {
                return null;
            }

            var slice = row.Slice(start, end - start);
            return ReadVariable(slice, col);
        }

        return null;
    }

    private object? ReadFixed(ReadOnlySpan<byte> row, int off, Jet4Column col)
    {
        if (off < 0)
        {
            return null;
        }

        return col.Type switch
        {
            TypeByte when off < row.Length => row[off],
            TypeInt when off + 2 <= row.Length => BitConverter.ToInt16(row.Slice(off, 2)),
            TypeLong when off + 4 <= row.Length => BitConverter.ToInt32(row.Slice(off, 4)),
            TypeFloat when off + 4 <= row.Length => BitConverter.ToSingle(row.Slice(off, 4)),
            TypeDouble or TypeDateTime or TypeMoney when off + 8 <= row.Length
                => BitConverter.ToDouble(row.Slice(off, 8)),
            TypeRepId when off + 16 <= row.Length => row.Slice(off, 16).ToArray(),
            _ => null
        };
    }

    private object? ReadVariable(ReadOnlySpan<byte> data, Jet4Column col)
    {
        return col.Type switch
        {
            TypeText or TypeBinary => DecodeTextOrBytes(data, col.Type == TypeText),
            TypeMemo => ReadMemo(data, text: true),
            TypeOle => ReadMemo(data, text: false),
            _ => data.ToArray()
        };
    }

    private object? DecodeTextOrBytes(ReadOnlySpan<byte> data, bool text)
    {
        if (!text)
        {
            return data.ToArray();
        }

        return DecodeJetText(data);
    }

    private object? ReadMemo(ReadOnlySpan<byte> data, bool text)
    {
        if (data.Length < 4)
        {
            return text ? DecodeJetText(data) : data.ToArray();
        }

        var raw = BitConverter.ToUInt32(data);
        var memoLen = (int)(raw & 0x00FFFFFF);
        var flags = (byte)(raw >> 24);
        if (memoLen < 0 || memoLen > 16_000_000)
        {
            return text ? DecodeJetText(data) : data.ToArray();
        }

        if ((flags & 0x80) != 0)
        {
            var inline = data.Length > 12 ? data[12..] : data;
            var take = Math.Min(memoLen, inline.Length);
            return text ? DecodeJetText(inline[..take]) : inline[..take].ToArray();
        }

        if (data.Length < 8)
        {
            return text ? DecodeJetText(data) : data.ToArray();
        }

        var lvalDp = BitConverter.ToInt32(data.Slice(4, 4));
        var blob = ReadLval(lvalDp, flags, memoLen);
        if (blob is null)
        {
            return null;
        }

        return text ? DecodeJetText(blob) : blob;
    }

    private byte[]? ReadLval(int pgRow, byte flags, int expectedLen)
    {
        var acc = new MemoryStream(Math.Max(16, expectedLen));
        var current = pgRow;
        var hops = 0;
        while (current != 0 && hops++ < 256)
        {
            if (!TryFindPgRow(current, out var page, out var start, out var len) || len <= 0)
            {
                break;
            }

            if ((flags & 0x40) != 0)
            {
                var take = Math.Min(len, page.Length - start);
                acc.Write(page, start, take);
                break;
            }

            if (len < 4 || start + 4 > page.Length)
            {
                break;
            }

            current = BitConverter.ToInt32(page, start);
            var body = Math.Min(len - 4, page.Length - start - 4);
            if (body > 0)
            {
                acc.Write(page, start + 4, body);
            }
        }

        return acc.Length == 0 ? null : acc.ToArray();
    }

    private bool TryFindPgRow(int pgRow, out byte[] page, out int start, out int len)
    {
        page = [];
        start = 0;
        len = 0;
        if (pgRow == 0)
        {
            return false;
        }

        var pg = (uint)(pgRow >> 8);
        var row = pgRow & 0xFF;
        try
        {
            page = ReadPage(pg);
        }
        catch (InvalidDataException)
        {
            return false;
        }

        return TryGetRow(page, row, out start, out len);
    }

    private static bool TryGetRow(byte[] page, int row, out int start, out int len)
    {
        start = 0;
        len = 0;
        if (row < 0 || RowCountOffset + 2 + (row + 1) * 2 > page.Length)
        {
            return false;
        }

        start = BitConverter.ToUInt16(page, RowCountOffset + 2 + row * 2);
        var next = row == 0
            ? PageSize
            : BitConverter.ToUInt16(page, RowCountOffset + row * 2) & OffsetMask;
        var begin = start & OffsetMask;
        if (begin >= PageSize || begin > next || next > PageSize)
        {
            return false;
        }

        len = next - begin;
        return len > 0;
    }

    private static bool IsNull(byte[] mask, int colNum1Based)
    {
        var byteNum = (colNum1Based - 1) / 8;
        var bitNum = (colNum1Based - 1) % 8;
        if ((uint)byteNum >= (uint)mask.Length)
        {
            return true;
        }

        return (mask[byteNum] & (1 << bitNum)) == 0;
    }

    public static string DecodeJetText(ReadOnlySpan<byte> src)
    {
        if (src.Length == 0)
        {
            return string.Empty;
        }

        if (src.Length >= 2 && src[0] == 0xFF && src[1] == 0xFE)
        {
            var tmp = new byte[src.Length * 2];
            var n = DecompressUnicode(src[2..], tmp);
            return Encoding.Unicode.GetString(tmp, 0, n);
        }

        if (src.Length >= 2 && src[1] == 0x00)
        {
            return Encoding.Unicode.GetString(src);
        }

        return Encoding.Unicode.GetString(src);
    }

    private static int DecompressUnicode(ReadOnlySpan<byte> src, Span<byte> dst)
    {
        var compress = true;
        var t = 0;
        var i = 0;
        while (i < src.Length && t + 1 < dst.Length)
        {
            if (src[i] == 0)
            {
                compress = !compress;
                i++;
            }
            else if (compress)
            {
                dst[t++] = src[i++];
                dst[t++] = 0;
            }
            else if (i + 1 < src.Length)
            {
                dst[t++] = src[i++];
                dst[t++] = src[i++];
            }
            else
            {
                break;
            }
        }

        return t;
    }

    private static string DecodeUcs2(byte[] raw)
        => Encoding.Unicode.GetString(raw).TrimEnd('\0');

    private sealed class TdefCursor
    {
        private readonly List<byte[]> _pages;

        public TdefCursor(List<byte[]> pages) => _pages = pages;

        public int ReadInt16(int pos) => BitConverter.ToInt16(GetBytes(pos, 2));
        public int ReadInt32(int pos) => BitConverter.ToInt32(GetBytes(pos, 4));
        public int ReadInt16(ref int pos)
        {
            var v = ReadInt16(pos);
            pos += 2;
            return v;
        }

        public byte[] ReadBytes(ref int pos, int len)
        {
            var data = GetBytes(pos, len);
            pos += len;
            return data;
        }

        private byte[] GetBytes(int pos, int len)
        {
            var buf = new byte[len];
            for (var i = 0; i < len; i++)
            {
                buf[i] = GetByte(pos + i);
            }

            return buf;
        }

        private byte GetByte(int pos)
        {
            var pageIndex = 0;
            var cur = pos;
            while (cur >= PageSize)
            {
                pageIndex++;
                cur -= PageSize - 8;
                if (pageIndex >= _pages.Count)
                {
                    return 0;
                }
            }

            if (pageIndex > 0 && cur < 8)
            {
                return 0;
            }

            var page = _pages[Math.Min(pageIndex, _pages.Count - 1)];
            return (uint)cur < (uint)page.Length ? page[cur] : (byte)0;
        }
    }
}

internal static class Rc4
{
    public static void Apply(ReadOnlySpan<byte> key, Span<byte> data)
    {
        var s = new byte[256];
        for (var i = 0; i < 256; i++)
        {
            s[i] = (byte)i;
        }

        var j = 0;
        for (var i = 0; i < 256; i++)
        {
            j = (j + s[i] + key[i % key.Length]) & 0xFF;
            (s[i], s[j]) = (s[j], s[i]);
        }

        var x = 0;
        var y = 0;
        for (var i = 0; i < data.Length; i++)
        {
            x = (x + 1) & 0xFF;
            y = (y + s[x]) & 0xFF;
            (s[x], s[y]) = (s[y], s[x]);
            data[i] ^= s[(s[x] + s[y]) & 0xFF];
        }
    }
}

internal sealed class Jet4CatalogEntry
{
    public string Name { get; set; } = string.Empty;
    public int ObjectType { get; set; }
    public int Flags { get; set; }
    public uint TablePage { get; set; }
    public bool IsUserTable => ObjectType == 1 && (Flags & unchecked((int)0x80000002)) == 0;
}

internal sealed class Jet4Column
{
    public string Name { get; set; } = string.Empty;
    public byte Type { get; set; }
    public byte ColNum { get; set; }
    public ushort VarColNum { get; set; }
    public ushort RowColNum { get; set; }
    public bool IsFixed { get; set; }
    public ushort FixedOffset { get; set; }
    public ushort Size { get; set; }
}

internal sealed class Jet4Row
{
    private readonly Dictionary<string, object?> _values;

    public Jet4Row(Dictionary<string, object?> values) => _values = values;

    public IReadOnlyDictionary<string, object?> Values => _values;

    public bool TryGet(string name, out object? value) => _values.TryGetValue(name, out value);

    public string GetString(string name)
    {
        if (!_values.TryGetValue(name, out var v) || v is null)
        {
            return string.Empty;
        }

        return v switch
        {
            string s => s,
            byte[] b => Jet4Database.DecodeJetText(b),
            IFormattable f => f.ToString(null, CultureInfo.InvariantCulture) ?? string.Empty,
            _ => Convert.ToString(v, CultureInfo.InvariantCulture) ?? string.Empty
        };
    }

    public bool GetBool(string name)
    {
        if (!_values.TryGetValue(name, out var v) || v is null)
        {
            return false;
        }

        return v switch
        {
            bool b => b,
            byte b => b != 0,
            short s => s != 0,
            int i => i != 0,
            string s => s.Equals("True", StringComparison.OrdinalIgnoreCase) || s == "1",
            _ => false
        };
    }

    public int GetInt(string name)
    {
        if (!_values.TryGetValue(name, out var v) || v is null)
        {
            return 0;
        }

        return v switch
        {
            byte b => b,
            short s => s,
            int i => i,
            long l => (int)l,
            float f => (int)f,
            double d => (int)d,
            string s when int.TryParse(s, NumberStyles.Any, CultureInfo.InvariantCulture, out var n) => n,
            _ => 0
        };
    }

    public double GetDouble(string name)
    {
        if (!_values.TryGetValue(name, out var v) || v is null)
        {
            return 0;
        }

        return v switch
        {
            float f => f,
            double d => d,
            byte b => b,
            short s => s,
            int i => i,
            long l => l,
            string s when double.TryParse(s, NumberStyles.Any, CultureInfo.InvariantCulture, out var n) => n,
            _ => 0
        };
    }

    public byte[]? GetBytes(string name)
    {
        if (!_values.TryGetValue(name, out var v) || v is null)
        {
            return null;
        }

        return v switch
        {
            byte[] b => b,
            string s => Encoding.Unicode.GetBytes(s),
            _ => null
        };
    }
}

internal sealed class Jet4Table
{
    public string Name { get; set; } = string.Empty;
    public uint TablePage { get; set; }
    public int DeclaredRowCount { get; set; }
    public List<Jet4Column> Columns { get; set; } = [];
    public List<Jet4Row> Rows { get; set; } = [];
}
