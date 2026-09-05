using System.Diagnostics;
using SkiaSharp;

namespace LabelUp.Editor.Services;

/// <summary>폼텍 래스터 정리. BMP는 Skia 코덱을 쓰지 않고 픽셀을 직접 읽는다.</summary>
internal static class RasterImage
{
    public static SKBitmap? Decode(byte[] bytes)
    {
        if (bytes.Length >= 2 && bytes[0] == (byte)'B' && bytes[1] == (byte)'M')
        {
            var sw = Stopwatch.StartNew();
            var bmp = DecodeBmp(bytes);
            EditorLog.Info($"BMP 직접 디코드 {bytes.Length}b {sw.ElapsedMilliseconds}ms ok={bmp is not null}");
            return bmp;
        }
        return SKBitmap.Decode(bytes);
    }
    public static (byte[] Bytes, string Mime) Normalize(byte[] bytes, string mime)
    {
        if (bytes.Length == 0) return (bytes, mime);
        if (mime == "image/jpeg")
            return (TrimJpeg(bytes), mime);
        if (mime == "image/png")
            return (TrimPng(bytes), mime);
        return (bytes, mime);
    }

    public static string CacheKey(string dataUrl)
    {
        var n = dataUrl.Length;
        var h = n;
        var take = Math.Min(48, n);
        for (var i = 0; i < take; i++)
            h = HashCode.Combine(h, dataUrl[i], dataUrl[n - 1 - i]);
        return $"{n}:{h}";
    }

    internal static byte[] TrimJpeg(byte[] bytes)
    {
        if (bytes.Length < 4 || bytes[0] != 0xFF || bytes[1] != 0xD8)
            return bytes;
        var i = 2;
        while (i + 1 < bytes.Length)
        {
            if (bytes[i] != 0xFF)
            {
                i++;
                continue;
            }
            var marker = bytes[i + 1];
            if (marker == 0xFF) { i++; continue; }
            if (marker == 0xD9) return bytes[..(i + 2)];
            if (marker is 0xD8 or 0x01 or 0x00 || marker is >= 0xD0 and <= 0xD7)
            {
                i += 2;
                continue;
            }
            if (i + 3 >= bytes.Length) break;
            var len = (bytes[i + 2] << 8) | bytes[i + 3];
            if (len < 2) { i += 2; continue; }
            if (marker == 0xDA)
            {
                i += 2 + len;
                while (i + 1 < bytes.Length)
                {
                    if (bytes[i] == 0xFF && bytes[i + 1] != 0x00 && bytes[i + 1] is not (>= 0xD0 and <= 0xD7))
                    {
                        if (bytes[i + 1] == 0xD9) return bytes[..(i + 2)];
                        break;
                    }
                    i++;
                }
                break;
            }
            i += 2 + len;
        }
        return bytes;
    }

    internal static byte[] TrimPng(byte[] bytes)
    {
        for (var i = 8; i + 8 <= bytes.Length; i++)
        {
            if (bytes[i] == 0x49 && bytes[i + 1] == 0x45 && bytes[i + 2] == 0x4E && bytes[i + 3] == 0x44)
                return bytes[..Math.Min(bytes.Length, i + 8)];
        }
        return bytes;
    }

    private static SKBitmap? DecodeBmp(byte[] bmp)
    {
        if (bmp.Length < 54) return null;
        var pixelOff = BitConverter.ToInt32(bmp, 10);
        var dib = BitConverter.ToInt32(bmp, 14);
        if (dib < 40 || pixelOff < 14 || pixelOff >= bmp.Length) return null;
        var width = BitConverter.ToInt32(bmp, 18);
        var rawH = BitConverter.ToInt32(bmp, 22);
        var planes = BitConverter.ToUInt16(bmp, 26);
        var bpp = BitConverter.ToUInt16(bmp, 28);
        var compression = BitConverter.ToUInt32(bmp, 30);
        var height = Math.Abs(rawH);
        if (planes != 1 || compression != 0 || width is < 1 or > 8000 || height is < 1 or > 8000)
            return null;
        if (bpp is not (24 or 32))
            return null;

        var srcStride = bpp == 24 ? ((width * 3 + 3) / 4) * 4 : width * 4;
        if (pixelOff + (long)srcStride * height > bmp.Length)
            return null;

        var sk = new SKBitmap(width, height, SKColorType.Bgra8888, SKAlphaType.Opaque);
        var dest = sk.GetPixelSpan();
        var topDown = rawH < 0;
        var srcBpp = bpp / 8;
        for (var y = 0; y < height; y++)
        {
            var srcY = topDown ? y : height - 1 - y;
            var src = pixelOff + srcY * srcStride;
            var dst = y * width * 4;
            for (var x = 0; x < width; x++)
            {
                dest[dst] = bmp[src];
                dest[dst + 1] = bmp[src + 1];
                dest[dst + 2] = bmp[src + 2];
                dest[dst + 3] = 255;
                src += srcBpp;
                dst += 4;
            }
        }
        return sk;
    }
}
