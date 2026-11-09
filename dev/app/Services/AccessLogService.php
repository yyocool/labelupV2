<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AccessLogRepository;
use Throwable;

final class AccessLogService
{
    private const VISITOR_COOKIE = 'lup_vid';

    private AccessLogRepository $logs;

    public function __construct()
    {
        $this->logs = new AccessLogRepository();
    }

    public static function ensureVisitorCookie(): string
    {
        $existing = (string) ($_COOKIE[self::VISITOR_COOKIE] ?? '');
        if (preg_match('/^[a-f0-9]{32}$/', $existing) === 1) {
            return $existing;
        }
        try {
            $id = bin2hex(random_bytes(16));
        } catch (Throwable) {
            $id = substr(hash('sha256', uniqid('', true)), 0, 32);
        }
        if (!headers_sent()) {
            $params = [
                'expires' => time() + 86400 * 400,
                'path' => '/',
                'secure' => self::https(),
                'httponly' => true,
                'samesite' => 'Lax',
            ];
            $domain = site_cookie_domain();
            if ($domain !== '') {
                $params['domain'] = $domain;
            }
            setcookie(self::VISITOR_COOKIE, $id, $params);
        }
        $_COOKIE[self::VISITOR_COOKIE] = $id;

        return $id;
    }

    public function beginRequest(): int
    {
        try {
            if (!$this->shouldLogPage(request_method(), self::requestPath())) {
                return 0;
            }
            $row = $this->baseRow(self::requestPath(), (string) ($_SERVER['HTTP_REFERER'] ?? ''), (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
            $row['method'] = 'GET';
            $row['query_string'] = $this->cleanQuery((string) ($_SERVER['QUERY_STRING'] ?? ''));
            $row['status_code'] = 200;
            $utm = $this->utmFromQuery((string) ($_SERVER['QUERY_STRING'] ?? ''));
            $row['utm_source'] = $utm['source'];
            $row['utm_medium'] = $utm['medium'];
            $row['utm_campaign'] = $utm['campaign'];

            return $this->logs->insert($row);
        } catch (Throwable) {
            return 0;
        }
    }

    public function finishRequest(int $id, float $startedAt): void
    {
        if ($id <= 0) {
            return;
        }
        try {
            $status = http_response_code();
            $ms = (int) round((microtime(true) - $startedAt) * 1000);
            $this->logs->updateById($id, [
                'status_code' => $status > 0 ? $status : 200,
                'duration_ms' => max(0, min(600000, $ms)),
            ]);
        } catch (Throwable) {
        }
    }

    /** @param array<string, mixed> $payload */
    public function beacon(array $payload): void
    {
        $kind = (string) ($payload['kind'] ?? 'ping');
        if (!in_array($kind, ['ping', 'leave'], true)) {
            return;
        }
        $path = self::normalizeClientPath((string) ($payload['path'] ?? '/'));
        if ($path === '' || str_starts_with($path, '/api/')) {
            return;
        }
        $visitor = self::ensureVisitorCookie();
        $since = date('Y-m-d H:i:s', time() - 900);
        $pathKey = self::pathKey($path);
        $id = 0;
        try {
            $id = $this->logs->recentId($visitor, $path, $pathKey, $since);
        } catch (Throwable) {
            return;
        }

        $fields = $this->clientFields($payload, $kind === 'leave');
        if ($id > 0) {
            if ($fields !== []) {
                try {
                    $this->logs->updateById($id, $fields);
                } catch (Throwable) {
                }
            }
            return;
        }
        if ($kind === 'leave') {
            return;
        }
        try {
            $ip = self::clientIp();
            if ($ip !== '' && $this->logs->countSinceIp($ip, date('Y-m-d H:i:s', time() - 60)) >= 90) {
                return;
            }
            $referer = (string) ($payload['referrer'] ?? '');
            $row = $this->baseRow($path, $referer, (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
            foreach ($fields as $key => $value) {
                $row[$key] = $value;
            }
            $row['method'] = 'GET';
            $row['status_code'] = 200;
            $this->logs->insert($row);
        } catch (Throwable) {
        }
    }

    public static function requestPath(): string
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

        return self::normalizeClientPath((string) $path);
    }

    public static function normalizeClientPath(string $path): string
    {
        $only = parse_url($path, PHP_URL_PATH);
        $path = is_string($only) && $only !== '' ? $only : $path;
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        if ($path === '/editor' || str_starts_with($path, '/editor/')) {
            return '/editor';
        }

        return self::clip($path, 255);
    }

    public static function pathKey(string $path): string
    {
        $path = self::normalizeClientPath($path);
        if ($path === '/editor') {
            return '/editor';
        }
        $parts = explode('/', trim($path, '/'));
        $prev = '';
        foreach ($parts as $i => $part) {
            if ($part === '') {
                continue;
            }
            $replace = false;
            if (preg_match('/^\d+$/', $part) === 1 || preg_match('/^[0-9a-f]{8}-[0-9a-f-]{13,}$/i', $part) === 1) {
                $replace = true;
            } elseif ($prev === 'qr-coupon') {
                $replace = true;
            } elseif (strlen($part) >= 24 && preg_match('/^[A-Za-z0-9_-]+$/', $part) === 1) {
                $replace = true;
            }
            $prev = $part;
            if ($replace) {
                $parts[$i] = '{id}';
            }
        }
        $key = $parts === [] || $parts === [''] ? '/' : '/' . implode('/', $parts);

        return self::clip($key, 180);
    }

    public static function sectionFor(string $path): string
    {
        return match (true) {
            $path === '/' => 'home',
            str_starts_with($path, '/shop') => 'shop',
            str_starts_with($path, '/editor') => 'editor',
            str_starts_with($path, '/admin') => 'admin',
            str_starts_with($path, '/account'), str_starts_with($path, '/projects'), str_starts_with($path, '/locker'), str_starts_with($path, '/trash') => 'account',
            $path === '/login', $path === '/logout', str_starts_with($path, '/register'), str_starts_with($path, '/reset-password'), str_starts_with($path, '/auth') => 'auth',
            str_starts_with($path, '/faq'), str_starts_with($path, '/about'), str_starts_with($path, '/service'), str_starts_with($path, '/terms'), str_starts_with($path, '/privacy'), str_starts_with($path, '/company') => 'content',
            str_starts_with($path, '/qr-coupon') => 'qr',
            str_starts_with($path, '/compat') => 'compat',
            str_starts_with($path, '/partner') => 'partner',
            default => 'other',
        };
    }

    public static function clientIp(): string
    {
        $xff = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($xff !== '') {
            foreach (explode(',', $xff) as $part) {
                $part = trim($part);
                if (filter_var($part, FILTER_VALIDATE_IP)) {
                    return $part;
                }
            }
        }
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }

    /** @return array{device:string,os:string,browser:string,bot:bool} */
    public static function parseAgent(string $ua): array
    {
        $bot = preg_match('/bot|crawler|spider|slurp|facebookexternalhit|wget|curl\/|python-|headless|semrush|ahrefs|petalbot|bytespider|gptbot|claudebot|bingpreview|yandex|duckduckbot|baiduspider|applebot|preview/i', $ua) === 1;
        $browser = '기타';
        if (preg_match('/Edg\//', $ua) === 1) {
            $browser = 'Edge';
        } elseif (preg_match('/OPR\/|Opera/', $ua) === 1) {
            $browser = 'Opera';
        } elseif (preg_match('/SamsungBrowser/', $ua) === 1) {
            $browser = '삼성 인터넷';
        } elseif (preg_match('/Chrome\//', $ua) === 1 && preg_match('/Chromium/', $ua) !== 1) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\//', $ua) === 1) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\//', $ua) === 1 && preg_match('/Chrome\//', $ua) !== 1) {
            $browser = 'Safari';
        } elseif (preg_match('/MSIE|Trident/', $ua) === 1) {
            $browser = 'IE';
        }
        $os = '기타';
        if (preg_match('/Windows/', $ua) === 1) {
            $os = 'Windows';
        } elseif (preg_match('/Android/', $ua) === 1) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad|iOS/', $ua) === 1) {
            $os = 'iOS';
        } elseif (preg_match('/Mac OS X|Macintosh/', $ua) === 1) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $ua) === 1) {
            $os = 'Linux';
        }
        $device = 'desktop';
        if ($bot) {
            $device = 'bot';
        } elseif (preg_match('/iPad|Tablet/', $ua) === 1) {
            $device = 'tablet';
        } elseif (preg_match('/Mobile|Android|iPhone/', $ua) === 1) {
            $device = 'mobile';
        }

        return ['device' => $device, 'os' => $os, 'browser' => $browser, 'bot' => $bot];
    }

    private function shouldLogPage(string $method, string $path): bool
    {
        if (strtoupper($method) !== 'GET') {
            return false;
        }
        if ($path === '' || str_starts_with($path, '/api/')) {
            return false;
        }
        if (preg_match('/\.(css|js|map|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|eot|pdf|zip|mp4|webm|txt|xml)$/i', $path) === 1) {
            return false;
        }
        $purpose = strtolower((string) ($_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_PURPOSE'] ?? ''));
        if ($purpose === 'prefetch' || $purpose === 'preview') {
            return false;
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function baseRow(string $path, string $referer, string $ua): array
    {
        $path = self::normalizeClientPath($path);
        $agent = self::parseAgent($ua);
        $auth = new AuthService();
        $memberId = (int) ($auth->id() ?? 0);
        $adminId = (int) ($auth->adminId() ?? 0);
        $userId = null;
        $role = 'guest';
        if (str_starts_with($path, '/admin') && $adminId > 0) {
            $userId = $adminId;
            $role = 'admin';
        } elseif ($memberId > 0) {
            $userId = $memberId;
            $role = 'member';
        } elseif ($adminId > 0) {
            $userId = $adminId;
            $role = 'admin';
        }
        $now = date('Y-m-d H:i:s');
        $visitor = (string) ($_COOKIE[self::VISITOR_COOKIE] ?? '');
        if (preg_match('/^[a-f0-9]{32}$/', $visitor) !== 1) {
            $visitor = substr(hash('sha256', self::clientIp() . '|' . $ua), 0, 32);
        }
        $session = session_id();
        $sessionKey = $session !== '' ? substr(hash('sha256', $session), 0, 40) : $visitor;
        $ref = $this->refererParts($referer);
        $lang = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''))[0] ?? ''));

        return [
            'visited_at' => $now,
            'section' => self::sectionFor($path),
            'method' => 'GET',
            'path' => $path,
            'path_key' => self::pathKey($path),
            'query_string' => null,
            'title' => '',
            'status_code' => null,
            'duration_ms' => null,
            'dwell_ms' => null,
            'ip' => self::clip(self::clientIp(), 45),
            'visitor_key' => $visitor,
            'session_key' => $sessionKey,
            'user_id' => $userId,
            'actor_role' => $role,
            'is_bot' => $agent['bot'] ? 1 : 0,
            'device' => $agent['device'],
            'os_name' => self::clip($agent['os'], 40),
            'browser_name' => self::clip($agent['browser'], 40),
            'referer_host' => self::clip($ref['host'], 190),
            'referer' => $ref['raw'] !== '' ? self::clip($ref['raw'], 255) : null,
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'lang' => self::clip($lang, 20),
            'screen' => '',
            'viewport' => '',
            'timezone' => '',
            'user_agent' => $ua !== '' ? self::clip(self::utf8($ua), 255) : null,
            'created_at' => $now,
        ];
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    private function clientFields(array $payload, bool $withDwell): array
    {
        $fields = [];
        $screen = $this->size((string) ($payload['screen'] ?? ''));
        $viewport = $this->size((string) ($payload['viewport'] ?? ''));
        $lang = self::clip(strtolower(trim((string) ($payload['lang'] ?? ''))), 20);
        $tz = self::clip(trim((string) ($payload['tz'] ?? '')), 40);
        $title = self::clip(trim(self::utf8((string) ($payload['title'] ?? ''))), 120);
        if ($screen !== '') {
            $fields['screen'] = $screen;
        }
        if ($viewport !== '') {
            $fields['viewport'] = $viewport;
        }
        if ($lang !== '') {
            $fields['lang'] = $lang;
        }
        if ($tz !== '') {
            $fields['timezone'] = $tz;
        }
        if ($title !== '') {
            $fields['title'] = $title;
        }
        if ($withDwell) {
            $dwell = (int) ($payload['dwell_ms'] ?? 0);
            if ($dwell > 0) {
                $fields['dwell_ms'] = min(1800000, $dwell);
            }
        }

        return $fields;
    }

    /** @return array{host:string,raw:string} */
    private function refererParts(string $referer): array
    {
        $referer = trim($referer);
        if ($referer === '') {
            return ['host' => '', 'raw' => ''];
        }
        $host = parse_url($referer, PHP_URL_HOST);
        $host = is_string($host) ? strtolower($host) : '';
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        $own = request_host();
        if (str_starts_with($own, 'www.')) {
            $own = substr($own, 4);
        }
        if ($host === '' || $host === $own || $host === 'labelup.co.kr') {
            $host = '';
        }

        return ['host' => $host, 'raw' => self::utf8($referer)];
    }

    /** @return array{source:string,medium:string,campaign:string} */
    private function utmFromQuery(string $query): array
    {
        $parsed = [];
        if ($query !== '') {
            parse_str($query, $parsed);
        }
        $source = trim((string) ($parsed['utm_source'] ?? ''));
        $medium = trim((string) ($parsed['utm_medium'] ?? ''));
        $campaign = trim((string) ($parsed['utm_campaign'] ?? ''));
        if ($source === '' && !empty($parsed['gclid'])) {
            $source = 'google';
            $medium = $medium !== '' ? $medium : 'cpc';
        }
        if ($source === '' && !empty($parsed['fbclid'])) {
            $source = 'facebook';
            $medium = $medium !== '' ? $medium : 'paid';
        }

        return [
            'source' => self::clip($source, 80),
            'medium' => self::clip($medium, 80),
            'campaign' => self::clip($campaign, 120),
        ];
    }

    private function cleanQuery(string $query): ?string
    {
        if ($query === '') {
            return null;
        }
        parse_str($query, $parsed);
        if (!is_array($parsed)) {
            return null;
        }
        foreach (['password', 'passwd', 'pwd', 'token', 'access_token', 'refresh_token', 'secret', 'otp', 'auth', 'key', 'code', 'session'] as $drop) {
            unset($parsed[$drop]);
        }
        $built = http_build_query($parsed);

        return $built === '' ? null : self::clip($built, 255);
    }

    private function size(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^\d{2,5}x\d{2,5}$/', $value) !== 1) {
            return '';
        }

        return self::clip($value, 20);
    }

    private static function clip(string $value, int $max): string
    {
        $value = self::utf8(trim($value));
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max);
    }

    private static function utf8(string $value): string
    {
        $clean = preg_replace('/[\xF0-\xF7][\x80-\xBF]{3}/', '', $value);

        return $clean ?? $value;
    }

    private static function https(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
            || is_labelup_public_host();
    }
}
