<?php

class MenuService
{
    public static function getByProject($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT m.*, mp.storyboard_status, mp.design_status, mp.publishing_status, mp.coding_status, mp.review_status,
                   mp.storyboard_note, mp.design_note, mp.publishing_note, mp.coding_note, mp.review_note,
                   mp.assignee_id, mp.priority, mp.due_date
            FROM menus m
            LEFT JOIN menu_progress mp ON mp.menu_id = m.id
            WHERE m.project_id = ? AND m.is_active = 1
            ORDER BY m.depth, m.sort_order, m.id
        ');
        $stmt->execute([$projectId]);
        $menus = $stmt->fetchAll();
        foreach ($menus as &$menu) {
            $menu['progress_pct'] = progress_percent([
                'storyboard_status' => isset($menu['storyboard_status']) ? $menu['storyboard_status'] : 'pending',
                'design_status'     => isset($menu['design_status']) ? $menu['design_status'] : 'pending',
                'publishing_status' => isset($menu['publishing_status']) ? $menu['publishing_status'] : 'pending',
                'coding_status'     => isset($menu['coding_status']) ? $menu['coding_status'] : 'pending',
                'review_status'     => isset($menu['review_status']) ? $menu['review_status'] : 'pending',
            ]);
        }
        return $menus;
    }

    public static function getById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT m.*, mp.storyboard_status, mp.design_status, mp.publishing_status, mp.coding_status, mp.review_status,
                   mp.storyboard_note, mp.design_note, mp.publishing_note, mp.coding_note, mp.review_note, mp.general_note,
                   mp.assignee_id, mp.due_date, mp.priority
            FROM menus m
            LEFT JOIN menu_progress mp ON mp.menu_id = m.id
            WHERE m.id = ?
        ');
        $stmt->execute(array($id));
        $menu = $stmt->fetch();
        if ($menu) {
            if ($menu['storyboard_status'] === null && $menu['publishing_status'] === null) {
                self::ensureProgressRow($menu['id']);
                $stmt->execute(array($id));
                $menu = $stmt->fetch();
            }
            if ($menu) {
                $menu['progress_pct'] = progress_percent(array(
                    'storyboard_status' => isset($menu['storyboard_status']) ? $menu['storyboard_status'] : 'pending',
                    'design_status'     => isset($menu['design_status']) ? $menu['design_status'] : 'pending',
                    'publishing_status' => isset($menu['publishing_status']) ? $menu['publishing_status'] : 'pending',
                    'coding_status'     => isset($menu['coding_status']) ? $menu['coding_status'] : 'pending',
                    'review_status'     => isset($menu['review_status']) ? $menu['review_status'] : 'pending',
                ));
            }
        }
        return $menu ? $menu : null;
    }

    public static function ensureProgressRow($menuId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM menu_progress WHERE menu_id = ? LIMIT 1');
        $stmt->execute(array($menuId));
        if (!$stmt->fetch()) {
            $db->prepare('INSERT INTO menu_progress (menu_id) VALUES (?)')->execute(array($menuId));
        }
    }

    public static function create($projectId, array $data)
    {
        $db = Database::getConnection();
        $parentId = $data['parent_id'] ?: null;
        $depth = 0;
        if ($parentId) {
            $parent = self::getById($parentId);
            $depth = (isset($parent['depth']) ? $parent['depth'] : 0) + 1;
        }

        $stmt = $db->prepare('
            INSERT INTO menus (project_id, parent_id, title, slug, description, sort_order, depth, url_path)
            VALUES (?,?,?,?,?,?,?,?)
        ');
        $stmt->execute([
            $projectId,
            $parentId,
            $data['title'],
            isset($data['slug']) ? $data['slug'] : null,
            isset($data['description']) ? $data['description'] : null,
            isset($data['sort_order']) ? $data['sort_order'] : 0,
            $depth,
            isset($data['url_path']) ? $data['url_path'] : null,
        ]);
        $menuId = (int) $db->lastInsertId();

        $db->prepare('INSERT INTO menu_progress (menu_id) VALUES (?)')->execute([$menuId]);
        self::rebuildCodes($projectId);
        return $menuId;
    }

    public static function update($id, array $data)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE menus SET title=?, slug=?, description=?, sort_order=?, url_path=?, parent_id=?, depth=?, updated_at=NOW()
            WHERE id=?
        ');
        $parentId = $data['parent_id'] ?: null;
        $depth = 0;
        if ($parentId) {
            $parent = self::getById($parentId);
            $depth = (isset($parent['depth']) ? $parent['depth'] : 0) + 1;
        }
        $stmt->execute([
            $data['title'],
            isset($data['slug']) ? $data['slug'] : null,
            isset($data['description']) ? $data['description'] : null,
            isset($data['sort_order']) ? $data['sort_order'] : 0,
            isset($data['url_path']) ? $data['url_path'] : null,
            $parentId,
            $depth,
            $id,
        ]);

        $menu = self::getById($id);
        if ($menu) {
            self::rebuildCodes($menu['project_id']);
        }
    }

    public static function delete($id)
    {
        $db = Database::getConnection();
        $menu = self::getById($id);
        if (!$menu) {
            return false;
        }
        $projectId = (int) $menu['project_id'];
        $ids = self::collectDescendantIds((int) $id);
        $ids[] = (int) $id;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("UPDATE menus SET is_active = 0 WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        self::rebuildCodes($projectId);
        return true;
    }

    /** 하위 메뉴 id 목록 (재귀, 본인 제외) */
    public static function collectDescendantIds($parentId)
    {
        $db = Database::getConnection();
        $ids = array();
        $queue = array((int) $parentId);
        while ($queue) {
            $pid = array_shift($queue);
            $stmt = $db->prepare('SELECT id FROM menus WHERE parent_id = ? AND is_active = 1');
            $stmt->execute(array($pid));
            foreach ($stmt->fetchAll() as $row) {
                $cid = (int) $row['id'];
                $ids[] = $cid;
                $queue[] = $cid;
            }
        }
        return $ids;
    }

    /**
     * IA 관례형 계층 메뉴코드 생성 (01, 01-01, 01-01-03 …)
     */
    public static function rebuildCodes($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT id, parent_id, sort_order
            FROM menus
            WHERE project_id = ? AND is_active = 1
            ORDER BY depth, sort_order, id
        ');
        $stmt->execute(array((int) $projectId));
        $menus = $stmt->fetchAll();
        if (empty($menus)) {
            return;
        }

        $tree = build_menu_tree($menus);
        $update = $db->prepare('UPDATE menus SET menu_code = ? WHERE id = ?');
        self::assignMenuCodes($tree, null, $update);
    }

    private static function assignMenuCodes(array $nodes, $parentCode, $update)
    {
        $index = 1;
        foreach ($nodes as $node) {
            $segment = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $code = ($parentCode === null) ? $segment : $parentCode . '-' . $segment;
            $update->execute(array($code, (int) $node['id']));
            if (!empty($node['children'])) {
                self::assignMenuCodes($node['children'], $code, $update);
            }
            $index++;
        }
    }

    public static function needsCodeRebuild($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT COUNT(*) FROM menus
            WHERE project_id = ? AND is_active = 1 AND (menu_code IS NULL OR menu_code = "")
        ');
        $stmt->execute(array((int) $projectId));
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function updateProgress($menuId, array $data)
    {
        self::ensureProgressRow($menuId);
        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE menu_progress SET
                storyboard_status=?, design_status=?, publishing_status=?, coding_status=?, review_status=?,
                storyboard_note=?, design_note=?, publishing_note=?, coding_note=?, review_note=?, general_note=?,
                assignee_id=?, due_date=?, priority=?, updated_at=NOW()
            WHERE menu_id=?
        ');
        $stmt->execute([
            isset($data['storyboard_status']) ? $data['storyboard_status'] : 'pending',
            isset($data['design_status']) ? $data['design_status'] : 'pending',
            isset($data['publishing_status']) ? $data['publishing_status'] : 'pending',
            isset($data['coding_status']) ? $data['coding_status'] : 'pending',
            isset($data['review_status']) ? $data['review_status'] : 'pending',
            isset($data['storyboard_note']) ? $data['storyboard_note'] : null,
            isset($data['design_note']) ? $data['design_note'] : null,
            isset($data['publishing_note']) ? $data['publishing_note'] : null,
            isset($data['coding_note']) ? $data['coding_note'] : null,
            isset($data['review_note']) ? $data['review_note'] : null,
            isset($data['general_note']) ? $data['general_note'] : null,
            $data['assignee_id'] ?: null,
            $data['due_date'] ?: null,
            isset($data['priority']) ? $data['priority'] : 'medium',
            $menuId,
        ]);
    }

    public static function getBreadcrumb($menuId)
    {
        $crumb = array();
        $current = self::getById($menuId);
        while ($current) {
            array_unshift($crumb, $current);
            if (!$current['parent_id']) break;
            $current = self::getById($current['parent_id']);
        }
        return $crumb;
    }
}
