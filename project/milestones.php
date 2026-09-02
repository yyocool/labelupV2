<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && is_admin()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'create') {
        $db->prepare('INSERT INTO milestones (project_id, title, description, due_date, status) VALUES (?,?,?,?,?)')
           ->execute(array($project['id'], $_POST['title'], isset($_POST['description']) ? $_POST['description'] : null, $_POST['due_date'], 'upcoming'));
        flash('success', '마일스톤이 등록되었습니다.');
    } elseif ($action === 'update_status') {
        $db->prepare('UPDATE milestones SET status=? WHERE id=? AND project_id=?')
           ->execute(array($_POST['status'], (int) $_POST['id'], $project['id']));
        flash('success', '상태가 변경되었습니다.');
    }
    redirect('milestones.php');
}

$stmt = $db->prepare('SELECT * FROM milestones WHERE project_id = ? ORDER BY due_date');
$stmt->execute(array($project['id']));
$milestones = $stmt->fetchAll();

$pageTitle = '마일스톤';
$currentPage = 'milestones';

render_page(__DIR__ . '/views/milestones.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker', 'milestones'
));
