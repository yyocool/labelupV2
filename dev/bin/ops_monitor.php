<?php

declare(strict_types=1);

/*
 * 운영 점검 (cron 5분 간격):
 *   * /5 * * * * php /home/wwwroot/labelup/bin/ops_monitor.php >/dev/null 2>&1
 * php-fpm 과 같은 사용자로 돌려야 storage/logs 상태 파일을 함께 쓸 수 있다.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Helpers\Database;
use App\Services\OpenAiBillingService;
use App\Services\OpsSmsNotifier;

const FAILS_BEFORE_ALERT = 2;
const AI_CHECK_INTERVAL = 1800;

$state = OpsSmsNotifier::monitorState();
$now = time();
$problems = [];

$dbOk = true;
try {
    Database::connection()->query('SELECT 1');
} catch (Throwable $e) {
    $dbOk = false;
    $problems[] = 'DB 연결 실패';
}

$base = rtrim((string) env('OPS_MONITOR_URL', 'https://www.labelup.co.kr'), '/');
$ch = curl_init($base . '/api/health');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: LabelUp-OpsMonitor'],
]);
$body = curl_exec($ch);
$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);
$health = is_string($body) ? json_decode($body, true) : null;
if ($body === false) {
    $problems[] = '사이트 접속 불가 (' . ($curlErr ?: 'timeout') . ')';
} elseif ($http !== 200 || !is_array($health) || empty($health['success'])) {
    $problems[] = '사이트 응답 오류 HTTP ' . $http;
} elseif (($health['data']['database'] ?? '') !== 'connected' && $dbOk) {
    $problems[] = '웹 DB 연결 실패';
}

$wasDown = !empty($state['down']);
$fails = $problems === [] ? 0 : (int) ($state['fail_count'] ?? 0) + 1;
$stamp = date('m/d H:i');

if ($problems !== [] && $fails >= FAILS_BEFORE_ALERT) {
    $since = $wasDown ? (string) ($state['down_since'] ?? $stamp) : $stamp;
    OpsSmsNotifier::notify('site_down', '사이트 장애 감지 ' . $since . ' ~ : ' . implode(', ', array_unique($problems)) . ' / ' . $base, 'down');
    $state['down'] = true;
    $state['down_since'] = $since;
} elseif ($problems === [] && $wasDown) {
    OpsSmsNotifier::notify('site_down', '사이트 복구됨 ' . $stamp . ' (장애 시작 ' . ($state['down_since'] ?? '-') . ')', 'up:' . $now);
    $state['down'] = false;
    $state['down_since'] = null;
}

if ($dbOk && $now - (int) ($state['last_ai_check'] ?? 0) >= AI_CHECK_INTERVAL) {
    $state['last_ai_check'] = $now;
    try {
        $bill = OpenAiBillingService::summary();
        if (!empty($bill['ok']) && !empty($bill['is_low'])) {
            OpsSmsNotifier::notify('ai_balance_low', sprintf(
                'Chat GPT API 추정 잔액 $%.2f (경고 기준 $%.2f). OpenAI 크레딧 충전이 필요합니다.',
                (float) $bill['remaining'],
                (float) $bill['low_threshold']
            ));
        }
        $state['ai_error'] = !empty($bill['configured']) && empty($bill['ok']) ? (string) ($bill['error'] ?? '') : '';
    } catch (Throwable $e) {
        $state['ai_error'] = $e->getMessage();
    }
}

$state['last_run'] = date('Y-m-d H:i:s');
$state['fail_count'] = $fails;
$state['last_problem'] = implode(', ', $problems);
OpsSmsNotifier::saveMonitorState($state);

echo ($problems === [] ? 'OK' : 'FAIL ' . implode(', ', $problems)) . PHP_EOL;
