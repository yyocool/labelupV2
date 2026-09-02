<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$menuView = (isset($_GET['view']) && $_GET['view'] === 'tree') ? 'tree' : 'list';
if (isset($_POST['return_view']) && $_POST['return_view'] === 'tree') {
    $menuView = 'tree';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && is_admin()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $user = current_user();
    $userId = $user ? $user['id'] : null;

    if ($action === 'create') {
        $id = MenuService::create($project['id'], $_POST);
        log_activity($project['id'], $userId, 'menu_create', 'menu', $id, '메뉴 "' . $_POST['title'] . '" 생성');
        flash('success', '메뉴가 등록되었습니다.');
    } elseif ($action === 'update') {
        MenuService::update((int) $_POST['id'], $_POST);
        flash('success', '메뉴가 수정되었습니다.');
    } elseif ($action === 'delete') {
        $deleteId = (int) $_POST['id'];
        $target = MenuService::getById($deleteId);
        $childCount = 0;
        if ($target) {
            $childCount = count(MenuService::collectDescendantIds($deleteId));
            MenuService::delete($deleteId);
            $msg = '메뉴 "' . (isset($target['title']) ? $target['title'] : '') . '"';
            if ($childCount > 0) {
                $msg .= ' 및 하위 ' . $childCount . '건';
            }
            $msg .= '이 삭제되었습니다.';
            log_activity($project['id'], $userId, 'menu_delete', 'menu', $deleteId, $msg);
            flash('success', $msg);
        } else {
            flash('error', '삭제할 메뉴를 찾을 수 없습니다.');
        }
    }
    redirect('menus.php' . ($menuView === 'tree' ? '?view=tree' : ''));
}

$menuList = MenuService::getByProject($project['id']);
if (MenuService::needsCodeRebuild($project['id'])) {
    MenuService::rebuildCodes($project['id']);
    $menuList = MenuService::getByProject($project['id']);
}

$menuTree = build_menu_tree($menuList);
$pageTitle = '메뉴 구성도';
$currentPage = 'menus';
$GLOBALS['LABELUP_MENU_VIEW'] = $menuView;

$printMode = isset($_GET['print']) && $_GET['print'] === '1';
if ($printMode) {
    include __DIR__ . '/views/menus-print.php';
    exit;
}

render_page(__DIR__ . '/views/menus.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker', 'menuView'
));
