<?php

class StoryboardFileService
{
    public static function getDir()
    {
        return APP_ROOT . '/storyboard';
    }

    public static function getFilePath($menuCode)
    {
        if (!$menuCode || !preg_match('/^[0-9]+(?:-[0-9]+)*$/', $menuCode)) {
            return null;
        }
        return self::getDir() . '/' . $menuCode . '.php';
    }

    public static function exists($menuCode)
    {
        $path = self::getFilePath($menuCode);
        return $path && file_exists($path);
    }

    public static function render(array $menu, $storyboard = null, array $vars = array())
    {
        $path = self::getFilePath(isset($menu['menu_code']) ? $menu['menu_code'] : '');
        if (!$path || !file_exists($path)) {
            return false;
        }
        extract($vars, EXTR_SKIP);
        include $path;
        return true;
    }

    /**
     * 스토리보드 파일 콘텐츠 상태: ready | stub | none
     */
    public static function getContentStatus($menuCode)
    {
        $path = self::getFilePath($menuCode);
        if (!$path || !file_exists($path)) {
            return 'none';
        }
        $content = @file_get_contents($path);
        if ($content === false) {
            return 'none';
        }
        if (strpos($content, 'sb-page--placeholder') !== false) {
            return 'stub';
        }
        return 'ready';
    }

    /**
     * hi-fi 디자인 산출물 포함 여부 (와이어프레임·CSS 클래스명만으로는 판정하지 않음)
     */
    public static function hasHifiDesign($menuCode)
    {
        $path = self::getFilePath($menuCode);
        if (!$path || !file_exists($path)) {
            return false;
        }
        $content = @file_get_contents($path);
        if ($content === false) {
            return false;
        }
        if (preg_match('/[\'"][^\'"]*-hifi-(?:wireframe-body|styles)\.php[\'"]/', $content)) {
            return true;
        }
        $fragDir = self::getDir() . '/_fragments';
        $bodyFile = $menuCode . '-hifi-wireframe-body.php';
        $stylesFile = $menuCode . '-hifi-styles.php';
        if (file_exists($fragDir . '/' . $bodyFile) && stripos($content, $bodyFile) !== false) {
            return true;
        }
        if (file_exists($fragDir . '/' . $stylesFile) && stripos($content, $stylesFile) !== false) {
            return true;
        }
        return false;
    }

    public static function getContentStatusMap(array $menus)
    {
        $map = array();
        foreach ($menus as $menu) {
            if (!empty($menu['id'])) {
                $map[$menu['id']] = self::getContentStatus(isset($menu['menu_code']) ? $menu['menu_code'] : '');
            }
        }
        return $map;
    }

    public static function buildFsMenuTree(array $tree, $activeMenuId, array $statusMap, $linkBase)
    {
        $nodes = array();
        foreach ($tree as $item) {
            $id = (int) $item['id'];
            $node = array(
                'id' => $id,
                'title' => isset($item['title']) ? $item['title'] : '',
                'code' => isset($item['menu_code']) ? $item['menu_code'] : '',
                'url' => $linkBase . (strpos($linkBase, '?') !== false ? '&' : '?') . 'menu_id=' . $id,
                'active' => ($id === (int) $activeMenuId),
                'status' => isset($statusMap[$id]) ? $statusMap[$id] : 'none',
            );
            if (!empty($item['children'])) {
                $node['children'] = self::buildFsMenuTree($item['children'], $activeMenuId, $statusMap, $linkBase);
            }
            $nodes[] = $node;
        }
        return $nodes;
    }

    /**
     * 전체화면 AJAX용 — 와이어프레임 HTML만 캡처
     */
    public static function captureFragment(array $menu, $storyboard = null, array $vars = array())
    {
        $code = isset($menu['menu_code']) ? $menu['menu_code'] : '';
        $path = self::getFilePath($code);

        if (!$path || !file_exists($path)) {
            return self::wrapFragmentEmpty($menu);
        }

        $vars['sbRenderMode'] = 'wireframe';
        ob_start();
        self::render($menu, $storyboard, $vars);
        $html = trim(ob_get_clean());

        if ($html === '') {
            return self::wrapFragmentEmpty($menu);
        }

        if (strpos($html, 'id="sbWfRoot"') === false) {
            $html = '<div class="sb-wf-fragment-stub">' . $html . '</div>';
        }

        return $html;
    }

    private static function wrapFragmentEmpty(array $menu)
    {
        $title = isset($menu['title']) ? htmlspecialchars($menu['title'], ENT_QUOTES, 'UTF-8') : '메뉴';
        return '<div class="sb-wf-fragment-empty"><p><strong>' . $title . '</strong></p><p>스토리보드 파일이 없습니다.</p></div>';
    }

    public static function getFragmentVars(array $menus, $menuTree, $menuId, $linkBase)
    {
        return array(
            'sbFsMenuTree' => $menuTree,
            'sbFsMenuId' => $menuId,
            'sbFsLinkBase' => $linkBase,
            'sbFsContentStatusMap' => self::getContentStatusMap($menus),
        );
    }

    public static function generateStubContent($menuCode, $title)
    {
        $code = addslashes($menuCode);
        $name = addslashes($title);
        return <<<PHP
<?php
/**
 * 스토리보드: {$title}
 * 메뉴코드: {$menuCode}
 *
 * @var array \$menu
 * @var array|null \$storyboard
 */
?>
<div class="sb-page sb-page--placeholder">
    <div class="sb-page-meta">
        <span class="sb-page-code"><?= e(isset(\$menu['menu_code']) ? \$menu['menu_code'] : '{$menuCode}') ?></span>
        <h2 class="sb-page-title"><?= e(isset(\$menu['title']) ? \$menu['title'] : '{$name}') ?></h2>
    </div>
    <p class="sb-page-notice">스토리보드 작업 예정입니다.</p>
    <p class="sb-page-hint">이 파일(<code>storyboard/{$menuCode}.php</code>)을 직접 편집하여 화면을 구성하세요.</p>
</div>

PHP;
    }

    public static function writeStub($menuCode, $title, $overwrite = false)
    {
        $path = self::getFilePath($menuCode);
        if (!$path) {
            return false;
        }
        $dir = self::getDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (file_exists($path) && !$overwrite) {
            return false;
        }
        file_put_contents($path, self::generateStubContent($menuCode, $title));
        return true;
    }

    public static function generateStubsFromSeedTree($overwrite = false)
    {
        $tree = MenuSeedService::getTreeData();
        $created = 0;
        $skipped = 0;
        self::walkSeedTree($tree, null, $created, $skipped, $overwrite);
        return array('created' => $created, 'skipped' => $skipped);
    }

    public static function syncStubsForProject($projectId)
    {
        $menus = MenuService::getByProject($projectId);
        $validCodes = array();
        foreach ($menus as $menu) {
            if (!empty($menu['menu_code'])) {
                $validCodes[$menu['menu_code']] = $menu['title'];
            }
        }

        $dir = self::getDir();
        if (is_dir($dir)) {
            foreach (glob($dir . '/*.php') as $file) {
                $code = basename($file, '.php');
                if (!isset($validCodes[$code])) {
                    @unlink($file);
                }
            }
        }

        $created = 0;
        foreach ($validCodes as $code => $title) {
            if (self::writeStub($code, $title, false)) {
                $created++;
            }
        }
        return array('created' => $created, 'total' => count($validCodes));
    }

    public static function generateStubsFromMenus($projectId, $overwrite = false)
    {
        $menus = MenuService::getByProject($projectId);
        $created = 0;
        $skipped = 0;
        foreach ($menus as $menu) {
            if (empty($menu['menu_code'])) {
                continue;
            }
            if (self::writeStub($menu['menu_code'], $menu['title'], $overwrite)) {
                $created++;
            } else {
                $skipped++;
            }
        }
        return array('created' => $created, 'skipped' => $skipped);
    }

    private static function walkSeedTree(array $nodes, $parentCode, &$created, &$skipped, $overwrite)
    {
        $index = 1;
        foreach ($nodes as $node) {
            $segment = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $code = ($parentCode === null) ? $segment : $parentCode . '-' . $segment;
            if (self::writeStub($code, $node['title'], $overwrite)) {
                $created++;
            } else {
                $skipped++;
            }
            if (!empty($node['children'])) {
                self::walkSeedTree($node['children'], $code, $created, $skipped, $overwrite);
            }
            $index++;
        }
    }

    public static function getFileStatusMap(array $menus)
    {
        $map = array();
        foreach ($menus as $menu) {
            if (!empty($menu['menu_code'])) {
                $map[$menu['id']] = self::exists($menu['menu_code']);
            }
        }
        return $map;
    }
}
