using System.Collections.Concurrent;
using SkiaSharp;

namespace LabelUp.Editor.Services;

/// <summary>데이터 열의 이미지 URL을 필요할 때 받아 캐시한다.</summary>
public static class BoundImageCache
{
    private static readonly ConcurrentDictionary<string, SKBitmap?> Bitmaps = new(StringComparer.OrdinalIgnoreCase);
    private static readonly ConcurrentDictionary<string, byte> Pending = new(StringComparer.OrdinalIgnoreCase);
    private static Func<string, Task<string?>>? _fetch;
    private static Action? _onReady;

    public static void Configure(Func<string, Task<string?>> fetch, Action onReady)
    {
        _fetch = fetch;
        _onReady = onReady;
    }

    public static SKBitmap? Get(string? src)
    {
        if (string.IsNullOrWhiteSpace(src)) return null;
        if (Bitmaps.TryGetValue(src, out var bmp)) return bmp;
        Request(src);
        return null;
    }

    public static void Request(string? src)
    {
        if (string.IsNullOrWhiteSpace(src) || !LooksLikeUrl(src)) return;
        if (Bitmaps.ContainsKey(src) || !Pending.TryAdd(src, 1)) return;
        var fetch = _fetch;
        if (fetch is null) return;
        _ = LoadAsync(src, fetch);
    }

    private static async Task LoadAsync(string src, Func<string, Task<string?>> fetch)
    {
        try
        {
            var dataUrl = await fetch(src);
            if (string.IsNullOrWhiteSpace(dataUrl)) return;
            var comma = dataUrl.IndexOf(',');
            var raw = comma >= 0 ? dataUrl[(comma + 1)..] : dataUrl;
            var bytes = Convert.FromBase64String(raw);
            using var data = SKData.CreateCopy(bytes);
            var bmp = SKBitmap.Decode(data);
            if (bmp is null) return;
            Bitmaps[src] = bmp;
            _onReady?.Invoke();
        }
        catch (Exception ex)
        {
            EditorLog.Warn($"데이터 이미지 로드 실패: {src} · {ex.Message}");
        }
        finally
        {
            Pending.TryRemove(src, out _);
        }
    }

    public static bool LooksLikeUrl(string? src)
        => src is { Length: > 7 } && (src.StartsWith("http://", StringComparison.OrdinalIgnoreCase)
            || src.StartsWith("https://", StringComparison.OrdinalIgnoreCase)
            || src.StartsWith("data:image", StringComparison.OrdinalIgnoreCase));
}
