<?php
/**
 * 이력서 관리 — 인물 및 학력·경력·수상·프로젝트
 */
class ResumeService
{
    public static function getCategories()
    {
        return array(
            'education' => array('label' => '학력', 'title_ph' => '학교 · 전공', 'org_ph' => '학과/학위'),
            'career' => array('label' => '경력', 'title_ph' => '회사 · 직무', 'org_ph' => '부서/직급'),
            'award' => array('label' => '수상', 'title_ph' => '수상명', 'org_ph' => '주최/기관'),
            'project' => array('label' => '주요 프로젝트', 'title_ph' => '프로젝트명', 'org_ph' => '역할/소속'),
        );
    }

    public static function listPeople($projectId, $search = '')
    {
        $db = Database::getConnection();
        $sql = 'SELECT p.*,
                (SELECT COUNT(*) FROM resume_entries e WHERE e.person_id = p.id) AS entry_count
            FROM resume_people p
            WHERE p.project_id = ?';
        $params = array((int) $projectId);
        if ($search !== '') {
            $sql .= ' AND (p.name LIKE ? OR p.job_title LIKE ? OR p.organization LIKE ? OR p.email LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= ' ORDER BY p.sort_order ASC, p.name ASC, p.id ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countPeople($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM resume_people WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        return (int) $stmt->fetchColumn();
    }

    public static function getPerson($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM resume_people WHERE id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    public static function createPerson($projectId, array $data, $userId = null)
    {
        $name = trim(isset($data['name']) ? (string) $data['name'] : '');
        if ($name === '') {
            throw new InvalidArgumentException('이름을 입력해 주세요.');
        }
        $db = Database::getConnection();
        $sort = self::nextPersonSort($projectId);
        $stmt = $db->prepare('
            INSERT INTO resume_people
                (project_id, name, job_title, organization, email, phone, summary, skills, sort_order, created_by, updated_by, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute(array(
            (int) $projectId,
            $name,
            self::nullIfEmpty(isset($data['job_title']) ? $data['job_title'] : ''),
            self::nullIfEmpty(isset($data['organization']) ? $data['organization'] : ''),
            self::nullIfEmpty(isset($data['email']) ? $data['email'] : ''),
            self::nullIfEmpty(isset($data['phone']) ? $data['phone'] : ''),
            self::nullIfEmpty(isset($data['summary']) ? $data['summary'] : ''),
            self::nullIfEmpty(isset($data['skills']) ? $data['skills'] : ''),
            $sort,
            $userId,
            $userId,
        ));
        return (int) $db->lastInsertId();
    }

    public static function updatePerson($id, array $data, $userId = null)
    {
        $existing = self::getPerson($id);
        if (!$existing) {
            throw new InvalidArgumentException('인물을 찾을 수 없습니다.');
        }
        $name = trim(isset($data['name']) ? (string) $data['name'] : (string) $existing['name']);
        if ($name === '') {
            throw new InvalidArgumentException('이름을 입력해 주세요.');
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE resume_people SET
                name = ?, job_title = ?, organization = ?, email = ?, phone = ?,
                summary = ?, skills = ?, updated_by = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute(array(
            $name,
            self::nullIfEmpty(isset($data['job_title']) ? $data['job_title'] : $existing['job_title']),
            self::nullIfEmpty(isset($data['organization']) ? $data['organization'] : $existing['organization']),
            self::nullIfEmpty(isset($data['email']) ? $data['email'] : $existing['email']),
            self::nullIfEmpty(isset($data['phone']) ? $data['phone'] : $existing['phone']),
            self::nullIfEmpty(isset($data['summary']) ? $data['summary'] : $existing['summary']),
            self::nullIfEmpty(isset($data['skills']) ? $data['skills'] : $existing['skills']),
            $userId,
            (int) $id,
        ));
        return true;
    }

    public static function deletePerson($id)
    {
        $db = Database::getConnection();
        $db->prepare('DELETE FROM resume_entries WHERE person_id = ?')->execute(array((int) $id));
        $stmt = $db->prepare('DELETE FROM resume_people WHERE id = ?');
        $stmt->execute(array((int) $id));
        return $stmt->rowCount() > 0;
    }

    public static function getEntries($personId, $category = null)
    {
        $db = Database::getConnection();
        if ($category) {
            $stmt = $db->prepare('
                SELECT * FROM resume_entries
                WHERE person_id = ? AND category = ?
                ORDER BY sort_order ASC, period_start DESC, id DESC
            ');
            $stmt->execute(array((int) $personId, $category));
        } else {
            $stmt = $db->prepare('
                SELECT * FROM resume_entries
                WHERE person_id = ?
                ORDER BY FIELD(category, \'education\', \'career\', \'award\', \'project\'), sort_order ASC, period_start DESC, id DESC
            ');
            $stmt->execute(array((int) $personId));
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getEntriesGrouped($personId)
    {
        $grouped = array();
        foreach (array_keys(self::getCategories()) as $cat) {
            $grouped[$cat] = array();
        }
        foreach (self::getEntries($personId) as $row) {
            $cat = $row['category'];
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = array();
            }
            $grouped[$cat][] = $row;
        }
        return $grouped;
    }

    public static function getEntry($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM resume_entries WHERE id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    public static function createEntry($personId, array $data)
    {
        $cats = self::getCategories();
        $category = isset($data['category']) ? $data['category'] : '';
        if (!isset($cats[$category])) {
            throw new InvalidArgumentException('이력 유형이 올바르지 않습니다.');
        }
        $title = trim(isset($data['title']) ? (string) $data['title'] : '');
        if ($title === '') {
            throw new InvalidArgumentException('제목을 입력해 주세요.');
        }
        $db = Database::getConnection();
        $sort = self::nextEntrySort($personId, $category);
        $stmt = $db->prepare('
            INSERT INTO resume_entries
                (person_id, category, title, organization, period_start, period_end, is_current, description, sort_order, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $isCurrent = !empty($data['is_current']) ? 1 : 0;
        $stmt->execute(array(
            (int) $personId,
            $category,
            $title,
            self::nullIfEmpty(isset($data['organization']) ? $data['organization'] : ''),
            self::nullIfEmpty(isset($data['period_start']) ? $data['period_start'] : ''),
            $isCurrent ? null : self::nullIfEmpty(isset($data['period_end']) ? $data['period_end'] : ''),
            $isCurrent,
            self::nullIfEmpty(isset($data['description']) ? $data['description'] : ''),
            $sort,
        ));
        return (int) $db->lastInsertId();
    }

    public static function updateEntry($id, array $data)
    {
        $existing = self::getEntry($id);
        if (!$existing) {
            throw new InvalidArgumentException('이력을 찾을 수 없습니다.');
        }
        $cats = self::getCategories();
        $category = isset($data['category']) ? $data['category'] : $existing['category'];
        if (!isset($cats[$category])) {
            throw new InvalidArgumentException('이력 유형이 올바르지 않습니다.');
        }
        $title = trim(isset($data['title']) ? (string) $data['title'] : (string) $existing['title']);
        if ($title === '') {
            throw new InvalidArgumentException('제목을 입력해 주세요.');
        }
        $isCurrent = array_key_exists('is_current', $data)
            ? (!empty($data['is_current']) ? 1 : 0)
            : (int) $existing['is_current'];
        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE resume_entries SET
                category = ?, title = ?, organization = ?, period_start = ?, period_end = ?,
                is_current = ?, description = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute(array(
            $category,
            $title,
            self::nullIfEmpty(isset($data['organization']) ? $data['organization'] : $existing['organization']),
            self::nullIfEmpty(isset($data['period_start']) ? $data['period_start'] : $existing['period_start']),
            $isCurrent ? null : self::nullIfEmpty(isset($data['period_end']) ? $data['period_end'] : $existing['period_end']),
            $isCurrent,
            self::nullIfEmpty(isset($data['description']) ? $data['description'] : $existing['description']),
            (int) $id,
        ));
        return true;
    }

    public static function deleteEntry($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM resume_entries WHERE id = ?');
        $stmt->execute(array((int) $id));
        return $stmt->rowCount() > 0;
    }

    /**
     * 인물 목록 순서 저장 (project 소속만)
     * @param int[] $orderedIds
     */
    public static function reorderPeople($projectId, array $orderedIds)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM resume_people WHERE project_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute(array((int) $projectId));
        $allowed = array();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $allowed[(int) $id] = true;
        }
        $clean = self::normalizeOrderedIds($orderedIds, $allowed);
        $upd = $db->prepare('UPDATE resume_people SET sort_order = ?, updated_at = NOW() WHERE id = ? AND project_id = ?');
        $order = 10;
        foreach ($clean as $oid) {
            $upd->execute(array($order, $oid, (int) $projectId));
            $order += 10;
        }
        return array('ok' => true, 'count' => count($clean));
    }

    /**
     * 이력 항목 순서 저장 (같은 person + category)
     * @param int[] $orderedIds
     */
    public static function reorderEntries($personId, $category, array $orderedIds)
    {
        $cats = self::getCategories();
        if (!isset($cats[$category])) {
            throw new InvalidArgumentException('이력 유형이 올바르지 않습니다.');
        }
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id FROM resume_entries WHERE person_id = ? AND category = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute(array((int) $personId, $category));
        $allowed = array();
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $allowed[(int) $id] = true;
        }
        $clean = self::normalizeOrderedIds($orderedIds, $allowed);
        $upd = $db->prepare('UPDATE resume_entries SET sort_order = ?, updated_at = NOW() WHERE id = ? AND person_id = ? AND category = ?');
        $order = 10;
        foreach ($clean as $oid) {
            $upd->execute(array($order, $oid, (int) $personId, $category));
            $order += 10;
        }
        return array('ok' => true, 'count' => count($clean));
    }

    public static function formatPeriod($entry)
    {
        $start = !empty($entry['period_start']) ? $entry['period_start'] : '';
        $end = !empty($entry['is_current']) ? '현재' : (!empty($entry['period_end']) ? $entry['period_end'] : '');
        if ($start === '' && $end === '') {
            return '';
        }
        if ($start !== '' && $end !== '') {
            return $start . ' ~ ' . $end;
        }
        return $start !== '' ? $start : $end;
    }

    private static function nextPersonSort($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM resume_people WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        return (int) $stmt->fetchColumn();
    }

    private static function nextEntrySort($personId, $category)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM resume_entries WHERE person_id = ? AND category = ?');
        $stmt->execute(array((int) $personId, $category));
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
