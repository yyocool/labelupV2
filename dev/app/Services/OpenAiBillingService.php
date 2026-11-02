<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SiteSettingsRepository;
use RuntimeException;

/**
 * OpenAI 사용 금액(Costs API) + 관리자가 기록한 충전액으로 남은 잔액을 추정한다.
 * OpenAI는 선불 잔액 조회 API를 제공하지 않으므로 잔액은 "충전 합계 − 첫 충전일 이후 사용액"이다.
 * Costs API는 조직 Admin API 키(sk-admin-…)만 받으며, 날짜 버킷은 UTC 기준이다.
 */
final class OpenAiBillingService
{
    public const KEY = 'openai_billing';
    public const CACHE_KEY = 'openai_billing_cache';
    private const CACHE_TTL = 600;
    private const MAX_DAYS = 400;
    private const COSTS_URL = 'https://api.openai.com/v1/organization/costs';

    /** @return array<string, mixed> */
    public static function adminPayload(): array
    {
        $config = self::stored();
        $topups = $config['topups'];
        usort($topups, static fn (array $a, array $b): int => strcmp($b['date'], $a['date']) ?: strcmp($b['id'], $a['id']));

        return [
            'key_configured' => $config['admin_key'] !== '',
            'key_hint' => $config['admin_key'] !== '' ? '…' . substr($config['admin_key'], -4) : '',
            'low_threshold' => $config['low_threshold'],
            'topups' => $topups,
            'topup_total' => round(array_sum(array_column($topups, 'amount')), 2),
        ];
    }

    /** @param array<string, mixed> $input */
    public static function saveConfig(array $input): array
    {
        $config = self::stored();
        $keyIn = trim((string) ($input['admin_key'] ?? ''));
        if (!empty($input['clear_key'])) {
            $config['admin_key'] = '';
        } elseif ($keyIn !== '') {
            if (!str_starts_with($keyIn, 'sk-admin-') || strlen($keyIn) < 24 || strlen($keyIn) > 300 || preg_match('/\s/', $keyIn)) {
                throw new RuntimeException('Admin API 키는 sk-admin- 으로 시작합니다. OpenAI 조직 설정 › Admin keys 에서 발급해 주세요.');
            }
            $config['admin_key'] = $keyIn;
        }
        if (array_key_exists('low_threshold', $input)) {
            $threshold = (float) $input['low_threshold'];
            if ($threshold < 0 || $threshold > 100000) {
                throw new RuntimeException('잔액 경고 기준은 0 ~ 100,000 달러 사이로 입력해 주세요.');
            }
            $config['low_threshold'] = round($threshold, 2);
        }
        self::persist($config);

        return self::adminPayload();
    }

    /** @param array<string, mixed> $input */
    public static function addTopup(array $input): array
    {
        $date = trim((string) ($input['date'] ?? ''));
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, new \DateTimeZone('UTC'));
        if (!$dt || $dt->format('Y-m-d') !== $date) {
            throw new RuntimeException('충전일을 YYYY-MM-DD 형식으로 입력해 주세요.');
        }
        if ($dt->getTimestamp() > time() + 86400) {
            throw new RuntimeException('충전일은 오늘 이후로 입력할 수 없습니다.');
        }
        if ($dt->getTimestamp() < time() - self::MAX_DAYS * 86400) {
            throw new RuntimeException('충전일은 최근 ' . self::MAX_DAYS . '일 이내로 입력해 주세요.');
        }
        $amount = round((float) ($input['amount'] ?? 0), 2);
        if ($amount <= 0 || $amount > 100000) {
            throw new RuntimeException('충전 금액은 0보다 크고 100,000 달러 이하로 입력해 주세요.');
        }
        $memo = mb_substr(trim((string) ($input['memo'] ?? '')), 0, 100);

        $config = self::stored();
        $config['topups'][] = [
            'id' => bin2hex(random_bytes(6)),
            'date' => $date,
            'amount' => $amount,
            'memo' => $memo,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        self::persist($config);

        return self::adminPayload();
    }

    public static function deleteTopup(string $id): array
    {
        $config = self::stored();
        $before = count($config['topups']);
        $config['topups'] = array_values(array_filter($config['topups'], static fn (array $t): bool => $t['id'] !== $id));
        if (count($config['topups']) === $before) {
            throw new RuntimeException('삭제할 충전 기록을 찾지 못했습니다.');
        }
        self::persist($config);

        return self::adminPayload();
    }

    /** @return array<string, mixed> */
    public static function summary(bool $refresh = false): array
    {
        $config = self::stored();
        $topups = $config['topups'];
        $topupTotal = round(array_sum(array_column($topups, 'amount')), 2);
        $firstTopup = $topups !== [] ? min(array_column($topups, 'date')) : null;

        $base = [
            'configured' => $config['admin_key'] !== '',
            'topup_total' => $topupTotal,
            'topup_count' => count($topups),
            'first_topup_date' => $firstTopup,
            'low_threshold' => $config['low_threshold'],
        ];
        if (!$base['configured']) {
            return $base + ['ok' => false, 'error' => 'Admin API 키가 설정되지 않았습니다.'];
        }

        $today = gmdate('Y-m-d');
        $monthStart = gmdate('Y-m-01');
        $weekStart = gmdate('Y-m-d', strtotime($today . ' -6 days'));
        $from = min(array_filter([$monthStart, $weekStart, $firstTopup]));

        try {
            $days = self::dailyCosts($config['admin_key'], $from, $refresh);
        } catch (RuntimeException $e) {
            return $base + ['ok' => false, 'error' => $e->getMessage()];
        }

        $sum = static function (string $start) use ($days): float {
            $total = 0.0;
            foreach ($days['days'] as $date => $cost) {
                if ($date >= $start) {
                    $total += $cost;
                }
            }
            return round($total, 4);
        };
        $last7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = gmdate('Y-m-d', strtotime($today . " -{$i} days"));
            $last7[] = ['date' => $d, 'cost' => round($days['days'][$d] ?? 0.0, 4)];
        }

        $spent = $firstTopup !== null ? $sum($firstTopup) : null;
        $remaining = $spent !== null ? round($topupTotal - $spent, 2) : null;

        return $base + [
            'ok' => true,
            'fetched_at' => $days['fetched_at'],
            'today' => $today,
            'today_cost' => round($days['days'][$today] ?? 0.0, 4),
            'month_cost' => $sum($monthStart),
            'last7' => $last7,
            'spent_since_topup' => $spent,
            'remaining' => $remaining,
            'is_low' => $remaining !== null && $remaining <= $config['low_threshold'],
        ];
    }

    /** @return array{fetched_at: string, days: array<string, float>} */
    private static function dailyCosts(string $adminKey, string $from, bool $refresh): array
    {
        $repo = new SiteSettingsRepository();
        $cached = json_decode($repo->get(self::CACHE_KEY, ''), true);
        if (!$refresh && is_array($cached)
            && ($cached['from'] ?? '') === $from
            && ($cached['key'] ?? '') === hash('sha256', $adminKey)
            && time() - (int) ($cached['ts'] ?? 0) < self::CACHE_TTL
            && is_array($cached['days'] ?? null)) {
            return ['fetched_at' => (string) $cached['fetched_at'], 'days' => array_map('floatval', $cached['days'])];
        }

        $start = (new \DateTimeImmutable($from . ' 00:00:00', new \DateTimeZone('UTC')))->getTimestamp();
        $days = [];
        $page = null;
        for ($guard = 0; $guard < 5; $guard++) {
            $query = ['start_time' => $start, 'bucket_width' => '1d', 'limit' => 180];
            if ($page !== null) {
                $query['page'] = $page;
            }
            $data = self::get($adminKey, self::COSTS_URL . '?' . http_build_query($query));
            foreach ((array) ($data['data'] ?? []) as $bucket) {
                $date = gmdate('Y-m-d', (int) ($bucket['start_time'] ?? 0));
                foreach ((array) ($bucket['results'] ?? []) as $result) {
                    $days[$date] = ($days[$date] ?? 0.0) + (float) ($result['amount']['value'] ?? 0);
                }
            }
            if (empty($data['has_more']) || empty($data['next_page'])) {
                break;
            }
            $page = (string) $data['next_page'];
        }

        $fetchedAt = date('Y-m-d H:i:s');
        $repo->set(self::CACHE_KEY, (string) json_encode([
            'ts' => time(),
            'fetched_at' => $fetchedAt,
            'from' => $from,
            'key' => hash('sha256', $adminKey),
            'days' => $days,
        ]));

        return ['fetched_at' => $fetchedAt, 'days' => $days];
    }

    /** @return array<string, mixed> */
    private static function get(string $adminKey, string $url): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('서버에 curl 확장이 없어 OpenAI 사용량을 조회할 수 없습니다.');
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $adminKey, 'Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('OpenAI 연결에 실패했습니다. ' . $err);
        }
        $data = json_decode((string) $body, true);
        if ($status === 401 || $status === 403) {
            throw new RuntimeException('OpenAI가 Admin API 키를 거부했습니다. 키가 올바른지, 조직 Owner 권한으로 발급했는지 확인해 주세요.');
        }
        if ($status >= 400 || !is_array($data)) {
            $msg = is_array($data) ? (string) ($data['error']['message'] ?? '') : '';
            throw new RuntimeException('OpenAI 사용량 조회 실패 (HTTP ' . $status . ')' . ($msg !== '' ? ': ' . $msg : ''));
        }

        return $data;
    }

    /** @return array{admin_key: string, low_threshold: float, topups: array<int, array{id: string, date: string, amount: float, memo: string, created_at: string}>} */
    private static function stored(): array
    {
        $raw = json_decode((new SiteSettingsRepository())->get(self::KEY, ''), true);
        if (!is_array($raw)) {
            $raw = [];
        }
        $topups = [];
        foreach ((array) ($raw['topups'] ?? []) as $t) {
            if (!is_array($t) || empty($t['id']) || empty($t['date'])) {
                continue;
            }
            $topups[] = [
                'id' => (string) $t['id'],
                'date' => (string) $t['date'],
                'amount' => round((float) ($t['amount'] ?? 0), 2),
                'memo' => (string) ($t['memo'] ?? ''),
                'created_at' => (string) ($t['created_at'] ?? ''),
            ];
        }

        return [
            'admin_key' => trim((string) ($raw['admin_key'] ?? '')),
            'low_threshold' => round((float) ($raw['low_threshold'] ?? 10), 2),
            'topups' => $topups,
        ];
    }

    /** @param array<string, mixed> $config */
    private static function persist(array $config): void
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('OpenAI 잔액 설정을 저장하지 못했습니다.');
        }
        $repo = new SiteSettingsRepository();
        $repo->set(self::KEY, $json);
        $repo->set(self::CACHE_KEY, '');
    }
}
