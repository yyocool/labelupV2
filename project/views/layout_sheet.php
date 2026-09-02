<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(isset($pageTitle) ? $pageTitle : 'Dashboard') ?> — Label-UP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="sb-page sheet-page">
    <button type="button" class="sb-hamburger" id="sbHamburger" aria-label="메뉴 열기">
        <span></span><span></span><span></span>
    </button>
    <div class="sb-drawer-overlay" id="sbDrawerOverlay"></div>

    <div class="app-layout app-layout--storyboard app-layout--sheet">
        <aside class="sidebar sb-drawer" id="sbDrawer">
            <div class="sidebar-brand">
                <a href="<?= url('index.php') ?>">
                    <span class="brand-icon">LU</span>
                    <div>
                        <strong>Label-UP</strong>
                        <small>with AI</small>
                    </div>
                </a>
            </div>
            <nav class="sidebar-nav">
                <?php
                $sidebarNavCompact = true;
                if (!isset($currentPage)) {
                    $currentPage = '';
                }
                include dirname(__DIR__) . '/includes/app_sidebar_nav.php';
                ?>
                <?php if (is_super_admin()): ?>
                <div class="nav-divider"></div>
                <a href="<?= admin_url('index.php') ?>" class="nav-item admin-link">관리자</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <?php $user = current_user(); if ($user): ?>
                <div class="user-info">
                    <span class="avatar" style="background:<?= e(isset($user['avatar_color']) ? $user['avatar_color'] : '#6366f1') ?>"><?= e(avatar_initials($user['name'])) ?></span>
                    <div>
                        <strong><?= e($user['name']) ?></strong>
                        <small><?= e(role_label($user['role'])) ?></small>
                    </div>
                </div>
                <a href="<?= url('logout.php') ?>" class="logout-link">로그아웃</a>
                <?php endif; ?>
            </div>
        </aside>
        <main class="main-content main-content--storyboard main-content--sheet">
            <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success sb-flash"><?= e($msg) ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
            <div class="alert alert-error sb-flash"><?= e($msg) ?></div>
            <?php endif; ?>
            <?= isset($content) ? $content : '' ?>
        </main>
    </div>
    <script src="<?= asset('js/app.js') ?>"></script>
    <script src="<?= asset('js/storyboard.js') ?>"></script>
</body>
</html>
