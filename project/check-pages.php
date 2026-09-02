<?php
/**
 * 페이지별 500 원인 진단 (설치 후 삭제 권장)
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

echo '<h1>Label-UP 페이지 진단</h1><pre>';

echo 'PHP: ' . PHP_VERSION . "\n";
echo 'openssl: ' . (function_exists('openssl_random_pseudo_bytes') ? 'OK' : '없음') . "\n";
echo 'random_bytes: ' . (function_exists('random_bytes') ? 'OK' : '없음') . "\n";
echo 'security.php: ' . (file_exists(__DIR__ . '/includes/security.php') ? '있음' : '없음') . "\n";
echo 'render_menu_tree_view: ' . (function_exists('render_menu_tree_view') ? '있음' : '아직 없음') . "\n";
echo 'render_storyboard_menu_tree: ' . (function_exists('render_storyboard_menu_tree') ? '있음' : '아직 없음') . "\n\n";

try {
    require_once __DIR__ . '/includes/bootstrap.php';
    echo "bootstrap: OK\n";

    if (function_exists('csrf_token')) {
        $t = csrf_token();
        echo 'csrf_token: OK (' . strlen($t) . " chars)\n";
    } else {
        echo "csrf_token: 함수 없음\n";
    }

    if (function_exists('render_menu_tree_view')) {
        echo "render_menu_tree_view: OK\n";
    } else {
        echo "render_menu_tree_view: 없음 → menus.php 500 가능\n";
    }

    if (function_exists('render_storyboard_menu_tree')) {
        echo "render_storyboard_menu_tree: OK\n";
    } else {
        echo "render_storyboard_menu_tree: 없음 → storyboard.php 500 가능\n";
    }

    $project = ProjectService::getOrCreateDefault();
    $pid = $project['id'];
    echo "\nproject ID: {$pid}\n\n";

    $tests = array(
        'MenuService' => function () use ($pid) { return MenuService::getByProject($pid); },
        'IssueService' => function () use ($pid) { return IssueService::getByProject($pid); },
        'ArchiveService' => function () use ($pid) { return ArchiveService::getByProject($pid); },
        'milestones' => function () use ($pid) {
            $db = Database::getConnection();
            $s = $db->prepare('SELECT * FROM milestones WHERE project_id = ?');
            $s->execute(array($pid));
            return $s->fetchAll();
        },
        'notices' => function () use ($pid) {
            $db = Database::getConnection();
            $s = $db->prepare('SELECT n.*, u.name as author FROM notices n LEFT JOIN users u ON u.id = n.created_by WHERE n.project_id = ?');
            $s->execute(array($pid));
            return $s->fetchAll();
        },
        'view menus' => function () use ($pid) {
            $project = ProjectService::getById($pid);
            $menus = MenuService::getByProject($pid);
            $menuTree = build_menu_tree($menus);
            $pageTitle = 't'; $currentPage = 'menus';
            ob_start();
            include __DIR__ . '/views/menus.php';
            return strlen(ob_get_clean());
        },
        'view issues+csrf' => function () use ($pid) {
            $project = ProjectService::getById($pid);
            $menuTree = build_menu_tree(MenuService::getByProject($pid));
            $issues = IssueService::getByProject($pid);
            $users = get_all_users();
            $menus = MenuService::getByProject($pid);
            $filter = ''; $showNew = false; $prefillMenuId = 0;
            $pageTitle = 't'; $currentPage = 'issues';
            ob_start();
            include __DIR__ . '/views/issues.php';
            return strlen(ob_get_clean());
        },
        'view notices' => function () use ($pid) {
            $project = ProjectService::getById($pid);
            $menuTree = build_menu_tree(MenuService::getByProject($pid));
            $db = Database::getConnection();
            $s = $db->prepare('SELECT n.*, u.name as author FROM notices n LEFT JOIN users u ON u.id = n.created_by WHERE n.project_id = ?');
            $s->execute(array($pid));
            $notices = $s->fetchAll();
            $pageTitle = 't'; $currentPage = 'notices';
            ob_start();
            include __DIR__ . '/views/notices.php';
            return strlen(ob_get_clean());
        },
        'full render menus+layout' => function () use ($pid) {
            $project = ProjectService::getById($pid);
            $menus = MenuService::getByProject($pid);
            $menuTree = build_menu_tree($menus);
            $phaseTracker = ProjectService::getPhaseTracker($pid);
            $pageTitle = '메뉴 구성도';
            $currentPage = 'menus';
            $vars = compact('pageTitle', 'currentPage', 'project', 'menuTree', 'menus', 'phaseTracker');
            extract($vars);
            ob_start();
            include __DIR__ . '/views/menus.php';
            $content = ob_get_clean();
            ob_start();
            include __DIR__ . '/includes/layout.php';
            return strlen(ob_get_clean());
        },
        'StoryboardService counts' => function () use ($pid) {
            return StoryboardService::getFrameCountsForProject($pid);
        },
        'StoryboardService public menus' => function () use ($pid) {
            return StoryboardService::getPublicMenuIds($pid);
        },
        'view storyboard+csrf' => function () use ($pid) {
            $project = ProjectService::getById($pid);
            $menus = MenuService::getByProject($pid);
            $menuTree = build_menu_tree($menus);
            $menuId = !empty($menuTree) ? (int) get_first_menu_id_from_tree($menuTree) : 0;
            $menu = $menuId ? MenuService::getById($menuId) : null;
            $storyboard = $menu ? StoryboardService::getByMenu($menuId) : null;
            $frames = $storyboard ? StoryboardService::getFrames($storyboard['id']) : array();
            $comments = $storyboard ? StoryboardService::getComments($storyboard['id']) : array();
            $history = $storyboard ? StoryboardService::getHistory($storyboard['id']) : array();
            $frameCounts = StoryboardService::getFrameCountsForProject($pid);
            $activeFrameId = !empty($frames) ? (int) $frames[0]['id'] : 0;
            $breadcrumb = $menu ? MenuService::getBreadcrumb($menuId) : array();
            $user = array('id' => 1, 'name' => 'Test', 'role' => 'admin', 'avatar_color' => '#6366f1');
            $pageTitle = '스토리보드';
            $currentPage = 'storyboard';
            $storyboardAdminMode = false;
            $storyboardBackUrl = null;
            $storyboardLinkBase = url('storyboard.php');
            $canEditStoryboard = true;
            $canManageAll = true;
            ob_start();
            include __DIR__ . '/views/storyboard.php';
            return strlen(ob_get_clean());
        },
        'full render storyboard+layout' => function () use ($pid) {
            $project = ProjectService::getById($pid);
            $menus = MenuService::getByProject($pid);
            $menuTree = build_menu_tree($menus);
            $menuId = !empty($menuTree) ? (int) get_first_menu_id_from_tree($menuTree) : 0;
            $menu = $menuId ? MenuService::getById($menuId) : null;
            $storyboard = $menu ? StoryboardService::getByMenu($menuId) : null;
            $frames = $storyboard ? StoryboardService::getFrames($storyboard['id']) : array();
            $comments = $storyboard ? StoryboardService::getComments($storyboard['id']) : array();
            $history = $storyboard ? StoryboardService::getHistory($storyboard['id']) : array();
            $frameCounts = StoryboardService::getFrameCountsForProject($pid);
            $activeFrameId = !empty($frames) ? (int) $frames[0]['id'] : 0;
            $breadcrumb = $menu ? MenuService::getBreadcrumb($menuId) : array();
            $user = array('id' => 1, 'name' => 'Test', 'role' => 'admin', 'avatar_color' => '#6366f1');
            $phaseTracker = ProjectService::getPhaseTracker($pid);
            $pageTitle = '스토리보드';
            $currentPage = 'storyboard';
            $storyboardAdminMode = false;
            $storyboardBackUrl = null;
            $storyboardLinkBase = url('storyboard.php');
            $canEditStoryboard = true;
            $canManageAll = true;
            ob_start();
            include __DIR__ . '/views/storyboard.php';
            $content = ob_get_clean();
            ob_start();
            include __DIR__ . '/includes/layout_storyboard.php';
            return strlen(ob_get_clean());
        },
        'MenuService getById' => function () use ($pid) {
            $menus = MenuService::getByProject($pid);
            if (empty($menus)) {
                return array();
            }
            return MenuService::getById($menus[0]['id']);
        },
        'MenuService breadcrumb' => function () use ($pid) {
            $menus = MenuService::getByProject($pid);
            if (empty($menus)) {
                return array();
            }
            return MenuService::getBreadcrumb($menus[0]['id']);
        },
        'view menu_detail+csrf' => function () use ($pid) {
            $project = ProjectService::getById($pid);
            $menus = MenuService::getByProject($pid);
            $menuTree = build_menu_tree($menus);
            $menu = !empty($menus) ? MenuService::getById($menus[0]['id']) : null;
            $menuId = $menu ? (int) $menu['id'] : 0;
            $breadcrumb = $menuId ? MenuService::getBreadcrumb($menuId) : array();
            $users = get_all_users();
            $menuIssues = array();
            $pageTitle = $menu ? $menu['title'] : '메뉴';
            $currentPage = 'menus';
            ob_start();
            include __DIR__ . '/views/menu_detail.php';
            return strlen(ob_get_clean());
        },
        'full render menu_detail+layout' => function () use ($pid) {
            $project = ProjectService::getById($pid);
            $menus = MenuService::getByProject($pid);
            $menuTree = build_menu_tree($menus);
            $menu = !empty($menus) ? MenuService::getById($menus[0]['id']) : null;
            $menuId = $menu ? (int) $menu['id'] : 0;
            $breadcrumb = $menuId ? MenuService::getBreadcrumb($menuId) : array();
            $users = get_all_users();
            $menuIssues = array();
            $phaseTracker = ProjectService::getPhaseTracker($pid);
            $pageTitle = $menu ? $menu['title'] : '메뉴';
            $currentPage = 'menus';
            ob_start();
            include __DIR__ . '/views/menu_detail.php';
            $content = ob_get_clean();
            ob_start();
            include __DIR__ . '/includes/layout.php';
            return strlen(ob_get_clean());
        },
    );

    foreach ($tests as $name => $fn) {
        try {
            $r = $fn();
            $info = is_array($r) ? count($r) . ' rows' : (is_numeric($r) ? $r . ' bytes' : 'OK');
            echo "OK  {$name} ({$info})\n";
        } catch (Exception $e) {
            echo "FAIL {$name}: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "\nFATAL: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
}

echo '</pre>';
