<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$db = Database::getConnection();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf() && is_admin()) {
    $db->prepare('INSERT INTO notices (project_id, title, content, is_pinned, created_by) VALUES (?,?,?,?,?)')
       ->execute(array(
           $project['id'],
           $_POST['title'],
           $_POST['content'],
           isset($_POST['is_pinned']) ? 1 : 0,
           $user ? $user['id'] : null
       ));
    flash('success', '공지가 등록되었습니다.');
    redirect('notices.php');
}

$stmt = $db->prepare('SELECT n.*, u.name as author FROM notices n LEFT JOIN users u ON u.id = n.created_by WHERE n.project_id = ? ORDER BY n.is_pinned DESC, n.created_at DESC');
$stmt->execute(array($project['id']));
$notices = $stmt->fetchAll();

$pageTitle = '공지사항';
$currentPage = 'notices';

render_page(__DIR__ . '/views/notices.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker', 'notices'
));
