<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['APP_CONFIG'] ?? [];
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

function base_path(string $path = ''): string
{
    return rtrim(APP_ROOT, '/\\') . ($path ? '/' . ltrim($path, '/') : '');
}

function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path ? '/' . ltrim($path, '/') : ''));
}

function public_path(string $path = ''): string
{
    return base_path('public' . ($path ? '/' . ltrim($path, '/') : ''));
}

function view_path(string $path = ''): string
{
    return base_path('views' . ($path ? '/' . ltrim($path, '/') : ''));
}

function url(string $path = ''): string
{
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($base === '/') {
        $base = '';
    }
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $rel = 'assets/' . ltrim($path, '/');
    $file = public_path($rel);
    $version = is_readable($file) ? (string) filemtime($file) : (string) time();
    return url($rel . '?v=' . $version);
}

function css(string $path): string
{
    $rel = 'css/' . ltrim($path, '/');
    $file = public_path($rel);
    $version = is_readable($file) ? (string) filemtime($file) : (string) time();
    return url($rel . '?v=' . $version);
}

function js(string $path): string
{
    $rel = 'js/' . ltrim($path, '/');
    $file = public_path($rel);
    $version = is_readable($file) ? (string) filemtime($file) : (string) time();
    return url($rel . '?v=' . $version);
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $file = view_path(str_replace('.', '/', $template) . '.php');
    if (!is_readable($file)) {
        throw new RuntimeException('View not found: ' . $template);
    }
    require $file;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Admin pagination page window (centered around current when possible).
 * @return list<int>
 */
function admin_pagination_window(int $page, int $pages, int $window = 7): array
{
    $page = max(1, $page);
    $pages = max(1, $pages);
    $window = max(1, $window);
    if ($pages <= $window) {
        return range(1, $pages);
    }
    $half = intdiv($window, 2);
    $start = max(1, $page - $half);
    $end = min($pages, $start + $window - 1);
    $start = max(1, $end - $window + 1);

    return range($start, $end);
}

/**
 * Build admin pagination href for a page number.
 * @param array<string, scalar|null> $params query params excluding page
 */
function admin_pagination_href(string $basePath, array $params, int $page): string
{
    $query = [];
    foreach ($params as $key => $value) {
        if ($value === null || $value === '' || $value === false) {
            continue;
        }
        $query[$key] = $value;
    }
    if ($page > 1) {
        $query['page'] = $page;
    }
    $qs = http_build_query($query);

    return url($basePath) . ($qs !== '' ? '?' . $qs : '');
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function request_json(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function request_input(string $key, mixed $default = null): mixed
{
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    if (isset($_GET[$key])) {
        return $_GET[$key];
    }
    $json = request_json();
    return $json[$key] ?? $default;
}

function credit_balance_for_user(?array $authUser): int
{
    if (empty($authUser['id'])) {
        return 0;
    }
    return (new \App\Services\CreditService())->balance((int) $authUser['id']);
}

function safe_redirect_path(string $path, string $fallback = '/'): string
{
    $path = trim($path);
    if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
        return $fallback;
    }
    return $path;
}
