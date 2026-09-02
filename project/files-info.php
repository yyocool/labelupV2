<?php
/**
 * PHP 엔트리 파일 상태 확인 (설치 후 삭제 권장)
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

$files = array(
    'menus.php', 'issues.php', 'storyboard.php', 'menu-detail.php', 'milestones.php',
    'notices.php', 'archive.php', 'index.php',
    'includes/bootstrap.php', 'includes/security.php', 'includes/helpers.php',
    'includes/view.php', 'includes/StoryboardService.php', 'includes/MenuService.php', 'includes/layout_storyboard.php',
    'views/menus.php', 'views/storyboard.php', 'views/storyboard_collab.php',
    'views/menu_detail.php', 'menu-detail-trace.php',
);

echo "Label-UP 파일 점검\n";
echo str_repeat('-', 50) . "\n";

foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    if (!file_exists($path)) {
        echo "MISSING  {$f}\n";
        continue;
    }
    $raw = file_get_contents($path);
    $size = strlen($raw);
    $head = substr($raw, 0, 80);
    $head = str_replace(array("\r", "\n"), array('\\r', '\\n'), $head);
    $bom = (substr($raw, 0, 3) === "\xEF\xBB\xBF") ? ' BOM!' : '';
    echo sprintf("OK %-28s %6d bytes%s\n", $f, $size, $bom);
    echo "   head: {$head}\n";

    $output = array();
    @exec('php -l ' . escapeshellarg($path) . ' 2>&1', $output);
    if (!empty($output)) {
        echo '   lint: ' . implode(' ', $output) . "\n";
    }
}

echo str_repeat('-', 50) . "\n";

require_once __DIR__ . '/includes/bootstrap.php';
$sbFuncs = array(
    'render_storyboard_menu_tree',
    'storyboard_json_frame',
    'storyboard_active_frame_number',
    'storyboard_visibility_badge',
);
echo "스토리보드 헬퍼 함수:\n";
foreach ($sbFuncs as $fn) {
    echo '  ' . $fn . ': ' . (function_exists($fn) ? 'OK' : 'MISSING') . "\n";
}
echo str_repeat('-', 50) . "\n";

if (file_exists(__DIR__ . '/storage/php-error.log')) {
    echo "최근 오류 로그 (storage/php-error.log):\n";
    $lines = file(__DIR__ . '/storage/php-error.log');
    echo implode('', array_slice($lines, -10));
} else {
    echo "오류 로그 없음 (storage/php-error.log)\n";
}
