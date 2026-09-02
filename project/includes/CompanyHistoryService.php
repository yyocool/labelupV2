<?php
/**
 * 회사 연혁 관리 — 회사 및 연혁(타임라인) 항목
 */
class CompanyHistoryService
{
    public static function getEventCategories()
    {
        return array(
            'founding' => '설립·조직',
            'business' => '사업·제품',
            'award' => '수상·인증',
            'expansion' => '확장·투자',
            'other' => '기타',
        );
    }

    public static function getAchievementCategories()
    {
        return array(
            'project' => '프로젝트',
            'sales' => '매출·성장',
            'award' => '수상·인증',
            'partnership' => '제휴·협력',
            'other' => '기타',
        );
    }

    public static function listCompanies($projectId, $search = '')
    {
        $db = Database::getConnection();
        $sql = 'SELECT c.*,
                (SELECT COUNT(*) FROM company_history_events e WHERE e.company_id = c.id) AS event_count,
                (SELECT COUNT(*) FROM company_achievements a WHERE a.company_id = c.id) AS achievement_count
            FROM company_profiles c
            WHERE c.project_id = ?';
        $params = array((int) $projectId);
        if ($search !== '') {
            $sql .= ' AND (c.name LIKE ? OR c.industry LIKE ? OR c.summary LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= ' ORDER BY c.sort_order ASC, c.name ASC, c.id ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countCompanies($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM company_profiles WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        return (int) $stmt->fetchColumn();
    }

    public static function getCompany($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM company_profiles WHERE id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    public static function createCompany($projectId, array $data, $userId = null)
    {
        $name = trim(isset($data['name']) ? (string) $data['name'] : '');
        if ($name === '') {
            throw new InvalidArgumentException('회사명을 입력해 주세요.');
        }
        $db = Database::getConnection();
        $sort = self::nextCompanySort($projectId);
        $stmt = $db->prepare('
            INSERT INTO company_profiles
                (project_id, name, founded_year, industry, website, summary, sort_order, created_by, updated_by, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute(array(
            (int) $projectId,
            $name,
            self::nullIfEmpty(isset($data['founded_year']) ? $data['founded_year'] : ''),
            self::nullIfEmpty(isset($data['industry']) ? $data['industry'] : ''),
            self::nullIfEmpty(isset($data['website']) ? $data['website'] : ''),
            self::nullIfEmpty(isset($data['summary']) ? $data['summary'] : ''),
            $sort,
            $userId,
            $userId,
        ));
        return (int) $db->lastInsertId();
    }

    public static function updateCompany($id, array $data, $userId = null)
    {
        $existing = self::getCompany($id);
        if (!$existing) {
            throw new InvalidArgumentException('회사를 찾을 수 없습니다.');
        }
        $name = trim(isset($data['name']) ? (string) $data['name'] : (string) $existing['name']);
        if ($name === '') {
            throw new InvalidArgumentException('회사명을 입력해 주세요.');
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE company_profiles SET
                name = ?, founded_year = ?, industry = ?, website = ?, summary = ?,
                updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute(array(
            $name,
            self::nullIfEmpty(isset($data['founded_year']) ? $data['founded_year'] : $existing['founded_year']),
            self::nullIfEmpty(isset($data['industry']) ? $data['industry'] : $existing['industry']),
            self::nullIfEmpty(isset($data['website']) ? $data['website'] : $existing['website']),
            self::nullIfEmpty(isset($data['summary']) ? $data['summary'] : $existing['summary']),
            $userId,
            (int) $id,
        ));
        return true;
    }

    public static function deleteCompany($id)
    {
        $db = Database::getConnection();
        $db->prepare('DELETE FROM company_achievements WHERE company_id = ?')->execute(array((int) $id));
        $db->prepare('DELETE FROM company_history_events WHERE company_id = ?')->execute(array((int) $id));
        $stmt = $db->prepare('DELETE FROM company_profiles WHERE id = ?');
        $stmt->execute(array((int) $id));
        return $stmt->rowCount() > 0;
    }

    public static function getEvents($companyId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT * FROM company_history_events
            WHERE company_id = ?
            ORDER BY sort_order ASC, id ASC
        ');
        $stmt->execute(array((int) $companyId));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getEventsGroupedByYear($companyId)
    {
        $grouped = array();
        foreach (self::getEvents($companyId) as $row) {
            $year = !empty($row['event_year']) ? (string) $row['event_year'] : '기타';
            if (!isset($grouped[$year])) {
                $grouped[$year] = array();
            }
            $grouped[$year][] = $row;
        }
        return $grouped;
    }

    /**
     * 회사 목록 순서 저장
     * @param int[] $orderedIds
     */
    public static function reorderCompanies($projectId, array $orderedIds)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM company_profiles WHERE project_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute(array((int) $projectId));
        $allowed = array();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $allowed[(int) $id] = true;
        }
        $clean = self::normalizeOrderedIds($orderedIds, $allowed);
        $upd = $db->prepare('UPDATE company_profiles SET sort_order = ?, updated_at = NOW() WHERE id = ? AND project_id = ?');
        $order = 10;
        foreach ($clean as $oid) {
            $upd->execute(array($order, $oid, (int) $projectId));
            $order += 10;
        }
        return array('ok' => true, 'count' => count($clean));
    }

    /**
     * 연혁 타임라인 순서 저장
     * @param int[] $orderedIds
     */
    public static function reorderEvents($companyId, array $orderedIds)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM company_history_events WHERE company_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute(array((int) $companyId));
        $allowed = array();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $allowed[(int) $id] = true;
        }
        $clean = self::normalizeOrderedIds($orderedIds, $allowed);
        $upd = $db->prepare('UPDATE company_history_events SET sort_order = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $order = 10;
        foreach ($clean as $oid) {
            $upd->execute(array($order, $oid, (int) $companyId));
            $order += 10;
        }
        return array('ok' => true, 'count' => count($clean));
    }

    public static function getEvent($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM company_history_events WHERE id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    public static function createEvent($companyId, array $data)
    {
        $title = trim(isset($data['title']) ? (string) $data['title'] : '');
        if ($title === '') {
            throw new InvalidArgumentException('연혁 제목을 입력해 주세요.');
        }
        $cats = self::getEventCategories();
        $category = isset($data['category']) ? $data['category'] : 'other';
        if (!isset($cats[$category])) {
            $category = 'other';
        }
        $year = self::nullIfEmpty(isset($data['event_year']) ? $data['event_year'] : '');
        $month = self::nullIfEmpty(isset($data['event_month']) ? $data['event_month'] : '');
        if ($month !== null) {
            $month = (int) $month;
            if ($month < 1 || $month > 12) {
                $month = null;
            }
        }
        $db = Database::getConnection();
        $sort = self::nextEventSort($companyId);
        $stmt = $db->prepare('
            INSERT INTO company_history_events
                (company_id, category, event_year, event_month, title, description, sort_order, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute(array(
            (int) $companyId,
            $category,
            $year,
            $month,
            $title,
            self::nullIfEmpty(isset($data['description']) ? $data['description'] : ''),
            $sort,
        ));
        return (int) $db->lastInsertId();
    }

    public static function updateEvent($id, array $data)
    {
        $existing = self::getEvent($id);
        if (!$existing) {
            throw new InvalidArgumentException('연혁을 찾을 수 없습니다.');
        }
        $title = trim(isset($data['title']) ? (string) $data['title'] : (string) $existing['title']);
        if ($title === '') {
            throw new InvalidArgumentException('연혁 제목을 입력해 주세요.');
        }
        $cats = self::getEventCategories();
        $category = isset($data['category']) ? $data['category'] : $existing['category'];
        if (!isset($cats[$category])) {
            $category = $existing['category'];
        }
        $year = array_key_exists('event_year', $data)
            ? self::nullIfEmpty($data['event_year'])
            : $existing['event_year'];
        $month = array_key_exists('event_month', $data)
            ? self::nullIfEmpty($data['event_month'])
            : $existing['event_month'];
        if ($month !== null && $month !== '') {
            $month = (int) $month;
            if ($month < 1 || $month > 12) {
                $month = null;
            }
        } else {
            $month = null;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE company_history_events SET
                category = ?, event_year = ?, event_month = ?, title = ?, description = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute(array(
            $category,
            $year,
            $month,
            $title,
            self::nullIfEmpty(isset($data['description']) ? $data['description'] : $existing['description']),
            (int) $id,
        ));
        return true;
    }

    public static function deleteEvent($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM company_history_events WHERE id = ?');
        $stmt->execute(array((int) $id));
        return $stmt->rowCount() > 0;
    }

    public static function formatEventDate($event)
    {
        $year = !empty($event['event_year']) ? $event['event_year'] : '';
        $month = !empty($event['event_month']) ? (int) $event['event_month'] : 0;
        if ($year === '' && !$month) {
            return '';
        }
        if ($year !== '' && $month) {
            return $year . '.' . str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        }
        return (string) $year;
    }

    public static function getAchievements($companyId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT * FROM company_achievements
            WHERE company_id = ?
            ORDER BY sort_order ASC, id ASC
        ');
        $stmt->execute(array((int) $companyId));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAchievement($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM company_achievements WHERE id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    public static function createAchievement($companyId, array $data)
    {
        $title = trim(isset($data['title']) ? (string) $data['title'] : '');
        if ($title === '') {
            throw new InvalidArgumentException('실적 제목을 입력해 주세요.');
        }
        $cats = self::getAchievementCategories();
        $category = isset($data['category']) ? $data['category'] : 'project';
        if (!isset($cats[$category])) {
            $category = 'project';
        }
        $db = Database::getConnection();
        $sort = self::nextAchievementSort($companyId);
        $stmt = $db->prepare('
            INSERT INTO company_achievements
                (company_id, category, title, client, metric, achieved_year, description, sort_order, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute(array(
            (int) $companyId,
            $category,
            $title,
            self::nullIfEmpty(isset($data['client']) ? $data['client'] : ''),
            self::nullIfEmpty(isset($data['metric']) ? $data['metric'] : ''),
            self::nullIfEmpty(isset($data['achieved_year']) ? $data['achieved_year'] : ''),
            self::nullIfEmpty(isset($data['description']) ? $data['description'] : ''),
            $sort,
        ));
        return (int) $db->lastInsertId();
    }

    public static function updateAchievement($id, array $data)
    {
        $existing = self::getAchievement($id);
        if (!$existing) {
            throw new InvalidArgumentException('실적을 찾을 수 없습니다.');
        }
        $title = trim(isset($data['title']) ? (string) $data['title'] : (string) $existing['title']);
        if ($title === '') {
            throw new InvalidArgumentException('실적 제목을 입력해 주세요.');
        }
        $cats = self::getAchievementCategories();
        $category = isset($data['category']) ? $data['category'] : $existing['category'];
        if (!isset($cats[$category])) {
            $category = $existing['category'];
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE company_achievements SET
                category = ?, title = ?, client = ?, metric = ?, achieved_year = ?, description = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute(array(
            $category,
            $title,
            self::nullIfEmpty(isset($data['client']) ? $data['client'] : $existing['client']),
            self::nullIfEmpty(isset($data['metric']) ? $data['metric'] : $existing['metric']),
            self::nullIfEmpty(isset($data['achieved_year']) ? $data['achieved_year'] : $existing['achieved_year']),
            self::nullIfEmpty(isset($data['description']) ? $data['description'] : $existing['description']),
            (int) $id,
        ));
        return true;
    }

    public static function deleteAchievement($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM company_achievements WHERE id = ?');
        $stmt->execute(array((int) $id));
        return $stmt->rowCount() > 0;
    }

    /**
     * 주요 실적 순서 저장
     * @param int[] $orderedIds
     */
    public static function reorderAchievements($companyId, array $orderedIds)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM company_achievements WHERE company_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute(array((int) $companyId));
        $allowed = array();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $allowed[(int) $id] = true;
        }
        $clean = self::normalizeOrderedIds($orderedIds, $allowed);
        $upd = $db->prepare('UPDATE company_achievements SET sort_order = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $order = 10;
        foreach ($clean as $oid) {
            $upd->execute(array($order, $oid, (int) $companyId));
            $order += 10;
        }
        return array('ok' => true, 'count' => count($clean));
    }

    private static function nextCompanySort($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM company_profiles WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        return (int) $stmt->fetchColumn();
    }

    private static function nextEventSort($companyId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM company_history_events WHERE company_id = ?');
        $stmt->execute(array((int) $companyId));
        return (int) $stmt->fetchColumn();
    }

    private static function nextAchievementSort($companyId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM company_achievements WHERE company_id = ?');
        $stmt->execute(array((int) $companyId));
        return (int) $stmt->fetchColumn();
    }

    private static function normalizeOrderedIds(array $orderedIds, array $allowed)
    {
        $clean = array();
        foreach ($orderedIds as $oid) {
            $oid = (int) $oid;
            if ($oid > 0 && isset($allowed[$oid])) {
                $clean[] = $oid;
                unset($allowed[$oid]);
            }
        }
        foreach (array_keys($allowed) as $sid) {
            $clean[] = (int) $sid;
        }
        return $clean;
    }

    private static function nullIfEmpty($value)
    {
        $v = is_string($value) ? trim($value) : $value;
        if ($v === null || $v === '') {
            return null;
        }
        return $v;
    }
}
