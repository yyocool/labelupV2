<?php



class IssueService

{

    public static function getByProject($projectId, $status = null)

    {

        $db = Database::getConnection();

        $sql = '

            SELECT i.*, m.title as menu_title, r.name as reporter_name, a.name as assignee_name

            FROM issues i

            LEFT JOIN menus m ON m.id = i.menu_id

            LEFT JOIN users r ON r.id = i.reporter_id

            LEFT JOIN users a ON a.id = i.assignee_id

            WHERE i.project_id = ?

        ';

        $params = array($projectId);

        if ($status) {

            $sql .= ' AND i.status = ?';

            $params[] = $status;

        }

        $sql .= ' ORDER BY FIELD(i.priority,"urgent","high","medium","low"), i.created_at DESC';

        $stmt = $db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll();

    }



    public static function getById($id)

    {

        $db = Database::getConnection();

        $stmt = $db->prepare('

            SELECT i.*, m.title as menu_title, r.name as reporter_name, a.name as assignee_name

            FROM issues i

            LEFT JOIN menus m ON m.id = i.menu_id

            LEFT JOIN users r ON r.id = i.reporter_id

            LEFT JOIN users a ON a.id = i.assignee_id

            WHERE i.id = ?

        ');

        $stmt->execute(array($id));

        $row = $stmt->fetch();

        return $row ? $row : null;

    }



    public static function create($projectId, array $data, $reporterId = null)

    {

        $db = Database::getConnection();

        $stmt = $db->prepare('

            INSERT INTO issues (project_id, menu_id, title, description, type, status, priority, reporter_id, assignee_id, due_date)

            VALUES (?,?,?,?,?,?,?,?,?,?)

        ');

        $stmt->execute(array(

            $projectId,

            !empty($data['menu_id']) ? $data['menu_id'] : null,

            $data['title'],

            isset($data['description']) ? $data['description'] : null,

            isset($data['type']) ? $data['type'] : 'task',

            isset($data['status']) ? $data['status'] : 'open',

            isset($data['priority']) ? $data['priority'] : 'medium',

            $reporterId,

            !empty($data['assignee_id']) ? $data['assignee_id'] : null,

            !empty($data['due_date']) ? $data['due_date'] : null,

        ));

        return (int) $db->lastInsertId();

    }



    public static function update($id, array $data)

    {

        $db = Database::getConnection();

        $closedAt = (isset($data['status']) ? $data['status'] : '') === 'closed' ? date('Y-m-d H:i:s') : null;

        $stmt = $db->prepare('

            UPDATE issues SET title=?, description=?, type=?, status=?, priority=?,

            assignee_id=?, menu_id=?, due_date=?, closed_at=COALESCE(?, closed_at), updated_at=NOW()

            WHERE id=?

        ');

        $stmt->execute(array(

            $data['title'],

            isset($data['description']) ? $data['description'] : null,

            isset($data['type']) ? $data['type'] : 'task',

            isset($data['status']) ? $data['status'] : 'open',

            isset($data['priority']) ? $data['priority'] : 'medium',

            !empty($data['assignee_id']) ? $data['assignee_id'] : null,

            !empty($data['menu_id']) ? $data['menu_id'] : null,

            !empty($data['due_date']) ? $data['due_date'] : null,

            $closedAt,

            $id,

        ));

    }



    public static function getComments($issueId)

    {

        $db = Database::getConnection();

        $stmt = $db->prepare('

            SELECT ic.*, u.name as user_name, u.avatar_color

            FROM issue_comments ic

            JOIN users u ON u.id = ic.user_id

            WHERE ic.issue_id = ? ORDER BY ic.created_at

        ');

        $stmt->execute(array($issueId));

        return $stmt->fetchAll();

    }



    public static function addComment($issueId, $userId, $content)

    {

        $db = Database::getConnection();

        $db->prepare('INSERT INTO issue_comments (issue_id, user_id, content) VALUES (?,?,?)')

           ->execute(array($issueId, $userId, $content));

    }

}

