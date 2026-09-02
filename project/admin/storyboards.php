<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_admin();
extract(init_project_context());

$user = current_user();
$userId = $user ? $user['id'] : null;

$storyboardList = StoryboardService::getListForProject($project['id']);
$visibilityOptions = StoryboardService::getVisibilityOptions();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'create_storyboard') {
        $menuId = (int) (isset($_POST['menu_id']) ? $_POST['menu_id'] : 0);
        $menu = MenuService::getById($menuId);
        if (!$menu || (int) $menu['project_id'] !== (int) $project['id']) {
            flash('error', '메뉴를 찾을 수 없습니다.');
        } elseif (StoryboardService::getByMenu($menuId)) {
            flash('error', '이미 스토리보드가 등록된 메뉴입니다.');
        } else {
            $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
            if ($title === '') {
                $title = $menu['title'] . ' 스토리보드';
            }
            $id = StoryboardService::create($menuId, array(
                'title'       => $title,
                'description' => isset($_POST['description']) ? $_POST['description'] : null,
                'visibility'  => 'working',
            ), $userId);
            log_activity($project['id'], $userId, 'storyboard_create', 'storyboard', $id, '스토리보드 등록: ' . $title);
            flash('success', '스토리보드가 등록되었습니다.');
            admin_redirect('storyboard.php?menu_id=' . $menuId);
        }
        admin_redirect('storyboards.php');
    }

    if ($action === 'set_visibility') {
        $storyboardId = (int) (isset($_POST['storyboard_id']) ? $_POST['storyboard_id'] : 0);
        $visibility = isset($_POST['visibility']) ? $_POST['visibility'] : 'working';
        $sb = StoryboardService::getById($storyboardId);
        if ($sb) {
            $menu = MenuService::getById($sb['menu_id']);
            if ($menu && (int) $menu['project_id'] === (int) $project['id']) {
                StoryboardService::setVisibility($storyboardId, $visibility);
                $label = isset($visibilityOptions[$visibility]['label']) ? $visibilityOptions[$visibility]['label'] : $visibility;
                StoryboardService::logHistory(
                    $storyboardId, $userId, 'status_change',
                    '공개 상태 변경: ' . $label, null, $label
                );
                flash('success', '공개 상태가 변경되었습니다.');
            }
        }
        admin_redirect('storyboards.php');
    }
}

$menusWithoutStoryboard = array();
foreach ($storyboardList as $row) {
    if (empty($row['storyboard_id'])) {
        $menusWithoutStoryboard[] = $row;
    }
}

$pageTitle = '스토리보드 관리';
$currentPage = 'admin-storyboards';

render_admin_page(__DIR__ . '/views/storyboards.php', compact(
    'pageTitle', 'currentPage', 'project', 'storyboardList', 'visibilityOptions', 'menusWithoutStoryboard', 'phaseTracker'
));
