<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();

extract(init_project_context());



$user = current_user();

$frameCounts = StoryboardService::getFrameCountsForProject($project['id']);
$storyboardFileMap = StoryboardFileService::getFileStatusMap($menus);

$menuId = (int) (isset($_GET['menu_id']) ? $_GET['menu_id'] : 0);

$storyboardLinkBase = admin_url('storyboard.php');



if (!$menuId && !empty($menuTree)) {

    $menuId = get_first_menu_id_from_tree($menuTree);

}



$menu = null;

$storyboard = null;

$frames = array();

$comments = array();

$history = array();

$activeFrameId = (int) (isset($_GET['frame_id']) ? $_GET['frame_id'] : 0);



if ($menuId) {

    $menu = MenuService::getById($menuId);

    if (!$menu || $menu['project_id'] != $project['id']) {

        flash('error', '메뉴를 찾을 수 없습니다.');

        admin_redirect('storyboards.php');

    }

}



$redirectUrl = 'storyboard.php?menu_id=' . $menuId;



if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && $menu) {

    $storyboard = StoryboardService::getByMenu($menuId);

    if (!$storyboard) {

        $sbId = StoryboardService::create($menuId, array('title' => $menu['title'] . ' 스토리보드'), $user['id']);

        $storyboard = StoryboardService::getById($sbId);

    }



    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'set_visibility') {

        $visibility = isset($_POST['visibility']) ? $_POST['visibility'] : 'working';

        StoryboardService::setVisibility($storyboard['id'], $visibility);

        flash('success', '공개 상태가 변경되었습니다.');

        admin_redirect($redirectUrl . ($activeFrameId ? '&frame_id=' . $activeFrameId : ''));

    }



    StoryboardService::handleFramePost($project, $menu, $user, $storyboard, admin_url($redirectUrl));

}



$storyboardFileExists = false;

if ($menu) {
    $storyboardFileExists = !empty($menu['menu_code']) && StoryboardFileService::exists($menu['menu_code']);
    $storyboard = StoryboardService::getByMenu($menuId);
    if ($storyboardFileExists && !$storyboard) {
        $storyboard = StoryboardService::getOrCreate($menuId, $user ? $user['id'] : null);
    }
    if (!$storyboard && !$storyboardFileExists) {
        flash('error', '스토리보드가 등록되지 않았습니다. 목록에서 먼저 등록해 주세요.');
        admin_redirect('storyboards.php');
    }
    if ($storyboard) {
        $frames = StoryboardService::getFrames($storyboard['id']);
        $comments = StoryboardService::getComments($storyboard['id']);
        $history = StoryboardService::getHistory($storyboard['id']);
        if (!$activeFrameId && !empty($frames)) {
            $activeFrameId = (int) $frames[0]['id'];
        }
    }
}



$breadcrumb = $menu ? MenuService::getBreadcrumb($menuId) : array();

$pageTitle = '스토리보드 편집';

$currentPage = 'admin-storyboards';

$storyboardAdminMode = true;

$storyboardBackUrl = admin_url('storyboards.php');

$canEditStoryboard = StoryboardService::canEditFrames();



render_admin_page(__DIR__ . '/../views/storyboard.php', compact(
    'pageTitle', 'currentPage', 'project', 'menus', 'menuTree', 'menu', 'storyboard',
    'frames', 'breadcrumb', 'frameCounts', 'storyboardFileMap', 'storyboardFileExists', 'menuId', 'activeFrameId', 'comments', 'history', 'user',
    'storyboardAdminMode', 'storyboardBackUrl', 'storyboardLinkBase', 'canEditStoryboard'
));

