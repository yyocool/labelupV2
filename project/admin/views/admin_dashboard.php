<div class="page-header">
    <h1>관리자 대시보드</h1>
    <p><?= e($project['name']) ?> 프로젝트 관리</p>
</div>

<div class="stats-grid">
    <div class="stat-card highlight">
        <div class="stat-label">프로젝트 진척도</div>
        <div class="stat-value"><?= (int)$project['progress'] ?>%</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">참여자</div>
        <div class="stat-value"><?= count($members) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">메뉴</div>
        <div class="stat-value"><?= count($menuList) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">열린 이슈</div>
        <div class="stat-value"><?= $stats['openIssues'] ?></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>빠른 링크</h3></div>
        <div class="btn-group" style="flex-direction:column;align-items:stretch">
            <a href="<?= admin_url('participants.php') ?>" class="btn btn-secondary">참여자 관리</a>
            <a href="<?= admin_url('menus.php') ?>" class="btn btn-secondary">메뉴 관리</a>
            <a href="<?= admin_url('progress.php') ?>" class="btn btn-secondary">진행 관리</a>
            <a href="<?= admin_url('users.php') ?>" class="btn btn-secondary">사용자 관리</a>
            <a href="<?= admin_url('projects.php') ?>" class="btn btn-secondary">프로젝트 설정</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>참여자 현황</h3></div>
        <?php foreach ($members as $m): ?>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-light)">
            <span class="avatar" style="background:<?= e($m['avatar_color']) ?>"><?= e(avatar_initials($m['name'])) ?></span>
            <div><strong><?= e($m['name']) ?></strong><br><small><?= e(role_label($m['role'])) ?></small></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
