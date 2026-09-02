<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(isset($pageTitle) ? $pageTitle : '관리자') ?> — Label-UP Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar admin-sidebar">
            <div class="sidebar-brand">
                <a href="<?= admin_url('index.php') ?>">
                    <span class="brand-icon admin">AD</span>
                    <div>
                        <strong>Admin</strong>
                        <small>Label-UP</small>
                    </div>
                </a>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= admin_url('index.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'admin-dashboard' ? 'active' : '' ?>">대시보드</a>
                <a href="<?= admin_url('participants.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'participants' ? 'active' : '' ?>">참여자 관리</a>
                <a href="<?= admin_url('menus.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'admin-menus' ? 'active' : '' ?>">메뉴 관리</a>
                <a href="<?= admin_url('storyboards.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'admin-storyboards' ? 'active' : '' ?>">스토리보드 관리</a>
                <a href="<?= admin_url('progress.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'progress' ? 'active' : '' ?>">진행 관리</a>
                <a href="<?= admin_url('projects.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'projects' ? 'active' : '' ?>">프로젝트 설정</a>
                <a href="<?= admin_url('users.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'users' ? 'active' : '' ?>">사용자 관리</a>
                <div class="nav-divider"></div>
                <a href="<?= url('index.php') ?>" class="nav-item">← 메인으로</a>
            </nav>
        </aside>
        <main class="main-content">
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success"><?= e($msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
            <div class="alert alert-error"><?= e($msg) ?></div>
            <?php endif; ?>
            <?= isset($content) ? $content : '' ?>
        </main>
    </div>
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
