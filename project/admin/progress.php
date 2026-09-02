<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_admin();
extract(init_project_context());

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    if (isset($_POST['action']) && $_POST['action'] === 'update_phase') {
        $mode = isset($_POST['phase_mode']) ? $_POST['phase_mode'] : 'auto';
        $phase = isset($_POST['current_phase']) ? $_POST['current_phase'] : null;
        ProjectService::updatePhaseSettings($project['id'], $mode, $phase);
        flash('success', '프로젝트 진행 단계가 저장되었습니다.');
        admin_redirect('progress.php');
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_db_design') {
        ProjectService::updateProjectSetupProgress($project['id'], $_POST);
        ProjectService::updateProgress($project['id']);
        flash('success', 'DB설계 진행상황이 저장되었습니다.');
        admin_redirect('progress.php');
    }

    $menuId = (int) (isset($_POST['menu_id']) ? $_POST['menu_id'] : 0);
    MenuService::updateProgress($menuId, $_POST);
    ProjectService::updateProgress($project['id']);
    flash('success', '진행상황이 저장되었습니다.');
    admin_redirect('progress.php');
}

$menuList = MenuService::getByProject($project['id']);
$users = get_all_users();
$project = ProjectService::getById($project['id']);
$phaseTracker = ProjectService::getPhaseTracker($project['id']);
$phaseOptions = ProjectService::getPhaseOptions();
$progressPhases = ProjectService::getProgressBreakdown($project['id']);

$pageTitle = '진행 관리';
$currentPage = 'progress';

render_admin_page(__DIR__ . '/views/progress.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuList', 'users', 'phaseTracker', 'phaseOptions', 'progressPhases'
));
