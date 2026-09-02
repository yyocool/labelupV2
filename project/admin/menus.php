<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_admin();
extract(init_project_context());

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'create') {
        MenuService::create($project['id'], $_POST);
        flash('success', '메뉴가 등록되었습니다.');
    } elseif ($action === 'update') {
        MenuService::update((int) $_POST['id'], $_POST);
        flash('success', '메뉴가 수정되었습니다.');
    } elseif ($action === 'delete') {
        MenuService::delete((int) $_POST['id']);
        flash('success', '메뉴가 삭제되었습니다.');
    }
    admin_redirect('menus.php');
}

$menuList = MenuService::getByProject($project['id']);
if (MenuService::needsCodeRebuild($project['id'])) {
    MenuService::rebuildCodes($project['id']);
    $menuList = MenuService::getByProject($project['id']);
}
$menuTree = build_menu_tree($menuList);

$pageTitle = '메뉴 관리';
$currentPage = 'admin-menus';

render_admin_page(__DIR__ . '/views/admin_menus.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuList', 'menuTree', 'phaseTracker'
));
