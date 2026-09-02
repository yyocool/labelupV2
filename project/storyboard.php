<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$user = current_user();
$canManageAll = is_admin();
$canEditStoryboard = StoryboardService::canEditFrames();
$storyboardLinkBase = url('storyboard.php');
$frameCounts = StoryboardService::getFrameCountsForProject($project['id']);
$storyboardContentStatusMap = StoryboardFileService::getContentStatusMap($menus);
/* 좌측 메뉴: 권한/공개 여부와 무관하게 전체 트리 표시 */
$accessibleMenuIds = array();

$menuId = (int) (isset($_GET['menu_id']) ? $_GET['menu_id'] : 0);
if (!$menuId && !empty($menuTree)) {
    $menuId = (int) get_first_menu_id_from_tree($menuTree);
}

$menu = null;
$storyboard = null;
$frames = array();
$comments = array();
$history = array();
$activeFrameId = (int) (isset($_GET['frame_id']) ? $_GET['frame_id'] : 0);

if ($menuId) {
    $menu = MenuService::getById($menuId);
    if (!$menu || (int) $menu['project_id'] !== (int) $project['id']) {
        flash('error', '메뉴를 찾을 수 없습니다.');
        redirect('storyboard.php');
    }
}

$redirectUrl = 'storyboard.php?menu_id=' . $menuId;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && $menu) {
    $storyboard = StoryboardService::getByMenu($menuId);
    if (!$storyboard) {
        if ($canManageAll) {
            $storyboard = StoryboardService::getOrCreate($menuId, $user ? $user['id'] : null);
        } else {
            flash('error', '스토리보드를 찾을 수 없습니다.');
            redirect('storyboard.php');
        }
    } elseif (!StoryboardService::canView($storyboard, $user)) {
        flash('error', '열람 권한이 없습니다.');
        redirect('storyboard.php');
    }
    StoryboardService::handleFramePost($project, $menu, $user, $storyboard, $redirectUrl);
}

$storyboardFileExists = false;

if ($menu) {
    $storyboardFileExists = !empty($menu['menu_code']) && StoryboardFileService::exists($menu['menu_code']);
    $storyboard = StoryboardService::getByMenu($menuId);
    if ($storyboardFileExists && !$storyboard && $canManageAll) {
        $storyboard = StoryboardService::getOrCreate($menuId, $user ? $user['id'] : null);
    }
    /* 열람: 로그인 사용자는 공개/작업중 구분 없이 화면 확인 가능 (편집은 canEditStoryboard) */
    if ($storyboard) {
        $frames = StoryboardService::getFrames($storyboard['id']);
        $comments = StoryboardService::getComments($storyboard['id']);
        $history = StoryboardService::getHistory($storyboard['id']);
        if (!$activeFrameId && !empty($frames)) {
            $activeFrameId = (int) $frames[0]['id'];
        }
    }
} else {
    $storyboardFileExists = false;
}

$breadcrumb = $menu ? MenuService::getBreadcrumb($menuId) : array();
$pageTitle = '스토리보드';
$currentPage = 'storyboard';
$storyboardAdminMode = false;
$storyboardBackUrl = null;

render_page(__DIR__ . '/views/storyboard.php', compact(
    'pageTitle', 'currentPage', 'project', 'menus', 'menuTree', 'menu', 'storyboard',
    'frames', 'breadcrumb', 'frameCounts', 'storyboardContentStatusMap', 'storyboardFileExists', 'menuId', 'activeFrameId', 'comments', 'history', 'user',
    'storyboardAdminMode', 'storyboardBackUrl', 'storyboardLinkBase', 'canEditStoryboard', 'canManageAll',
    'phaseTracker'
), 'layout_storyboard.php');
