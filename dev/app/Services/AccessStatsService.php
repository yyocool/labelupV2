<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AccessLogRepository;
use Throwable;

final class AccessStatsService
{
    private AccessLogRepository $logs;

    public function __construct()
    {
        $this->logs = new AccessLogRepository();
    }

    /** @param array<string, mixed> $query
     *  @return array<string, mixed>
     */
    public function filters(array $query): array
    {
        $range = (string) ($query['range'] ?? '7d');
        if (!in_array($range, ['today', 'yesterday', '7d', '30d', '90d', 'month', 'custom'], true)) {
            $range = '7d';
        }
        $today = date('Y-m-d');
        $from = $today;
        $to = $today;
        if ($range === 'yesterday') {
            $from = $to = date('Y-m-d', strtotime('-1 day'));
        } elseif ($range === '7d') {
            $from = date('Y-m-d', strtotime('-6 days'));
        } elseif ($range === '30d') {
            $from = date('Y-m-d', strtotime('-29 days'));
        } elseif ($range === '90d') {
            $from = date('Y-m-d', strtotime('-89 days'));
        } elseif ($range === 'month') {
            $from = date('Y-m-01');
        } elseif ($range === 'custom') {
            $from = $this->dateOr((string) ($query['from'] ?? ''), date('Y-m-d', strtotime('-6 days')));
            $to = $this->dateOr((string) ($query['to'] ?? ''), $today);
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            if (strtotime($to) - strtotime($from) > 86400 * 366) {
                $from = date('Y-m-d', strtotime($to . ' -366 days'));
            }
        }
        $section = (string) ($query['section'] ?? '');
        if (!array_key_exists($section, self::sections())) {
            $section = '';
        }
        $device = (string) ($query['device'] ?? '');
        if (!in_array($device, ['', 'desktop', 'mobile', 'tablet', 'bot'], true)) {
            $device = '';
        }
        $actor = (string) ($query['actor'] ?? '');
        if (!in_array($actor, ['', 'guest', 'member', 'admin'], true)) {
            $actor = '';
        }
        $http = (string) ($query['http'] ?? '');
        if (!in_array($http, ['', 'ok', 'err'], true)) {
            $http = '';
        }
        $grain = (string) ($query['grain'] ?? 'day');
        if (!in_array($grain, ['day', 'week', 'month'], true)) {
            $grain = 'day';
        }

        return [
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'from_dt' => $from . ' 00:00:00',
            'to_dt' => $to . ' 23:59:59',
            'bots' => (string) ($query['bots'] ?? '0') === '1' ? 1 : 0,
            'admin' => (string) ($query['admin'] ?? '0') === '1' ? 1 : 0,
            'section' => $section,
            'q' => mb_substr(trim((string) ($query['q'] ?? '')), 0, 80),
            'device' => $device,
            'actor' => $actor,
            'http' => $http,
            'grain' => $grain,
        ];
    }

    /** @param array<string, mixed> $filters
     *  @param array<string, mixed> $override
     */
    public function query(array $filters, array $override = [], bool $keepExtra = false): string
    {
        $range = (string) ($override['range'] ?? $filters['range']);
        $params = [
            'range' => $range,
            'bots' => (string) ($override['bots'] ?? $filters['bots']),
            'admin' => (string) ($override['admin'] ?? $filters['admin']),
        ];
        $section = (string) ($override['section'] ?? $filters['section']);
        if ($section !== '') {
            $params['section'] = $section;
        }
        if ($range === 'custom') {
            $params['from'] = (string) ($override['from'] ?? $filters['from']);
            $params['to'] = (string) ($override['to'] ?? $filters['to']);
        }
        if ($keepExtra) {
            foreach (['q', 'device', 'actor', 'http', 'grain'] as $key) {
                $value = (string) ($override[$key] ?? $filters[$key] ?? '');
                if ($value !== '') {
                    $params[$key] = $value;
                }
            }
        }

        return http_build_query($params);
    }

    /** @return array<string, string> */
    public static function sections(): array
    {
        return [
            '' => '전체 영역',
            'home' => '홈',
            'shop' => '쇼핑',
            'editor' => '편집기',
            'account' => '마이페이지',
            'auth' => '로그인·가입',
            'content' => '안내·FAQ',
            'qr' => 'QR쿠폰',
            'compat' => '호환코드',
            'partner' => '협력사',
            'admin' => '관리자',
            'other' => '기타',
        ];
    }

    public static function sectionLabel(string $section): string
    {
        return self::sections()[$section] ?? $section;
    }

    public static function deviceLabel(string $device): string
    {
        return match ($device) {
            'desktop' => 'PC',
            'mobile' => '모바일',
            'tablet' => '태블릿',
            'bot' => '봇',
            default => '기타',
        };
    }

    public static function channelLabel(string $channel): string
    {
        return match ($channel) {
            'direct' => '직접 방문',
            'search' => '검색',
            'social' => '소셜',
            'referral' => '외부 링크',
            default => '기타',
        };
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        $summary = $this->summary($filters);
        $previous = $this->summary($this->previousFilters($filters));
        $summary['bounce_rate'] = $this->rate((int) $summary['bounce_sessions'], (int) $summary['sessions']);
        $previous['bounce_rate'] = $this->rate((int) $previous['bounce_sessions'], (int) $previous['sessions']);
        $summary['pages_per_session'] = $this->per((int) $summary['pv'], (int) $summary['sessions']);
        $previous['pages_per_session'] = $this->per((int) $previous['pv'], (int) $previous['sessions']);
        [$where, $params] = $this->where($filters);
        $daily = $this->fillDays($this->logs->all(
            "SELECT DATE(visited_at) AS day, COUNT(*) AS pv,
                    COUNT(DISTINCT visitor_key) AS uv,
                    COUNT(DISTINCT session_key) AS sessions
             FROM access_logs WHERE {$where}
             GROUP BY DATE(visited_at) ORDER BY day",
            $params
        ), $filters['from'], $filters['to']);
        $hourly = $this->hoursFilled($this->logs->all(
            "SELECT HOUR(visited_at) AS hour, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs WHERE {$where} GROUP BY HOUR(visited_at)",
            $params
        ));
        $sections = $this->logs->all(
            "SELECT section, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs WHERE {$where} GROUP BY section ORDER BY pv DESC",
            $params
        );
        $pages = $this->logs->all(
            "SELECT path_key, MAX(title) AS title, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv,
                    AVG(dwell_ms) AS avg_dwell
             FROM access_logs WHERE {$where}
             GROUP BY path_key ORDER BY pv DESC LIMIT 10",
            $params
        );
        $devices = $this->logs->all(
            "SELECT device, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs WHERE {$where} GROUP BY device ORDER BY pv DESC",
            $params
        );
        $errors = $this->logs->all(
            "SELECT path_key, status_code, COUNT(*) AS pv
             FROM access_logs WHERE {$where} AND status_code >= 400
             GROUP BY path_key, status_code ORDER BY pv DESC LIMIT 8",
            $params
        );

        return [
            'summary' => $summary,
            'previous' => $previous,
            'prev_label' => $this->previousFilters($filters)['from'] . ' ~ ' . $this->previousFilters($filters)['to'],
            'daily' => $daily,
            'hourly' => $hourly,
            'sections' => $sections,
            'pages' => $pages,
            'devices' => $devices,
            'channels' => $this->channels($filters),
            'errors' => $errors,
            'visitors' => $this->newReturning($filters),
            'bots' => $this->botCount($filters),
        ];
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    public function trend(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $grain = (string) $filters['grain'];
        $bucket = match ($grain) {
            'week' => "DATE(DATE_SUB(visited_at, INTERVAL WEEKDAY(visited_at) DAY))",
            'month' => "DATE_FORMAT(visited_at, '%Y-%m')",
            default => 'DATE(visited_at)',
        };
        $rows = $this->logs->all(
            "SELECT {$bucket} AS bucket, COUNT(*) AS pv,
                    COUNT(DISTINCT visitor_key) AS uv,
                    COUNT(DISTINCT session_key) AS sessions,
                    COUNT(DISTINCT CASE WHEN actor_role = 'member' THEN user_id END) AS members,
                    AVG(dwell_ms) AS avg_dwell
             FROM access_logs WHERE {$where}
             GROUP BY {$bucket} ORDER BY bucket",
            $params
        );
        if ($grain === 'day') {
            $rows = $this->fillDays($rows, $filters['from'], $filters['to'], 'bucket');
        }

        return [
            'rows' => $rows,
            'summary' => $this->summary($filters),
            'previous' => $this->summary($this->previousFilters($filters)),
        ];
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    public function pages(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $like = $this->like($filters, $where, $params);
        $rows = $this->logs->all(
            "SELECT path_key, MAX(title) AS title, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv,
                    COUNT(DISTINCT session_key) AS sessions, AVG(dwell_ms) AS avg_dwell,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS errors
             FROM access_logs WHERE {$like['where']}
             GROUP BY path_key ORDER BY pv DESC LIMIT 80",
            $like['params']
        );
        $entries = $this->edgePages($filters, 'MIN');
        $exits = $this->edgePages($filters, 'MAX');
        $sections = $this->logs->all(
            "SELECT section, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv, AVG(dwell_ms) AS avg_dwell
             FROM access_logs WHERE {$where} GROUP BY section ORDER BY pv DESC",
            $params
        );

        return compact('rows', 'entries', 'exits', 'sections');
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    public function referrers(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $hosts = $this->logs->all(
            "SELECT referer_host, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv, COUNT(DISTINCT session_key) AS sessions
             FROM access_logs WHERE {$where}
             GROUP BY referer_host ORDER BY pv DESC LIMIT 40",
            $params
        );
        $utm = $this->logs->all(
            "SELECT utm_source, utm_medium, utm_campaign, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs WHERE {$where}
               AND (utm_source <> '' OR utm_medium <> '' OR utm_campaign <> '')
             GROUP BY utm_source, utm_medium, utm_campaign
             ORDER BY pv DESC LIMIT 40",
            $params
        );
        $landings = $this->logs->all(
            "SELECT path_key, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs WHERE {$where} AND referer_host <> ''
             GROUP BY path_key ORDER BY pv DESC LIMIT 15",
            $params
        );
        $engineSql = "CASE
                WHEN referer_host LIKE '%google.%' THEN 'Google'
                WHEN referer_host LIKE '%naver.%' AND referer_host NOT LIKE '%blog.naver%' AND referer_host NOT LIKE '%cafe.naver%' AND referer_host NOT LIKE '%post.naver%' THEN 'Naver'
                WHEN referer_host LIKE '%daum.%' THEN 'Daum'
                WHEN referer_host LIKE '%bing.%' THEN 'Bing'
                WHEN referer_host LIKE '%yahoo.%' THEN 'Yahoo'
                WHEN referer_host LIKE '%zum.com%' THEN 'Zum'
                WHEN referer_host LIKE '%duckduckgo.%' THEN 'DuckDuckGo'
                ELSE ''
             END";
        $engines = $this->logs->all(
            "SELECT {$engineSql} AS engine, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs WHERE {$where}
             GROUP BY {$engineSql} ORDER BY pv DESC",
            $params
        );
        $engines = array_values(array_filter($engines, static fn (array $row): bool => (string) ($row['engine'] ?? '') !== ''));

        return [
            'channels' => $this->channels($filters),
            'hosts' => $hosts,
            'utm' => $utm,
            'landings' => $landings,
            'engines' => $engines,
        ];
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    public function visitors(array $filters): array
    {
        [$where, $params] = $this->where($filters, 'l');
        $actors = $this->logs->all(
            "SELECT actor_role, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs l WHERE {$where} GROUP BY actor_role ORDER BY pv DESC",
            $params
        );
        $bucketSql = "CASE
                WHEN cnt = 1 THEN '1회'
                WHEN cnt BETWEEN 2 AND 3 THEN '2~3회'
                WHEN cnt BETWEEN 4 AND 10 THEN '4~10회'
                ELSE '11회 이상'
             END";
        $freq = $this->logs->all(
            "SELECT {$bucketSql} AS bucket, COUNT(*) AS visitors FROM (
                SELECT visitor_key, COUNT(*) AS cnt
                FROM access_logs l WHERE {$where} AND visitor_key <> ''
                GROUP BY visitor_key
             ) t GROUP BY {$bucketSql}",
            $params
        );
        $members = $this->logs->all(
            "SELECT l.user_id, u.email, IFNULL(p.name, '') AS name, COUNT(*) AS pv,
                    COUNT(DISTINCT DATE(l.visited_at)) AS days, MAX(l.visited_at) AS last_at
             FROM access_logs l
             LEFT JOIN users u ON u.id = l.user_id
             LEFT JOIN user_profiles p ON p.user_id = l.user_id AND p.deleted_at IS NULL
             WHERE {$where} AND l.actor_role = 'member' AND l.user_id IS NOT NULL
             GROUP BY l.user_id, u.email, p.name
             ORDER BY pv DESC LIMIT 30",
            $params
        );
        $anonymous = $this->logs->all(
            "SELECT visitor_key, ip, device, browser_name, os_name, COUNT(*) AS pv,
                    MIN(visited_at) AS first_at, MAX(visited_at) AS last_at
             FROM access_logs l
             WHERE {$where} AND l.actor_role = 'guest' AND visitor_key <> ''
             GROUP BY visitor_key, ip, device, browser_name, os_name
             ORDER BY pv DESC LIMIT 30",
            $params
        );

        return [
            'split' => $this->newReturning($filters),
            'actors' => $actors,
            'freq' => $freq,
            'members' => $members,
            'anonymous' => $anonymous,
        ];
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    public function environment(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $pull = function (string $column) use ($where, $params): array {
            return $this->logs->all(
                "SELECT {$column} AS label, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
                 FROM access_logs WHERE {$where} AND {$column} <> ''
                 GROUP BY {$column} ORDER BY pv DESC LIMIT 20",
                $params
            );
        };
        $botFilters = $filters;
        $botFilters['bots'] = 1;
        [$botWhere, $botParams] = $this->where($botFilters);
        $bots = $this->logs->all(
            "SELECT browser_name AS label, COUNT(*) AS pv, COUNT(DISTINCT ip) AS ips
             FROM access_logs WHERE {$botWhere} AND is_bot = 1
             GROUP BY browser_name ORDER BY pv DESC LIMIT 15",
            $botParams
        );

        return [
            'devices' => $this->logs->all(
                "SELECT device AS label, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
                 FROM access_logs WHERE {$where} GROUP BY device ORDER BY pv DESC",
                $params
            ),
            'os' => $pull('os_name'),
            'browsers' => $pull('browser_name'),
            'screens' => $pull('screen'),
            'viewports' => $pull('viewport'),
            'langs' => $pull('lang'),
            'timezones' => $pull('timezone'),
            'bots' => $bots,
        ];
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    public function hours(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $cells = $this->logs->all(
            "SELECT DAYOFWEEK(visited_at) AS dow, HOUR(visited_at) AS hour, COUNT(*) AS pv
             FROM access_logs WHERE {$where}
             GROUP BY DAYOFWEEK(visited_at), HOUR(visited_at)",
            $params
        );
        $byHour = $this->hoursFilled($this->logs->all(
            "SELECT HOUR(visited_at) AS hour, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs WHERE {$where} GROUP BY HOUR(visited_at)",
            $params
        ));
        $byDow = $this->logs->all(
            "SELECT DAYOFWEEK(visited_at) AS dow, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs WHERE {$where} GROUP BY DAYOFWEEK(visited_at)",
            $params
        );
        $grid = [];
        $max = 0;
        foreach ($cells as $cell) {
            $dow = (int) $cell['dow'];
            $hour = (int) $cell['hour'];
            $pv = (int) $cell['pv'];
            $grid[$dow][$hour] = $pv;
            $max = max($max, $pv);
        }
        $peak = null;
        foreach ($cells as $cell) {
            if ($peak === null || (int) $cell['pv'] > (int) $peak['pv']) {
                $peak = $cell;
            }
        }

        return [
            'grid' => $grid,
            'max' => $max,
            'by_hour' => $byHour,
            'by_dow' => $byDow,
            'peak' => $peak,
        ];
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    public function live(array $filters): array
    {
        [$scope, $scopeParams] = $this->scope($filters);
        $scopeSql = $scope !== '' ? ' AND ' . $scope : '';
        $m5 = date('Y-m-d H:i:s', time() - 300);
        $m15 = date('Y-m-d H:i:s', time() - 900);
        $m60 = date('Y-m-d H:i:s', time() - 3600);
        $active = $this->logs->one(
            "SELECT
                COUNT(DISTINCT CASE WHEN visited_at >= :m5 THEN visitor_key END) AS m5,
                COUNT(DISTINCT CASE WHEN visited_at >= :m15 THEN visitor_key END) AS m15,
                COUNT(DISTINCT CASE WHEN visited_at >= :m60 THEN visitor_key END) AS m60,
                SUM(CASE WHEN visited_at >= :m60b THEN 1 ELSE 0 END) AS pv60
             FROM access_logs
             WHERE visited_at >= :since AND visitor_key <> '' {$scopeSql}",
            array_merge($scopeParams, ['m5' => $m5, 'm15' => $m15, 'm60' => $m60, 'm60b' => $m60, 'since' => $m60])
        ) ?? [];
        $recent = $this->logs->all(
            "SELECT visited_at, path, path_key, title, section, actor_role, device, browser_name, os_name,
                    referer_host, ip, status_code, is_bot, user_id
             FROM access_logs
             WHERE visited_at >= :since {$scopeSql}
             ORDER BY id DESC LIMIT 40",
            array_merge($scopeParams, ['since' => $m60])
        );
        $top = $this->logs->all(
            "SELECT path_key, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs
             WHERE visited_at >= :since {$scopeSql}
             GROUP BY path_key ORDER BY pv DESC LIMIT 8",
            array_merge($scopeParams, ['since' => $m60])
        );

        return ['active' => $active, 'recent' => $recent, 'top' => $top];
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    public function logs(array $filters, int $page, int $perPage = 40): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        [$where, $params] = $this->logWhere($filters, 'l');
        $total = (int) (($this->logs->one("SELECT COUNT(*) AS c FROM access_logs l WHERE {$where}", $params)['c'] ?? 0));
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;
        $items = $this->logs->all(
            "SELECT l.*, u.email, IFNULL(p.name, '') AS name
             FROM access_logs l
             LEFT JOIN users u ON u.id = l.user_id
             LEFT JOIN user_profiles p ON p.user_id = l.user_id AND p.deleted_at IS NULL
             WHERE {$where}
             ORDER BY l.id DESC
             LIMIT {$offset}, {$perPage}",
            $params
        );

        return compact('items', 'total', 'page', 'pages');
    }

    /** @param array<string, mixed> $filters
     *  @return array<int, array<string, mixed>>
     */
    public function logsForExport(array $filters): array
    {
        [$where, $params] = $this->logWhere($filters, 'l');

        return $this->logs->all(
            "SELECT l.visited_at, l.section, l.path, l.title, l.status_code, l.dwell_ms, l.ip,
                    l.actor_role, l.device, l.os_name, l.browser_name, l.referer_host, l.utm_source,
                    l.utm_medium, l.utm_campaign, l.screen, l.lang, u.email, IFNULL(p.name, '') AS name
             FROM access_logs l
             LEFT JOIN users u ON u.id = l.user_id
             LEFT JOIN user_profiles p ON p.user_id = l.user_id AND p.deleted_at IS NULL
             WHERE {$where}
             ORDER BY l.id DESC
             LIMIT 5000",
            $params
        );
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, int|float>
     */
    private function summary(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $row = $this->logs->one(
            "SELECT COUNT(*) AS pv,
                    COUNT(DISTINCT visitor_key) AS uv,
                    COUNT(DISTINCT session_key) AS sessions,
                    COUNT(DISTINCT CASE WHEN actor_role = 'member' THEN user_id END) AS members,
                    SUM(CASE WHEN actor_role = 'guest' THEN 1 ELSE 0 END) AS guest_pv,
                    SUM(CASE WHEN actor_role = 'member' THEN 1 ELSE 0 END) AS member_pv,
                    AVG(dwell_ms) AS avg_dwell,
                    SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) AS errors
             FROM access_logs WHERE {$where}",
            $params
        ) ?? [];
        $bounce = $this->logs->one(
            "SELECT SUM(CASE WHEN cnt = 1 THEN 1 ELSE 0 END) AS bounce_sessions, COUNT(*) AS session_count
             FROM (
                SELECT session_key, COUNT(*) AS cnt
                FROM access_logs WHERE {$where} AND session_key <> ''
                GROUP BY session_key
             ) s",
            $params
        ) ?? [];

        return [
            'pv' => (int) ($row['pv'] ?? 0),
            'uv' => (int) ($row['uv'] ?? 0),
            'sessions' => (int) ($row['sessions'] ?? 0),
            'members' => (int) ($row['members'] ?? 0),
            'guest_pv' => (int) ($row['guest_pv'] ?? 0),
            'member_pv' => (int) ($row['member_pv'] ?? 0),
            'avg_dwell' => (int) round((float) ($row['avg_dwell'] ?? 0)),
            'errors' => (int) ($row['errors'] ?? 0),
            'bounce_sessions' => (int) ($bounce['bounce_sessions'] ?? 0),
            'session_count' => (int) ($bounce['session_count'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, int>
     */
    private function newReturning(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $row = $this->logs->one(
            "SELECT
                SUM(CASE WHEN first_at >= :fromDt AND first_at <= :toDt THEN 1 ELSE 0 END) AS new_visitors,
                SUM(CASE WHEN first_at < :fromDt THEN 1 ELSE 0 END) AS returning_visitors
             FROM (
                SELECT v.visitor_key, (SELECT MIN(h.visited_at) FROM access_logs h WHERE h.visitor_key = v.visitor_key) AS first_at
                FROM (
                    SELECT DISTINCT visitor_key FROM access_logs WHERE {$where} AND visitor_key <> ''
                ) v
             ) t",
            $params
        ) ?? [];

        return [
            'new_visitors' => (int) ($row['new_visitors'] ?? 0),
            'returning_visitors' => (int) ($row['returning_visitors'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $filters */
    private function botCount(array $filters): int
    {
        $copy = $filters;
        $copy['bots'] = 1;
        [$where, $params] = $this->where($copy);
        $row = $this->logs->one(
            "SELECT COUNT(*) AS c FROM access_logs WHERE {$where} AND is_bot = 1",
            $params
        );

        return (int) ($row['c'] ?? 0);
    }

    /** @param array<string, mixed> $filters
     *  @return array<int, array<string, mixed>>
     */
    private function channels(array $filters): array
    {
        [$where, $params] = $this->where($filters);
        $expr = "CASE
            WHEN referer_host = '' THEN 'direct'
            WHEN referer_host IN ('t.co', 'x.com', 'youtu.be')
              OR referer_host LIKE '%facebook.%' OR referer_host LIKE '%instagram.%'
              OR referer_host LIKE '%youtube.%' OR referer_host LIKE '%twitter.%'
              OR referer_host LIKE '%threads.%' OR referer_host LIKE '%linkedin.%'
              OR referer_host LIKE '%pinterest.%' OR referer_host LIKE '%kakao%'
              OR referer_host LIKE '%blog.naver%' OR referer_host LIKE '%cafe.naver%'
              OR referer_host LIKE '%post.naver%' THEN 'social'
            WHEN referer_host LIKE '%google.%' OR referer_host LIKE '%naver.%'
              OR referer_host LIKE '%daum.%' OR referer_host LIKE '%bing.%'
              OR referer_host LIKE '%yahoo.%' OR referer_host LIKE '%zum.com%'
              OR referer_host LIKE '%duckduckgo.%' THEN 'search'
            ELSE 'referral'
        END";

        return $this->logs->all(
            "SELECT {$expr} AS channel, COUNT(*) AS pv, COUNT(DISTINCT visitor_key) AS uv
             FROM access_logs WHERE {$where}
             GROUP BY {$expr} ORDER BY pv DESC",
            $params
        );
    }

    /** @param array<string, mixed> $filters
     *  @return array<int, array<string, mixed>>
     */
    private function edgePages(array $filters, string $fn): array
    {
        $fn = $fn === 'MAX' ? 'MAX' : 'MIN';
        [$where, $params] = $this->where($filters);
        return $this->logs->all(
            "SELECT a.path_key, COUNT(*) AS pv, COUNT(DISTINCT a.visitor_key) AS uv
             FROM access_logs a
             INNER JOIN (
                SELECT session_key, {$fn}(id) AS edge_id
                FROM access_logs WHERE {$where} AND session_key <> ''
                GROUP BY session_key
             ) e ON a.id = e.edge_id
             GROUP BY a.path_key ORDER BY pv DESC LIMIT 15",
            $params
        );
    }

    /** @param array<string, mixed> $filters
     *  @param array<string, mixed> $params
     *  @return array{where:string,params:array<string,mixed>}
     */
    private function like(array $filters, string $where, array $params): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q === '') {
            return ['where' => $where, 'params' => $params];
        }
        $params['q'] = '%' . $q . '%';

        return [
            'where' => $where . ' AND (path LIKE :q OR path_key LIKE :q OR title LIKE :q)',
            'params' => $params,
        ];
    }

    /** @param array<string, mixed> $filters
     *  @return array{0:string,1:array<string,mixed>}
     */
    private function logWhere(array $filters, string $alias): array
    {
        [$where, $params] = $this->where($filters, $alias);
        $p = $alias . '.';
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $params['q'] = '%' . $q . '%';
            $extra = "({$p}path LIKE :q OR {$p}path_key LIKE :q OR {$p}ip LIKE :q OR {$p}title LIKE :q OR {$p}referer_host LIKE :q OR {$p}browser_name LIKE :q OR {$p}os_name LIKE :q OR {$p}user_agent LIKE :q";
            if (ctype_digit($q)) {
                $params['uid'] = (int) $q;
                $extra .= " OR {$p}user_id = :uid";
            }
            $where .= ' AND ' . $extra . ')';
        }
        if (($filters['device'] ?? '') !== '') {
            $params['device'] = $filters['device'];
            $where .= " AND {$p}device = :device";
        }
        if (($filters['actor'] ?? '') !== '') {
            $params['actor'] = $filters['actor'];
            $where .= " AND {$p}actor_role = :actor";
        }
        if (($filters['http'] ?? '') === 'err') {
            $where .= " AND {$p}status_code >= 400";
        } elseif (($filters['http'] ?? '') === 'ok') {
            $where .= " AND ({$p}status_code IS NULL OR {$p}status_code < 400)";
        }

        return [$where, $params];
    }

    /** @param array<string, mixed> $filters
     *  @return array{0:string,1:array<string,mixed>}
     */
    private function where(array $filters, string $alias = ''): array
    {
        $p = $alias !== '' ? $alias . '.' : '';
        $sql = $p . 'visited_at >= :fromDt AND ' . $p . 'visited_at <= :toDt';
        $params = ['fromDt' => $filters['from_dt'], 'toDt' => $filters['to_dt']];
        [$scope, $scopeParams] = $this->scope($filters, $alias);
        if ($scope !== '') {
            $sql .= ' AND ' . $scope;
            $params = array_merge($params, $scopeParams);
        }

        return [$sql, $params];
    }

    /** @param array<string, mixed> $filters
     *  @return array{0:string,1:array<string,mixed>}
     */
    private function scope(array $filters, string $alias = ''): array
    {
        $p = $alias !== '' ? $alias . '.' : '';
        $parts = [];
        $params = [];
        if (empty($filters['bots'])) {
            $parts[] = $p . 'is_bot = 0';
        }
        if (empty($filters['admin']) && ($filters['section'] ?? '') !== 'admin') {
            $parts[] = $p . "section <> 'admin' AND " . $p . "actor_role <> 'admin'";
        }
        if (($filters['section'] ?? '') !== '') {
            $parts[] = $p . 'section = :section';
            $params['section'] = $filters['section'];
        }

        return [implode(' AND ', $parts), $params];
    }

    /** @param array<string, mixed> $filters
     *  @return array<string, mixed>
     */
    private function previousFilters(array $filters): array
    {
        $start = strtotime((string) $filters['from_dt']) ?: time();
        $end = strtotime((string) $filters['to_dt']) ?: time();
        $len = max(1, $end - $start + 1);
        $prevEnd = $start - 1;
        $prevStart = $prevEnd - $len + 1;
        $copy = $filters;
        $copy['from_dt'] = date('Y-m-d H:i:s', $prevStart);
        $copy['to_dt'] = date('Y-m-d H:i:s', $prevEnd);
        $copy['from'] = date('Y-m-d', $prevStart);
        $copy['to'] = date('Y-m-d', $prevEnd);

        return $copy;
    }

    /** @param array<int, array<string, mixed>> $rows
     *  @return array<int, array<string, mixed>>
     */
    private function fillDays(array $rows, string $from, string $to, string $key = 'day'): array
    {
        $map = [];
        foreach ($rows as $row) {
            $map[(string) ($row[$key] ?? $row['day'] ?? '')] = $row;
        }
        $out = [];
        $cursor = strtotime($from . ' 00:00:00');
        $end = strtotime($to . ' 00:00:00');
        if ($cursor === false || $end === false) {
            return $rows;
        }
        $guard = 0;
        while ($cursor <= $end && $guard < 400) {
            $day = date('Y-m-d', $cursor);
            $out[] = $map[$day] ?? [$key => $day, 'day' => $day, 'pv' => 0, 'uv' => 0, 'sessions' => 0, 'members' => 0, 'avg_dwell' => 0];
            $cursor = strtotime('+1 day', $cursor) ?: ($cursor + 86400);
            $guard++;
        }

        return $out;
    }

    /** @param array<int, array<string, mixed>> $rows
     *  @return array<int, array<string, mixed>>
     */
    private function hoursFilled(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['hour']] = $row;
        }
        $out = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $out[] = $map[$hour] ?? ['hour' => $hour, 'pv' => 0, 'uv' => 0];
        }

        return $out;
    }

    private function rate(int $part, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round($part / $total * 100, 1);
    }

    private function per(int $part, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round($part / $total, 2);
    }

    private function dateOr(string $value, string $fallback): string
    {
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return $fallback;
        }
        $time = strtotime($value . ' 00:00:00');

        return $time === false ? $fallback : date('Y-m-d', $time);
    }

    public function failureMessage(Throwable $e): string
    {
        $message = $e->getMessage();
        if (str_contains($message, 'access_logs') || str_contains($message, 'Base table')) {
            return '접속 로그 테이블이 아직 없습니다. 마이그레이션을 적용한 뒤 다시 열어 주세요.';
        }

        return '접속 통계를 불러오지 못했습니다. 잠시 후 다시 시도해 주세요.';
    }
}
