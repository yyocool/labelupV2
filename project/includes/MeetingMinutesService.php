<?php

class MeetingMinutesService
{
    public static function getByProject($projectId, $search = '')
    {
        $db = Database::getConnection();
        $sql = 'SELECT m.*,
                       cu.name AS creator_name,
                       uu.name AS updater_name
                FROM meeting_minutes m
                LEFT JOIN users cu ON cu.id = m.created_by
                LEFT JOIN users uu ON uu.id = m.updated_by
                WHERE m.project_id = ?';
        $params = array((int) $projectId);

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' AND (m.title LIKE ? OR m.attendees LIKE ? OR m.content LIKE ? OR m.location LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY m.meeting_date DESC, m.meeting_time DESC, m.id DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT m.*,
                                     cu.name AS creator_name,
                                     uu.name AS updater_name
                              FROM meeting_minutes m
                              LEFT JOIN users cu ON cu.id = m.created_by
                              LEFT JOIN users uu ON uu.id = m.updated_by
                              WHERE m.id = ?');
        $stmt->execute(array((int) $id));
        $row = $stmt->fetch();
        return $row ? $row : null;
    }

    public static function countByProject($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM meeting_minutes WHERE project_id = ?');
        $stmt->execute(array((int) $projectId));
        return (int) $stmt->fetchColumn();
    }

    public static function create($projectId, array $data, $userId = null)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT INTO meeting_minutes
            (project_id, title, meeting_date, meeting_time, location, attendees, agenda, content, created_by, updated_by)
            VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array(
            (int) $projectId,
            trim(isset($data['title']) ? $data['title'] : ''),
            isset($data['meeting_date']) ? $data['meeting_date'] : date('Y-m-d'),
            self::normalizeTime(isset($data['meeting_time']) ? $data['meeting_time'] : ''),
            trim(isset($data['location']) ? $data['location'] : '') ?: null,
            trim(isset($data['attendees']) ? $data['attendees'] : '') ?: null,
            trim(isset($data['agenda']) ? $data['agenda'] : '') ?: null,
            self::normalizeContent(isset($data['content']) ? $data['content'] : ''),
            $userId,
            $userId,
        ));
        return (int) $db->lastInsertId();
    }

    public static function update($id, array $data, $userId = null)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE meeting_minutes SET
            title = ?, meeting_date = ?, meeting_time = ?, location = ?,
            attendees = ?, agenda = ?, content = ?,
            updated_by = ?, updated_at = NOW()
            WHERE id = ?');
        $stmt->execute(array(
            trim(isset($data['title']) ? $data['title'] : ''),
            isset($data['meeting_date']) ? $data['meeting_date'] : date('Y-m-d'),
            self::normalizeTime(isset($data['meeting_time']) ? $data['meeting_time'] : ''),
            trim(isset($data['location']) ? $data['location'] : '') ?: null,
            trim(isset($data['attendees']) ? $data['attendees'] : '') ?: null,
            trim(isset($data['agenda']) ? $data['agenda'] : '') ?: null,
            self::normalizeContent(isset($data['content']) ? $data['content'] : ''),
            $userId,
            (int) $id,
        ));
    }

    public static function delete($id)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM meeting_minutes WHERE id = ?');
        $stmt->execute(array((int) $id));
    }

    public static function formatDateLabel($date)
    {
        if (!$date) {
            return '—';
        }
        $ts = strtotime($date);
        if (!$ts) {
            return $date;
        }
        return date('Y.m.d', $ts) . ' (' . array('일', '월', '화', '수', '목', '금', '토')[date('w', $ts)] . ')';
    }

    public static function formatTimeLabel($time)
    {
        if (!$time) {
            return '';
        }
        return substr($time, 0, 5);
    }

    private static function normalizeTime($time)
    {
        $time = trim($time);
        if ($time === '') {
            return null;
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $parts = explode(':', $time);
            return sprintf('%02d:%02d:00', (int) $parts[0], (int) $parts[1]);
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        return null;
    }

    private static function normalizeContent($content)
    {
        return sanitize_rich_html($content);
    }
}
