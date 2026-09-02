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

/** @return array{id:int,name:string,slug:string,description:string,color:string,is_default:int,is_active:int}|null */
function member_grade_for_user(?array $authUser): ?array
{
    if (empty($authUser['id'])) {
        return null;
    }
    return (new \App\Services\MemberGradeService())->forUser((int) $authUser['id']);
}

function safe_redirect_path(string $path, string $fallback = '/'): string
{
    $path = trim($path);
    if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
        return $fallback;
    }
    return $path;
}

/** @return array<int, array{key:string,label:string,href:string,group:string,ic:string}> */
function admin_menu_catalog(): array
{
    return [
        ['key' => 'dashboard', 'label' => '대시보드', 'href' => 'admin', 'group' => '홈', 'ic' => '▣'],
        ['key' => 'shop-categories', 'label' => '카테고리', 'href' => 'admin/shop/categories', 'group' => '쇼핑몰운영', 'ic' => '▦'],
        ['key' => 'shop-specs', 'label' => '라벨 규격', 'href' => 'admin/shop/specs', 'group' => '쇼핑몰운영', 'ic' => '▣'],
        ['key' => 'shop-products', 'label' => '상품 관리', 'href' => 'admin/shop/products', 'group' => '쇼핑몰운영', 'ic' => '◇'],
        ['key' => 'shop-orders', 'label' => '주문 관리', 'href' => 'admin/shop/orders', 'group' => '쇼핑몰운영', 'ic' => '◎'],
        ['key' => 'shop-shipping', 'label' => '배송 관리', 'href' => 'admin/shop/shipping', 'group' => '쇼핑몰운영', 'ic' => '▷'],
        ['key' => 'shop-coupons', 'label' => '쿠폰·프로모션', 'href' => 'admin/shop/coupons', 'group' => '쇼핑몰운영', 'ic' => '◈'],
        ['key' => 'shop-banners', 'label' => '배너·전시', 'href' => 'admin/shop/banners', 'group' => '쇼핑몰운영', 'ic' => '▣'],
        ['key' => 'content-cliparts', 'label' => '클립아트관리', 'href' => 'admin/content/cliparts', 'group' => '컨텐츠관리', 'ic' => '✦'],
        ['key' => 'content-user-designs', 'label' => '사용자디자인', 'href' => 'admin/content/user-designs', 'group' => '컨텐츠관리', 'ic' => '★'],
        ['key' => 'content-templates', 'label' => '템플릿관리', 'href' => 'admin/content/templates', 'group' => '컨텐츠관리', 'ic' => '▦'],
        ['key' => 'ai-example-prompts', 'label' => '예시프롬프트 관리', 'href' => 'admin/ai/example-prompts', 'group' => 'AI 관리', 'ic' => '✦'],
        ['key' => 'ai-usage', 'label' => '사용량 통계', 'href' => 'admin/ai/usage', 'group' => 'AI 관리', 'ic' => '▣'],
        ['key' => 'users', 'label' => '회원 관리', 'href' => 'admin/users', 'group' => '운영관리', 'ic' => '◎'],
        ['key' => 'settings', 'label' => '운영설정', 'href' => 'admin/settings', 'group' => '운영관리', 'ic' => '⚙'],
        ['key' => 'ops-hero-slides', 'label' => '히어로 이미지 관리', 'href' => 'admin/ops/hero-slides', 'group' => '운영관리', 'ic' => '▦'],
        ['key' => 'ops-event-popups', 'label' => '이벤트 팝업관리', 'href' => 'admin/ops/event-popups', 'group' => '운영관리', 'ic' => '◎'],
        ['key' => 'ops-faq', 'label' => 'FAQ 관리', 'href' => 'admin/ops/faq', 'group' => '운영관리', 'ic' => '?'],
        ['key' => 'ops-inquiries', 'label' => '1:1 문의', 'href' => 'admin/ops/inquiries', 'group' => '운영관리', 'ic' => '✉'],
        ['key' => 'ops-credit-rewards', 'label' => '크레딧보상 관리', 'href' => 'admin/ops/credit-rewards', 'group' => '운영관리', 'ic' => '◈'],
        ['key' => 'ops-purchase-credits', 'label' => '구매크레딧', 'href' => 'admin/ops/purchase-credits', 'group' => '운영관리', 'ic' => '▣'],
        ['key' => 'settings-admins', 'label' => '관리자', 'href' => 'admin/settings/admins', 'group' => '설정', 'ic' => '⚙'],
        ['key' => 'settings-member-grades', 'label' => '회원등급 설정', 'href' => 'admin/settings/member-grades', 'group' => '설정', 'ic' => '◇'],
        ['key' => 'settings-seo', 'label' => 'SEO 설정', 'href' => 'admin/settings/seo', 'group' => '설정', 'ic' => '◎'],
        ['key' => 'settings-tracking', 'label' => '광고 스크립트', 'href' => 'admin/settings/tracking', 'group' => '설정', 'ic' => '◈'],
    ];
}

function admin_menu_by_key(string $key): ?array
{
    foreach (admin_menu_catalog() as $item) {
        if ($item['key'] === $key) {
            return $item;
        }
    }
    return null;
}

function admin_can_menu(string $key): bool
{
    static $cache = null;
    if ($cache === null) {
        $auth = new \App\Services\AuthService();
        $id = (int) ($auth->adminId() ?? 0);
        $cache = ['super' => false, 'keys' => ['dashboard']];
        if ($id > 0) {
            try {
                $svc = new \App\Services\AdminAccessService();
                $cache['super'] = $svc->isSuper($id);
                $cache['keys'] = $svc->allowedKeys($id);
            } catch (\Throwable) {
                $cache['super'] = true;
            }
        }
    }
    if ($key === 'dashboard' || $cache['super']) {
        return true;
    }
    return in_array($key, $cache['keys'], true);
}

/** @param array<int, array<string, mixed>> $items */
function admin_filter_menu_items(array $items): array
{
    return array_values(array_filter(
        $items,
        static fn (array $item): bool => admin_can_menu((string) ($item['key'] ?? ''))
    ));
}

/**
 * @param array<string, mixed> $override
 */
function seo_render_head(?string $pageKey = null, array $override = []): void
{
    try {
        echo (new \App\Services\SeoService())->headHtml($pageKey, $override);
    } catch (\Throwable) {
        $title = (string) ($override['title'] ?? $override['fallback_title'] ?? '라벨업 LABEL UP');
        echo '<title>' . e($title) . '</title>' . "\n";
    }
}

function marketing_render_head(): void
{
    try {
        echo (new \App\Services\SeoService())->marketingHeadHtml();
    } catch (\Throwable) {
    }
}

function marketing_render_body_start(): void
{
    try {
        echo (new \App\Services\SeoService())->marketingBodyStartHtml();
    } catch (\Throwable) {
    }
}

function marketing_render_body_end(): void
{
    try {
        echo (new \App\Services\SeoService())->marketingBodyEndHtml();
    } catch (\Throwable) {
    }
}
