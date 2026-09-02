<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$menuId = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
$menu = MenuService::getById($menuId);
if (!$menu || (int) $menu['project_id'] !== (int) $project['id']) {
    flash('error', '메뉴를 찾을 수 없습니다.');
    redirect('menus.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    MenuService::updateProgress($menuId, $_POST);
    ProjectService::updateProgress($project['id']);
    $user = current_user();
    log_activity(
        $project['id'],
        $user ? $user['id'] : null,
        'progress_update',
        'menu',
        $menuId,
        '메뉴 "' . $menu['title'] . '" 진행상황 업데이트'
    );
    flash('success', '진행상황이 저장되었습니다.');
    redirect('menu-detail.php?id=' . $menuId);
}

$breadcrumb = MenuService::getBreadcrumb($menuId);
$users = get_all_users();
$menuIssues = IssueService::getByProject($project['id']);
$menuIssues = array_values(array_filter($menuIssues, function ($i) use ($menuId) {
    return (int) $i['menu_id'] === (int) $menuId;
}));

$pageTitle = $menu['title'];
$currentPage = 'menus';

render_page(__DIR__ . '/views/menu_detail.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'menu', 'breadcrumb', 'users', 'menuIssues', 'phaseTracker'
));
