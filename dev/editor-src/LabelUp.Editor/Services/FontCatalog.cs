using SkiaSharp;

namespace LabelUp.Editor.Services;

/// <summary>Loads Korean-capable typefaces for SkiaSharp (WASM has no system Hangul fonts).</summary>
public sealed class FontCatalog : IAsyncDisposable
{
    private readonly HttpClient _http;
    private SKTypeface? _regular;
    private SKTypeface? _bold;
    private bool _loaded;

    public FontCatalog(HttpClient http) => _http = http;

    public bool IsReady => _loaded && _regular is not null;

    public async Task EnsureLoadedAsync()
    {
        if (_loaded) return;
        _regular = await LoadAsync("fonts/Pretendard-Regular.otf");
        _bold = await LoadAsync("fonts/Pretendard-Bold.otf") ?? _regular;
        _loaded = true;
    }

    private async Task<SKTypeface?> LoadAsync(string relativePath)
    {
        try
        {
            var bytes = await _http.GetByteArrayAsync(relativePath);
            using var data = SKData.CreateCopy(bytes);
            return SKTypeface.FromData(data);
        }
        catch
        {
            return null;
        }
    }

    public SKTypeface Resolve(bool bold = false)
    {
        if (bold && _bold is not null) return _bold;
        if (_regular is not null) return _regular;
        return SKTypeface.Default;
    }

    public ValueTask DisposeAsync()
    {
        _regular?.Dispose();
        if (!ReferenceEquals(_bold, _regular))
            _bold?.Dispose();
        _regular = null;
        _bold = null;
        return ValueTask.CompletedTask;
    }
}
