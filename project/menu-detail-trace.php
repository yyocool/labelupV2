<?php
/**
 * menu-detail.php 단계별 추적 (설치 후 삭제 권장)
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

$entry = __DIR__ . '/menu-detail.php';
if (!file_exists($entry)) {
    step('   MISSING menu-detail.php');
    exit;
}
step('2. menu-detail.php size=' . filesize($entry) . ' bytes');
step('   head: ' . substr(str_replace(array("\r", "\n"), ' ', file_get_contents($entry)), 0, 100));

step('3. bootstrap');
require_once __DIR__ . '/includes/bootstrap.php';
step('   bootstrap OK');

$menuId = (int) (isset($_GET['id']) ? $_GET['id'] : 2);
step('4. menu_id=' . $menuId);

try {
    $menu = MenuService::getById($menuId);
    if (!$menu) {
        step('   MenuService::getById: 메뉴 없음');
    } else {
        step('   menu id=' . $menu['id'] . ', title=' . $menu['title'] . ', progress=' . (isset($menu['progress_pct']) ? $menu['progress_pct'] : '?') . '%');
    }
} catch (Exception $e) {
    step('   getById FAIL: ' . $e->getMessage());
}

try {
    $breadcrumb = MenuService::getBreadcrumb($menuId);
    step('   breadcrumb: ' . count($breadcrumb) . ' items');
} catch (Exception $e) {
    step('   breadcrumb FAIL: ' . $e->getMessage());
}

$project = ProjectService::getOrCreateDefault();
$users = get_all_users();
$menuIssues = array();
if ($menu && (int) $menu['project_id'] === (int) $project['id']) {
    $menuIssues = array_values(array_filter(IssueService::getByProject($project['id']), function ($i) use ($menuId) {
        return (int) $i['menu_id'] === (int) $menuId;
    }));
}
step('   users=' . count($users) . ', menuIssues=' . count($menuIssues));

step('5. 뷰 렌더');
$menuTree = build_menu_tree(MenuService::getByProject($project['id']));
$pageTitle = $menu ? $menu['title'] : '메뉴';
$currentPage = 'menus';
$phaseTracker = ProjectService::getPhaseTracker($project['id']);
if (!$menu) {
    $menu = array('id' => $menuId, 'title' => '(없음)', 'description' => '', 'progress_pct' => 0);
    $breadcrumb = array();
}

try {
    ob_start();
    include __DIR__ . '/views/menu_detail.php';
    step('   view bytes=' . strlen(ob_get_clean()));
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    step('   view 예외: ' . $e->getMessage());
}

step('6. layout 포함');
try {
    ob_start();
    include __DIR__ . '/views/menu_detail.php';
    $content = ob_get_clean();
    ob_start();
    include __DIR__ . '/includes/layout.php';
    step('   full bytes=' . strlen(ob_get_clean()));
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_end_clean();
    }
    step('   layout 예외: ' . $e->getMessage());
}

step('7. 로그인: ' . (is_logged_in() ? 'YES' : 'NO'));
