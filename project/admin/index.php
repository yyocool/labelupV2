<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_admin();
extract(init_project_context());

$stats = ProjectService::getDashboardStats($project['id']);
$members = ProjectService::getMembers($project['id']);
$menuList = MenuService::getByProject($project['id']);

$pageTitle = '관리자 대시보드';
$currentPage = 'admin-dashboard';

render_admin_page(__DIR__ . '/views/admin_dashboard.php', compact(
    'pageTitle', 'currentPage', 'project', 'stats', 'members', 'menuList', 'phaseTracker'
));
