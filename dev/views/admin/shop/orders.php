<?php
use App\Services\ShopAdminService;
$items = $list['items'] ?? [];
$total = (int) ($list['total'] ?? 0);
$page = (int) ($list['page'] ?? 1);
$pages = (int) ($list['pages'] ?? 1);
$filters = $filters ?? ['q' => '', 'status' => '', 'payment_status' => '', 'date_from' => '', 'date_to' => '', 'missing_tracking' => false];
$counts = $counts ?? [];
$carriers = $carriers ?? ShopAdminService::carriers();
$tabs = [
    '' => ["\u{C804}\u{CCB4}", (int) ($counts['all'] ?? $total)],
    'pending' => [ShopAdminService::orderStatusLabel('pending'), (int) ($counts['pending'] ?? 0)],
    'paid' => [ShopAdminService::orderStatusLabel('paid'), (int) ($counts['paid'] ?? 0)],
    'preparing' => [ShopAdminService::orderStatusLabel('preparing'), (int) ($counts['preparing'] ?? 0)],
    'shipping' => [ShopAdminService::orderStatusLabel('shipping'), (int) ($counts['shipping'] ?? 0)],
    'delivered' => [ShopAdminService::orderStatusLabel('delivered'), (int) ($counts['delivered'] ?? 0)],
    'cancel_group' => ["\u{CDE8}\u{C18C}/\u{D658}\u{BD88}", (int) ($counts['cancelled'] ?? 0) + (int) ($counts['refunded'] ?? 0)],
];
$hasFilter = ($filters['q'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['payment_status'] ?? '') !== ''
    || ($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '' || !empty($filters['missing_tracking']);
$queryBase = [
    'q' => $filters['q'] ?? '',
    'payment_status' => $filters['payment_status'] ?? '',
    'date_from' => $filters['date_from'] ?? '',
    'date_to' => $filters['date_to'] ?? '',
    'missing_tracking' => !empty($filters['missing_tracking']) ? '1' : '',
];
$tabUrl = static function (string $status) use ($queryBase): string {
    $q = array_filter($queryBase, static fn ($v) => $v !== '' && $v !== null);
    if ($status !== '') {
        $q['status'] = $status;
    }
    return url('admin/shop/orders' . ($q ? ('?' . http_build_query($q)) : ''));
};
$exportUrl = url('admin/shop/orders/export?' . http_build_query(array_filter($queryBase + ['status' => $filters['status'] ?? ''], static fn ($v) => $v !== '' && $v !== null)));
?>
<div class="admin-head">
  <div>
    <h1><?= "\u{C8FC}\u{BB38} \u{AD00}\u{B9AC}" ?></h1>
    <p><?= "\u{ACB0}\u{C81C}\u{C811}\u{C218}\u{BD80}\u{D130} \u{BC30}\u{C1A1}\u{C644}\u{B8CC}\u{AE4C}\u{C9C0} \u{C0C1}\u{D0DC}\u{BCC4}\u{B85C} \u{CC98}\u{B9AC}\u{D569}\u{B2C8}\u{B2E4}." ?></p>
  </div>
  <div class="admin-head-actions">
    <a class="admin-btn" href="<?= e($exportUrl) ?>"><?= "\u{C8FC}\u{BB38} \u{B0B4}\u{BCF4}\u{B0B4}\u{AE30} CSV" ?></a>
  </div>
</div>

<nav class="admin-order-tabs" aria-label="<?= "\u{C8FC}\u{BB38} \u{C0C1}\u{D0DC}" ?>">
  <?php foreach ($tabs as $key => [$label, $cnt]): ?>
  <a class="admin-order-tab<?= (($filters['status'] ?? '') === $key) ? ' is-active' : '' ?>" href="<?= e($tabUrl((string) $key)) ?>">
    <?= e($label) ?> <b><?= number_format($cnt) ?></b>
  </a>
  <?php endforeach; ?>
</nav>

<form class="admin-filter-bar" method="get" action="<?= url('admin/shop/orders') ?>">
  <input type="hidden" name="status" value="<?= e((string) ($filters['status'] ?? '')) ?>">
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e((string) ($filters['q'] ?? '')) ?>" placeholder="<?= "\u{C8FC}\u{BB38}\u{BC88}\u{D638}, \u{AD6C}\u{B9E4}\u{C790}, \u{C5F0}\u{B77D}\u{CC98}, \u{C0C1}\u{D488}\u{BA85}, \u{C1A1}\u{C7A5}" ?>">
  <input class="admin-input" type="date" name="date_from" value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
  <input class="admin-input" type="date" name="date_to" value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
  <select class="admin-select" name="payment_status">
    <option value=""><?= "\u{C804}\u{CCB4} \u{ACB0}\u{C81C}" ?></option>
    <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $pay): ?>
    <option value="<?= e($pay) ?>"<?= (($filters['payment_status'] ?? '') === $pay) ? ' selected' : '' ?>><?= e(ShopAdminService::paymentStatusLabel($pay)) ?></option>
    <?php endforeach; ?>
  </select>
  <label class="admin-check">
    <input type="checkbox" name="missing_tracking" value="1"<?= !empty($filters['missing_tracking']) ? ' checked' : '' ?>>
    <?= "\u{C1A1}\u{C7A5} \u{BBF8}\u{B4F1}\u{B85D}" ?>
  </label>
  <button class="admin-btn admin-btn--primary" type="submit"><?= "\u{AC80}\u{C0C9}" ?></button>
  <?php if ($hasFilter): ?><a class="admin-btn" href="<?= url('admin/shop/orders') ?>"><?= "\u{CD08}\u{AE30}\u{D654}" ?></a><?php endif; ?>
</form>

<div class="admin-order-bulk">
  <select class="admin-select" id="bulkCarrier">
    <option value=""><?= "\u{C77C}\u{AD04} \u{D0DD}\u{BC30}\u{C0AC}" ?></option>
    <?php foreach ($carriers as $carrier): ?>
    <option value="<?= e($carrier) ?>"><?= e($carrier) ?></option>
    <?php endforeach; ?>
  </select>
  <input class="admin-input" id="bulkTracking" type="text" placeholder="<?= "\u{C77C}\u{AD04} \u{C1A1}\u{C7A5}\u{BC88}\u{D638}" ?>">
  <button type="button" class="admin-btn js-order-bulk" data-status="preparing"><?= "\u{C0C1}\u{D488}\u{C900}\u{BE44}" ?></button>
  <button type="button" class="admin-btn js-order-bulk" data-status="shipping"><?= "\u{BC30}\u{C1A1}\u{C911}(\u{C1A1}\u{C7A5})" ?></button>
  <button type="button" class="admin-btn js-order-bulk" data-status="delivered"><?= "\u{BC30}\u{C1A1}\u{C644}\u{B8CC}" ?></button>
  <button type="button" class="admin-btn js-order-bulk" data-status="cancelled"><?= "\u{C8FC}\u{BB38}\u{CDE8}\u{C18C}" ?></button>
</div>

<p class="admin-meta-line"><?= "\u{CD1D}" ?> <b><?= number_format($total) ?></b><?= "\u{AC74}" ?><?php if ($pages > 1): ?> · <?= $page ?> / <?= $pages ?> <?= "\u{D398}\u{C774}\u{C9C0}" ?><?php endif; ?></p>
<div id="adminAlert" class="admin-alert"></div>

<div class="admin-table-wrap">
  <table class="admin-table admin-order-table" id="orderTable">
    <thead>
      <tr>
        <th><input type="checkbox" id="orderSelectAll" aria-label="all"></th>
        <th><?= "\u{C8FC}\u{BB38}\u{C815}\u{BCF4}" ?></th>
        <th><?= "\u{C0C1}\u{D488}" ?></th>
        <th><?= "\u{AD6C}\u{B9E4}\u{C790}" ?></th>
        <th><?= "\u{ACB0}\u{C81C}" ?></th>
        <th><?= "\u{C0C1}\u{D0DC}" ?></th>
        <th><?= "\u{BC30}\u{C1A1}/\u{C1A1}\u{C7A5}" ?></th>
        <th><?= "\u{CC98}\u{B9AC}" ?></th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($items)): ?>
      <tr><td colspan="8" class="empty"><?= "\u{C8FC}\u{BB38}\u{C774} \u{C5C6}\u{C2B5}\u{B2C8}\u{B2E4}." ?></td></tr>
    <?php else: ?>
    <?php foreach ($items as $row): ?>
      <?php
        $orderItems = $row['items'] ?? [];
        $firstItem = $orderItems[0] ?? null;
        $more = max(0, count($orderItems) - 1);
      ?>
      <tr data-order-row="<?= (int) $row['id'] ?>">
        <td><input type="checkbox" class="js-order-check" value="<?= (int) $row['id'] ?>"></td>
        <td>
          <code><?= e((string) $row['order_no']) ?></code>
          <div class="admin-muted"><?= e(substr((string) ($row['created_at'] ?? ''), 0, 16)) ?></div>
        </td>
        <td>
          <?php if ($firstItem): ?>
            <?= e((string) $firstItem['product_name']) ?>
            <small class="admin-muted">x<?= (int) ($firstItem['qty'] ?? 0) ?><?php if ($more): ?> <?= "\u{C678}" ?> <?= $more ?><?= "\u{AC74}" ?><?php endif; ?></small>
          <?php else: ?>-<?php endif; ?>
        </td>
        <td>
          <?= e((string) $row['customer_name']) ?>
          <div class="admin-muted"><?= e((string) ($row['customer_phone'] ?? '')) ?></div>
        </td>
        <td>
          <strong><?= number_format((int) $row['total_amount']) ?><?= "\u{C6D0}" ?></strong>
          <div class="admin-muted"><?= e(ShopAdminService::paymentStatusLabel((string) ($row['payment_status'] ?? ''))) ?></div>
        </td>
        <td><span class="admin-badge admin-badge--<?= e((string) $row['status']) ?>"><?= e(ShopAdminService::orderStatusLabel((string) $row['status'])) ?></span></td>
        <td>
          <form class="admin-ship-row js-order-ship" data-id="<?= (int) $row['id'] ?>" data-memo="<?= e((string) ($row['admin_memo'] ?? '')) ?>">
            <select name="carrier" class="admin-select">
              <option value=""><?= "\u{D0DD}\u{BC30}\u{C0AC}" ?></option>
              <?php foreach ($carriers as $carrier): ?>
              <option value="<?= e($carrier) ?>"<?= (($row['carrier'] ?? '') === $carrier) ? ' selected' : '' ?>><?= e($carrier) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" name="tracking_no" value="<?= e((string) ($row['tracking_no'] ?? '')) ?>" placeholder="<?= "\u{C1A1}\u{C7A5}\u{BC88}\u{D638}" ?>">
            <button type="submit" class="admin-btn admin-btn--sm"><?= "\u{BC1C}\u{C1A1}" ?></button>
          </form>
        </td>
        <td>
          <button type="button" class="admin-btn admin-btn--sm js-shop-edit" data-entity="order" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'><?= "\u{C0C1}\u{C138}" ?></button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php if ($pages > 1): ?>
<?php
  $basePath = 'admin/shop/orders';
  $queryParams = [
    'q' => $filters['q'] ?? '',
    'status' => $filters['status'] ?? '',
    'payment_status' => $filters['payment_status'] ?? '',
    'date_from' => $filters['date_from'] ?? '',
    'date_to' => $filters['date_to'] ?? '',
    'missing_tracking' => !empty($filters['missing_tracking']) ? '1' : '',
  ];
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>
<script>window.SHOP_ORDER_META=<?= json_encode(['carriers' => $carriers], JSON_UNESCAPED_UNICODE) ?>;</script>
<?php require view_path('admin/shop/partials/modal.php'); ?>
<script src="<?= js('shop-admin.js') ?>"></script>
