<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>공지사항</h1>
            <p>프로젝트 공지 및 팀 메모</p>
        </div>
        <?php if (is_admin()): ?>
        <button class="btn btn-primary" data-modal="noticeModal">+ 공지 작성</button>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($notices)): ?>
<div class="card"><div class="empty-state"><p>등록된 공지가 없습니다.</p></div></div>
<?php else: ?>
<?php foreach ($notices as $notice): ?>
<div class="card" style="margin-bottom:12px">
    <div class="card-header">
        <h3><?= e($notice['title']) ?> <?php if($notice['is_pinned']): ?><span class="badge badge-yellow">고정</span><?php endif; ?></h3>
        <small style="color:var(--text-muted)"><?= e(isset($notice['author']) ? $notice['author'] : '') ?> · <?= time_ago($notice['created_at']) ?></small>
    </div>
    <div style="font-size:14px;color:var(--text-secondary);line-height:1.7"><?= nl2br(e($notice['content'])) ?></div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (is_admin()): ?>
<div class="modal-overlay" id="noticeModal">
    <div class="modal">
        <div class="modal-header"><h3>공지 작성</h3><button class="modal-close">&times;</button></div>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group"><label>제목</label><input type="text" name="title" class="form-control" required></div>
            <div class="form-group"><label>내용</label><textarea name="content" class="form-control" rows="6" required></textarea></div>
            <div class="form-group"><label><input type="checkbox" name="is_pinned" value="1"> 상단 고정</label></div>
            <button type="submit" class="btn btn-primary">등록</button>
        </form>
    </div>
</div>
<?php endif; ?>
