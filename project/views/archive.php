<?php
/**
 * 자료실
 * @var bool $canUpload 로그인 사용자 전원 true
 * @var int $currentUserId
 */
if (!isset($canUpload)) {
    $canUpload = true;
}
if (!isset($currentUserId)) {
    $u = current_user();
    $currentUserId = $u ? (int) $u['id'] : 0;
}
?>
<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>자료실</h1>
            <p>계약서, 기획서, 참고 자료 등 프로젝트 관련 문서를 보관합니다.</p>
        </div>
        <?php if ($canUpload): ?>
        <button type="button" class="btn btn-primary" data-modal="archiveUploadModal" id="archiveUploadBtn">+ 자료 등록</button>
        <?php endif; ?>
    </div>
</div>

<div class="archive-filters card" style="margin-bottom:20px;padding:12px 16px">
    <div class="archive-filter-tabs">
        <a href="<?= url('archive.php') ?>" class="archive-filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
            전체 <span class="archive-filter-count"><?= $totalCount ?></span>
        </a>
        <?php foreach ($categories as $key => $meta): ?>
        <a href="<?= url('archive.php?category=' . $key) ?>" class="archive-filter-tab <?= $filter === $key ? 'active' : '' ?>">
            <?= $meta['icon'] ?> <?= e($meta['label']) ?>
            <?php if (!empty($categoryCounts[$key])): ?><span class="archive-filter-count"><?= (int) $categoryCounts[$key] ?></span><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php
$hasAny = false;
foreach ($grouped as $key => $group) {
    if (!empty($group['items'])) {
        $hasAny = true;
        break;
    }
}
?>

<?php if (!$hasAny): ?>
<div class="card">
    <div class="empty-state">
        <div style="font-size:40px;margin-bottom:12px">📂</div>
        <p>등록된 자료가 없습니다.</p>
        <?php if ($canUpload): ?>
        <button type="button" class="btn btn-primary" style="margin-top:12px" data-modal="archiveUploadModal">첫 자료 등록하기</button>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<?php foreach ($grouped as $key => $group): ?>
<?php if (empty($group['items'])) continue; ?>
<?php if ($filter !== 'all' && $filter !== $key) continue; ?>
<div class="card archive-section" style="margin-bottom:16px">
    <div class="card-header">
        <h3><?= e($group['meta']['icon']) ?> <?= e($group['meta']['label']) ?></h3>
        <span class="badge badge-light"><?= count($group['items']) ?>건</span>
    </div>
    <div class="archive-list">
        <?php foreach ($group['items'] as $doc): ?>
        <?php $canManage = ArchiveService::canManage($doc, $currentUserId); ?>
        <div class="archive-item">
            <div class="archive-item-icon"><?= archive_file_icon($doc['original_name']) ?></div>
            <div class="archive-item-body">
                <div class="archive-item-title"><?= e($doc['title']) ?></div>
                <div class="archive-item-meta">
                    <span><?= e($doc['original_name']) ?></span>
                    <span>·</span>
                    <span><?= format_file_size($doc['file_size']) ?></span>
                    <span>·</span>
                    <span><?= e(isset($doc['uploader']) ? $doc['uploader'] : '') ?></span>
                    <span>·</span>
                    <span><?= time_ago($doc['created_at']) ?></span>
                </div>
                <?php if (!empty($doc['description'])): ?>
                <div class="archive-item-desc"><?= e($doc['description']) ?></div>
                <?php endif; ?>
            </div>
            <div class="archive-item-actions">
                <a href="<?= url('archive-download.php?id=' . $doc['id']) ?>" class="btn btn-secondary btn-sm">다운로드</a>
                <?php if ($canManage): ?>
                <button type="button"
                        class="btn btn-secondary btn-sm archive-edit-btn"
                        data-modal="archiveEditModal"
                        data-id="<?= (int) $doc['id'] ?>"
                        data-category="<?= e($doc['category']) ?>"
                        data-title="<?= e($doc['title']) ?>"
                        data-description="<?= e(isset($doc['description']) ? $doc['description'] : '') ?>"
                        data-filename="<?= e($doc['original_name']) ?>">수정</button>
                <form method="post" class="archive-delete-form" onsubmit="return confirm('이 자료를 삭제하시겠습니까?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="document_id" value="<?= (int) $doc['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-sm archive-delete-btn">삭제</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if ($canUpload): ?>
<div class="modal-overlay" id="archiveUploadModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3>자료 등록</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload">
            <div class="form-group">
                <label>분류</label>
                <select name="category" class="form-control" required>
                    <?php foreach ($categories as $key => $meta): ?>
                    <option value="<?= e($key) ?>" <?= ($filter === $key || ($filter === 'all' && $key === 'contract')) ? 'selected' : '' ?>>
                        <?= e($meta['icon'] . ' ' . $meta['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>제목</label>
                <input type="text" name="title" class="form-control" placeholder="미입력 시 파일명으로 저장됩니다">
            </div>
            <div class="form-group">
                <label>설명 (선택)</label>
                <textarea name="description" class="form-control" rows="3" placeholder="자료에 대한 간단한 설명"></textarea>
            </div>
            <div class="form-group">
                <label>파일 첨부</label>
                <input type="file" name="document" class="form-control archive-file-input" required>
                <small class="form-hint">PDF, Word, Excel, PPT, HWP, ZIP, 이미지 등 · 최대 20MB</small>
            </div>
            <button type="submit" class="btn btn-primary">등록</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="modal-overlay" id="archiveEditModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3>자료 수정</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <form method="post" enctype="multipart/form-data" id="archiveEditForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="document_id" id="archiveEditId" value="">
            <div class="form-group">
                <label>분류</label>
                <select name="category" id="archiveEditCategory" class="form-control" required>
                    <?php foreach ($categories as $key => $meta): ?>
                    <option value="<?= e($key) ?>"><?= e($meta['icon'] . ' ' . $meta['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>제목</label>
                <input type="text" name="title" id="archiveEditTitle" class="form-control" required>
            </div>
            <div class="form-group">
                <label>설명 (선택)</label>
                <textarea name="description" id="archiveEditDescription" class="form-control" rows="3" placeholder="자료에 대한 간단한 설명"></textarea>
            </div>
            <div class="form-group">
                <label>파일 교체 (선택)</label>
                <input type="file" name="document" class="form-control archive-file-input">
                <small class="form-hint">현재 파일: <span id="archiveEditFilename"></span> · 새 파일을 선택하면 교체됩니다 · 최대 20MB</small>
            </div>
            <button type="submit" class="btn btn-primary">저장</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.archive-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idEl = document.getElementById('archiveEditId');
            var catEl = document.getElementById('archiveEditCategory');
            var titleEl = document.getElementById('archiveEditTitle');
            var descEl = document.getElementById('archiveEditDescription');
            var nameEl = document.getElementById('archiveEditFilename');
            if (idEl) idEl.value = btn.getAttribute('data-id') || '';
            if (catEl) catEl.value = btn.getAttribute('data-category') || 'reference';
            if (titleEl) titleEl.value = btn.getAttribute('data-title') || '';
            if (descEl) descEl.value = btn.getAttribute('data-description') || '';
            if (nameEl) nameEl.textContent = btn.getAttribute('data-filename') || '';
        });
    });
});
</script>
