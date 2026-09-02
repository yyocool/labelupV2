<?php
use App\Services\ShopAdminService;
$items = $list['items'] ?? [];
$total = (int) ($list['total'] ?? 0);
$page = (int) ($list['page'] ?? 1);
$pages = (int) ($list['pages'] ?? 1);
$statuses = ['', 'pending', 'paid', 'preparing', 'shipping', 'delivered', 'cancelled', 'refunded'];
?>
<div class="admin-head">
  <div><h1>주문 관리</h1><p>총 <?= number_format($total) ?>건 · 주문 상태 및 결제를 관리합니다.</p></div>
</div>
<form class="admin-toolbar" method="get" action="<?= url('admin/shop/orders') ?>">
  <select class="admin-select" name="status">
    <option value="">전체 상태</option>
    <?php foreach ($statuses as $s): if ($s === '') continue; ?>
    <option value="<?= e($s) ?>"<?= ($status ?? '') === $s ? ' selected' : '' ?>><?= e(ShopAdminService::orderStatusLabel($s)) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="admin-btn admin-btn--primary" type="submit">필터</button>
  <?php if (!empty($status)): ?><a class="admin-btn" href="<?= url('admin/shop/orders') ?>">초기화</a><?php endif; ?>
</form>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>주문번호</th><th>주문자</th><th>연락처</th><th>금액</th><th>주문상태</th><th>결제</th><th>주문일</th><th>관리</th></tr></thead>
    <tbody>
    <?php if (empty($items)): ?><tr><td colspan="8" class="empty">주문이 없습니다.</td></tr><?php else: ?>
    <?php foreach ($items as $row): ?>
    <tr data-order-row="<?= (int) $row['id'] ?>">
      <td><code><?= e($row['order_no']) ?></code></td>
      <td><?= e($row['customer_name']) ?><br><small class="admin-muted"><?= e($row['customer_email']) ?></small></td>
      <td><?= e($row['customer_phone'] ?? '-') ?></td>
      <td><?= number_format((int) $row['total_amount']) ?>원</td>
      <td><span class="admin-badge"><?= e(ShopAdminService::orderStatusLabel((string) $row['status'])) ?></span></td>
      <td><?= e($row['payment_status']) ?></td>
      <td><?= e(substr((string) ($row['created_at'] ?? ''), 0, 16)) ?></td>
      <td><button type="button" class="admin-btn admin-btn--sm js-shop-edit" data-entity="order" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>상세/수정</button></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($pages > 1): ?>
<?php
  $basePath = 'admin/shop/orders';
  $queryParams = ['status' => $status ?? ''];
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>
<?php require view_path('admin/shop/partials/modal.php'); ?>
<script src="<?= js('shop-admin.js') ?>"></script>
