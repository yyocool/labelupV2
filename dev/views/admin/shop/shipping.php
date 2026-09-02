<?php use App\Services\ShopAdminService; ?>
<div class="admin-head">
  <div><h1>배송 관리</h1><p>결제완료·준비·배송중 주문의 택배사·송장을 관리합니다.</p></div>
  <div class="admin-head-actions"><a class="admin-btn" href="<?= url('admin/shop/orders') ?>">전체 주문 보기</a></div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>주문번호</th><th>수령인</th><th>배송지</th><th>상태</th><th>택배사</th><th>송장번호</th><th>관리</th></tr></thead>
    <tbody>
    <?php if (empty($items)): ?><tr><td colspan="7" class="empty">배송 처리 대상 주문이 없습니다.</td></tr><?php else: ?>
    <?php foreach ($items as $row): ?>
    <tr>
      <td><code><?= e($row['order_no']) ?></code></td>
      <td><?= e($row['shipping_name'] ?? $row['customer_name']) ?><br><small class="admin-muted"><?= e($row['shipping_phone'] ?? '') ?></small></td>
      <td class="admin-cell-wrap"><?= e($row['shipping_address'] ?? '-') ?></td>
      <td><?= e(ShopAdminService::orderStatusLabel((string) $row['status'])) ?></td>
      <td><?= e($row['carrier'] ?? '-') ?></td>
      <td><?= e($row['tracking_no'] ?? '-') ?></td>
      <td><button type="button" class="admin-btn admin-btn--sm js-shop-edit" data-entity="order" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>배송처리</button></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php require view_path('admin/shop/partials/modal.php'); ?>
<script src="<?= js('shop-admin.js') ?>"></script>
