<nav class="breadcrumb">
    <a href="<?= url('menus.php') ?>">메뉴 구성도</a>
    <?php foreach ($breadcrumb as $i => $crumb): ?>
    <span class="breadcrumb-sep">/</span>
    <?php if ($i < count($breadcrumb) - 1): ?>
    <a href="<?= url('menu-detail.php?id=' . $crumb['id']) ?>"><?= e($crumb['title']) ?></a>
    <?php else: ?>
    <span><?= e($crumb['title']) ?></span>
    <?php endif; ?>
    <?php endforeach; ?>
</nav>

<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1><?= e($menu['title']) ?></h1>
            <?php if (!empty($menu['menu_code'])): ?>
            <p class="menu-detail-code">메뉴코드 <code><?= e($menu['menu_code']) ?></code></p>
            <?php endif; ?>
            <?php if (!empty($menu['description'])): ?><p><?= e($menu['description']) ?></p><?php endif; ?>
        </div>
        <div class="btn-group">
            <a href="<?= url('storyboard.php?menu_id=' . $menu['id']) ?>" class="btn btn-primary btn-sm">스토리보드</a>
            <a href="<?= url('issues.php?action=new&menu_id=' . $menu['id']) ?>" class="btn btn-secondary btn-sm">+ 이슈</a>
        </div>
    </div>
</div>

<div class="stat-card highlight" style="margin-bottom:24px">
    <div class="stat-label">메뉴 진척도</div>
    <div class="stat-value"><?= (int) (isset($menu['progress_pct']) ? $menu['progress_pct'] : 0) ?>%</div>
    <div class="progress-bar progress-bar-lg" style="margin-top:12px">
        <div class="progress-bar-fill" style="width:<?= (int) (isset($menu['progress_pct']) ? $menu['progress_pct'] : 0) ?>%"></div>
    </div>
</div>

<form method="post">
    <?= csrf_field() ?>

    <div class="phase-grid" style="margin-bottom:24px">
        <?php
        $phases = array(
            'storyboard' => array('스토리보드', '🎨', 'storyboard_status', 'storyboard_note'),
            'design'     => array('디자인', '✏️', 'design_status', 'design_note'),
            'publishing' => array('퍼블리싱', '🖌️', 'publishing_status', 'publishing_note'),
            'coding'     => array('코딩', '💻', 'coding_status', 'coding_note'),
            'review'     => array('검수', '✅', 'review_status', 'review_note'),
        );
        foreach ($phases as $key => $phase):
            $label = $phase[0];
            $icon = $phase[1];
            $statusKey = $phase[2];
            $noteKey = $phase[3];
            $status = isset($menu[$statusKey]) ? $menu[$statusKey] : 'pending';
        ?>
        <div class="phase-card <?= e($status) ?>">
            <div class="phase-card-icon"><?= $icon ?></div>
            <div class="phase-card-label"><?= e($label) ?></div>
            <select name="<?= $statusKey ?>" class="form-control status-select" style="margin-top:8px">
                <option value="pending" <?= $status==='pending'?'selected':'' ?>>대기</option>
                <option value="in_progress" <?= $status==='in_progress'?'selected':'' ?>>진행중</option>
                <option value="done" <?= $status==='done'?'selected':'' ?>>완료</option>
                <option value="na" <?= $status==='na'?'selected':'' ?>>해당없음</option>
            </select>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>단계별 비고</h3></div>
            <?php foreach ($phases as $phase): ?>
            <div class="form-group">
                <label><?= e($phase[0]) ?> 비고</label>
                <textarea name="<?= e($phase[3]) ?>" class="form-control" rows="2"><?= e(isset($menu[$phase[3]]) ? $menu[$phase[3]] : '') ?></textarea>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-header"><h3>담당 및 일정</h3></div>
            <div class="form-group">
                <label>담당자</label>
                <select name="assignee_id" class="form-control">
                    <option value="">— 미지정 —</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (isset($menu['assignee_id']) ? $menu['assignee_id'] : '') == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?> (<?= e(role_label($u['role'])) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>마감일</label>
                    <input type="date" name="due_date" class="form-control" value="<?= e(isset($menu['due_date']) ? $menu['due_date'] : '') ?>">
                </div>
                <div class="form-group">
                    <label>우선순위</label>
                    <select name="priority" class="form-control">
                        <?php foreach (array('low'=>'낮음','medium'=>'보통','high'=>'높음','urgent'=>'긴급') as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= (isset($menu['priority']) ? $menu['priority'] : 'medium')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>종합 비고</label>
                <textarea name="general_note" class="form-control" rows="4"><?= e(isset($menu['general_note']) ? $menu['general_note'] : '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">저장</button>
        </div>
    </div>
</form>

<?php if (!empty($menuIssues)): ?>
<div class="card" style="margin-top:20px">
    <div class="card-header"><h3>관련 이슈</h3></div>
    <?php foreach ($menuIssues as $issue): ?>
    <div class="issue-item">
        <span class="issue-icon"><?= issue_type_icon($issue['type']) ?></span>
        <div class="issue-content">
            <a href="<?= url('issue-detail.php?id=' . $issue['id']) ?>" class="issue-title"><?= e($issue['title']) ?></a>
            <div class="issue-meta"><?= status_badge($issue['status']) ?> <?= priority_badge($issue['priority']) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
