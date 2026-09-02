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
                ORDER BY sort_order ASC, id ASC
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

    /** 같은 상위(형제) 목록 */
    public static function listSiblings($item)
    {
        $db = Database::getConnection();
        $projectId = (int) $item['project_id'];
        $phaseKey = $item['phase_key'];
        $depth = (int) $item['depth'];
        $parentId = isset($item['parent_id']) && $item['parent_id'] !== null && (int) $item['parent_id'] > 0
            ? (int) $item['parent_id'] : null;

        if ($parentId) {
            $stmt = $db->prepare('
                SELECT id, sort_order FROM dev_scope_items
                WHERE project_id = ? AND phase_key = ? AND depth = ? AND parent_id = ?
                ORDER BY sort_order ASC, id ASC
            ');
            $stmt->execute(array($projectId, $phaseKey, $depth, $parentId));
        } else {
            $stmt = $db->prepare('
                SELECT id, sort_order FROM dev_scope_items
                WHERE project_id = ? AND phase_key = ? AND depth = ? AND parent_id IS NULL
                ORDER BY sort_order ASC, id ASC
            ');
            $stmt->execute(array($projectId, $phaseKey, $depth));
        }
        return $stmt->fetchAll();
    }

    /**
     * 형제 간 순서 이동 (up|down)
     * @return array{ok:bool,swapped:bool}
     */
    public static function moveSort($id, $direction, $userId = null)
    {
        $item = self::getById($id);
        if (!$item) {
            throw new InvalidArgumentException('항목을 찾을 수 없습니다.');
        }
        $direction = ($direction === 'up') ? 'up' : 'down';
        $siblings = self::listSiblings($item);
        $index = -1;
        foreach ($siblings as $i => $row) {
            if ((int) $row['id'] === (int) $id) {
                $index = $i;
                break;
            }
        }
        if ($index < 0) {
            return array('ok' => true, 'swapped' => false);
        }
        $swapIndex = ($direction === 'up') ? $index - 1 : $index + 1;
        if ($swapIndex < 0 || $swapIndex >= count($siblings)) {
            return array('ok' => true, 'swapped' => false);
        }

        $a = $siblings[$index];
        $b = $siblings[$swapIndex];
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE dev_scope_items SET sort_order = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
        // 임시값으로 충돌 방지
        $tmp = -1 * ((int) $a['id'] + 100000);
        $stmt->execute(array($tmp, $userId, (int) $a['id']));
        $stmt->execute(array((int) $a['sort_order'], $userId, (int) $b['id']));
        $stmt->execute(array((int) $b['sort_order'], $userId, (int) $a['id']));

        return array('ok' => true, 'swapped' => true);
    }

    /**
     * 형제 id 배열 순서대로 sort_order 재부여 (10, 20, 30…)
     * @param int[] $orderedIds
     */
    public static function reorderSiblings($projectId, $orderedIds, $userId = null)
    {
        if (empty($orderedIds)) {
            return array('ok' => true, 'count' => 0);
        }
        $first = self::getById((int) $orderedIds[0]);
        if (!$first || (int) $first['project_id'] !== (int) $projectId) {
            throw new InvalidArgumentException('항목을 찾을 수 없습니다.');
        }
        $siblings = self::listSiblings($first);
        $allowed = array();
        foreach ($siblings as $s) {
            $allowed[(int) $s['id']] = true;
        }
        $clean = array();
        foreach ($orderedIds as $oid) {
            $oid = (int) $oid;
            if (isset($allowed[$oid])) {
                $clean[] = $oid;
                unset($allowed[$oid]);
            }
        }
        // 누락된 형제는 뒤에 유지
        foreach ($siblings as $s) {
            $sid = (int) $s['id'];
            if (isset($allowed[$sid])) {
                $clean[] = $sid;
            }
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE dev_scope_items SET sort_order = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND project_id = ?');
        $order = 10;
        foreach ($clean as $oid) {
            $stmt->execute(array($order, $userId, $oid, $projectId));
            $order += 10;
        }
        return array('ok' => true, 'count' => count($clean));
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
        $titleRaw = isset($data['title']) ? $data['title'] : (isset($existing['title']) ? $existing['title'] : '');
        $title = trim((string) $titleRaw);
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
        $description = isset($data['description'])
            ? (string) $data['description']
            : (isset($existing['description']) ? (string) $existing['description'] : '');

        $styleJson = null;
        $hasStyle = array_key_exists('style_json', $data) || array_key_exists('style', $data);
        if ($hasStyle) {
            if (isset($data['style']) && is_array($data['style'])) {
                $styleJson = self::encodeStyle($data['style']);
            } elseif (array_key_exists('style_json', $data)) {
                $parsed = self::parseStyle(isset($data['style_json']) ? $data['style_json'] : null);
                $styleJson = self::encodeStyle($parsed);
            }
        } else {
            $styleJson = isset($existing['style_json']) ? $existing['style_json'] : null;
        }

        $db = Database::getConnection();
        try {
            $stmt = $db->prepare('
                UPDATE dev_scope_items
                SET title = ?, description = ?, priority = ?, status = ?, sort_order = ?, style_json = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ?
            ');
            $stmt->execute(array(
                $title,
                $description,
                $priority,
                $status,
                $sort,
                $styleJson,
                $userId,
                $id,
            ));
        } catch (Exception $e) {
            // style_json 미적용 구버전 테이블 호환
            $stmt = $db->prepare('
                UPDATE dev_scope_items
                SET title = ?, description = ?, priority = ?, status = ?, sort_order = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ?
            ');
            $stmt->execute(array(
                $title,
                $description,
                $priority,
                $status,
                $sort,
                $userId,
                $id,
            ));
        }
        return true;
    }

    /**
     * 셀 스타일만 저장 (title / description 필드별)
     * @param array $fieldStyle eg array('bg'=>'#fff','color'=>'#000','bold'=>true)
     */
    public static function updateFieldStyle($id, $field, array $fieldStyle, $userId = null)
    {
        $existing = self::getById($id);
        if (!$existing) {
            throw new InvalidArgumentException('항목을 찾을 수 없습니다.');
        }
        $field = ($field === 'description') ? 'description' : 'title';
        $styles = self::parseStyle(isset($existing['style_json']) ? $existing['style_json'] : null);
        $styles[$field] = self::normalizeFieldStyle($fieldStyle);
        if (self::isEmptyFieldStyle($styles[$field])) {
            unset($styles[$field]);
        }
        $encoded = self::encodeStyle($styles);

        $db = Database::getConnection();
        // style_json 컬럼이 없는 구버전 대비
        try {
            $stmt = $db->prepare('UPDATE dev_scope_items SET style_json = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute(array($encoded, $userId, $id));
        } catch (Exception $e) {
            throw new RuntimeException('style_json 컬럼이 없습니다. 페이지를 새로고침해 마이그레이션을 적용하세요.');
        }
        return self::parseStyle($encoded);
    }

    public static function parseStyle($json)
    {
        if ($json === null || $json === '') {
            return array();
        }
        if (is_array($json)) {
            $data = $json;
        } else {
            $data = json_decode((string) $json, true);
            if (!is_array($data)) {
                return array();
            }
        }
        $out = array();
        foreach (array('title', 'description') as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $norm = self::normalizeFieldStyle($data[$field]);
                if (!self::isEmptyFieldStyle($norm)) {
                    $out[$field] = $norm;
                }
            }
        }
        // 구형: 최상위에 bg/color/bold 만 있는 경우 → title에 적용
        if (empty($out) && (isset($data['bg']) || isset($data['color']) || isset($data['bold']))) {
            $norm = self::normalizeFieldStyle($data);
            if (!self::isEmptyFieldStyle($norm)) {
                $out['title'] = $norm;
            }
        }
        return $out;
    }

    public static function encodeStyle(array $styles)
    {
        $clean = array();
        foreach (array('title', 'description') as $field) {
            if (!isset($styles[$field]) || !is_array($styles[$field])) {
                continue;
            }
            $norm = self::normalizeFieldStyle($styles[$field]);
            if (!self::isEmptyFieldStyle($norm)) {
                $clean[$field] = $norm;
            }
        }
        if (empty($clean)) {
            return null;
        }
        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    public static function normalizeFieldStyle(array $s)
    {
        $bg = isset($s['bg']) ? trim((string) $s['bg']) : '';
        $color = isset($s['color']) ? trim((string) $s['color']) : '';
        $bold = !empty($s['bold']) && $s['bold'] !== '0' && $s['bold'] !== false;

        if ($bg !== '' && !preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $bg)) {
            $bg = '';
        }
        if ($color !== '' && !preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            $color = '';
        }

        return array(
            'bg' => $bg,
            'color' => $color,
            'bold' => $bold ? 1 : 0,
        );
    }

    public static function isEmptyFieldStyle(array $s)
    {
        return (empty($s['bg']) && empty($s['color']) && empty($s['bold']));
    }

    /** HTML style 속성 문자열 */
    public static function fieldStyleAttr(array $styles, $field)
    {
        if (!isset($styles[$field]) || !is_array($styles[$field])) {
            return '';
        }
        $s = $styles[$field];
        $parts = array();
        if (!empty($s['bg'])) {
            $parts[] = 'background-color:' . $s['bg'];
        }
        if (!empty($s['color'])) {
            $parts[] = 'color:' . $s['color'];
        }
        if (!empty($s['bold'])) {
            $parts[] = 'font-weight:700';
        } elseif (!empty($s['bg']) || !empty($s['color'])) {
            $parts[] = 'font-weight:400';
        }
        return implode(';', $parts);
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
     * 항목(+하위)을 다른 구축 단계로 이동
     * @return array{moved:int,ids:int[],target:string,created_parents:int}
     */
    public static function moveToPhase($id, $targetPhaseKey, $userId = null)
    {
        $phases = self::getPhases();
        if (!isset($phases[$targetPhaseKey])) {
            throw new InvalidArgumentException('잘못된 구축 단계입니다.');
        }
        $item = self::getById($id);
        if (!$item) {
            throw new InvalidArgumentException('항목을 찾을 수 없습니다.');
        }
        if ($item['phase_key'] === $targetPhaseKey) {
            return array('moved' => 0, 'ids' => array(), 'target' => $targetPhaseKey, 'created_parents' => 0);
        }

        $db = Database::getConnection();
        $ids = self::collectSubtreeIds($id);
        $depth = (int) $item['depth'];
        $projectId = (int) $item['project_id'];
        $createdParents = 0;

        $newParentId = null;
        if ($depth > 1) {
            $ensured = self::ensureParentChainInPhase($item, $targetPhaseKey, $userId);
            $newParentId = $ensured['parent_id'];
            $createdParents = $ensured['created'];
        }

        $sort = self::nextSortOrder($projectId, $targetPhaseKey, $newParentId, $depth);

        $db->beginTransaction();
        try {
            $phStmt = $db->prepare('UPDATE dev_scope_items SET phase_key = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
            foreach ($ids as $moveId) {
                $phStmt->execute(array($targetPhaseKey, $userId, $moveId));
            }

            $rootStmt = $db->prepare('
                UPDATE dev_scope_items
                SET parent_id = ?, sort_order = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ?
            ');
            $rootStmt->execute(array($newParentId, $sort, $userId, $id));

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

        return array(
            'moved' => count($ids),
            'ids' => $ids,
            'target' => $targetPhaseKey,
            'created_parents' => $createdParents,
        );
    }

    /** @return int[] */
    public static function collectSubtreeIds($id)
    {
        $db = Database::getConnection();
        $ids = array((int) $id);
        $queue = array((int) $id);
        while ($queue) {
            $pid = array_shift($queue);
            $stmt = $db->prepare('SELECT id FROM dev_scope_items WHERE parent_id = ? ORDER BY sort_order ASC, id ASC');
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
     * 대상 phase에 동일한 상위 경로를 찾거나 생성
     * @return array{parent_id:?int,created:int}
     */
    public static function ensureParentChainInPhase(array $item, $targetPhaseKey, $userId = null)
    {
        $depth = (int) $item['depth'];
        $projectId = (int) $item['project_id'];
        $created = 0;

        if ($depth <= 1) {
            return array('parent_id' => null, 'created' => 0);
        }

        if ($depth === 2) {
            $p1 = self::getById((int) $item['parent_id']);
            if (!$p1) {
                return array('parent_id' => null, 'created' => 0);
            }
            $found = self::findByTitle($projectId, $targetPhaseKey, 1, null, $p1['title']);
            if ($found) {
                return array('parent_id' => (int) $found['id'], 'created' => 0);
            }
            $newId = self::create($projectId, array(
                'depth' => 1,
                'parent_id' => 0,
                'phase_key' => $targetPhaseKey,
                'title' => $p1['title'],
                'description' => isset($p1['description']) ? $p1['description'] : '',
                'priority' => isset($p1['priority']) ? $p1['priority'] : 'P1',
                'status' => 'planned',
            ), $userId);
            return array('parent_id' => $newId, 'created' => 1);
        }

        // depth 3
        $p2 = self::getById((int) $item['parent_id']);
        if (!$p2) {
            return array('parent_id' => null, 'created' => 0);
        }
        $p1 = self::getById((int) $p2['parent_id']);
        $d1Id = null;
        if ($p1) {
            $found1 = self::findByTitle($projectId, $targetPhaseKey, 1, null, $p1['title']);
            if ($found1) {
                $d1Id = (int) $found1['id'];
            } else {
                $d1Id = self::create($projectId, array(
                    'depth' => 1,
                    'parent_id' => 0,
                    'phase_key' => $targetPhaseKey,
                    'title' => $p1['title'],
                    'description' => isset($p1['description']) ? $p1['description'] : '',
                    'priority' => isset($p1['priority']) ? $p1['priority'] : 'P1',
                    'status' => 'planned',
                ), $userId);
                $created++;
            }
        }

        $found2 = self::findByTitle($projectId, $targetPhaseKey, 2, $d1Id, $p2['title']);
        if ($found2) {
            return array('parent_id' => (int) $found2['id'], 'created' => $created);
        }
        $d2Id = self::create($projectId, array(
            'depth' => 2,
            'parent_id' => $d1Id ? $d1Id : 0,
            'phase_key' => $targetPhaseKey,
            'title' => $p2['title'],
            'description' => isset($p2['description']) ? $p2['description'] : '',
            'priority' => isset($p2['priority']) ? $p2['priority'] : 'P1',
            'status' => 'planned',
        ), $userId);
        $created++;
        return array('parent_id' => $d2Id, 'created' => $created);
    }

    public static function findByTitle($projectId, $phaseKey, $depth, $parentId, $title)
    {
        try {
            $db = Database::getConnection();
            if ($parentId) {
                $stmt = $db->prepare('
                    SELECT * FROM dev_scope_items
                    WHERE project_id = ? AND phase_key = ? AND depth = ? AND parent_id = ? AND title = ?
                    ORDER BY sort_order ASC, id ASC
                    LIMIT 1
                ');
                $stmt->execute(array($projectId, $phaseKey, $depth, $parentId, $title));
            } else {
                $stmt = $db->prepare('
                    SELECT * FROM dev_scope_items
                    WHERE project_id = ? AND phase_key = ? AND depth = ? AND parent_id IS NULL AND title = ?
                    ORDER BY sort_order ASC, id ASC
                    LIMIT 1
                ');
                $stmt->execute(array($projectId, $phaseKey, $depth, $title));
            }
            $row = $stmt->fetch();
            return $row ? $row : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 시트를 위한 flat rows: 상위 depth 단위로 묶어 트리 순(영역→블록→항목) 전개
     */
    public static function buildSheetRows($projectId, $phaseKey)
    {
        $items = self::listByPhase($projectId, $phaseKey);
        $byId = array();
        $children = array();

        foreach ($items as $it) {
            $id = (int) $it['id'];
            $byId[$id] = $it;
            $pid = (isset($it['parent_id']) && $it['parent_id'] !== null && (int) $it['parent_id'] > 0)
                ? (int) $it['parent_id'] : 0;
            if (!isset($children[$pid])) {
                $children[$pid] = array();
            }
            $children[$pid][] = $it;
        }

        foreach ($children as &$list) {
            usort($list, function ($a, $b) {
                $sa = (int) $a['sort_order'];
                $sb = (int) $b['sort_order'];
                if ($sa !== $sb) {
                    return $sa - $sb;
                }
                return (int) $a['id'] - (int) $b['id'];
            });
        }
        unset($list);

        $rows = array();
        $visited = array();

        $appendRow = function ($it, $d1, $d2, $d3, $d1Id, $d2Id) use (&$rows) {
            $rows[] = array(
                'item' => $it,
                'd1' => $d1,
                'd2' => $d2,
                'd3' => $d3,
                'd1_id' => $d1Id,
                'd2_id' => $d2Id,
                'depth' => (int) $it['depth'],
            );
        };

        $walk = function ($parentId, $d1, $d2, $d1Id, $d2Id) use (&$walk, &$visited, $children, $appendRow) {
            if (!isset($children[$parentId])) {
                return;
            }
            foreach ($children[$parentId] as $it) {
                $id = (int) $it['id'];
                if (isset($visited[$id])) {
                    continue;
                }
                $visited[$id] = true;
                $depth = (int) $it['depth'];
                $title = $it['title'];
                $rd1 = $d1;
                $rd2 = $d2;
                $rd3 = '';
                $rd1Id = $d1Id;
                $rd2Id = $d2Id;
                if ($depth === 1) {
                    $rd1 = $title;
                    $rd2 = '';
                    $rd1Id = $id;
                    $rd2Id = 0;
                } elseif ($depth === 2) {
                    $rd2 = $title;
                    $rd2Id = $id;
                    if (!$rd1Id) {
                        $rd1Id = $parentId;
                    }
                } else {
                    $rd3 = $title;
                    if (!$rd2Id) {
                        $rd2Id = $parentId;
                    }
                }
                $appendRow($it, $rd1, $rd2, $rd3, $rd1Id, $rd2Id);
                $walk($id, $rd1, $rd2, $rd1Id, $rd2Id);
            }
        };

        $walk(0, '', '', 0, 0);

        // 상위가 없거나 끊긴 고아 항목도 누락되지 않게 뒤에 붙임
        foreach ($items as $it) {
            $id = (int) $it['id'];
            if (isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;
            $depth = (int) $it['depth'];
            $d1 = '';
            $d2 = '';
            $d3 = '';
            $d1Id = 0;
            $d2Id = 0;
            if ($depth === 1) {
                $d1 = $it['title'];
                $d1Id = $id;
            } elseif ($depth === 2) {
                $parent = isset($byId[(int) $it['parent_id']]) ? $byId[(int) $it['parent_id']] : null;
                $d1 = $parent ? $parent['title'] : '';
                $d1Id = $parent ? (int) $parent['id'] : 0;
                $d2 = $it['title'];
                $d2Id = $id;
            } else {
                $p2 = isset($byId[(int) $it['parent_id']]) ? $byId[(int) $it['parent_id']] : null;
                $p1 = ($p2 && isset($byId[(int) $p2['parent_id']])) ? $byId[(int) $p2['parent_id']] : null;
                $d1 = $p1 ? $p1['title'] : '';
                $d2 = $p2 ? $p2['title'] : '';
                $d3 = $it['title'];
                $d1Id = $p1 ? (int) $p1['id'] : 0;
                $d2Id = $p2 ? (int) $p2['id'] : 0;
            }
            $appendRow($it, $d1, $d2, $d3, $d1Id, $d2Id);
            $walk($id, $d1, $d2, $d1Id, $d2Id);
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
            foreach ((isset($phase['areas']) ? $phase['areas'] : array()) as $area) {
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
                foreach ((isset($area['blocks']) ? $area['blocks'] : array()) as $block) {
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
                    foreach ((isset($block['items']) ? $block['items'] : array()) as $itemText) {
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

    /**
     * 엑셀(.xlsx) 바이너리 생성 — ZipArchive 사용 (라이브러리 불필요)
     * @param string $scope 'all' | 'current'
     * @return array{filename:string,mime:string,body:string}
     */
    public static function buildExcelExport($projectId, $scope = 'all', $phaseKey = 'phase-1')
    {
        $phases = self::getPhases();
        $priorities = self::getPriorities();
        $statuses = self::getStatuses();
        $headers = array('Depth', '구분', '항목', '내용', '우선순위', '상태', '설명');

        $sheets = array();
        if ($scope === 'current' && isset($phases[$phaseKey])) {
            $sheets[$phaseKey] = $phases[$phaseKey]['label'];
        } else {
            foreach ($phases as $key => $ph) {
                $sheets[$key] = $ph['label'];
            }
        }

        $sheetRowsMap = array();
        foreach ($sheets as $key => $label) {
            $rows = array($headers);
            foreach (self::buildSheetRows($projectId, $key) as $r) {
                $it = $r['item'];
                $prio = isset($it['priority']) ? $it['priority'] : '';
                $st = isset($it['status']) ? $it['status'] : '';
                $rows[] = array(
                    (string) $r['depth'],
                    isset($r['d1']) ? $r['d1'] : '',
                    isset($r['d2']) ? $r['d2'] : '',
                    isset($r['d3']) ? $r['d3'] : '',
                    isset($priorities[$prio]) ? $priorities[$prio] : $prio,
                    isset($statuses[$st]) ? $statuses[$st] : $st,
                    isset($it['description']) ? $it['description'] : '',
                );
            }
            $sheetRowsMap[$key] = array('name' => $label, 'rows' => $rows);
        }

        if (class_exists('ZipArchive')) {
            $body = self::buildXlsxBinary($sheetRowsMap);
            $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $ext = 'xlsx';
        } else {
            $body = self::buildSpreadsheetMl($sheetRowsMap);
            $mime = 'application/vnd.ms-excel';
            $ext = 'xls';
        }

        $date = date('Ymd');
        if ($scope === 'current' && isset($phases[$phaseKey])) {
            $safe = preg_replace('/[^\w가-힣\-]+/u', '_', $phases[$phaseKey]['label']);
            $filename = '개발범위_' . $safe . '_' . $date . '.' . $ext;
        } else {
            $filename = '개발범위_전체_' . $date . '.' . $ext;
        }

        return array(
            'filename' => $filename,
            'mime' => $mime,
            'body' => $body,
        );
    }

    private static function xmlEscape($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    private static function xlsxColLetter($index)
    {
        // 0-based → A, B, ...
        $index = (int) $index;
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = (int) floor($index / 26) - 1;
        }
        return $letter;
    }

    private static function buildXlsxBinary(array $sheetRowsMap)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dsx');
        if ($tmp === false) {
            throw new RuntimeException('임시 파일을 만들 수 없습니다.');
        }
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new RuntimeException('엑셀 파일을 생성할 수 없습니다.');
        }

        $sheetFiles = array();
        $i = 1;
        foreach ($sheetRowsMap as $key => $sheet) {
            $sheetFiles[] = array(
                'key' => $key,
                'name' => self::sanitizeSheetName($sheet['name'], $i),
                'path' => 'xl/worksheets/sheet' . $i . '.xml',
                'rows' => $sheet['rows'],
                'index' => $i,
            );
            $i++;
        }

        $zip->addFromString('[Content_Types].xml', self::xlsxContentTypes($sheetFiles));
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', self::xlsxWorkbook($sheetFiles));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::xlsxWorkbookRels($sheetFiles));
        $zip->addFromString('xl/styles.xml', self::xlsxStyles());

        foreach ($sheetFiles as $sf) {
            $zip->addFromString($sf['path'], self::xlsxSheetXml($sf['rows']));
        }

        $zip->close();
        $body = file_get_contents($tmp);
        @unlink($tmp);
        if ($body === false) {
            throw new RuntimeException('엑셀 파일을 읽지 못했습니다.');
        }
        return $body;
    }

    private static function sanitizeSheetName($name, $fallbackIndex)
    {
        $name = preg_replace('/[\\\\\\/\\*\\?\\:\\[\\]]/', '', (string) $name);
        $name = trim($name);
        if ($name === '') {
            $name = 'Sheet' . $fallbackIndex;
        }
        if (function_exists('mb_substr')) {
            $name = mb_substr($name, 0, 31, 'UTF-8');
        } else {
            $name = substr($name, 0, 31);
        }
        return $name;
    }

    private static function xlsxContentTypes(array $sheetFiles)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($sheetFiles as $sf) {
            $xml .= '<Override PartName="/' . $sf['path'] . '" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $xml .= '</Types>';
        return $xml;
    }

    private static function xlsxWorkbook(array $sheetFiles)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>';
        foreach ($sheetFiles as $sf) {
            $xml .= '<sheet name="' . self::xmlEscape($sf['name']) . '" sheetId="' . (int) $sf['index'] . '" r:id="rId' . (int) $sf['index'] . '"/>';
        }
        $xml .= '</sheets></workbook>';
        return $xml;
    }

    private static function xlsxWorkbookRels(array $sheetFiles)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($sheetFiles as $sf) {
            $xml .= '<Relationship Id="rId' . (int) $sf['index'] . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . ' Target="worksheets/sheet' . (int) $sf['index'] . '.xml"/>';
        }
        $xml .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $xml .= '</Relationships>';
        return $xml;
    }

    private static function xlsxStyles()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Malgun Gothic"/></font>'
            . '<font><b/><sz val="11"/><name val="Malgun Gothic"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF3F3F3"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf fontId="0" fillId="0" borderId="0"/>'
            . '<xf fontId="1" fillId="2" borderId="0" applyFont="1" applyFill="1"/>'
            . '<xf fontId="0" fillId="0" borderId="0" applyAlignment="1"><alignment wrapText="1"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    private static function xlsxSheetXml(array $rows)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>'
            . '<col min="1" max="1" width="8" customWidth="1"/>'
            . '<col min="2" max="2" width="18" customWidth="1"/>'
            . '<col min="3" max="3" width="18" customWidth="1"/>'
            . '<col min="4" max="4" width="40" customWidth="1"/>'
            . '<col min="5" max="5" width="12" customWidth="1"/>'
            . '<col min="6" max="6" width="10" customWidth="1"/>'
            . '<col min="7" max="7" width="28" customWidth="1"/>'
            . '</cols>'
            . '<sheetData>';

        $rIdx = 1;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rIdx . '">';
            $cIdx = 0;
            foreach ($row as $cell) {
                $ref = self::xlsxColLetter($cIdx) . $rIdx;
                $style = ($rIdx === 1) ? '1' : '0';
                $val = self::xmlEscape($cell);
                // Excel inlineStr: escape control chars already via htmlspecialchars
                $xml .= '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . $val . '</t></is></c>';
                $cIdx++;
            }
            $xml .= '</row>';
            $rIdx++;
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private static function buildSpreadsheetMl(array $sheetRowsMap)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $i = 1;
        foreach ($sheetRowsMap as $sheet) {
            $name = self::sanitizeSheetName($sheet['name'], $i);
            $xml .= '<Worksheet ss:Name="' . self::xmlEscape($name) . '"><Table>';
            foreach ($sheet['rows'] as $row) {
                $xml .= '<Row>';
                foreach ($row as $cell) {
                    $xml .= '<Cell><Data ss:Type="String">' . self::xmlEscape($cell) . '</Data></Cell>';
                }
                $xml .= '</Row>';
            }
            $xml .= '</Table></Worksheet>';
            $i++;
        }
        $xml .= '</Workbook>';
        return $xml;
    }
}
