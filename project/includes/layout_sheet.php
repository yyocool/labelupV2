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
                <a href="<?= url('index.php') ?>" class="nav-item">대시보드</a>
                <a href="<?= url('competitive-analysis.php') ?>" class="nav-item">경쟁서비스 분석</a>
                <a href="<?= url('policies.php') ?>" class="nav-item">정책관리</a>
                <a href="<?= url('pricing-analysis.php') ?>" class="nav-item">요금정책분석</a>
                <?php if (is_super_admin()): ?>
                <a href="<?= url('format-analysis.php') ?>" class="nav-item">포맷 분석</a>
                <?php endif; ?>
                <a href="<?= url('menus.php') ?>" class="nav-item">메뉴 구성도</a>
                <a href="<?= url('storyboard.php') ?>" class="nav-item">스토리보드</a>
                <a href="<?= url('issues.php') ?>" class="nav-item">이슈 관리</a>
                <a href="<?= url('milestones.php') ?>" class="nav-item">마일스톤</a>
                <a href="<?= url('schedule.php') ?>" class="nav-item">일정관리</a>
                <a href="<?= url('meeting-minutes.php') ?>" class="nav-item">회의록</a>
                <a href="<?= url('notices.php') ?>" class="nav-item">공지사항</a>
                <a href="<?= url('archive.php') ?>" class="nav-item">자료실</a>
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
