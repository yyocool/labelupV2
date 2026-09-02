<?php

class ScheduleService
{
    public static function getTypeMeta()
    {
        return array(
            'milestone'     => array('label' => '마일스톤', 'color' => '#6366f1', 'icon' => '🎯'),
            'issue'         => array('label' => '이슈',     'color' => '#ef4444', 'icon' => '🐛'),
            'menu'          => array('label' => '메뉴',     'color' => '#10b981', 'icon' => '📋'),
            'project_start' => array('label' => '프로젝트 시작', 'color' => '#8b5cf6', 'icon' => '🚀'),
            'project_end'   => array('label' => '프로젝트 종료', 'color' => '#f59e0b', 'icon' => '🏁'),
        );
    }

    public static function getEventsForProject($projectId, $from = null, $to = null)
    {
        $events = array();
        $events = array_merge($events, self::getMilestoneEvents($projectId));
        $events = array_merge($events, self::getIssueEvents($projectId));
        $events = array_merge($events, self::getMenuEvents($projectId));
        $events = array_merge($events, self::getProjectEvents($projectId));

        if ($from || $to) {
            $events = array_values(array_filter($events, function ($ev) use ($from, $to) {
                $d = $ev['date'];
                if ($from && $d < $from) {
                    return false;
                }
                if ($to && $d > $to) {
                    return false;
                }
                return true;
            }));
        }

        usort($events, function ($a, $b) {
            $cmp = strcmp($a['date'], $b['date']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp($a['sort_key'], $b['sort_key']);
        });

        return $events;
    }

    public static function getEventsForMonth($projectId, $year, $month)
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = (int) date('t', strtotime($from));
        $to = sprintf('%04d-%02d-%02d', $year, $month, $lastDay);
        return self::getEventsForProject($projectId, $from, $to);
    }

    public static function getEventsForWeek($projectId, $anchorDate)
    {
        $ts = strtotime($anchorDate);
        $dow = (int) date('N', $ts);
        $monday = date('Y-m-d', strtotime('-' . ($dow - 1) . ' days', $ts));
        $sunday = date('Y-m-d', strtotime('+6 days', strtotime($monday)));
        return self::getEventsForProject($projectId, $monday, $sunday);
    }

    public static function groupByDate(array $events)
    {
        $grouped = array();
        foreach ($events as $ev) {
            $d = $ev['date'];
            if (!isset($grouped[$d])) {
                $grouped[$d] = array();
            }
            $grouped[$d][] = $ev;
        }
        return $grouped;
    }

    public static function getCalendarGrid($year, $month)
    {
        $firstDay = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = (int) date('t', $firstDay);
        $startDow = (int) date('N', $firstDay);
        $cells = array();

        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        $prevDays = (int) date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));

        for ($i = $startDow - 1; $i > 0; $i--) {
            $day = $prevDays - $i + 1;
            $cells[] = array(
                'date' => sprintf('%04d-%02d-%02d', $prevYear, $prevMonth, $day),
                'day' => $day,
                'in_month' => false,
            );
        }

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $cells[] = array(
                'date' => sprintf('%04d-%02d-%02d', $year, $month, $d),
                'day' => $d,
                'in_month' => true,
            );
        }

        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        $remain = 42 - count($cells);
        for ($d = 1; $d <= $remain; $d++) {
            $cells[] = array(
                'date' => sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $d),
                'day' => $d,
                'in_month' => false,
            );
        }

        return $cells;
    }

    public static function getWeekDays($anchorDate)
    {
        $ts = strtotime($anchorDate);
        $dow = (int) date('N', $ts);
        $monday = strtotime('-' . ($dow - 1) . ' days', $ts);
        $days = array();
        $labels = array('월', '화', '수', '목', '금', '토', '일');
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime('+' . $i . ' days', $monday));
            $days[] = array(
                'date' => $date,
                'label' => $labels[$i],
                'day' => (int) date('j', strtotime($date)),
                'is_today' => ($date === date('Y-m-d')),
            );
        }
        return $days;
    }

    private static function getMilestoneEvents($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM milestones WHERE project_id = ? AND due_date IS NOT NULL ORDER BY due_date');
        $stmt->execute(array($projectId));
        $events = array();
        foreach ($stmt->fetchAll() as $row) {
            $isOverdue = $row['status'] !== 'completed' && strtotime($row['due_date']) < strtotime(date('Y-m-d'));
            $events[] = self::buildEvent(
                'milestone',
                $row['id'],
                $row['title'],
                $row['due_date'],
                isset($row['status']) ? $row['status'] : 'upcoming',
                url('milestones.php'),
                isset($row['description']) ? $row['description'] : '',
                $isOverdue,
                isset($row['status']) ? $row['status'] : ''
            );
        }
        return $events;
    }

    private static function getIssueEvents($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT i.*, m.title AS menu_title, a.name AS assignee_name
            FROM issues i
            LEFT JOIN menus m ON m.id = i.menu_id
            LEFT JOIN users a ON a.id = i.assignee_id
            WHERE i.project_id = ? AND i.due_date IS NOT NULL AND i.due_date != ""
            ORDER BY i.due_date
        ');
        $stmt->execute(array($projectId));
        $events = array();
        foreach ($stmt->fetchAll() as $row) {
            $isOverdue = !in_array($row['status'], array('resolved', 'closed'), true)
                && strtotime($row['due_date']) < strtotime(date('Y-m-d'));
            $meta = array();
            if (!empty($row['menu_title'])) {
                $meta[] = $row['menu_title'];
            }
            if (!empty($row['assignee_name'])) {
                $meta[] = $row['assignee_name'];
            }
            $events[] = self::buildEvent(
                'issue',
                $row['id'],
                $row['title'],
                $row['due_date'],
                isset($row['status']) ? $row['status'] : 'open',
                url('issue-detail.php?id=' . $row['id']),
                implode(' · ', $meta),
                $isOverdue,
                isset($row['priority']) ? $row['priority'] : ''
            );
        }
        return $events;
    }

    private static function getMenuEvents($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            SELECT m.id, m.title, mp.due_date, mp.priority,
                   u.name AS assignee_name
            FROM menus m
            INNER JOIN menu_progress mp ON mp.menu_id = m.id
            LEFT JOIN users u ON u.id = mp.assignee_id
            WHERE m.project_id = ? AND m.is_active = 1
              AND mp.due_date IS NOT NULL AND mp.due_date != ""
            ORDER BY mp.due_date
        ');
        $stmt->execute(array($projectId));
        $events = array();
        foreach ($stmt->fetchAll() as $row) {
            $meta = array();
            if (!empty($row['assignee_name'])) {
                $meta[] = $row['assignee_name'];
            }
            if (!empty($row['priority'])) {
                $meta[] = $row['priority'];
            }
            $events[] = self::buildEvent(
                'menu',
                $row['id'],
                $row['title'],
                $row['due_date'],
                'due',
                url('menu-detail.php?id=' . $row['id']),
                implode(' · ', $meta),
                strtotime($row['due_date']) < strtotime(date('Y-m-d')),
                ''
            );
        }
        return $events;
    }

    private static function getProjectEvents($projectId)
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, name, start_date, end_date FROM projects WHERE id = ?');
        $stmt->execute(array($projectId));
        $row = $stmt->fetch();
        if (!$row) {
            return array();
        }
        $events = array();
        if (!empty($row['start_date'])) {
            $events[] = self::buildEvent(
                'project_start',
                $row['id'],
                $row['name'] . ' 시작',
                $row['start_date'],
                'milestone',
                url('index.php'),
                '프로젝트 시작일',
                false,
                ''
            );
        }
        if (!empty($row['end_date'])) {
            $events[] = self::buildEvent(
                'project_end',
                $row['id'],
                $row['name'] . ' 종료',
                $row['end_date'],
                'milestone',
                url('index.php'),
                '프로젝트 종료일',
                strtotime($row['end_date']) < strtotime(date('Y-m-d')),
                ''
            );
        }
        return $events;
    }

    private static function buildEvent($type, $sourceId, $title, $date, $status, $link, $meta, $isOverdue, $extra)
    {
        $types = self::getTypeMeta();
        $typeMeta = isset($types[$type]) ? $types[$type] : array('label' => $type, 'color' => '#64748b', 'icon' => '📌');
        return array(
            'uid' => $type . '-' . $sourceId . '-' . $date,
            'type' => $type,
            'type_label' => $typeMeta['label'],
            'type_color' => $typeMeta['color'],
            'type_icon' => $typeMeta['icon'],
            'source_id' => (int) $sourceId,
            'title' => $title,
            'date' => $date,
            'status' => $status,
            'link' => $link,
            'meta' => $meta,
            'is_overdue' => $isOverdue,
            'extra' => $extra,
            'sort_key' => $type . '-' . str_pad((string) $sourceId, 8, '0', STR_PAD_LEFT),
        );
    }
}
