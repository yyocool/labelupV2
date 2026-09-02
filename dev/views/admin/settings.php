<div class="admin-head">
  <div>
    <h1>운영설정</h1>
    <p>회원가입 약관 및 정책 문서를 관리합니다.</p>
  </div>
</div>

<div id="adminAlert" class="admin-alert"></div>

<div class="admin-legal-tabs">
  <?php foreach ($documents as $i => $doc): ?>
  <button type="button" class="admin-legal-tab<?= $i === 0 ? ' is-active' : '' ?>" data-tab="<?= e($doc['doc_key']) ?>">
    <?= e($doc['title']) ?>
  </button>
  <?php endforeach; ?>
</div>

<?php foreach ($documents as $i => $doc): ?>
<section class="admin-legal-panel<?= $i === 0 ? ' is-active' : '' ?>" data-panel="<?= e($doc['doc_key']) ?>">
  <form class="admin-legal-form" data-doc-key="<?= e($doc['doc_key']) ?>">
    <div class="admin-legal-meta">
      <span>문서키: <code><?= e($doc['doc_key']) ?></code></span>
      <span>버전: v<?= (int) $doc['version'] ?></span>
      <span><?= ($doc['is_required'] ?? false) ? '필수 동의' : '선택 동의' ?></span>
      <?php if (!empty($doc['updated_at'])): ?>
      <span>수정: <?= e(substr((string) $doc['updated_at'], 0, 16)) ?></span>
      <?php endif; ?>
    </div>
    <div class="admin-field">
      <label>제목</label>
      <input type="text" name="title" value="<?= e($doc['title']) ?>" required>
    </div>
    <div class="admin-field admin-field--editor">
      <label>내용</label>
      <textarea
        id="legal-content-<?= e($doc['doc_key']) ?>"
        class="js-legal-editor"
        name="content"
        rows="16"
        required
      ><?= e($doc['content']) ?></textarea>
    </div>
    <div class="admin-head-actions">
      <button type="submit" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </form>
</section>
<?php endforeach; ?>
