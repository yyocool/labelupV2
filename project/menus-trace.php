<?php
/**
 * menus.php 단계별 추적 (설치 후 삭제 권장)
 * 로그인 상태에서 접속하면 전체 흐름을 확인할 수 있습니다.
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

step('2. bootstrap 로드');
require_once __DIR__ . '/includes/bootstrap.php';
step('   bootstrap OK');

step('3. 로그인 여부: ' . (is_logged_in() ? 'YES' : 'NO'));
if (!is_logged_in()) {
    step('   → 로그인 필요. 이 스크립트는 로그인 후 다시 실행하세요.');
    step('   redirect URL: ' . url('login.php'));
    exit;
}

step('4. init_project_context');
extract(init_project_context());
step('   project id=' . $project['id']);

step('5. 메뉴 데이터');
$menus = MenuService::getByProject($project['id']);
$menuTree = build_menu_tree($menus);
step('   menus=' . count($menus) . ', tree=' . count($menuTree));

$pageTitle = '메뉴 구성도';
$currentPage = 'menus';
$phaseTracker = isset($phaseTracker) ? $phaseTracker : ProjectService::getPhaseTracker($project['id']);

step('6. view만 렌더');
ob_start();
include __DIR__ . '/views/menus.php';
$viewLen = strlen(ob_get_clean());
step('   view bytes=' . $viewLen);

step('7. layout 포함 전체 렌더');
try {
    render_page(__DIR__ . '/views/menus.php', compact(
        'pageTitle', 'currentPage', 'project', 'menuTree', 'menus', 'phaseTracker'
    ));
    step('8. 완료');
} catch (Exception $ex) {
    step('8. 예외: ' . $ex->getMessage());
    step('   ' . $ex->getFile() . ':' . $ex->getLine());
}
