<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Repositories\SiteSettingsRepository;
use RuntimeException;

/**
 * 운영 문자알림. 설정은 site_settings.ops_notify_sms 에 두고,
 * DB 장애 때도 보낼 수 있도록 수신자·SENS 키 사본을 storage/logs 에 0600 으로 남긴다.
 * notify() 는 절대 예외를 던지지 않는다 (결제·문의 흐름을 막지 않기 위해).
 */
final class OpsSmsNotifier
{
    public const KEY = 'ops_notify_sms';
    private const MAX_RECIPIENTS = 10;
    private const LOG_LIMIT = 100;

    /** @return array<string, array{label: string, desc: string, critical: bool, default_on: bool, cooldown: int}> */
    public static function events(): array
    {
        return [
            'site_down' => ['label' => '사이트 장애', 'desc' => '5분마다 사이트 접속과 DB 연결을 점검해 2회 연속 실패하면 알리고, 복구되면 복구 알림을 보냅니다.', 'critical' => true, 'default_on' => true, 'cooldown' => 30],
            'ai_balance_low' => ['label' => 'AI API 잔액 경고', 'desc' => 'Chat GPT 추정 잔액이 환경설정의 경고 기준 이하로 떨어지면 알립니다.', 'critical' => true, 'default_on' => true, 'cooldown' => 720],
            'ai_api_error' => ['label' => 'AI API 오류', 'desc' => 'OpenAI가 잔액 소진·키 오류·서버 장애로 요청을 거부하면 알립니다. (라비 사용 불가 상태)', 'critical' => true, 'default_on' => true, 'cooldown' => 60],
            'paid_order' => ['label' => '신규 결제 주문', 'desc' => '쇼핑몰 주문 결제가 완료될 때마다 주문번호·금액을 알립니다.', 'critical' => false, 'default_on' => false, 'cooldown' => 0],
            'new_inquiry' => ['label' => '1:1 문의 접수', 'desc' => '회원이 1:1 문의를 남기면 알립니다.', 'critical' => false, 'default_on' => false, 'cooldown' => 0],
            'error_report' => ['label' => '오류&제안 접수', 'desc' => '사용자가 오류&제안을 등록하면 알립니다.', 'critical' => false, 'default_on' => false, 'cooldown' => 0],
        ];
    }

    /** @return array<string, mixed> */
    public static function adminPayload(): array
    {
        $config = self::config();
        $state = self::readJson('ops_notify_state.json');

        return $config + [
            'catalog' => self::events(),
            'sms_ready' => NaverSmsSettings::sendConfig() !== null,
            'monitor' => $state['monitor'] ?? null,
            'logs' => array_slice(self::readJson('ops_notify_log.json'), 0, 30),
        ];
    }

    /** @param array<string, mixed> $input */
    public static function save(array $input): array
    {
        $catalog = self::events();
        $events = [];
        foreach ($catalog as $key => $meta) {
            $row = (array) (($input['events'] ?? [])[$key] ?? []);
            $cooldown = (int) ($row['cooldown'] ?? $meta['cooldown']);
            $events[$key] = [
                'enabled' => !empty($row['enabled']),
                'cooldown' => max(0, min(1440, $cooldown)),
            ];
        }

        $recipients = [];
        foreach ((array) ($input['recipients'] ?? []) as $r) {
            if (!is_array($r)) {
                continue;
            }
            $phone = preg_replace('/\D+/', '', (string) ($r['phone'] ?? '')) ?? '';
            $name = mb_substr(trim((string) ($r['name'] ?? '')), 0, 30);
            if ($phone === '' && $name === '') {
                continue;
            }
            if (!preg_match('/^01\d{8,9}$/', $phone)) {
                throw new RuntimeException('휴대폰 번호를 확인해 주세요: ' . ($name !== '' ? $name . ' ' : '') . ($r['phone'] ?? ''));
            }
            if (isset($recipients[$phone])) {
                throw new RuntimeException('같은 번호가 두 번 등록되었습니다: ' . $phone);
            }
            $recipients[$phone] = [
                'id' => preg_match('/^[a-f0-9]{12}$/', (string) ($r['id'] ?? '')) ? (string) $r['id'] : bin2hex(random_bytes(6)),
                'name' => $name !== '' ? $name : '담당자',
                'phone' => $phone,
                'enabled' => !empty($r['enabled']),
                'events' => array_values(array_intersect(array_keys($catalog), array_map('strval', (array) ($r['events'] ?? [])))),
            ];
        }
        if (count($recipients) > self::MAX_RECIPIENTS) {
            throw new RuntimeException('알림 수신자는 최대 ' . self::MAX_RECIPIENTS . '명까지 등록할 수 있습니다.');
        }

        $quiet = (array) ($input['quiet'] ?? []);
        $config = [
            'events' => $events,
            'recipients' => array_values($recipients),
            'quiet' => [
                'enabled' => !empty($quiet['enabled']),
                'start' => max(0, min(23, (int) ($quiet['start'] ?? 22))),
                'end' => max(0, min(23, (int) ($quiet['end'] ?? 8))),
            ],
        ];
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('문자알림 설정을 저장하지 못했습니다.');
        }
        (new SiteSettingsRepository())->set(self::KEY, $json);
        self::writeMirror($config, NaverSmsSettings::sendConfig());

        return self::adminPayload();
    }

    public static function sendTest(string $phone): void
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (!preg_match('/^01\d{8,9}$/', $phone)) {
            throw new RuntimeException('휴대폰 번호를 확인해 주세요.');
        }
        $sens = NaverSmsSettings::sendConfig();
        if ($sens === null) {
            throw new RuntimeException('네이버 문자 API가 설정되지 않았습니다. 환경설정 › 문자인증 탭에서 먼저 입력해 주세요.');
        }
        $text = '[라벨업 알림] 테스트 문자입니다. 운영 알림을 이 번호로 받습니다.';
        (new NaverSmsClient())->sendText($sens, $phone, $text);
        self::appendLog('test', $text, [$phone], true, '');
    }

    /**
     * @return array{sent: int, skipped: string}
     */
    public static function notify(string $event, string $message, string $dedupeKey = ''): array
    {
        try {
            $catalog = self::events();
            if (!isset($catalog[$event])) {
                return ['sent' => 0, 'skipped' => 'unknown event'];
            }
            [$config, $sens] = self::loadForSend();
            $setting = $config['events'][$event] ?? ['enabled' => false, 'cooldown' => 0];
            if (empty($setting['enabled'])) {
                return ['sent' => 0, 'skipped' => 'disabled'];
            }
            if ($sens === null) {
                return ['sent' => 0, 'skipped' => 'sms not configured'];
            }
            if (!$catalog[$event]['critical'] && self::inQuietHours($config['quiet'])) {
                return ['sent' => 0, 'skipped' => 'quiet hours'];
            }
            $phones = [];
            foreach ($config['recipients'] as $r) {
                if (!empty($r['enabled']) && in_array($event, $r['events'], true)) {
                    $phones[] = $r['phone'];
                }
            }
            if ($phones === []) {
                return ['sent' => 0, 'skipped' => 'no recipients'];
            }

            $stateKey = $event . ($dedupeKey !== '' ? ':' . $dedupeKey : '');
            $cooldown = (int) ($setting['cooldown'] ?? 0) * 60;
            $state = self::readJson('ops_notify_state.json');
            $last = (int) ($state['sent'][$stateKey] ?? 0);
            if ($cooldown > 0 && time() - $last < $cooldown) {
                return ['sent' => 0, 'skipped' => 'cooldown'];
            }
            $state['sent'][$stateKey] = time();
            self::writeJson('ops_notify_state.json', $state);

            $text = '[라벨업 알림] ' . $message;
            if (mb_strlen($text) > 600) {
                $text = mb_substr($text, 0, 600) . '…';
            }
            $client = new NaverSmsClient();
            $sent = 0;
            $errors = [];
            foreach ($phones as $phone) {
                try {
                    $client->sendText($sens, $phone, $text);
                    $sent++;
                } catch (\Throwable $e) {
                    $errors[] = substr($phone, -4) . ': ' . $e->getMessage();
                }
            }
            self::appendLog($event, $text, $phones, $sent > 0, implode(' / ', $errors));

            return ['sent' => $sent, 'skipped' => ''];
        } catch (\Throwable $e) {
            Logger::error('Ops SMS notify failed', ['event' => $event, 'error' => $e->getMessage()]);
            return ['sent' => 0, 'skipped' => 'error: ' . $e->getMessage()];
        }
    }

    /** @param array<string, mixed> $monitor */
    public static function saveMonitorState(array $monitor): void
    {
        $state = self::readJson('ops_notify_state.json');
        $state['monitor'] = $monitor;
        self::writeJson('ops_notify_state.json', $state);
    }

    /** @return array<string, mixed> */
    public static function monitorState(): array
    {
        return (array) (self::readJson('ops_notify_state.json')['monitor'] ?? []);
    }

    /** @return array{events: array<string, array{enabled: bool, cooldown: int}>, recipients: array<int, array<string, mixed>>, quiet: array{enabled: bool, start: int, end: int}} */
    private static function config(?string $raw = null): array
    {
        $raw ??= (new SiteSettingsRepository())->get(self::KEY, '');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = [];
        }
        $events = [];
        foreach (self::events() as $key => $meta) {
            $row = (array) (($data['events'] ?? [])[$key] ?? []);
            $events[$key] = [
                'enabled' => array_key_exists('enabled', $row) ? (bool) $row['enabled'] : $meta['default_on'],
                'cooldown' => (int) ($row['cooldown'] ?? $meta['cooldown']),
            ];
        }
        $recipients = [];
        foreach ((array) ($data['recipients'] ?? []) as $r) {
            if (is_array($r) && !empty($r['phone'])) {
                $recipients[] = [
                    'id' => (string) ($r['id'] ?? ''),
                    'name' => (string) ($r['name'] ?? ''),
                    'phone' => (string) $r['phone'],
                    'enabled' => !empty($r['enabled']),
                    'events' => array_values(array_map('strval', (array) ($r['events'] ?? []))),
                ];
            }
        }
        $quiet = (array) ($data['quiet'] ?? []);

        return [
            'events' => $events,
            'recipients' => $recipients,
            'quiet' => [
                'enabled' => !empty($quiet['enabled']),
                'start' => (int) ($quiet['start'] ?? 22),
                'end' => (int) ($quiet['end'] ?? 8),
            ],
        ];
    }

    /** @return array{0: array<string, mixed>, 1: ?array<string, string>} */
    private static function loadForSend(): array
    {
        $raw = '';
        $sens = null;
        try {
            $raw = (new SiteSettingsRepository())->get(self::KEY, '');
            $sens = NaverSmsSettings::sendConfig();
        } catch (\Throwable) {
        }
        if ($raw !== '' && $sens !== null) {
            $config = self::config($raw);
            self::writeMirror($config, $sens);
            return [$config, $sens];
        }
        $mirror = self::readJson('ops_notify_sms.json');
        if ($raw === '' && isset($mirror['config'])) {
            $config = self::config((string) json_encode($mirror['config']));
        } else {
            $config = self::config($raw);
        }
        $sens ??= is_array($mirror['sens'] ?? null) ? $mirror['sens'] : null;

        return [$config, $sens];
    }

    /** @param array{enabled: bool, start: int, end: int} $quiet */
    private static function inQuietHours(array $quiet): bool
    {
        if (empty($quiet['enabled']) || $quiet['start'] === $quiet['end']) {
            return false;
        }
        $hour = (int) (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Seoul')))->format('G');
        return $quiet['start'] < $quiet['end']
            ? $hour >= $quiet['start'] && $hour < $quiet['end']
            : $hour >= $quiet['start'] || $hour < $quiet['end'];
    }

    /** @param array<int, string> $phones */
    private static function appendLog(string $event, string $text, array $phones, bool $ok, string $error): void
    {
        $logs = self::readJson('ops_notify_log.json');
        array_unshift($logs, [
            'at' => date('Y-m-d H:i:s'),
            'event' => $event,
            'text' => $text,
            'to' => array_map(static fn (string $p): string => substr($p, 0, 3) . '-****-' . substr($p, -4), $phones),
            'ok' => $ok,
            'error' => $error,
        ]);
        self::writeJson('ops_notify_log.json', array_slice($logs, 0, self::LOG_LIMIT));
    }

    /** @param array<string, mixed> $config */
    private static function writeMirror(array $config, ?array $sens): void
    {
        $payload = ['config' => $config, 'sens' => $sens];
        if (self::readJson('ops_notify_sms.json') == $payload) {
            return;
        }
        self::writeJson('ops_notify_sms.json', $payload);
    }

    /** @return array<mixed> */
    private static function readJson(string $file): array
    {
        $path = storage_path('logs/' . $file);
        if (!is_file($path)) {
            return [];
        }
        $data = json_decode((string) @file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    /** @param array<mixed> $data */
    private static function writeJson(string $file, array $data): void
    {
        $dir = storage_path('logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir . '/' . $file;
        $tmp = $path . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
            @chmod($tmp, 0600);
            @rename($tmp, $path);
        }
    }
}
