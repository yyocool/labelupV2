<?php
/**
 * 스토리보드 전체 PDF(인쇄)용 데이터 수집
 */
class StoryboardPdfService
{
    /**
     * 메뉴 트리를 PDF 페이지 목록으로 펼침
     * @return array<int,array>
     */
    public static function collectPages($projectId, $scope = 'all')
    {
        $menus = MenuService::getByProject($projectId);
        $tree = build_menu_tree($menus);
        $pages = array();
        self::walkTree($tree, array(), $pages, $scope);
        return $pages;
    }

    private static function walkTree(array $nodes, array $trail, array &$pages, $scope)
    {
        foreach ($nodes as $item) {
            $code = isset($item['menu_code']) ? $item['menu_code'] : '';
            $status = StoryboardFileService::getContentStatus($code);
            $nextTrail = $trail;
            $nextTrail[] = isset($item['title']) ? $item['title'] : '';

            $include = false;
            if ($scope === 'ready') {
                $include = ($status === 'ready');
            } elseif ($scope === 'files') {
                $include = ($status === 'ready' || $status === 'stub');
            } else {
                // all: 파일이 있거나 depth가 있는 메뉴 전부
                $include = ($status !== 'none' || empty($item['children']));
            }

            if ($include || $status !== 'none') {
                if ($status !== 'none' || ($scope === 'all' && empty($item['children']))) {
                    if ($status !== 'none') {
                        $pages[] = array(
                            'id' => (int) $item['id'],
                            'title' => isset($item['title']) ? $item['title'] : '',
                            'code' => $code,
                            'status' => $status,
                            'trail' => $nextTrail,
                            'url_path' => isset($item['url_path']) ? $item['url_path'] : '',
                            'has_hifi' => StoryboardFileService::hasHifiDesign($code),
                        );
                    }
                }
            }

            if (!empty($item['children'])) {
                self::walkTree($item['children'], $nextTrail, $pages, $scope);
            }
        }
    }

    public static function statusLabel($status)
    {
        if ($status === 'ready') {
            return '완료';
        }
        if ($status === 'stub') {
            return '준비중';
        }
        return '미작성';
    }

    /**
     * 와이어프레임 HTML 캡처
     */
    public static function renderWireframeHtml(array $menu, array $menus, $menuTree, $linkBase)
    {
        $vars = StoryboardFileService::getFragmentVars(
            $menus,
            $menuTree,
            isset($menu['id']) ? (int) $menu['id'] : 0,
            $linkBase
        );
        return StoryboardFileService::captureFragment($menu, null, $vars);
    }
}
