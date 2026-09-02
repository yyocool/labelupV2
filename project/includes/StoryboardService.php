<?php

class StoryboardService
{
    public static function getByMenu($menuId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM storyboards WHERE menu_id = ? ORDER BY updated_at DESC LIMIT 1');
        $stmt->execute([$menuId]);
        return $stmt->fetch() ?: null;
    }

    public static function getById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM storyboards WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create($menuId, array $data, $userId = null)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT INTO storyboards (menu_id, title, description, version, status, visibility, created_by) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([
            $menuId,
            isset($data['title']) ? $data['title'] : '스토리보드',
            isset($data['description']) ? $data['description'] : null,
            isset($data['version']) ? $data['version'] : '1.0',
            isset($data['status']) ? $data['status'] : 'draft',
            isset($data['visibility']) ? $data['visibility'] : 'working',
            $userId,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function getOrCreate($menuId, $userId = null)
    {
        $sb = self::getByMenu($menuId);
        if ($sb) return $sb;
        $id = self::create($menuId, ['title' => '스토리보드'], $userId);
        return self::getById($id);
    }

    public static function getFrames($storyboardId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM storyboard_frames WHERE storyboard_id = ? ORDER BY sort_order, id');
        $stmt->execute([$storyboardId]);
        return $stmt->fetchAll();
    }

    public static function addFrame($storyboardId, array $data)
    {
        $db = Database::getConnection();
        $maxOrder = $db->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM storyboard_frames WHERE storyboard_id = ?');
        $maxOrder->execute([$storyboardId]);
        $order = (int) $maxOrder->fetchColumn();

        $stmt = $db->prepare('INSERT INTO storyboard_frames (storyboard_id, title, description, sort_order, notes) VALUES (?,?,?,?,?)');
        $stmt->execute([
            $storyboardId,
            $data['title'],
            isset($data['description']) ? $data['description'] : null,
            $order,
            isset($data['notes']) ? $data['notes'] : null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function updateFrame($frameId, array $data)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE storyboard_frames SET title=?, description=?, notes=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([
            $data['title'],
            isset($data['description']) ? $data['description'] : null,
            isset($data['notes']) ? $data['notes'] : null,
            $frameId,
        ]);
    }

    public static function deleteFrame($frameId)
    {
        $db = Database::getConnection();
        $db->prepare('DELETE FROM storyboard_frames WHERE id = ?')->execute([$frameId]);
    }

    public static function reorderFrames($storyboardId, array $frameIds)
    {
        $db = Database::getConnection();
        foreach ($frameIds as $order => $frameId) {
            $db->prepare('UPDATE storyboard_frames SET sort_order = ? WHERE id = ? AND storyboard_id = ?')
               ->execute([$order, $frameId, $storyboardId]);
        }
    }

    /** 프로젝트 메뉴별 스토리보드 화면 수 */
    public static function getFrameCountsForProject($projectId)
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('
                SELECT m.id as menu_id, COUNT(sf.id) as frame_count
                FROM menus m
                LEFT JOIN storyboards sb ON sb.menu_id = m.id
                LEFT JOIN storyboard_frames sf ON sf.storyboard_id = sb.id
                WHERE m.project_id = ? AND m.is_active = 1
                GROUP BY m.id
            ');
            $stmt->execute(array($projectId));
            $counts = array();
            foreach ($stmt->fetchAll() as $row) {
                $counts[$row['menu_id']] = (int) $row['frame_count'];
            }
            return $counts;
        } catch (Exception $e) {
            return array();
        }
    }

    public static function getFrameById($frameId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM storyboard_frames WHERE id = ?');
        $stmt->execute(array($frameId));
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    /* ── 의견 ── */
    public static function getComments($storyboardId)
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('
                SELECT sc.*, u.name as user_name, u.avatar_color, u.role as user_role,
                       sf.title as frame_title
                FROM storyboard_comments sc
                JOIN users u ON u.id = sc.user_id
                LEFT JOIN storyboard_frames sf ON sf.id = sc.frame_id
                WHERE sc.storyboard_id = ?
                ORDER BY sc.created_at DESC
            ');
            $stmt->execute(array($storyboardId));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return array();
        }
    }

    public static function addComment($storyboardId, $userId, $content, $frameId = null)
    {
        $db = Database::getConnection();
        $frameId = $frameId ? (int) $frameId : null;
        $stmt = $db->prepare('INSERT INTO storyboard_comments (storyboard_id, frame_id, user_id, content) VALUES (?,?,?,?)');
        $stmt->execute(array($storyboardId, $frameId, $userId, trim($content)));
        return (int) $db->lastInsertId();
    }

    /* ── 변경 이력 ── */
    public static function logHistory($storyboardId, $userId, $action, $summary, $frameId = null, $detail = null)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT INTO storyboard_history (storyboard_id, frame_id, user_id, action, summary, detail) VALUES (?,?,?,?,?,?)');
        $stmt->execute(array(
            $storyboardId,
            $frameId ? (int) $frameId : null,
            $userId,
            $action,
            $summary,
            $detail,
        ));
    }

    public static function getHistory($storyboardId, $limit = 50)
    {
        try {
            $db = Database::getConnection();
            $limit = max(1, min(200, (int) $limit));
            $stmt = $db->prepare('
                SELECT sh.*, u.name as user_name, u.avatar_color,
                       sf.title as frame_title
                FROM storyboard_history sh
                LEFT JOIN users u ON u.id = sh.user_id
                LEFT JOIN storyboard_frames sf ON sf.id = sh.frame_id
                WHERE sh.storyboard_id = ?
                ORDER BY sh.created_at DESC
                LIMIT ' . $limit . '
            ');
            $stmt->execute(array($storyboardId));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return array();
        }
    }

    public static function buildFrameChangeDetail($before, $after)
    {
        $lines = array();
        $fields = array('title' => '화면명', 'description' => '설명', 'notes' => '메모');
        foreach ($fields as $key => $label) {
            $old = isset($before[$key]) ? $before[$key] : '';
            $new = isset($after[$key]) ? $after[$key] : '';
            if ($old != $new) {
                $lines[] = $label . ': "' . $old . '" → "' . $new . '"';
            }
        }
        return implode("\n", $lines);
    }

    public static function getVisibilityOptions()
    {
        return array(
            'working' => array('label' => '작업중', 'badge' => 'badge-yellow'),
            'public'  => array('label' => '공개',   'badge' => 'badge-green'),
        );
    }

    public static function canView(array $storyboard, $user = null)
    {
        if (is_admin()) {
            return true;
        }
        $visibility = isset($storyboard['visibility']) ? $storyboard['visibility'] : 'working';
        return $visibility === 'public';
    }

    public static function canEditFrames()
    {
        return is_admin();
    }

    public static function setVisibility($storyboardId, $visibility)
    {
        $options = self::getVisibilityOptions();
        if (!isset($options[$visibility])) {
            $visibility = 'working';
        }
        $db = Database::getConnection();
        $db->prepare('UPDATE storyboards SET visibility = ?, updated_at = NOW() WHERE id = ?')
           ->execute(array($visibility, $storyboardId));
    }

    public static function updateMeta($storyboardId, array $data)
    {
        $db = Database::getConnection();
        $db->prepare('UPDATE storyboards SET title = ?, description = ?, updated_at = NOW() WHERE id = ?')
           ->execute(array(
               isset($data['title']) ? $data['title'] : '스토리보드',
               isset($data['description']) ? $data['description'] : null,
               $storyboardId,
           ));
    }

    public static function getListForProject($projectId)
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('
                SELECT m.id AS menu_id, m.title AS menu_title, m.depth, m.parent_id, m.url_path,
                       sb.id AS storyboard_id, sb.title AS storyboard_title, sb.description,
                       sb.visibility, sb.status, sb.updated_at,
                       (SELECT COUNT(*) FROM storyboard_frames sf WHERE sf.storyboard_id = sb.id) AS frame_count,
                       u.name AS creator_name
                FROM menus m
                LEFT JOIN storyboards sb ON sb.menu_id = m.id
                LEFT JOIN users u ON u.id = sb.created_by
                WHERE m.project_id = ? AND m.is_active = 1
                ORDER BY m.depth, m.sort_order, m.id
            ');
            $stmt->execute(array($projectId));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return array();
        }
    }

    public static function getPublicMenuIds($projectId)
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('
                SELECT m.id
                FROM menus m
                INNER JOIN storyboards sb ON sb.menu_id = m.id
                WHERE m.project_id = ? AND m.is_active = 1 AND sb.visibility = "public"
            ');
            $stmt->execute(array($projectId));
            $ids = array();
            foreach ($stmt->fetchAll() as $row) {
                $ids[] = (int) $row['id'];
            }
            return $ids;
        } catch (Exception $e) {
            return array();
        }
    }

    public static function filterMenuTree(array $tree, array $allowedMenuIds)
    {
        $filtered = array();
        foreach ($tree as $item) {
            $children = !empty($item['children'])
                ? self::filterMenuTree($item['children'], $allowedMenuIds)
                : array();
            $allowed = in_array((int) $item['id'], $allowedMenuIds, true);
            if ($allowed || !empty($children)) {
                $item['children'] = $children;
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

    public static function expandMenuIdsWithAncestors(array $menuIds, array $allMenus)
    {
        $byId = array();
        foreach ($allMenus as $menu) {
            $byId[(int) $menu['id']] = $menu;
        }
        $expanded = array();
        foreach ($menuIds as $id) {
            $current = (int) $id;
            while ($current && !in_array($current, $expanded, true)) {
                $expanded[] = $current;
                if (!isset($byId[$current]['parent_id']) || !$byId[$current]['parent_id']) {
                    break;
                }
                $current = (int) $byId[$current]['parent_id'];
            }
        }
        return $expanded;
    }

    public static function handleFramePost(array $project, array $menu, array $user, $storyboard, $redirectUrl)
    {
        if (!self::canEditFrames()) {
            flash('error', '편집 권한이 없습니다.');
            redirect($redirectUrl);
        }

        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if ($action === 'add_frame') {
            $newId = self::addFrame($storyboard['id'], $_POST);
            self::logHistory(
                $storyboard['id'], $user['id'], 'frame_create',
                '화면 추가: ' . $_POST['title'], $newId,
                isset($_POST['description']) ? $_POST['description'] : null
            );
            log_activity($project['id'], $user['id'], 'frame_add', 'storyboard', $storyboard['id'], '스토리보드 화면 추가: ' . $_POST['title']);
            flash('success', '화면이 추가되었습니다.');
            redirect($redirectUrl . '&frame_id=' . $newId);
        }

        if ($action === 'update_frame') {
            $frameId = (int) $_POST['frame_id'];
            $before = self::getFrameById($frameId);
            self::updateFrame($frameId, $_POST);
            $detail = $before ? self::buildFrameChangeDetail($before, $_POST) : null;
            self::logHistory(
                $storyboard['id'], $user['id'], 'frame_update',
                '화면 수정: ' . $_POST['title'], $frameId, $detail
            );
            flash('success', '화면이 수정되었습니다.');
            redirect($redirectUrl . '&frame_id=' . $frameId);
        }

        if ($action === 'delete_frame') {
            $frameId = (int) $_POST['frame_id'];
            $before = self::getFrameById($frameId);
            self::deleteFrame($frameId);
            self::logHistory(
                $storyboard['id'], $user['id'], 'frame_delete',
                '화면 삭제: ' . ($before ? $before['title'] : '#' . $frameId), $frameId
            );
            flash('success', '화면이 삭제되었습니다.');
            redirect($redirectUrl);
        }

        if ($action === 'add_comment') {
            $content = trim(isset($_POST['content']) ? $_POST['content'] : '');
            if ($content === '') {
                flash('error', '의견 내용을 입력해 주세요.');
            } else {
                $scope = isset($_POST['comment_scope']) ? $_POST['comment_scope'] : 'frame';
                $commentFrameId = null;
                if ($scope === 'frame' && !empty($_POST['frame_id'])) {
                    $commentFrameId = (int) $_POST['frame_id'];
                }
                self::addComment($storyboard['id'], $user['id'], $content, $commentFrameId);
                $frameInfo = $commentFrameId ? self::getFrameById($commentFrameId) : null;
                $summary = '의견 등록';
                if ($frameInfo) {
                    $summary .= ' (' . $frameInfo['title'] . ')';
                } else {
                    $summary .= ' (메뉴 전체)';
                }
                self::logHistory(
                    $storyboard['id'], $user['id'], 'comment',
                    $summary, $commentFrameId,
                    mb_safe_substr($content, 0, 200)
                );
                flash('success', '의견이 등록되었습니다.');
                if ($commentFrameId) {
                    redirect($redirectUrl . '&frame_id=' . $commentFrameId);
                }
            }
            redirect($redirectUrl);
        }
    }
}
