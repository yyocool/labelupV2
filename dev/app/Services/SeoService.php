<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SeoRepository;
use RuntimeException;
use Throwable;

final class SeoService
{
    private SeoRepository $repo;

    /** @var array<string, string>|null */
    private static ?array $settingsCache = null;

    public function __construct()
    {
        $this->repo = new SeoRepository();
    }

    /** @return array<string, string> */
    public function settings(): array
    {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }
        try {
            self::$settingsCache = $this->repo->allSettings();
        } catch (Throwable) {
            self::$settingsCache = [];
        }
        return self::$settingsCache;
    }

    public function setting(string $key, string $default = ''): string
    {
        $v = $this->settings()[$key] ?? $default;
        return is_string($v) ? $v : $default;
    }

    /** @return array<int, array<string, mixed>> */
    public function pages(): array
    {
        try {
            return $this->repo->pages();
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<string, mixed> */
    public function adminSeoPayload(): array
    {
        $all = $this->settings();
        $seo = [];
        foreach ($all as $k => $v) {
            if (str_starts_with($k, 'seo.')) {
                $seo[substr($k, 4)] = $v;
            }
        }
        return [
            'seo' => $seo,
            'pages' => $this->pages(),
        ];
    }

    /** @return array<string, mixed> */
    public function adminMarketingPayload(): array
    {
        $all = $this->settings();
        $mkt = [];
        foreach ($all as $k => $v) {
            if (str_starts_with($k, 'mkt.')) {
                $mkt[substr($k, 4)] = $v;
            }
        }
        return [
            'marketing' => $mkt,
            'files' => $this->files(),
        ];
    }

    /** @param array<string, mixed> $input */
    public function saveSeo(array $input): void
    {
        $allowed = [
            'site_name', 'default_title', 'title_suffix', 'default_description', 'default_keywords',
            'default_og_image', 'og_site_name', 'twitter_card', 'twitter_site', 'canonical_base',
            'robots_default', 'robots_txt', 'sitemap_enabled', 'jsonld_enabled', 'locale',
            'favicon_url', 'org_name', 'org_url', 'org_logo', 'org_phone', 'org_email', 'org_same_as',
        ];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $val = $input[$key];
            if (in_array($key, ['sitemap_enabled', 'jsonld_enabled'], true)) {
                $val = !empty($val) && $val !== '0' && $val !== 'false' ? '1' : '0';
            } else {
                $val = trim((string) $val);
            }
            $this->repo->setSetting('seo.' . $key, $val);
        }
        self::$settingsCache = null;
    }

    /** @param array<string, mixed> $input */
    public function savePage(array $input): void
    {
        $key = trim((string) ($input['page_key'] ?? ''));
        $existing = $this->repo->page($key);
        if (!$existing) {
            throw new RuntimeException('없는 페이지입니다.');
        }
        $this->repo->savePage([
            'page_key' => $key,
            'label' => trim((string) ($input['label'] ?? $existing['label'])),
            'path_pattern' => trim((string) ($input['path_pattern'] ?? $existing['path_pattern'])),
            'title' => trim((string) ($input['title'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'keywords' => trim((string) ($input['keywords'] ?? '')),
            'og_title' => trim((string) ($input['og_title'] ?? '')),
            'og_description' => trim((string) ($input['og_description'] ?? '')),
            'og_image' => trim((string) ($input['og_image'] ?? '')),
            'og_type' => trim((string) ($input['og_type'] ?? 'website')) ?: 'website',
            'robots' => trim((string) ($input['robots'] ?? '')),
            'canonical_path' => trim((string) ($input['canonical_path'] ?? '')),
            'noindex' => !empty($input['noindex']) ? 1 : 0,
            'sitemap_include' => !empty($input['sitemap_include']) ? 1 : 0,
            'sitemap_changefreq' => $this->sanitizeFreq((string) ($input['sitemap_changefreq'] ?? 'weekly')),
            'sitemap_priority' => $this->sanitizePriority($input['sitemap_priority'] ?? 0.5),
            'extra_head' => (string) ($input['extra_head'] ?? ''),
            'sort_order' => (int) ($input['sort_order'] ?? $existing['sort_order'] ?? 0),
        ]);
    }

    /** @param array<string, mixed> $input */
    public function saveMarketing(array $input): void
    {
        $allowed = [
            'enabled', 'gtm_id', 'ga4_id', 'google_ads_id', 'google_site_verification',
            'naver_site_verification', 'naver_analytics_id', 'naver_wcs_id',
            'meta_pixel_id', 'kakao_pixel_id', 'bing_verification',
            'ads_txt', 'app_ads_txt', 'custom_head', 'custom_body_start', 'custom_body_end',
        ];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $val = $input[$key];
            if ($key === 'enabled') {
                $val = !empty($val) && $val !== '0' && $val !== 'false' ? '1' : '0';
            } elseif (in_array($key, ['gtm_id', 'ga4_id', 'google_ads_id', 'naver_analytics_id', 'naver_wcs_id', 'meta_pixel_id', 'kakao_pixel_id'], true)) {
                $val = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $val) ?? '';
            } elseif (in_array($key, ['google_site_verification', 'naver_site_verification', 'bing_verification'], true)) {
                $val = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $val) ?? '';
            } else {
                $val = (string) $val;
            }
            $this->repo->setSetting('mkt.' . $key, $val);
        }
        self::$settingsCache = null;
    }

    /** @return array<int, array<string, mixed>> */
    public function files(): array
    {
        try {
            return $this->repo->files();
        } catch (Throwable) {
            return [];
        }
    }

    public function saveFile(string $filename, string $content): int
    {
        $filename = $this->sanitizeFilename($filename);
        if ($content === '') {
            throw new RuntimeException('파일 내용을 입력해 주세요.');
        }
        $kind = str_ends_with(strtolower($filename), '.txt') ? 'txt' : 'html';
        return $this->repo->saveFile($filename, $content, $kind);
    }

    public function deleteFile(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('파일을 찾을 수 없습니다.');
        }
        $this->repo->deleteFile($id);
    }

    public function fileByName(string $filename): ?array
    {
        $filename = basename(str_replace('\\', '/', $filename));
        if (!$this->isSafeFilename($filename)) {
            return null;
        }
        try {
            return $this->repo->fileByName($filename);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    public function resolve(?string $pageKey = null, array $override = []): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rtrim((string) $path, '/') ?: '/';
        $page = null;
        if ($pageKey) {
            $page = $this->repo->page($pageKey);
        }
        if (!$page) {
            $page = $this->matchPage($path);
        }

        $siteName = $this->setting('seo.site_name', '라벨업 LABEL UP');
        $suffix = $this->setting('seo.title_suffix', ' — 라벨업');
        $fallbackTitle = trim((string) ($override['fallback_title'] ?? $override['title'] ?? ''));
        $pageTitle = trim((string) ($page['title'] ?? ''));
        $title = trim((string) ($override['title'] ?? ''));
        if ($title === '') {
            $title = $pageTitle !== '' ? $pageTitle : ($fallbackTitle !== '' ? $fallbackTitle : $this->setting('seo.default_title', $siteName));
        }
        if ($suffix !== '' && !str_contains($title, trim($suffix)) && $title !== $siteName) {
            // keep as-is if already has brand
        }

        $desc = trim((string) ($override['description'] ?? ''));
        if ($desc === '') {
            $desc = trim((string) ($page['description'] ?? ''));
        }
        if ($desc === '') {
            $desc = $this->setting('seo.default_description');
        }

        $keywords = trim((string) ($override['keywords'] ?? ($page['keywords'] ?? '')));
        if ($keywords === '') {
            $keywords = $this->setting('seo.default_keywords');
        }

        $ogTitle = trim((string) ($override['og_title'] ?? ($page['og_title'] ?? ''))) ?: $title;
        $ogDesc = trim((string) ($override['og_description'] ?? ($page['og_description'] ?? ''))) ?: $desc;
        $ogImage = trim((string) ($override['og_image'] ?? ($page['og_image'] ?? ''))) ?: $this->setting('seo.default_og_image');
        $ogType = trim((string) ($override['og_type'] ?? ($page['og_type'] ?? ''))) ?: 'website';

        $noindex = !empty($override['noindex']) || !empty($page['noindex']);
        $robots = trim((string) ($override['robots'] ?? ($page['robots'] ?? '')));
        if ($robots === '') {
            $robots = $noindex ? 'noindex,nofollow' : $this->setting('seo.robots_default', 'index,follow');
        }
        if ($noindex && !str_contains(strtolower($robots), 'noindex')) {
            $robots = 'noindex,nofollow';
        }

        $canonicalPath = trim((string) ($override['canonical_path'] ?? ($page['canonical_path'] ?? '')));
        if ($canonicalPath === '') {
            $canonicalPath = $path === '/' ? '/' : $path;
        }
        $canonical = $this->absoluteUrl($canonicalPath);
        $ogImageAbs = $ogImage !== '' ? $this->absoluteUrl($ogImage) : '';

        return [
            'page_key' => (string) ($page['page_key'] ?? $pageKey ?? ''),
            'site_name' => $siteName,
            'title' => $title,
            'description' => $desc,
            'keywords' => $keywords,
            'og_title' => $ogTitle,
            'og_description' => $ogDesc,
            'og_image' => $ogImageAbs,
            'og_type' => $ogType,
            'og_site_name' => $this->setting('seo.og_site_name', $siteName),
            'twitter_card' => $this->setting('seo.twitter_card', 'summary_large_image'),
            'twitter_site' => $this->setting('seo.twitter_site'),
            'robots' => $robots,
            'canonical' => $canonical,
            'locale' => $this->setting('seo.locale', 'ko_KR'),
            'favicon_url' => $this->setting('seo.favicon_url'),
            'extra_head' => (string) ($page['extra_head'] ?? ''),
            'jsonld' => $override['jsonld'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $override
     */
    public function headHtml(?string $pageKey = null, array $override = []): string
    {
        try {
            $m = $this->resolve($pageKey, $override);
        } catch (Throwable) {
            $title = (string) ($override['title'] ?? $override['fallback_title'] ?? '라벨업 LABEL UP');
            return '<title>' . e($title) . '</title>' . "\n";
        }

        $out = [];
        $out[] = '<title>' . e($m['title']) . '</title>';
        if ($m['description'] !== '') {
            $out[] = '<meta name="description" content="' . e($m['description']) . '">';
        }
        if ($m['keywords'] !== '') {
            $out[] = '<meta name="keywords" content="' . e($m['keywords']) . '">';
        }
        $out[] = '<meta name="robots" content="' . e($m['robots']) . '">';
        if ($m['canonical'] !== '') {
            $out[] = '<link rel="canonical" href="' . e($m['canonical']) . '">';
        }
        $fav = trim((string) $m['favicon_url']);
        if ($fav !== '') {
            $out[] = '<link rel="icon" href="' . e($this->absoluteUrl($fav)) . '">';
        }
        $out[] = '<meta property="og:type" content="' . e($m['og_type']) . '">';
        $out[] = '<meta property="og:site_name" content="' . e($m['og_site_name']) . '">';
        $out[] = '<meta property="og:title" content="' . e($m['og_title']) . '">';
        if ($m['og_description'] !== '') {
            $out[] = '<meta property="og:description" content="' . e($m['og_description']) . '">';
        }
        $out[] = '<meta property="og:url" content="' . e($m['canonical']) . '">';
        $out[] = '<meta property="og:locale" content="' . e($m['locale']) . '">';
        if ($m['og_image'] !== '') {
            $out[] = '<meta property="og:image" content="' . e($m['og_image']) . '">';
        }
        $out[] = '<meta name="twitter:card" content="' . e($m['twitter_card']) . '">';
        $out[] = '<meta name="twitter:title" content="' . e($m['og_title']) . '">';
        if ($m['og_description'] !== '') {
            $out[] = '<meta name="twitter:description" content="' . e($m['og_description']) . '">';
        }
        if ($m['og_image'] !== '') {
            $out[] = '<meta name="twitter:image" content="' . e($m['og_image']) . '">';
        }
        if ($m['twitter_site'] !== '') {
            $handle = $m['twitter_site'];
            if ($handle !== '' && !str_starts_with($handle, '@')) {
                $handle = '@' . $handle;
            }
            $out[] = '<meta name="twitter:site" content="' . e($handle) . '">';
        }

        $mkt = $this->settings();
        if (($mkt['mkt.enabled'] ?? '1') === '1') {
            foreach ([
                'google-site-verification' => $mkt['mkt.google_site_verification'] ?? '',
                'naver-site-verification' => $mkt['mkt.naver_site_verification'] ?? '',
                'msvalidate.01' => $mkt['mkt.bing_verification'] ?? '',
            ] as $name => $val) {
                $val = trim((string) $val);
                if ($val !== '') {
                    $out[] = '<meta name="' . e($name) . '" content="' . e($val) . '">';
                }
            }
        }

        if ($this->setting('seo.jsonld_enabled', '1') === '1') {
            $jsonld = $this->organizationJsonLd();
            if (is_array($m['jsonld'])) {
                if (!empty($m['jsonld']['image'])) {
                    $m['jsonld']['image'] = $this->absoluteUrl((string) $m['jsonld']['image']);
                }
                $jsonld = [$jsonld, $m['jsonld']];
            }
            $out[] = '<script type="application/ld+json">' . json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
        }

        $extra = trim((string) $m['extra_head']);
        if ($extra !== '') {
            $out[] = $extra;
        }

        return implode("\n", $out) . "\n";
    }

    public function marketingHeadHtml(): string
    {
        if ($this->setting('mkt.enabled', '1') !== '1') {
            return '';
        }
        $chunks = [];
        $gtm = $this->setting('mkt.gtm_id');
        if ($gtm !== '') {
            $chunks[] = "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','"
                . e($gtm) . "');</script>";
        }
        $ga4 = $this->setting('mkt.ga4_id');
        $ads = $this->setting('mkt.google_ads_id');
        if ($ga4 !== '' || $ads !== '') {
            $id = $ga4 !== '' ? $ga4 : $ads;
            $chunks[] = '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($id) . '"></script>';
            $chunks[] = '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());'
                . ($ga4 !== '' ? 'gtag("config","' . e($ga4) . '");' : '')
                . ($ads !== '' ? 'gtag("config","' . e($ads) . '");' : '')
                . '</script>';
        }
        $custom = trim($this->setting('mkt.custom_head'));
        if ($custom !== '') {
            $chunks[] = $custom;
        }
        return $chunks === [] ? '' : implode("\n", $chunks) . "\n";
    }

    public function marketingBodyStartHtml(): string
    {
        if ($this->setting('mkt.enabled', '1') !== '1') {
            return '';
        }
        $chunks = [];
        $gtm = $this->setting('mkt.gtm_id');
        if ($gtm !== '') {
            $chunks[] = '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . e($gtm)
                . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';
        }
        $custom = trim($this->setting('mkt.custom_body_start'));
        if ($custom !== '') {
            $chunks[] = $custom;
        }
        return $chunks === [] ? '' : implode("\n", $chunks) . "\n";
    }

    public function marketingBodyEndHtml(): string
    {
        if ($this->setting('mkt.enabled', '1') !== '1') {
            return '';
        }
        $chunks = [];
        $pixel = $this->setting('mkt.meta_pixel_id');
        if ($pixel !== '') {
            $chunks[] = "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','"
                . e($pixel) . "');fbq('track','PageView');</script>";
            $chunks[] = '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id='
                . e($pixel) . '&ev=PageView&noscript=1" alt=""></noscript>';
        }
        $naver = $this->setting('mkt.naver_analytics_id') ?: $this->setting('mkt.naver_wcs_id');
        if ($naver !== '') {
            $chunks[] = '<script src="//wcs.naver.net/wcslog.js"></script><script>if(!window.wcs_add){window.wcs_add={};}wcs_add["wa"]="'
                . e($naver) . '";if(!window._nasa){window._nasa={};}if(window.wcs){wcs.inflow();wcs_do();}</script>';
        }
        $kakao = $this->setting('mkt.kakao_pixel_id');
        if ($kakao !== '') {
            $chunks[] = '<script src="//t1.daumcdn.net/kas/static/kp.js"></script><script>if(window.kakaoPixel){kakaoPixel("'
                . e($kakao) . '").pageView();}</script>';
        }
        $custom = trim($this->setting('mkt.custom_body_end'));
        if ($custom !== '') {
            $chunks[] = $custom;
        }
        return $chunks === [] ? '' : implode("\n", $chunks) . "\n";
    }

    public function robotsTxt(): string
    {
        $txt = $this->setting('seo.robots_txt');
        if (trim($txt) === '') {
            $txt = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api/\n\nSitemap: /sitemap.xml\n";
        }
        $base = $this->siteBase();
        $txt = str_replace('Sitemap: /sitemap.xml', 'Sitemap: ' . $base . '/sitemap.xml', $txt);
        if (!str_contains($txt, 'Sitemap:') && $this->setting('seo.sitemap_enabled', '1') === '1') {
            $txt = rtrim($txt) . "\n\nSitemap: {$base}/sitemap.xml\n";
        }
        return $txt;
    }

    public function sitemapXml(): string
    {
        if ($this->setting('seo.sitemap_enabled', '1') !== '1') {
            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
        }
        $urls = [];
        foreach ($this->pages() as $page) {
            if (empty($page['sitemap_include']) || !empty($page['noindex'])) {
                continue;
            }
            $pattern = (string) $page['path_pattern'];
            if (str_contains($pattern, '{')) {
                continue;
            }
            $urls[] = [
                'loc' => $this->absoluteUrl($pattern),
                'changefreq' => (string) ($page['sitemap_changefreq'] ?? 'weekly'),
                'priority' => number_format((float) ($page['sitemap_priority'] ?? 0.5), 1, '.', ''),
                'lastmod' => $this->isoDate($page['updated_at'] ?? null),
            ];
        }
        try {
            foreach ($this->repo->sitemapCategories() as $cat) {
                $slug = trim((string) ($cat['slug'] ?? ''));
                if ($slug === '') {
                    continue;
                }
                $urls[] = [
                    'loc' => $this->absoluteUrl('/shop/products?category=' . rawurlencode($slug)),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                    'lastmod' => $this->isoDate($cat['updated_at'] ?? null),
                ];
            }
            foreach ($this->repo->sitemapProducts() as $p) {
                $urls[] = [
                    'loc' => $this->absoluteUrl('/shop/products/' . (int) $p['id']),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                    'lastmod' => $this->isoDate($p['updated_at'] ?? null),
                ];
            }
        } catch (Throwable) {
            // shop tables may be unavailable
        }

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        $seen = [];
        foreach ($urls as $u) {
            if (isset($seen[$u['loc']])) {
                continue;
            }
            $seen[$u['loc']] = true;
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . e($u['loc']) . '</loc>';
            if (!empty($u['lastmod'])) {
                $xml[] = '    <lastmod>' . e($u['lastmod']) . '</lastmod>';
            }
            $xml[] = '    <changefreq>' . e($u['changefreq']) . '</changefreq>';
            $xml[] = '    <priority>' . e($u['priority']) . '</priority>';
            $xml[] = '  </url>';
        }
        $xml[] = '</urlset>';
        return implode("\n", $xml) . "\n";
    }

    public function adsTxt(): string
    {
        return $this->setting('mkt.ads_txt');
    }

    public function appAdsTxt(): string
    {
        return $this->setting('mkt.app_ads_txt');
    }

    public function siteBase(): string
    {
        $base = trim($this->setting('seo.canonical_base'));
        if ($base === '') {
            $base = (string) app_config('url', '');
        }
        $base = rtrim($base, '/');
        if ($base === '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $base = ($https ? 'https://' : 'http://') . $host;
        }
        return $base;
    }

    public function absoluteUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return $this->siteBase() . '/';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        return $this->siteBase() . $path;
    }

    /** @return array<string, mixed> */
    private function organizationJsonLd(): array
    {
        $name = $this->setting('seo.org_name') ?: $this->setting('seo.site_name', '라벨업');
        $url = $this->setting('seo.org_url') ?: $this->siteBase();
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $url,
        ];
        $logo = $this->setting('seo.org_logo');
        if ($logo !== '') {
            $data['logo'] = $this->absoluteUrl($logo);
        }
        $phone = $this->setting('seo.org_phone');
        if ($phone !== '') {
            $data['telephone'] = $phone;
        }
        $email = $this->setting('seo.org_email');
        if ($email !== '') {
            $data['email'] = $email;
        }
        $same = trim($this->setting('seo.org_same_as'));
        if ($same !== '') {
            $links = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $same) ?: [])));
            if ($links !== []) {
                $data['sameAs'] = $links;
            }
        }
        return $data;
    }

    private function matchPage(string $path): ?array
    {
        $best = null;
        $bestLen = -1;
        foreach ($this->pages() as $page) {
            $pattern = (string) ($page['path_pattern'] ?? '');
            if ($pattern === '') {
                continue;
            }
            $norm = rtrim($pattern, '/') ?: '/';
            if ($norm === $path) {
                return $page;
            }
            $regex = '#^' . preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '[^/]+', preg_quote($norm, '#')) . '$#';
            if (preg_match($regex, $path) && strlen($norm) > $bestLen) {
                $best = $page;
                $bestLen = strlen($norm);
            }
        }
        return $best;
    }

    private function sanitizeFreq(string $freq): string
    {
        $freq = strtolower(trim($freq));
        $ok = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
        return in_array($freq, $ok, true) ? $freq : 'weekly';
    }

    private function sanitizePriority(mixed $v): string
    {
        $n = is_numeric($v) ? (float) $v : 0.5;
        $n = max(0.0, min(1.0, $n));
        return number_format($n, 1, '.', '');
    }

    private function sanitizeFilename(string $name): string
    {
        $name = basename(str_replace('\\', '/', trim($name)));
        if (!$this->isSafeFilename($name)) {
            throw new RuntimeException('파일명은 영문·숫자와 .html / .txt 만 사용할 수 있습니다.');
        }
        $reserved = ['robots.txt', 'sitemap.xml', 'index.html', 'index.php'];
        if (in_array(strtolower($name), $reserved, true)) {
            throw new RuntimeException('이 파일명은 사용할 수 없습니다.');
        }
        return $name;
    }

    private function isSafeFilename(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{1,160}\.(html|htm|txt)$/', $name);
    }

    private function isoDate(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return date('Y-m-d');
        }
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}
