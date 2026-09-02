<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = '잘못된 요청입니다.';
    } else {
        $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $user = authenticate($username, $password);
        if ($user) {
            login_user($user);
            redirect('index.php');
        } else {
            $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 — Label-UP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-icon">LU</div>
            <h1>Label-UP</h1>
            <p>with AI — 프로젝트 관리</p>
        </div>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>아이디</label>
                <input type="text" name="username" class="form-control" required autofocus
                       value="<?= e(isset($_POST['username']) ? $_POST['username'] : '') ?>" placeholder="admin">
            </div>
            <div class="form-group">
                <label>비밀번호</label>
                <input type="password" name="password" class="form-control" required placeholder="비밀번호">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">로그인</button>
        </form>
    </div>
</div>
</body>
</html>
