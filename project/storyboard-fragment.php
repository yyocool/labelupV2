<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => false, 'error' => 'Not installed'));
    exit;
}

require_login();
extract(init_project_context());

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$canManageAll = is_admin();
$linkBase = url('storyboard.php');
/* 전체화면 메뉴 전환: 권한과 무관하게 전체 메뉴 트리 사용 */

$menuId = (int) (isset($_GET['menu_id']) ? $_GET['menu_id'] : 0);
if (!$menuId) {
    echo json_encode(array('ok' => false, 'error' => 'menu_id가 필요합니다.'));
    exit;
}

$menu = MenuService::getById($menuId);
if (!$menu || (int) $menu['project_id'] !== (int) $project['id']) {
    echo json_encode(array('ok' => false, 'error' => '메뉴를 찾을 수 없습니다.'));
    exit;
}

$storyboard = StoryboardService::getByMenu($menuId);

$fragmentVars = StoryboardFileService::getFragmentVars($menus, $menuTree, $menuId, $linkBase);
$html = StoryboardFileService::captureFragment($menu, $storyboard, $fragmentVars);
$code = isset($menu['menu_code']) ? $menu['menu_code'] : '';

echo json_encode(array(
    'ok' => true,
    'menuId' => $menuId,
    'title' => isset($menu['title']) ? $menu['title'] : '',
    'code' => $code,
    'status' => StoryboardFileService::getContentStatus($code),
    'html' => $html,
    'pageUrl' => $linkBase . '?menu_id=' . $menuId,
    'hasZones' => (strpos($html, 'data-zone-id') !== false),
), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
