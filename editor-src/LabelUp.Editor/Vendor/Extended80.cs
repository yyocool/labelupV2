namespace LabelUp.Editor.Vendor;

/// <summary>x87 / Delphi Extended Precision (10바이트) 실수.</summary>
internal static class Extended80
{
    public static double ReadStandard(ReadOnlySpan<byte> data)
    {
        if (data.Length < 10) return 0;
        var frac = BitConverter.ToUInt64(data);
        var expSign = BitConverter.ToUInt16(data[8..]);
        var negative = (expSign & 0x8000) != 0;
        var exp = expSign & 0x7FFF;
        if (exp == 0)
        {
            if (frac == 0) return negative ? -0.0 : 0.0;
            var denorm = frac * Math.Pow(2, 1 - 16383 - 63);
            return negative ? -denorm : denorm;
        }
        if (exp == 0x7FFF)
            return frac == 0 ? (negative ? double.NegativeInfinity : double.PositiveInfinity) : double.NaN;
        var value = Math.ScaleB((double)frac, exp - 16383 - 63);
        return negative ? -value : value;
    }
}
