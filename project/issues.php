<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = isset($_POST['action']) ? $_POST['action'] : 'create';
    $user = current_user();
    $userId = $user ? $user['id'] : null;

    if ($action === 'create') {
        $id = IssueService::create($project['id'], $_POST, $userId);
        log_activity($project['id'], $userId, 'issue_create', 'issue', $id, '이슈 등록: ' . $_POST['title']);
        flash('success', '이슈가 등록되었습니다.');
        redirect('issue-detail.php?id=' . $id);
    }
}

$filter = isset($_GET['status']) ? $_GET['status'] : '';
$issues = IssueService::getByProject($project['id'], $filter ? $filter : null);
$users = get_all_users();
$menuList = MenuService::getByProject($project['id']);
$menuTree = build_menu_tree($menuList);
$showNew = isset($_GET['action']) && $_GET['action'] === 'new';
$prefillMenuId = (int) (isset($_GET['menu_id']) ? $_GET['menu_id'] : 0);

$pageTitle = '이슈 관리';
$currentPage = 'issues';

render_page(__DIR__ . '/views/issues.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'issues', 'users', 'menuList', 'filter', 'showNew', 'prefillMenuId'
));
