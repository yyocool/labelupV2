using System.Text;
using System.Text.Json;
using LabelUp.Editor.Models;
using Microsoft.AspNetCore.Components;
using Microsoft.JSInterop;

namespace LabelUp.Editor.Services;

public sealed class ClipartCatalog
{
    public const int PageSize = 48;

    private readonly HttpClient _http;
    private readonly NavigationManager _nav;
    private readonly IJSRuntime _js;
    private readonly List<ShopClipartItem> _items = [];
    private readonly List<ShopClipartCategory> _categories = [];
    private bool _categoriesLoaded;
    private string _query = "";
    private int? _categoryId;
    private int _page;
    private int _total;
    private bool _hasMore = true;
    private bool _loading;

    public ClipartCatalog(HttpClient http, NavigationManager nav, IJSRuntime js)
    {
        _http = http;
        _nav = nav;
        _js = js;
    }

    public IReadOnlyList<ShopClipartItem> Items => _items;
    public IReadOnlyList<ShopClipartCategory> Categories => _categories;
    public int Total => _total;
    public bool HasMore => _hasMore;
    public bool IsLoading => _loading;
    public string ActiveQuery => _query;
    public int? ActiveCategoryId => _categoryId;

    /// <summary>첫 페이지부터 다시 로드. 검색/카테고리 변경 시 사용.</summary>
    public async Task ResetAndLoadAsync(string? query = null, int? categoryId = null)
    {
        _query = (query ?? "").Trim();
        _categoryId = categoryId is > 0 ? categoryId : null;
        _items.Clear();
        _page = 0;
        _total = 0;
        _hasMore = true;
        await LoadMoreAsync();
    }

    public async Task EnsureCategoriesAsync()
    {
        if (_categoriesLoaded) return;
        // 카테고리는 첫 페이지 응답에 포함되므로 빈 조회로 확보
        if (_categories.Count == 0)
            await ResetAndLoadAsync();
        _categoriesLoaded = _categories.Count > 0 || !_hasMore;
    }

    public async Task<bool> LoadMoreAsync()
    {
        if (_loading || !_hasMore) return false;
        _loading = true;
        try
        {
            var next = _page + 1;
            var url = BuildUrl(next);
            var json = await _http.GetStringAsync(url);
            var env = JsonSerializer.Deserialize<ApiEnvelope<ShopClipartCatalogDto>>(json, LabelDocumentJson.Options);
            if (env?.Success != true || env.Data is null)
            {
                _hasMore = false;
                return false;
            }

            var data = env.Data;
            if (data.Categories is { Count: > 0 })
            {
                _categories.Clear();
                _categories.AddRange(data.Categories);
                _categoriesLoaded = true;
            }

            var seen = new HashSet<int>(_items.Select(i => i.Id));
            foreach (var item in data.Items ?? [])
            {
                if (item.Id <= 0 || !seen.Add(item.Id)) continue;
                _items.Add(item);
            }

            _page = data.Page > 0 ? data.Page : next;
            _total = Math.Max(data.Total, _items.Count);
            var pages = Math.Max(1, data.Pages);
            _hasMore = data.HasMore || _page < pages || _items.Count < _total;
            if ((data.Items?.Count ?? 0) == 0)
                _hasMore = false;
            else if (_items.Count >= _total && _total > 0)
                _hasMore = false;

            EditorLog.Info($"상점 클립아트 페이지 {_page}/{Math.Max(1, data.Pages)} (누적 {_items.Count}/{_total})");
            return true;
        }
        catch (Exception ex)
        {
            EditorLog.Warn("클립아트 목록 로드 실패: " + ex.Message);
            _hasMore = false;
            return false;
        }
        finally
        {
            _loading = false;
        }
    }

    private string BuildUrl(int page)
    {
        var sb = new StringBuilder("/api/editor/cliparts?page=");
        sb.Append(page);
        sb.Append("&per_page=");
        sb.Append(PageSize);
        if (!string.IsNullOrWhiteSpace(_query))
        {
            sb.Append("&q=");
            sb.Append(Uri.EscapeDataString(_query));
        }
        if (_categoryId is > 0)
        {
            sb.Append("&category_id=");
            sb.Append(_categoryId.Value);
        }
        return sb.ToString();
    }

    public async Task<(bool LoggedIn, IReadOnlyList<UserClipartItem> Items)> LoadMyClipartsAsync()
    {
        try
        {
            var raw = await _js.InvokeAsync<JsonElement>("labelUpEditor.listMyCliparts");
            if (raw.ValueKind != JsonValueKind.Object)
                return (false, []);

            var loggedIn = !(raw.TryGetProperty("loggedIn", out var loggedEl) && loggedEl.ValueKind == JsonValueKind.False);

            var list = new List<UserClipartItem>();
            if (raw.TryGetProperty("items", out var items) && items.ValueKind == JsonValueKind.Array)
            {
                foreach (var el in items.EnumerateArray())
                {
                    var item = JsonSerializer.Deserialize<UserClipartItem>(el.GetRawText(), LabelDocumentJson.Options);
                    if (item is null || string.IsNullOrWhiteSpace(item.ImageUrl)) continue;
                    list.Add(item);
                }
            }

            return (loggedIn, list);
        }
        catch (Exception ex)
        {
            EditorLog.Warn("내 클립아트 목록 로드 실패: " + ex.Message);
            return (false, []);
        }
    }

    public async Task<string?> FetchImageAsDataUrlAsync(string src)
    {
        try
        {
            if (string.IsNullOrWhiteSpace(src)) return null;
            if (src.StartsWith("data:image", StringComparison.OrdinalIgnoreCase))
                return src;

            var resolved = ResolvePublicUrl(src);
            try
            {
                var viaJs = await _js.InvokeAsync<string>("labelUpEditor.fetchImageDataUrl", resolved);
                if (!string.IsNullOrWhiteSpace(viaJs) && viaJs.StartsWith("data:image", StringComparison.OrdinalIgnoreCase))
                    return viaJs;
            }
            catch (Exception ex)
            {
                EditorLog.Warn("클립아트 JS 로드 실패: " + ex.Message);
            }

            using var res = await _http.GetAsync(resolved);
            if (!res.IsSuccessStatusCode) return null;
            var bytes = await res.Content.ReadAsByteArrayAsync();
            if (bytes.Length is 0 or > 8 * 1024 * 1024) return null;
            var mime = res.Content.Headers.ContentType?.MediaType;
            if (string.IsNullOrWhiteSpace(mime) || !mime.StartsWith("image/", StringComparison.OrdinalIgnoreCase))
            {
                mime = src.Contains(".jpg", StringComparison.OrdinalIgnoreCase)
                       || src.Contains(".jpeg", StringComparison.OrdinalIgnoreCase)
                    ? "image/jpeg"
                    : "image/png";
            }

            return $"data:{mime};base64,{Convert.ToBase64String(bytes)}";
        }
        catch (Exception ex)
        {
            EditorLog.Warn("클립아트 이미지 로드 실패: " + ex.Message);
            return null;
        }
    }

    private string ResolvePublicUrl(string src)
    {
        if (src.StartsWith("http://", StringComparison.OrdinalIgnoreCase)
            || src.StartsWith("https://", StringComparison.OrdinalIgnoreCase)
            || src.StartsWith("data:", StringComparison.OrdinalIgnoreCase)
            || src.StartsWith("blob:", StringComparison.OrdinalIgnoreCase))
            return src;

        var uri = new Uri(_nav.BaseUri);
        var origin = uri.GetLeftPart(UriPartial.Authority);
        return src.StartsWith('/') ? origin + src : origin + "/" + src.TrimStart('/');
    }
}
