<div class="admin-head">
  <div><h1>쿠폰·프로모션</h1><p>할인 쿠폰 코드와 프로모션을 관리합니다.</p></div>
  <div class="admin-head-actions"><button type="button" class="admin-btn admin-btn--primary js-shop-add" data-entity="coupon">+ 쿠폰 추가</button></div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>코드</th><th>이름</th><th>할인</th><th>최소주문</th><th>사용</th><th>기간</th><th>상태</th><th>관리</th></tr></thead>
    <tbody>
    <?php if (empty($items)): ?><tr><td colspan="8" class="empty">등록된 쿠폰이 없습니다.</td></tr><?php else: ?>
    <?php foreach ($items as $row): ?>
    <tr>
      <td><code><?= e($row['code']) ?></code></td>
      <td><?= e($row['name']) ?></td>
      <td><?= ($row['discount_type'] ?? '') === 'percent' ? (int) $row['discount_value'] . '%' : number_format((int) $row['discount_value']) . '원' ?></td>
      <td><?= number_format((int) $row['min_order_amount']) ?>원</td>
      <td><?= (int) $row['used_count'] ?><?php if (!empty($row['max_uses'])): ?> / <?= (int) $row['max_uses'] ?><?php endif; ?></td>
      <td><small><?= e(substr((string) ($row['starts_at'] ?? ''), 0, 10)) ?> ~ <?= e(substr((string) ($row['ends_at'] ?? ''), 0, 10)) ?></small></td>
      <td><?= ($row['is_active'] ?? false) ? '<span class="admin-badge admin-badge--ok">사용</span>' : '<span class="admin-badge admin-badge--err">중지</span>' ?></td>
      <td>
        <button type="button" class="admin-btn admin-btn--sm js-shop-edit" data-entity="coupon" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
        <button type="button" class="admin-btn admin-btn--sm js-shop-delete" data-entity="coupon" data-id="<?= (int) $row['id'] ?>">삭제</button>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require view_path('admin/shop/partials/modal.php'); ?>
<script src="<?= js('shop-admin.js') ?>"></script>
