<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
extract(init_project_context());

$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'add') {
        $userId = (int)$_POST['user_id'];
        $role = isset($_POST['role']) ? $_POST['role'] : 'developer';
        $db->prepare('INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?,?,?)')
           ->execute([$project['id'], $userId, $role]);
        log_activity($project['id'], current_user()['id'], 'member_add', 'member', $userId, '참여자 추가');
        flash('success', '참여자가 추가되었습니다.');
    } elseif ($action === 'remove') {
        $db->prepare('DELETE FROM project_members WHERE project_id=? AND user_id=?')
           ->execute([$project['id'], (int)$_POST['user_id']]);
        flash('success', '참여자가 제거되었습니다.');
    } elseif ($action === 'update_role') {
        $db->prepare('UPDATE project_members SET role=? WHERE project_id=? AND user_id=?')
           ->execute([$_POST['role'], $project['id'], (int)$_POST['user_id']]);
        flash('success', '역할이 변경되었습니다.');
    }
    admin_redirect('participants.php');
}

$members = ProjectService::getMembers($project['id']);
$allUsers = get_all_users();
$memberIds = array_column($members, 'user_id');
$availableUsers = array_filter($allUsers, function ($u) use ($memberIds) {
    return !in_array($u['id'], $memberIds);
});

$pageTitle = '참여자 관리';
$currentPage = 'participants';

render_admin_page(__DIR__ . '/views/participants.php', compact(
    'pageTitle', 'currentPage', 'project', 'members', 'availableUsers'
));
