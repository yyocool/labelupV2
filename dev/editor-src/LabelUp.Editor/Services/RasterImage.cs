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

    /// <summary>
    /// 그림의 픽셀 크기만 읽는다. 코덱 머리글만 보므로 화소를 펼치지 않는다.
    /// BMP 는 이 파일 맨 위 규칙대로 Skia 코덱을 건너뛰고 직접 읽는다.
    /// 머리글로 읽히지 않으면 직접 디코드로 물러선다.
    /// </summary>
    public static bool TryMeasure(byte[] bytes, out int width, out int height)
    {
        width = 0;
        height = 0;
        if (bytes is not { Length: > 8 }) return false;

        var isBmp = bytes[0] == (byte)'B' && bytes[1] == (byte)'M';
        if (!isBmp)
        {
            try
            {
                using var codec = SKCodec.Create(new MemoryStream(bytes, writable: false));
                if (codec is not null && codec.Info.Width > 0 && codec.Info.Height > 0)
                {
                    width = codec.Info.Width;
                    height = codec.Info.Height;
                    return true;
                }
            }
            catch (Exception ex)
            {
                EditorLog.Info($"그림 머리글 읽기 실패, 직접 디코드로 물러섬: {ex.Message}");
            }
        }

        try
        {
            using var bmp = Decode(bytes);
            if (bmp is null || bmp.Width <= 0 || bmp.Height <= 0) return false;
            width = bmp.Width;
            height = bmp.Height;
            return true;
        }
        catch (Exception ex)
        {
            EditorLog.Error("그림 크기 읽기 실패", ex);
            return false;
        }
    }

    /// <summary>data: URL 에서 화소 바이트를 꺼낸다. 원격 URL 이면 꺼낼 것이 없어 null.</summary>
    public static byte[]? TryReadDataUrl(string? dataUrl)
    {
        if (string.IsNullOrWhiteSpace(dataUrl)) return null;
        if (!dataUrl.StartsWith("data:", StringComparison.OrdinalIgnoreCase)) return null;
        var comma = dataUrl.IndexOf(',');
        if (comma < 0) return null;
        if (dataUrl.AsSpan(0, comma).IndexOf("base64", StringComparison.OrdinalIgnoreCase) < 0) return null;
        try
        {
            return Convert.FromBase64String(dataUrl[(comma + 1)..]);
        }
        catch (FormatException)
        {
            return null;
        }
    }

    public static (byte[] Bytes, string Mime) Normalize(byte[] bytes, string mime)
    {
        if (bytes.Length == 0) return (bytes, mime);
        if (mime == "image/jpeg")
            return (TrimJpeg(bytes), mime);
        if (mime == "image/png")
            return (TrimPng(bytes), mime);
        if (mime == "image/bmp")
        {
            var png = BmpToPng(bytes);
            if (png is { Length: > 0 })
                return (png, "image/png");
        }
        return (bytes, mime);
    }

    private static byte[]? BmpToPng(byte[] bmp)
    {
        var sw = Stopwatch.StartNew();
        using var sk = DecodeBmp(bmp);
        if (sk is null) return null;
        using var image = SKImage.FromBitmap(sk);
        using var encoded = image.Encode(SKEncodedImageFormat.Png, 80);
        var png = encoded?.ToArray();
        if (png is { Length: > 0 })
            EditorLog.Info($"BMP→PNG {bmp.Length}b → {png.Length}b {sw.ElapsedMilliseconds}ms");
        return png;
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
