<?php

class PolicyService
{
    public static function getCategories()
    {
        return array(
            'service'   => array('label' => '서비스·회원', 'icon' => '📜'),
            'privacy'   => array('label' => '개인정보',   'icon' => '🔒'),
            'commerce'  => array('label' => '쇼핑·주문',  'icon' => '🛒'),
            'design'    => array('label' => '디자인',     'icon' => '🎨'),
            'ai'        => array('label' => 'AI 서비스',  'icon' => '✦'),
            'payment'   => array('label' => '결제·구독',  'icon' => '💳'),
            'operation' => array('label' => '운영·CS',    'icon' => '⚙'),
        );
    }

    public static function getStatuses()
    {
        return array(
            'draft'    => array('label' => '초안',   'class' => 'badge-gray'),
            'active'   => array('label' => '적용중', 'class' => 'badge-green'),
            'archived' => array('label' => '보관',   'class' => 'badge-yellow'),
        );
    }

    public static function getAudiences()
    {
        return array(
            'customer' => '고객(Front)',
            'internal' => '내부(운영)',
            'both'     => '공통',
        );
    }

    public static function getByProject($projectId, $category = null, $status = null)
    {
        $db = Database::getConnection();
        $sql = 'SELECT p.*, u.name AS updater_name
                FROM policies p
                LEFT JOIN users u ON u.id = p.updated_by
                WHERE p.project_id = ?';
        $params = array((int) $projectId);

        if ($category && $category !== 'all') {
            $sql .= ' AND p.category = ?';
            $params[] = $category;
        }
        if ($status && $status !== 'all') {
            $sql .= ' AND p.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY p.sort_order ASC, p.id ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT p.*, u.name AS updater_name
                              FROM policies p
                              LEFT JOIN users u ON u.id = p.updated_by
                              WHERE p.id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public static function countByProject($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM policies WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        return (int) $stmt->fetchColumn();
    }

    public static function create($projectId, array $data, $userId = null)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT INTO policies
            (project_id, policy_key, category, title, summary, content, version, status, audience, related_menu_code, sort_order, created_by, updated_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array(
            (int) $projectId,
            self::normalizeKey(isset($data['policy_key']) ? $data['policy_key'] : ''),
            isset($data['category']) ? $data['category'] : 'service',
            trim(isset($data['title']) ? $data['title'] : ''),
            trim(isset($data['summary']) ? $data['summary'] : ''),
            trim(isset($data['content']) ? $data['content'] : ''),
            trim(isset($data['version']) ? $data['version'] : '1.0'),
            isset($data['status']) ? $data['status'] : 'draft',
            isset($data['audience']) ? $data['audience'] : 'customer',
            trim(isset($data['related_menu_code']) ? $data['related_menu_code'] : '') ?: null,
            (int) (isset($data['sort_order']) ? $data['sort_order'] : 0),
            $userId,
            $userId,
        ));
        return (int) $db->lastInsertId();
    }

    public static function update($id, array $data, $userId = null)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE policies SET
            policy_key = ?, category = ?, title = ?, summary = ?, content = ?,
            version = ?, status = ?, audience = ?, related_menu_code = ?, sort_order = ?,
            updated_by = ?, updated_at = NOW()
            WHERE id = ?');
        $stmt->execute(array(
            self::normalizeKey(isset($data['policy_key']) ? $data['policy_key'] : ''),
            isset($data['category']) ? $data['category'] : 'service',
            trim(isset($data['title']) ? $data['title'] : ''),
            trim(isset($data['summary']) ? $data['summary'] : ''),
            trim(isset($data['content']) ? $data['content'] : ''),
            trim(isset($data['version']) ? $data['version'] : '1.0'),
            isset($data['status']) ? $data['status'] : 'draft',
            isset($data['audience']) ? $data['audience'] : 'customer',
            trim(isset($data['related_menu_code']) ? $data['related_menu_code'] : '') ?: null,
            (int) (isset($data['sort_order']) ? $data['sort_order'] : 0),
            $userId,
            (int) $id,
        ));
    }

    public static function delete($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM policies WHERE id = ?');
        $stmt->execute(array((int) $id));
    }

    public static function ensureDefaults($projectId, $userId = null)
    {
        if (self::countByProject($projectId) === 0) {
            return self::seedDefaults($projectId, $userId, false);
        }
        return self::ensureMissingSeeds($projectId, $userId);
    }

    public static function ensureMissingSeeds($projectId, $userId = null)
    {
        $seedFile = __DIR__ . '/data/policy_seed.php';
        if (!file_exists($seedFile)) {
            return 0;
        }
        $items = require $seedFile;
        if (!is_array($items)) {
            return 0;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT policy_key FROM policies WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        $existing = array();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $key) {
            $existing[$key] = true;
        }

        $inserted = 0;
        foreach ($items as $item) {
            $policyKey = self::normalizeKey(isset($item['policy_key']) ? $item['policy_key'] : '');
            if ($policyKey === '' || isset($existing[$policyKey])) {
                continue;
            }
            $item['status'] = isset($item['status']) ? $item['status'] : 'active';
            self::create($projectId, $item, $userId);
            $inserted++;
        }
        return $inserted;
    }

    public static function seedDefaults($projectId, $userId = null, $replace = false)
    {
        $db = Database::getConnection();
        if ($replace) {
            $db->prepare('DELETE FROM policies WHERE project_id = ?')->execute(array((int) $projectId));
        } elseif (self::countByProject($projectId) > 0) {
            return 0;
        }

        $seedFile = __DIR__ . '/data/policy_seed.php';
        if (!file_exists($seedFile)) {
            return 0;
        }
        $items = require $seedFile;
        if (!is_array($items)) {
            return 0;
        }

        $inserted = 0;
        foreach ($items as $item) {
            $item['status'] = isset($item['status']) ? $item['status'] : 'active';
            self::create($projectId, $item, $userId);
            $inserted++;
        }
        return $inserted;
    }

    public static function groupByCategory(array $policies)
    {
        $groups = array();
        foreach (self::getCategories() as $key => $meta) {
            $groups[$key] = array('meta' => $meta, 'items' => array());
        }
        foreach ($policies as $policy) {
            $cat = isset($policy['category']) ? $policy['category'] : 'service';
            if (!isset($groups[$cat])) {
                $groups[$cat] = array('meta' => array('label' => $cat, 'icon' => '📄'), 'items' => array());
            }
            $groups[$cat]['items'][] = $policy;
        }
        return $groups;
    }

    private static function normalizeKey($key)
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9_]+/', '_', $key);
        return trim($key, '_');
    }
}
