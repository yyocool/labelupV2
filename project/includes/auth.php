<?php



function current_user()

{

    return isset($_SESSION['user']) ? $_SESSION['user'] : null;

}



function is_logged_in()

{

    return !empty($_SESSION['user']);

}



function is_admin()

{

    $user = current_user();

    return $user && in_array($user['role'], array('admin', 'pm'), true);

}



/** 최고 관리자 (시스템 관리자 페이지 전용) */

function is_super_admin()

{

    $user = current_user();

    return $user && $user['role'] === 'admin';

}



function require_login()

{

    if (!is_logged_in()) {

        redirect('login.php');

    }

}



function require_admin()

{

    require_login();

    if (!is_super_admin()) {

        flash('error', '최고 관리자 권한이 필요합니다.');

        redirect('index.php');

    }

}



function login_user(array $user)

{

    unset($user['password']);

    $_SESSION['user'] = $user;



    $db = Database::getConnection();

    $db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute(array($user['id']));

}



function logout_user()

{

    unset($_SESSION['user']);

    session_destroy();

}



function authenticate($username, $password)

{

    $db = Database::getConnection();

    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1');

    $stmt->execute(array(trim($username)));

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        return $user;

    }

    return null;

}



function get_user_by_id($id)

{

    $db = Database::getConnection();

    $stmt = $db->prepare('SELECT id, username, email, name, role, avatar_color, phone, is_active, last_login_at, created_at FROM users WHERE id = ?');

    $stmt->execute(array($id));

    $user = $stmt->fetch();

    return $user ? $user : null;

}



function get_all_users()

{

    $db = Database::getConnection();

    return $db->query('SELECT id, username, email, name, role, avatar_color, is_active, last_login_at, created_at FROM users ORDER BY name')->fetchAll();

}

