<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/schema_runner.php';

$installed = file_exists(APP_ROOT . '/storage/installed.lock');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    try {
        $dbConfig = require APP_ROOT . '/config/database.php';
        $port = isset($dbConfig['port']) ? $dbConfig['port'] : 3306;
        $charset = isset($dbConfig['charset']) ? $dbConfig['charset'] : 'utf8mb4';
        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $dbConfig['host'], $port, $charset);
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ));

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbConfig['dbname']}`");

        if (!schema_tables_exist($pdo)) {
            execute_sql_file($pdo, APP_ROOT . '/sql/schema.sql');
        }

        if (!schema_tables_exist($pdo)) {
            throw new RuntimeException('테이블 생성에 실패했습니다. MySQL 권한을 확인해 주세요.');
        }

        $admin = app_config('default_admin');
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $check->execute(array($admin['username']));
        if (!$check->fetch()) {
            $hash = password_hash($admin['password'], PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (username, email, password, name, role, avatar_color) VALUES (?,?,?,?,?,?)')
               ->execute(array($admin['username'], $admin['email'], $hash, $admin['name'], 'admin', '#6366f1'));

            $adminId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO projects (name, description, status, start_date) VALUES (?,?,?,CURDATE())')
                ->execute(array('Label-UP 메인 프로젝트', '웹 개발 프로젝트 관리 시스템', 'active'));
            $projectId = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO project_members (project_id, user_id, role) VALUES (?,?,?)')
                ->execute(array($projectId, $adminId, 'owner'));

            $sampleMenus = array(
                array('메인', null, 0),
                array('대시보드', 1, 1),
                array('로그인/회원가입', 1, 1),
                array('관리자', null, 0),
                array('참여자 관리', 4, 1),
                array('메뉴 관리', 4, 1),
            );
            $menuIds = array();
            foreach ($sampleMenus as $i => $item) {
                $title = $item[0];
                $parentIdx = $item[1];
                $depth = $item[2];
                $parentId = ($parentIdx !== null && isset($menuIds[$parentIdx - 1])) ? $menuIds[$parentIdx - 1] : null;
                $pdo->prepare('INSERT INTO menus (project_id, parent_id, title, sort_order, depth) VALUES (?,?,?,?,?)')
                    ->execute(array($projectId, $parentId, $title, $i, $depth));
                $mid = (int) $pdo->lastInsertId();
                $menuIds[] = $mid;
                $pdo->prepare('INSERT INTO menu_progress (menu_id) VALUES (?)')->execute(array($mid));
            }

            require_once __DIR__ . '/includes/MenuService.php';
            MenuService::rebuildCodes($projectId);

            $pdo->prepare('INSERT INTO milestones (project_id, title, due_date, status) VALUES (?,?,?,?)')
                ->execute(array($projectId, '1차 스토리보드 완료', date('Y-m-d', strtotime('+2 weeks')), 'upcoming'));
            $pdo->prepare('INSERT INTO milestones (project_id, title, due_date, status) VALUES (?,?,?,?)')
                ->execute(array($projectId, '퍼블리싱 완료', date('Y-m-d', strtotime('+1 month')), 'upcoming'));
            $pdo->prepare('INSERT INTO notices (project_id, title, content, is_pinned, created_by) VALUES (?,?,?,?,?)')
                ->execute(array($projectId, 'Label-UP 프로젝트 시작', '프로젝트 관리 시스템 구축을 시작합니다. 메뉴별 진행상황을 꾸준히 업데이트해 주세요.', 1, $adminId));
        }

        if (!is_dir(APP_ROOT . '/storage')) {
            mkdir(APP_ROOT . '/storage', 0755, true);
        }
        file_put_contents(APP_ROOT . '/storage/installed.lock', date('Y-m-d H:i:s'));

        $installed = true;
        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$env = app_config('environment', 'local');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>설치 — Label-UP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
<div class="install-page">
    <div class="install-card">
        <div class="login-brand">
            <div class="brand-icon">LU</div>
            <h1>Label-UP 설치</h1>
            <p>with AI — 프로젝트 관리 시스템</p>
        </div>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success">설치가 완료되었습니다!</div>
        <p style="margin-bottom:16px">기본 관리자 계정:</p>
        <ul style="margin-bottom:20px;color:var(--text-secondary)">
            <?php $defAdmin = app_config('default_admin', array()); ?>
            <li>아이디: <strong><?= e(isset($defAdmin['username']) ? $defAdmin['username'] : 'admin') ?></strong></li>
            <li>비밀번호: <strong><?= e(isset($defAdmin['password']) ? $defAdmin['password'] : '') ?></strong></li>
        </ul>
        <a href="<?= url('login.php') ?>" class="btn btn-primary">로그인하기</a>

        <?php elseif ($installed): ?>
        <div class="alert alert-success">이미 설치되어 있습니다.</div>
        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px;line-height:1.6">
            처음부터 다시 설치하려면 서버에서 <code>storage/installed.lock</code> 파일을 삭제한 뒤 이 페이지를 새로고침하세요.<br>
            DB도 초기화하려면 MySQL에서 <code>labelup</code> 데이터베이스(또는 사용 중인 DB)의 테이블을 모두 삭제한 후 설치를 진행하세요.
        </p>
        <a href="<?= url('login.php') ?>" class="btn btn-primary">로그인하기</a>

        <?php else: ?>
        <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <p style="color:var(--text-secondary);margin-bottom:20px">현재 환경: <strong><?= e($env) ?></strong></p>

        <ul class="install-steps">
            <li><span class="step-num">1</span> MySQL 데이터베이스 연결</li>
            <li><span class="step-num">2</span> 테이블 스키마 생성</li>
            <li><span class="step-num">3</span> 관리자 계정 및 샘플 데이터 생성</li>
        </ul>

        <form method="post">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary" style="width:100%">설치 시작</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
