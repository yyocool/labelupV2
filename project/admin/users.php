<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$db = Database::getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'create') {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $colors = ['#6366f1','#ec4899','#10b981','#f59e0b','#3b82f6'];
        $color = $colors[array_rand($colors)];
        $username = trim($_POST['username']);
        $exists = $db->prepare('SELECT id FROM users WHERE username = ?');
        $exists->execute(array($username));
        if ($exists->fetch()) {
            flash('error', '이미 사용 중인 아이디입니다.');
            admin_redirect('users.php');
        }
        $phone = isset($_POST['phone']) ? $_POST['phone'] : null;
        $db->prepare('INSERT INTO users (username, email, password, name, role, avatar_color, phone) VALUES (?,?,?,?,?,?,?)')
           ->execute(array($username, $_POST['email'], $hash, $_POST['name'], $_POST['role'], $color, $phone));
        flash('success', '사용자가 등록되었습니다.');
    } elseif ($action === 'toggle') {
        $db->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?')->execute([(int)$_POST['id']]);
        flash('success', '상태가 변경되었습니다.');
    }
    admin_redirect('users.php');
}

$users = get_all_users();

$pageTitle = '사용자 관리';
$currentPage = 'users';

render_admin_page(__DIR__ . '/views/users.php', compact('pageTitle', 'currentPage', 'users'));
