<div class="admin-head">
  <div><h1>배너·전시</h1><p>메인 페이지 배너 및 프로모션 전시를 관리합니다.</p></div>
  <div class="admin-head-actions"><button type="button" class="admin-btn admin-btn--primary js-shop-add" data-entity="banner">+ 배너 추가</button></div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>ID</th><th>제목</th><th>부제</th><th>링크</th><th>정렬</th><th>상태</th><th>관리</th></tr></thead>
    <tbody>
    <?php if (empty($items)): ?><tr><td colspan="7" class="empty">등록된 배너가 없습니다.</td></tr><?php else: ?>
    <?php foreach ($items as $row): ?>
    <tr>
      <td><?= (int) $row['id'] ?></td>
      <td><?= e($row['title']) ?></td>
      <td><?= e($row['subtitle'] ?? '-') ?></td>
      <td class="admin-cell-wrap"><code><?= e($row['link_url'] ?? '-') ?></code></td>
      <td><?= (int) $row['sort_order'] ?></td>
      <td><?= ($row['is_active'] ?? false) ? '<span class="admin-badge admin-badge--ok">노출</span>' : '<span class="admin-badge admin-badge--err">숨김</span>' ?></td>
      <td>
        <button type="button" class="admin-btn admin-btn--sm js-shop-edit" data-entity="banner" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
        <button type="button" class="admin-btn admin-btn--sm js-shop-delete" data-entity="banner" data-id="<?= (int) $row['id'] ?>">삭제</button>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require view_path('admin/shop/partials/modal.php'); ?>
<script src="<?= js('shop-admin.js') ?>"></script>
