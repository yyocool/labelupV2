using Microsoft.Extensions.Configuration;

namespace LabelUp.Editor.Services;

/// <summary>
/// 편집기가 호출할 API 원본(origin) 설정.
/// 배포본(서버가 편집기를 서빙)에서는 같은 출처를 쓰고,
/// 로컬 빌드(localhost)에서는 Api:LocalDevBaseUrl 서버의 DB를 그대로 조회한다.
/// Api:BaseUrl 을 채우면 호스트와 무관하게 그 값이 우선한다.
/// </summary>
public sealed class EditorApiOptions
{
    private readonly string _origin;

    public EditorApiOptions(IConfiguration configuration, string hostBaseAddress)
    {
        var configured = NormalizeOrigin(configuration["Api:BaseUrl"]);
        if (configured.Length == 0 && IsLocalHost(hostBaseAddress))
            configured = NormalizeOrigin(configuration["Api:LocalDevBaseUrl"]);

        _origin = configured;
        EditorLog.Info(_origin.Length == 0
            ? $"API 원본: 같은 출처 ({hostBaseAddress})"
            : $"API 원본: {_origin}");
    }

    /// <summary>API 원본. 빈 문자열이면 편집기와 같은 출처를 사용한다.</summary>
    public string Origin => _origin;

    public bool UsesRemoteOrigin => _origin.Length > 0;

    /// <summary>"/api/..." 경로를 호출 가능한 URL로 만든다.</summary>
    public string Url(string path)
    {
        var relative = path.StartsWith('/') ? path : "/" + path;
        return _origin.Length == 0 ? relative : _origin + relative;
    }

    /// <summary>서버가 돌려준 자산 경로(썸네일 등)를 API 원본 기준 절대 URL로 보정한다.</summary>
    public string? ResolveAssetUrl(string? url)
    {
        if (string.IsNullOrWhiteSpace(url)) return url;

        var value = url.Trim();
        if (value.StartsWith("data:", StringComparison.OrdinalIgnoreCase)
            || value.StartsWith("//", StringComparison.Ordinal)
            || Uri.IsWellFormedUriString(value, UriKind.Absolute))
            return value;

        return Url(value);
    }

    private static string NormalizeOrigin(string? value)
    {
        var trimmed = (value ?? "").Trim().TrimEnd('/');
        if (trimmed.Length == 0) return "";

        if (Uri.TryCreate(trimmed, UriKind.Absolute, out var uri)
            && (uri.Scheme == Uri.UriSchemeHttp || uri.Scheme == Uri.UriSchemeHttps))
            return trimmed;

        EditorLog.Warn($"API 원본 설정이 올바르지 않아 무시합니다: {trimmed}");
        return "";
    }

    private static bool IsLocalHost(string baseAddress)
    {
        if (!Uri.TryCreate(baseAddress, UriKind.Absolute, out var uri)) return false;
        return uri.Host is "localhost" or "127.0.0.1" or "::1" or "[::1]";
    }
}
