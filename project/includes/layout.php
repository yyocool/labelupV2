<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(isset($pageTitle) ? $pageTitle : 'Dashboard') ?> — Label-UP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <?php if (!empty($extraHead)): ?>
    <?= $extraHead ?>
    <?php endif; ?>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
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
                <a href="<?= url('index.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'dashboard' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    대시보드
                </a>
                <a href="<?= url('competitive-analysis.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'competitive-analysis' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                    경쟁서비스 분석
                </a>
                <a href="<?= url('policies.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'policies' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    정책관리
                </a>
                <a href="<?= url('pricing-analysis.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'pricing-analysis' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    요금정책분석
                </a>
                <?php if (is_super_admin()): ?>
                <a href="<?= url('format-analysis.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'format-analysis' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 13h2"/><path d="M8 17h2"/><path d="M14 13h2"/><path d="M14 17h2"/></svg>
                    포맷 분석
                </a>
                <?php endif; ?>
                <a href="<?= url('menus.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'menus' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    메뉴 구성도
                </a>
                <a href="<?= url('storyboard.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'storyboard' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    스토리보드
                </a>
                <a href="<?= url('issues.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'issues' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    이슈 관리
                </a>
                <a href="<?= url('milestones.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'milestones' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    마일스톤
                </a>
                <a href="<?= url('schedule.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'schedule' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    일정관리
                </a>
                <a href="<?= url('meeting-minutes.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'meeting-minutes' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    회의록
                </a>
                <a href="<?= url('notices.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'notices' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    공지사항
                </a>
                <a href="<?= url('archive.php') ?>" class="nav-item <?= (isset($currentPage) ? $currentPage : '') === 'archive' ? 'active' : '' ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                    자료실
                </a>

                <?php
                if (empty($phaseTracker) && function_exists('get_active_project_id')) {
                    $pid = get_active_project_id();
                    if ($pid) $phaseTracker = ProjectService::getPhaseTracker($pid);
                }
                if (!empty($phaseTracker)) {
                    echo render_phase_tracker($phaseTracker);
                }
                ?>

                <?php if (is_super_admin()): ?>
                <div class="nav-divider"></div>
                <a href="<?= admin_url('index.php') ?>" class="nav-item admin-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    관리자
                </a>
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
