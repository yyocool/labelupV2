<?php
/**
 * 개발범위(3depth 시트) 서비스
 * depth1=영역 · depth2=블록 · depth3=항목
 */
class DevScopeService
{
    public static function getPhases()
    {
        return array(
            'phase-1' => array('label' => '1차 구축', 'period' => '2026.07 ~ 2026.10'),
            'phase-enhance' => array('label' => '고도화', 'period' => '2026.11 ~ 2027.06'),
        );
    }

    public static function getPriorities()
    {
        return array(
            'P0' => 'P0 필수',
            'P1' => 'P1 중요',
            'P2' => 'P2 선택',
        );
    }

    public static function getStatuses()
    {
        return array(
            'planned' => '예정',
            'in_progress' => '진행중',
            'done' => '완료',
            'deferred' => '보류',
            'out' => '범위외',
        );
    }

    public static function listByPhase($projectId, $phaseKey)
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('
                SELECT * FROM dev_scope_items
                WHERE project_id = ? AND phase_key = ?
                ORDER BY depth ASC, sort_order ASC, id ASC
            ');
            $stmt->execute(array($projectId, $phaseKey));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return array();
        }
    }

    public static function getById($id)
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('SELECT * FROM dev_scope_items WHERE id = ?');
            $stmt->execute(array($id));
            $row = $stmt->fetch();
            return $row ? $row : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function countByProject($projectId)
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('SELECT COUNT(*) FROM dev_scope_items WHERE project_id = ?');
            $stmt->execute(array($projectId));
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    public static function nextSortOrder($projectId, $phaseKey, $parentId, $depth)
    {
        try {
            $db = Database::getConnection();
            if ($parentId) {
                $stmt = $db->prepare('
                    SELECT COALESCE(MAX(sort_order), 0) + 10
                    FROM dev_scope_items
                    WHERE project_id = ? AND phase_key = ? AND parent_id = ? AND depth = ?
                ');
                $stmt->execute(array($projectId, $phaseKey, $parentId, $depth));
            } else {
                $stmt = $db->prepare('
                    SELECT COALESCE(MAX(sort_order), 0) + 10
                    FROM dev_scope_items
                    WHERE project_id = ? AND phase_key = ? AND parent_id IS NULL AND depth = ?
                ');
                $stmt->execute(array($projectId, $phaseKey, $depth));
            }
            return (int) $stmt->fetchColumn();
        } catch (Exception $e) {
            return 10;
        }
    }

    public static function create($projectId, array $data, $userId = null)
    {
        $db = Database::getConnection();
        $depth = (int) (isset($data['depth']) ? $data['depth'] : 1);
        if ($depth < 1) {
            $depth = 1;
        }
        if ($depth > 3) {
            $depth = 3;
        }
        $parentId = isset($data['parent_id']) && (int) $data['parent_id'] > 0
            ? (int) $data['parent_id'] : null;
        if ($depth === 1) {
            $parentId = null;
        }
        $phaseKey = isset($data['phase_key']) ? $data['phase_key'] : 'phase-1';
        $phases = self::getPhases();
        if (!isset($phases[$phaseKey])) {
            $phaseKey = 'phase-1';
        }
        $title = trim(isset($data['title']) ? $data['title'] : '');
        if ($title === '') {
            throw new InvalidArgumentException('제목을 입력해 주세요.');
        }
        $priority = isset($data['priority']) ? $data['priority'] : 'P1';
        $priorities = self::getPriorities();
        if (!isset($priorities[$priority])) {
            $priority = 'P1';
        }
        $status = isset($data['status']) ? $data['status'] : 'planned';
        $statuses = self::getStatuses();
        if (!isset($statuses[$status])) {
            $status = 'planned';
        }
        $sort = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;
        if ($sort <= 0) {
            $sort = self::nextSortOrder($projectId, $phaseKey, $parentId, $depth);
        }

        $stmt = $db->prepare('
            INSERT INTO dev_scope_items
                (project_id, parent_id, depth, phase_key, title, description, priority, status, sort_order, created_by, updated_by, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute(array(
            $projectId,
            $parentId,
            $depth,
            $phaseKey,
            $title,
            isset($data['description']) ? $data['description'] : null,
            $priority,
            $status,
            $sort,
            $userId,
            $userId,
        ));
        return (int) $db->lastInsertId();
    }

    public static function update($id, array $data, $userId = null)
    {
        $existing = self::getById($id);
        if (!$existing) {
            throw new InvalidArgumentException('항목을 찾을 수 없습니다.');
        }
        $title = trim(isset($data['title']) ? $data['title'] : $existing['title']);
        if ($title === '') {
            throw new InvalidArgumentException('제목을 입력해 주세요.');
        }
        $priority = isset($data['priority']) ? $data['priority'] : $existing['priority'];
        $priorities = self::getPriorities();
        if (!isset($priorities[$priority])) {
            $priority = $existing['priority'];
        }
        $status = isset($data['status']) ? $data['status'] : $existing['status'];
        $statuses = self::getStatuses();
        if (!isset($statuses[$status])) {
            $status = $existing['status'];
        }
        $sort = isset($data['sort_order']) ? (int) $data['sort_order'] : (int) $existing['sort_order'];

        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE dev_scope_items
            SET title = ?, description = ?, priority = ?, status = ?, sort_order = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute(array(
            $title,
            isset($data['description']) ? $data['description'] : $existing['description'],
            $priority,
            $status,
            $sort,
            $userId,
            $id,
        ));
        return true;
    }

    public static function delete($id)
    {
        $db = Database::getConnection();
        // 자식 포함 재귀 삭제 (depth 최대 3이므로 2단계면 충분)
        $children = $db->prepare('SELECT id FROM dev_scope_items WHERE parent_id = ?');
        $children->execute(array($id));
        foreach ($children->fetchAll() as $ch) {
            self::delete((int) $ch['id']);
        }
        $stmt = $db->prepare('DELETE FROM dev_scope_items WHERE id = ?');
        $stmt->execute(array($id));
        return true;
    }

    /**
     * 시트를 위한 flat rows: depth1/2/3 타이틀을 컬럼으로 펼침
     */
    public static function buildSheetRows($projectId, $phaseKey)
    {
        $items = self::listByPhase($projectId, $phaseKey);
        $byId = array();
        foreach ($items as $it) {
            $byId[(int) $it['id']] = $it;
        }

        $rows = array();
        foreach ($items as $it) {
            $depth = (int) $it['depth'];
            $d1 = '';
            $d2 = '';
            $d3 = '';
            if ($depth === 1) {
                $d1 = $it['title'];
            } elseif ($depth === 2) {
                $parent = isset($byId[(int) $it['parent_id']]) ? $byId[(int) $it['parent_id']] : null;
                $d1 = $parent ? $parent['title'] : '';
                $d2 = $it['title'];
            } else {
                $p2 = isset($byId[(int) $it['parent_id']]) ? $byId[(int) $it['parent_id']] : null;
                $p1 = ($p2 && isset($byId[(int) $p2['parent_id']])) ? $byId[(int) $p2['parent_id']] : null;
                $d1 = $p1 ? $p1['title'] : '';
                $d2 = $p2 ? $p2['title'] : '';
                $d3 = $it['title'];
            }
            $rows[] = array(
                'item' => $it,
                'd1' => $d1,
                'd2' => $d2,
                'd3' => $d3,
                'depth' => $depth,
            );
        }
        return $rows;
    }

    public static function parentsForSelect($projectId, $phaseKey, $depth)
    {
        // depth 2 → parent depth 1, depth 3 → parent depth 2
        $parentDepth = $depth - 1;
        if ($parentDepth < 1) {
            return array();
        }
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare('
                SELECT id, title, depth FROM dev_scope_items
                WHERE project_id = ? AND phase_key = ? AND depth = ?
                ORDER BY sort_order ASC, id ASC
            ');
            $stmt->execute(array($projectId, $phaseKey, $parentDepth));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return array();
        }
    }

    public static function ensureDefaults($projectId, $userId = null)
    {
        if (self::countByProject($projectId) > 0) {
            return 0;
        }
        return self::seedFromFeatureMap($projectId, $userId, false);
    }

    public static function seedFromFeatureMap($projectId, $userId = null, $replace = false)
    {
        $mapFile = APP_ROOT . '/includes/data/feature_map.php';
        if (!file_exists($mapFile)) {
            return 0;
        }
        $map = require $mapFile;
        $scope = isset($map['scope']) && is_array($map['scope']) ? $map['scope'] : array();
        if (empty($scope['phases'])) {
            return 0;
        }

        if ($replace) {
            $db = Database::getConnection();
            $db->prepare('DELETE FROM dev_scope_items WHERE project_id = ?')->execute(array($projectId));
        } elseif (self::countByProject($projectId) > 0) {
            return 0;
        }

        $count = 0;
        $defaultPriority = array('phase-1' => 'P0', 'phase-enhance' => 'P1');

        foreach ($scope['phases'] as $phase) {
            $phaseKey = isset($phase['id']) ? $phase['id'] : 'phase-1';
            $prio = isset($defaultPriority[$phaseKey]) ? $defaultPriority[$phaseKey] : 'P1';
            $areaOrder = 10;
            foreach (isset($phase['areas']) ? $phase['areas'] : array() as $area) {
                $areaId = self::create($projectId, array(
                    'depth' => 1,
                    'phase_key' => $phaseKey,
                    'title' => isset($area['name']) ? $area['name'] : '영역',
                    'description' => isset($area['subtitle']) ? $area['subtitle'] : '',
                    'priority' => $prio,
                    'status' => 'planned',
                    'sort_order' => $areaOrder,
                ), $userId);
                $count++;
                $areaOrder += 10;

                $blockOrder = 10;
                foreach (isset($area['blocks']) ? $area['blocks'] : array() as $block) {
                    $blockId = self::create($projectId, array(
                        'depth' => 2,
                        'parent_id' => $areaId,
                        'phase_key' => $phaseKey,
                        'title' => isset($block['name']) ? $block['name'] : '블록',
                        'description' => isset($block['desc']) ? $block['desc'] : '',
                        'priority' => $prio,
                        'status' => 'planned',
                        'sort_order' => $blockOrder,
                    ), $userId);
                    $count++;
                    $blockOrder += 10;

                    $itemOrder = 10;
                    foreach (isset($block['items']) ? $block['items'] : array() as $itemText) {
                        self::create($projectId, array(
                            'depth' => 3,
                            'parent_id' => $blockId,
                            'phase_key' => $phaseKey,
                            'title' => $itemText,
                            'description' => '',
                            'priority' => $prio,
                            'status' => 'planned',
                            'sort_order' => $itemOrder,
                        ), $userId);
                        $count++;
                        $itemOrder += 10;
                    }
                }
            }
        }
        return $count;
    }
}
