<?php
/**
 * storyboard.php 단계별 추적 (설치 후 삭제 권장)
 * 로그인 없이도 1~6단계(함수·DB·뷰)까지 확인 가능합니다.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        echo "\n[FATAL] " . $e['message'] . "\n" . $e['file'] . ':' . $e['line'] . "\n";
    }
});

function step($msg)
{
    echo $msg . "\n";
    if (function_exists('flush')) {
        flush();
    }
}

step('1. 시작');

step('2. storyboard.php 구문·파일');
$entry = __DIR__ . '/storyboard.php';
if (!file_exists($entry)) {
    step('   MISSING storyboard.php');
    exit;
}
step('   size=' . filesize($entry) . ' bytes');
step('   head: ' . substr(str_replace(array("\r", "\n"), ' ', file_get_contents($entry)), 0, 100));

step('3. bootstrap 로드');
require_once __DIR__ . '/includes/bootstrap.php';
step('   bootstrap OK');

$funcs = array(
    'render_storyboard_menu_tree',
    'storyboard_json_frame',
    'storyboard_active_frame_number',
    'storyboard_visibility_badge',
    'storyboard_history_action_label',
    'csrf_field',
);
foreach ($funcs as $fn) {
    step('   ' . $fn . ': ' . (function_exists($fn) ? 'OK' : 'MISSING'));
}

step('4. StoryboardService');
$project = ProjectService::getOrCreateDefault();
$pid = $project['id'];
step('   project id=' . $pid);

try {
    $counts = StoryboardService::getFrameCountsForProject($pid);
    step('   getFrameCountsForProject: OK (' . count($counts) . ' menus)');
} catch (Exception $e) {
    step('   getFrameCountsForProject FAIL: ' . $e->getMessage());
}

try {
    $publicIds = StoryboardService::getPublicMenuIds($pid);
    step('   getPublicMenuIds: OK (' . count($publicIds) . ' ids)');
} catch (Exception $e) {
    step('   getPublicMenuIds FAIL: ' . $e->getMessage());
}

$menus = MenuService::getByProject($pid);
$menuTree = build_menu_tree($menus);
step('   menus=' . count($menus) . ', tree=' . count($menuTree));

$menuId = 0;
if (!empty($menuTree)) {
    $menuId = (int) get_first_menu_id_from_tree($menuTree);
}
$menu = $menuId ? MenuService::getById($menuId) : null;
$storyboard = $menu ? StoryboardService::getByMenu($menuId) : null;
$frames = array();
$comments = array();
$history = array();
if ($storyboard) {
    $frames = StoryboardService::getFrames($storyboard['id']);
    $comments = StoryboardService::getComments($storyboard['id']);
    $history = StoryboardService::getHistory($storyboard['id']);
    step('   storyboard id=' . $storyboard['id'] . ', frames=' . count($frames) . ', comments=' . count($comments) . ', history=' . count($history));
} else {
    step('   storyboard: 없음 (menu_id=' . $menuId . ')');
}

step('5. 뷰만 렌더 (로그인 불필요)');
$pageTitle = '스토리보드';
$currentPage = 'storyboard';
$frameCounts = isset($counts) ? $counts : array();
$activeFrameId = !empty($frames) ? (int) $frames[0]['id'] : 0;
$breadcrumb = $menu ? MenuService::getBreadcrumb($menuId) : array();
$user = array('id' => 0, 'name' => 'Test', 'role' => 'admin', 'avatar_color' => '#6366f1');
$storyboardAdminMode = false;
$storyboardBackUrl = null;
$storyboardLinkBase = url('storyboard.php');
$canEditStoryboard = true;
$canManageAll = true;
$phaseTracker = ProjectService::getPhaseTracker($pid);

try {
    ob_start();
    include __DIR__ . '/views/storyboard.php';
    $viewLen = strlen(ob_get_clean());
    step('   view bytes=' . $viewLen);
} catch (Exception $e) {
    ob_end_clean();
    step('   view 예외: ' . $e->getMessage());
    step('   ' . $e->getFile() . ':' . $e->getLine());
}

step('6. layout_storyboard 포함 전체 렌더');
try {
    ob_start();
    include __DIR__ . '/views/storyboard.php';
    $content = ob_get_clean();
    ob_start();
    include __DIR__ . '/includes/layout_storyboard.php';
    $fullLen = strlen(ob_get_clean());
    step('   full bytes=' . $fullLen);
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    step('   layout 예외: ' . $e->getMessage());
    step('   ' . $e->getFile() . ':' . $e->getLine());
}

step('7. 로그인 여부: ' . (is_logged_in() ? 'YES' : 'NO'));
if (!is_logged_in()) {
    step('   → 실제 storyboard.php는 로그인 필요. 위 단계가 OK면 엔트리 파일(storyboard.php) 업로드 여부를 확인하세요.');
    exit;
}

step('8. storyboard.php 엔트리 흐름 (로그인 상태)');
$user = current_user();
$canManageAll = is_admin();
$canEditStoryboard = StoryboardService::canEditFrames();
step('   user=' . $user['name'] . ', admin=' . ($canManageAll ? 'Y' : 'N'));

try {
    render_page(__DIR__ . '/views/storyboard.php', compact(
        'pageTitle', 'currentPage', 'project', 'menus', 'menuTree', 'menu', 'storyboard',
        'frames', 'breadcrumb', 'frameCounts', 'menuId', 'activeFrameId', 'comments', 'history', 'user',
        'storyboardAdminMode', 'storyboardBackUrl', 'storyboardLinkBase', 'canEditStoryboard', 'canManageAll',
        'phaseTracker'
    ), 'layout_storyboard.php');
    step('9. 완료');
} catch (Exception $e) {
    step('9. 예외: ' . $e->getMessage());
    step('   ' . $e->getFile() . ':' . $e->getLine());
}
