<?php
/**
 * 메뉴 구성도 시드 (관리자 전용)
 * 원격 서버에서 1회 실행 후 삭제 권장
 */
require_once __DIR__ . '/../includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_admin();
extract(init_project_context());

$seedCount = MenuSeedService::countNodes(MenuSeedService::getTreeData());
$currentCount = count(MenuService::getByProject($project['id']));
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'seed') {
        try {
            $inserted = MenuSeedService::seedProject($project['id']);
            $user = current_user();
            log_activity(
                $project['id'],
                $user ? $user['id'] : null,
                'menu_seed',
                'menu',
                null,
                '메뉴 구성도 시드 삽입 (' . $inserted . '개)'
            );
            $result = array(
                'success' => true,
                'message' => '기존 메뉴를 초기화하고 ' . $inserted . '개 메뉴를 등록했습니다.',
                'count' => $inserted,
            );
            $currentCount = $inserted;
        } catch (Exception $e) {
            $result = array(
                'success' => false,
                'message' => '오류: ' . $e->getMessage(),
            );
        }
    }
}

$pageTitle = '메뉴 시드';
$currentPage = 'admin-menus';

render_admin_page(__DIR__ . '/views/seed_menus.php', compact(
    'pageTitle', 'currentPage', 'project', 'seedCount', 'currentCount', 'result', 'phaseTracker'
));
