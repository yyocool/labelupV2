using SkiaSharp;

namespace LabelUp.Editor.Services;

/// <summary>Loads Korean-capable typefaces for SkiaSharp (WASM has no system Hangul fonts).</summary>
public sealed class FontCatalog : IAsyncDisposable
{
    private readonly HttpClient _http;
    private readonly SemaphoreSlim _gate = new(1, 1);
    private SKTypeface? _regular;
    private SKTypeface? _bold;
    private bool _loaded;

    public FontCatalog(HttpClient http) => _http = http;

    public bool IsReady => _loaded && _regular is not null;

    public async Task EnsureLoadedAsync()
    {
        if (IsReady) return;
        await _gate.WaitAsync().ConfigureAwait(false);
        try
        {
            if (IsReady) return;
            _regular = await LoadAsync("fonts/Pretendard-Regular.otf").ConfigureAwait(false);
            _bold = await LoadAsync("fonts/Pretendard-Bold.otf").ConfigureAwait(false) ?? _regular;
            // Only mark loaded when a Hangul-capable face is available.
            _loaded = _regular is not null;
        }
        finally
        {
            _gate.Release();
        }
    }

    private async Task<SKTypeface?> LoadAsync(string relativePath)
    {
        try
        {
            var bytes = await _http.GetByteArrayAsync(relativePath).ConfigureAwait(false);
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
        // Never fall back to SKTypeface.Default for content text — WASM default has no Hangul.
        // Callers should gate on IsReady; rulers may still need a face so return Default only there via ResolveOrDefault.
        return _regular ?? SKTypeface.Default;
    }

    public SKTypeface ResolveOrDefault(bool bold = false)
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
        _gate.Dispose();
        return ValueTask.CompletedTask;
    }
}
