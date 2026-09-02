<?php
/**
 * 로그인 없이 전체 페이지 렌더 테스트 (설치 후 삭제 권장)
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/includes/bootstrap.php';

$project = ProjectService::getOrCreateDefault();
$pid = $project['id'];
$menus = MenuService::getByProject($pid);
$menuTree = build_menu_tree($menus);
$phaseTracker = ProjectService::getPhaseTracker($pid);

$pageTitle = '메뉴 구성도';
$currentPage = 'menus';

echo '<!-- render test start -->';
render_page(__DIR__ . '/views/menus.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'menus', 'phaseTracker'
));
echo '<!-- render test end -->';
