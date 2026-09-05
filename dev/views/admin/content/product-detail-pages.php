<?php
use App\Services\ShopAdminService;

$list = $list ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'summary' => [], 'filters' => []];
$items = $list['items'] ?? [];
$f = $list['filters'] ?? [];
$s = $list['summary'] ?? [];
$categories = $categories ?? [];
$statuses = ['active', 'soldout', 'hidden', 'draft'];
$hasFilter = ($f['q'] ?? '') !== ''
    || !empty($f['category_id'])
    || ($f['registered'] ?? '') !== ''
    || ($f['product_status'] ?? '') !== '';
$queryParams = array_filter([
    'q' => (string) ($f['q'] ?? ''),
    'registered' => (string) ($f['registered'] ?? ''),
    'category_id' => !empty($f['category_id']) ? (string) (int) $f['category_id'] : '',
    'product_status' => (string) ($f['product_status'] ?? ''),
], static fn (string $v): bool => $v !== '');
?>
<div class="admin-head">
  <div>
    <h1>상세페이지관리</h1>
    <p>등록된 상품의 상세페이지 생성 여부를 확인하고, 이후 일괄 생성할 수 있는 화면입니다.</p>
  </div>
</div>

<form class="admin-filter-bar" method="get" action="<?= url('admin/content/product-detail-pages') ?>">
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e((string) ($f['q'] ?? '')) ?>" placeholder="상품명, 상품코드 검색">
  <select class="admin-select" name="registered">
    <option value="">상세페이지 전체</option>
    <option value="yes"<?= (($f['registered'] ?? '') === 'yes') ? ' selected' : '' ?>>등록</option>
    <option value="no"<?= (($f['registered'] ?? '') === 'no') ? ' selected' : '' ?>>미등록</option>
  </select>
  <select class="admin-select" name="category_id">
    <option value="">전체 카테고리</option>
    <?php foreach ($categories as $cat): ?>
    <option value="<?= (int) $cat['id'] ?>"<?= ((int) ($f['category_id'] ?? 0) === (int) $cat['id']) ? ' selected' : '' ?>><?= e((string) $cat['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="admin-select" name="product_status">
    <option value="">상품상태 전체</option>
    <?php foreach ($statuses as $code): ?>
    <option value="<?= e($code) ?>"<?= (($f['product_status'] ?? '') === $code) ? ' selected' : '' ?>><?= e(ShopAdminService::productStatusLabel($code)) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="admin-btn admin-btn--primary" type="submit">검색</button>
  <?php if ($hasFilter): ?>
  <a class="admin-btn" href="<?= url('admin/content/product-detail-pages') ?>">초기화</a>
  <?php endif; ?>
</form>

<div class="admin-kpis admin-kpis--sub">
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">대상 상품</div><div class="val"><?= number_format((int) ($s['total'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">상세페이지 등록</div><div class="val"><?= number_format((int) ($s['registered'] ?? 0)) ?></div></div>
  <div class="admin-kpi admin-kpi--sm"><div class="lbl">미등록</div><div class="val"><?= number_format((int) ($s['unregistered'] ?? 0)) ?></div></div>
</div>
<p class="admin-meta-line">총 <b><?= number_format((int) ($list['total'] ?? 0)) ?></b>개<?php if (($list['pages'] ?? 1) > 1): ?> · <?= (int) ($list['page'] ?? 1) ?> / <?= (int) $list['pages'] ?> 페이지<?php endif; ?> · 생성 방식은 이후 별도로 적용됩니다.</p>

<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>상세페이지</th>
        <th>상품명</th>
        <th>상품코드</th>
        <th>카테고리</th>
        <th>상품상태</th>
      </tr>
    </thead>
    <tbody>
    <?php if ($items === []): ?>
      <tr><td colspan="5" class="empty"><?= $hasFilter ? '검색 결과가 없습니다.' : '등록된 상품이 없습니다.' ?></td></tr>
    <?php else: ?>
      <?php foreach ($items as $row): ?>
      <?php $registered = !empty($row['has_detail_page']); ?>
      <tr>
        <td>
          <?php if ($registered): ?>
          <span class="admin-badge admin-badge--ok">등록</span>
          <?php else: ?>
          <span class="admin-badge admin-badge--pending">미등록</span>
          <?php endif; ?>
        </td>
        <td><strong><?= e((string) ($row['name'] ?? '')) ?></strong></td>
        <td><code><?= e((string) ($row['sku'] ?? '')) ?></code></td>
        <td><?= e((string) ($row['category_name'] ?? '-')) ?></td>
        <td><?= e(ShopAdminService::productStatusLabel((string) ($row['product_status'] ?? ''))) ?></td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php if (($list['pages'] ?? 1) > 1): ?>
<?php
  $page = (int) ($list['page'] ?? 1);
  $pages = (int) ($list['pages'] ?? 1);
  $basePath = 'admin/content/product-detail-pages';
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>
