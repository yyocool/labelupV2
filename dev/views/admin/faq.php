<?php
$items = $items ?? [];
$categories = $categories ?? [];
?>
<div class="admin-head">
  <div>
    <h1>FAQ 관리</h1>
    <p>자주 묻는 질문과 카테고리를 등록합니다. 사용자 페이지 <a href="<?= url('faq') ?>" target="_blank">/faq</a>에 노출됩니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn js-faq-cat-add">+ 카테고리</button>
    <button type="button" class="admin-btn admin-btn--primary js-faq-add">+ FAQ 추가</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>

<div class="admin-table-wrap" style="margin-bottom:18px">
  <table class="admin-table">
    <thead>
      <tr>
        <th>카테고리</th>
        <th>슬러그</th>
        <th>FAQ 수</th>
        <th>정렬</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($categories)): ?>
      <tr><td colspan="6" class="empty">카테고리가 없습니다. 먼저 카테고리를 추가해 주세요.</td></tr>
    <?php else: ?>
    <?php foreach ($categories as $cat): ?>
      <tr>
        <td><b><?= e($cat['name'] ?? '') ?></b></td>
        <td><small class="admin-muted"><?= e($cat['slug'] ?? '') ?></small></td>
        <td><?= (int) ($cat['faq_count'] ?? 0) ?></td>
        <td><?= (int) ($cat['sort_order'] ?? 0) ?></td>
        <td><?= !empty($cat['is_active']) ? '<span class="admin-badge admin-badge--ok">노출</span>' : '<span class="admin-badge admin-badge--err">숨김</span>' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-faq-cat-edit" data-row='<?= e(json_encode($cat, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <button type="button" class="admin-btn admin-btn--sm js-faq-cat-delete" data-id="<?= (int) $cat['id'] ?>">삭제</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>카테고리</th>
        <th>질문</th>
        <th>정렬</th>
        <th>상태</th>
        <th>관리</th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="5" class="empty">등록된 FAQ가 없습니다.</td></tr>
    <?php else: ?>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><?= e($row['category_name'] ?: '미분류') ?></td>
        <td><b><?= e($row['question'] ?? '') ?></b></td>
        <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
        <td><?= !empty($row['is_active']) ? '<span class="admin-badge admin-badge--ok">노출</span>' : '<span class="admin-badge admin-badge--err">숨김</span>' ?></td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-faq-edit" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <button type="button" class="admin-btn admin-btn--sm js-faq-delete" data-id="<?= (int) $row['id'] ?>">삭제</button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-modal" id="faqCatModal" hidden>
  <div class="admin-modal-backdrop" data-close="faqCatModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="faqCatModalTitle">카테고리</h2>
      <button type="button" class="admin-modal-close" data-close="faqCatModal" aria-label="닫기">×</button>
    </div>
    <form id="faqCatForm" class="admin-modal-body"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="faqCatModal">취소</button>
      <button type="submit" form="faqCatForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>

<div class="admin-modal" id="faqModal" hidden>
  <div class="admin-modal-backdrop" data-close="faqModal"></div>
  <div class="admin-modal-panel admin-modal-panel--wide" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="faqModalTitle">FAQ</h2>
      <button type="button" class="admin-modal-close" data-close="faqModal" aria-label="닫기">×</button>
    </div>
    <form id="faqForm" class="admin-modal-body"></form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn" data-close="faqModal">취소</button>
      <button type="submit" form="faqForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
<script>
window.LABELUP_FAQ_CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= js('faq-admin.js') ?>"></script>
