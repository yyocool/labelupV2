using ZXing;
using ZXing.Datamatrix;
using ZXing.Datamatrix.Encoder;

namespace LabelUp.Editor.Rendering;

/// <summary>
/// 아이라벨 DataMatrix. 폼텍 인코더는 사용하지 않는다.
/// ECC200 직사각은 가로가 긴 심볼이다(8×32 → 32×8). ZXing Dimension은 (가로, 세로).
/// Auto 실측: Binary/X12/C40/TEXT→16×16, EDIFACT→32×8, ASCII→12×12.
/// </summary>
internal static class ILabelDataMatrix
{
    /// <summary>Cols×Rows, dataCw 오름차순.</summary>
    private static readonly (int Cols, int Rows, int Data)[] Symbols =
    [
        (10, 10, 3), (12, 12, 5), (18, 8, 5), (14, 14, 8), (32, 8, 10),
        (16, 16, 12), (26, 12, 16), (18, 18, 18), (20, 20, 22), (36, 12, 22),
        (22, 22, 30), (36, 16, 32), (24, 24, 36), (26, 26, 44), (48, 16, 49),
        (32, 32, 62), (36, 36, 86), (40, 40, 114), (44, 44, 144), (48, 48, 174),
        (52, 52, 204), (64, 64, 280), (72, 72, 368), (80, 80, 456), (88, 88, 576),
        (96, 96, 696), (104, 104, 816), (120, 120, 1050)
    ];

    public static bool TryPickAutoSize(string value, string enc, bool squareOnly, out int cols, out int rows)
    {
        var need = EstimateCodewords(value ?? "", enc);
        foreach (var s in Symbols)
        {
            if (s.Data < need) continue;
            if (squareOnly && s.Cols != s.Rows) continue;
            cols = s.Cols;
            rows = s.Rows;
            return true;
        }
        cols = rows = 0;
        return false;
    }

    /// <summary>이름 16x36 → ZXing (36, 16). 정사각은 그대로.</summary>
    public static void ApplySymbolSize(DatamatrixEncodingOptions opt, int a, int b)
    {
        var cols = a == b ? a : Math.Max(a, b);
        var rows = a == b ? a : Math.Min(a, b);
        opt.SymbolShape = cols == rows ? SymbolShapeHint.FORCE_SQUARE : SymbolShapeHint.FORCE_RECTANGLE;
        opt.MinSize = new Dimension(cols, rows);
        opt.MaxSize = new Dimension(cols, rows);
    }

    public static void ApplyEncodation(DatamatrixEncodingOptions opt, string enc)
    {
        switch ((enc ?? "").Trim().ToUpperInvariant())
        {
            case "ASCII":
                opt.DefaultEncodation = Encodation.ASCII;
                break;
            case "C40":
                opt.DefaultEncodation = Encodation.C40;
                break;
            case "TEXT":
                opt.DefaultEncodation = Encodation.TEXT;
                break;
            case "X12":
                opt.DefaultEncodation = Encodation.X12;
                break;
            case "EDIFACT":
                opt.DefaultEncodation = Encodation.EDIFACT;
                break;
            case "BINARY":
            case "BASE256":
                opt.DefaultEncodation = Encodation.BASE256;
                break;
        }
    }

    private static int EstimateCodewords(string value, string enc)
    {
        var n = value.Length;
        if (n <= 0) return 1;
        return (enc ?? "").Trim().ToUpperInvariant() switch
        {
            "BINARY" or "BASE256" => 1 + (n < 250 ? 1 : 2) + n,
            "EDIFACT" => 1 + (n + 3) / 4 * 3,
            "C40" or "TEXT" or "X12" => 11,
            "ASCII" => CountAscii(value),
            _ => Math.Min(CountAscii(value), 1 + (n < 250 ? 1 : 2) + n)
        };
    }

    private static int CountAscii(string value)
    {
        var cw = 0;
        for (var i = 0; i < value.Length; i++)
        {
            if (i + 1 < value.Length && char.IsAsciiDigit(value[i]) && char.IsAsciiDigit(value[i + 1]))
            {
                cw++;
                i++;
            }
            else
                cw += value[i] <= 127 ? 1 : 2;
        }
        return Math.Max(1, cw);
    }
}
