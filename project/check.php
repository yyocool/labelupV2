<?php
/**
 * 서버 환경 진단 (설치 후 삭제 권장)
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/html; charset=utf-8');
echo '<h1>Label-UP 서버 진단</h1><pre>';

echo 'PHP 버전: ' . PHP_VERSION . "\n";
echo 'mbstring: ' . (function_exists('mb_substr') ? 'OK' : '없음 (폴백 사용)') . "\n";
echo 'PDO: ' . (class_exists('PDO') ? 'OK' : '없음') . "\n";
echo 'pdo_mysql: ' . (extension_loaded('pdo_mysql') ? 'OK' : '없음') . "\n\n";

echo 'security.php: ' . (file_exists(__DIR__ . '/includes/security.php') ? '있음' : '없음 → 업로드 필요') . "\n";
echo 'openssl: ' . (function_exists('openssl_random_pseudo_bytes') ? 'OK' : '없음 (폴백 사용)') . "\n";
echo 'random_bytes: ' . (function_exists('random_bytes') ? 'OK' : '없음') . "\n\n";

try {
    require_once __DIR__ . '/includes/bootstrap.php';
    echo "bootstrap: OK\n";

    if (function_exists('render_menu_tree_view')) {
        echo "render_menu_tree_view: OK\n";
    } else {
        echo "render_menu_tree_view: 없음 → helpers.php 업로드 필요\n";
    }

    $token = csrf_token();
    echo 'csrf_token: OK (' . strlen($token) . " chars)\n";

    echo 'APP_ROOT: ' . APP_ROOT . "\n";
    echo 'environment: ' . app_config('environment', '-') . "\n";
    echo 'installed.lock: ' . (file_exists(APP_ROOT . '/storage/installed.lock') ? '있음' : '없음') . "\n\n";

    $db = Database::getConnection();
    echo "DB 연결: OK\n";
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo '테이블 수: ' . count($tables) . "\n";

    if (in_array('users', $tables, true)) {
        $cnt = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        echo 'users 수: ' . $cnt . "\n";
    }

    if (file_exists(APP_ROOT . '/storage/installed.lock')) {
        $project = ProjectService::getOrCreateDefault();
        echo '프로젝트 ID: ' . $project['id'] . "\n";
        $tracker = ProjectService::getPhaseTracker($project['id']);
        echo 'phase tracker: OK (' . $tracker['current_label'] . ")\n";
    }
} catch (Exception $e) {
    echo "\n오류: " . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
}

echo '</pre><p><a href="check-pages.php">페이지별 진단</a> | <a href="login.php">로그인</a> | <a href="index.php">대시보드</a></p>';
