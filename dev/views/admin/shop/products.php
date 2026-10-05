<?php
use App\Services\ShopAdminService;
use App\Services\ShopProductImageService;
$list = $list ?? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
$items = $list['items'] ?? ($items ?? []);
$filters = $filters ?? ['q' => '', 'category_id' => 0, 'spec_id' => 0, 'status' => ''];
$categories = $categories ?? [];
$specs = $specs ?? [];
$hasFilter = ($filters['q'] ?? '') !== '' || !empty($filters['category_id']) || !empty($filters['spec_id']) || ($filters['status'] ?? '') !== '' || !empty($filters['compat_missing']);
$statuses = ['active', 'soldout', 'hidden', 'draft'];
?>
<div class="admin-head">
  <div><h1>상품 관리</h1><p>라벨지·소모품 등 쇼핑몰 상품을 관리합니다.</p></div>
  <div class="admin-head-actions"><button type="button" class="admin-btn admin-btn--primary js-shop-add" data-entity="product">+ 상품 추가</button></div>
</div>
<form class="admin-filter-bar" method="get" action="<?= url('admin/shop/products') ?>">
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= "\u{C0C1}\u{D488}\u{BA85}, SKU \u{AC80}\u{C0C9}" ?>">
  <?php
    $selectedId = (int) ($filters['category_id'] ?? 0);
    require view_path('admin/partials/category-filter.php');
  ?>
  <select class="admin-select" name="spec_id">
    <option value=""><?= "\u{C804}\u{CCB4} \u{ADC0}\u{ACA9}" ?></option>
    <?php foreach ($specs as $spec): ?>
    <option value="<?= (int) $spec['id'] ?>"<?= ((int) ($filters['spec_id'] ?? 0) === (int) $spec['id']) ? ' selected' : '' ?>><?= e((string) $spec['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="admin-select" name="status">
    <option value=""><?= "\u{C804}\u{CCB4} \u{C0C1}\u{D0DC}" ?></option>
    <?php foreach ($statuses as $code): ?>
    <option value="<?= e($code) ?>"<?= (($filters['status'] ?? '') === $code) ? ' selected' : '' ?>><?= e(ShopAdminService::productStatusLabel($code)) ?></option>
    <?php endforeach; ?>
  </select>
  <label class="admin-check">
    <input type="checkbox" name="compat" value="missing"<?= !empty($filters['compat_missing']) ? ' checked' : '' ?>>
    <?= "\u{D638}\u{D658}\u{CF54}\u{B4DC} \u{BBF8}\u{B4F1}\u{B85D}" ?>
  </label>
  <button class="admin-btn admin-btn--primary" type="submit"><?= "\u{AC80}\u{C0C9}" ?></button>
  <?php if ($hasFilter): ?><a class="admin-btn" href="<?= url('admin/shop/products') ?>"><?= "\u{CD08}\u{AE30}\u{D654}" ?></a><?php endif; ?>
</form>
<p class="admin-meta-line"><?= "\u{CD1D}" ?> <b><?= number_format((int) ($list['total'] ?? count($items))) ?></b><?= "\u{AC1C}" ?><?php if (($list['pages'] ?? 1) > 1): ?> · <?= (int) ($list['page'] ?? 1) ?> / <?= (int) $list['pages'] ?> <?= "\u{D398}\u{C774}\u{C9C0}" ?><?php endif; ?></p>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>ID</th><th>대표 이미지</th><th>상품명</th><th>SKU</th><th>카테고리</th><th>규격</th><th>가격</th><th>옵션</th><th>재고</th><th>상태</th><th>관리</th></tr></thead>
    <tbody>
    <?php if (empty($items)): ?><tr><td colspan="11" class="empty"><?= $hasFilter ? "\u{AC80}\u{C0C9} \u{ACB0}\u{ACFC}\u{AC00} \u{C5C6}\u{C2B5}\u{B2C8}\u{B2E4}." : "\u{B4F1}\u{B85D}\u{B41C} \u{C0C1}\u{D488}\u{C774} \u{C5C6}\u{C2B5}\u{B2C8}\u{B2E4}." ?></td></tr><?php else: ?>
    <?php foreach ($items as $row): ?>
    <tr>
      <td><?= (int) $row['id'] ?></td>
      <td>
        <?php if (!empty($row['thumbnail'])): ?>
        <button type="button" class="admin-thumb-btn js-image-preview" data-src="<?= e(ShopProductImageService::resolveUrl((string) $row['thumbnail'])) ?>" data-title="<?= e($row['name']) ?>">
          <img class="admin-thumb" src="<?= e(ShopProductImageService::resolveUrl((string) $row['thumbnail'])) ?>" alt="<?= e($row['name']) ?>">
        </button>
        <?php else: ?><span class="admin-muted">-</span><?php endif; ?>
      </td>
      <td>
        <strong><?= e($row['name']) ?></strong>
        <?php if ((int) ($row['ink_amount'] ?? 0) <= 0): ?>
        <form class="admin-compat-row js-compat-form" data-id="<?= (int) $row['id'] ?>">
          <label><?= "\u{D3FC}\u{D14D}" ?><textarea name="compat_formtec" rows="2"><?= e(\App\Helpers\ShopCompatHelper::toMultiline($row['compat_formtec'] ?? null)) ?></textarea></label>
          <label><?= "\u{C544}\u{C774}\u{B77C}\u{BCA8}" ?><textarea name="compat_ilabel" rows="2"><?= e(\App\Helpers\ShopCompatHelper::toMultiline($row['compat_ilabel'] ?? null)) ?></textarea></label>
          <label><?= "\u{C560}\u{B2C8}\u{B77C}\u{BCA8}" ?><textarea name="compat_anylabel" rows="2"><?= e(\App\Helpers\ShopCompatHelper::toMultiline($row['compat_anylabel'] ?? null)) ?></textarea></label>
          <button type="submit" class="admin-btn admin-btn--sm"><?= "\u{C800}\u{C7A5}" ?></button>
        </form>
        <?php endif; ?>
      </td>
      <td><code><?= e($row['sku']) ?></code></td>
      <td><?php
        $parentCat = trim((string) ($row['parent_category_name'] ?? ''));
        $catName = trim((string) ($row['category_name'] ?? ''));
        echo e($parentCat !== '' && $catName !== '' ? $parentCat . ' / ' . $catName : ($catName !== '' ? $catName : '-'));
      ?></td>
      <td><?php if ((int) ($row['ink_amount'] ?? 0) > 0): ?><?= number_format((int) $row['ink_amount']) ?> 잉크<?php else: ?><?= e($row['spec_name'] ?? '-') ?><?php endif; ?></td>
      <td><?= number_format((int) $row['price']) ?>원<?php if (!empty($row['sale_price'])): ?> <small class="admin-muted">→ <?= number_format((int) $row['sale_price']) ?>원</small><?php endif; ?></td>
      <td>
        <?php
          $rowOptions = $row['options'] ?? [];
          $activeOptions = array_filter($rowOptions, static fn (array $o): bool => !empty($o['is_active']));
        ?>
        <?php if ($rowOptions === []): ?>
        <span class="admin-muted">-</span>
        <?php else: ?>
        <?= count($activeOptions) ?>개<?php if (count($rowOptions) > count($activeOptions)): ?><small class="admin-muted"> / 전체 <?= count($rowOptions) ?></small><?php endif; ?>
        <?php endif; ?>
      </td>
      <td><?php if ((int) ($row['ink_amount'] ?? 0) > 0): ?><span class="admin-muted">디지털</span><?php else: ?><?= number_format((int) $row['stock_qty']) ?><?php endif; ?></td>
      <td><?= e(ShopAdminService::productStatusLabel((string) ($row['status'] ?? ''))) ?></td>
      <td>
        <?php
          $productId = (int) $row['id'];
          $productStatus = (string) ($row['status'] ?? '');
          // 사용자 페이지는 판매중·품절 상품이면서 카테고리까지 활성일 때만 열린다.
          // 조건이 하나라도 어긋나면 404 이므로 관리자 미리보기로 보낸다.
          $statusOpen = in_array($productStatus, ['active', 'soldout'], true);
          $categoryOpen = !empty($row['category_is_active']);
          $blockReason = !$statusOpen
            ? ShopAdminService::productStatusLabel($productStatus) . ' 상태라'
            : (!$categoryOpen ? '카테고리가 비활성이라' : '');
        ?>
        <div class="admin-table-actions">
          <button type="button" class="admin-btn admin-btn--sm js-shop-edit" data-entity="product" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
          <?php if ($blockReason === ''): ?>
          <a class="admin-btn admin-btn--sm" href="<?= url('shop/products/' . $productId) ?>" target="_blank" rel="noopener">상품페이지보기</a>
          <?php else: ?>
          <a class="admin-btn admin-btn--sm" href="<?= url('admin/content/product-detail-pages/preview/' . $productId) ?>" target="_blank" rel="noopener"
             title="<?= e($blockReason) ?> 사용자 페이지에서는 열리지 않습니다. 관리자 미리보기로 엽니다.">미리보기</a>
          <?php endif; ?>
          <button type="button" class="admin-btn admin-btn--sm js-shop-delete" data-entity="product" data-id="<?= $productId ?>">삭제</button>
        </div>
      </td>
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
  $basePath = 'admin/shop/products';
  $queryParams = [
    'q' => $filters['q'] ?? '',
    'category_id' => !empty($filters['category_id']) ? (int) $filters['category_id'] : '',
    'spec_id' => !empty($filters['spec_id']) ? (int) $filters['spec_id'] : '',
    'status' => $filters['status'] ?? '',
    'compat' => !empty($filters['compat_missing']) ? 'missing' : '',
  ];
  require view_path('admin/partials/pagination.php');
?>
<?php endif; ?>
<script>window.SHOP_META=<?= json_encode(['categories'=>$categories??[],'specs'=>$specs??[]], JSON_UNESCAPED_UNICODE) ?>;</script>
<?php require view_path('admin/shop/partials/modal.php'); ?>
<div id="adminLightbox" class="admin-lightbox" hidden>
  <div class="admin-lightbox-backdrop js-lightbox-close"></div>
  <div class="admin-lightbox-panel" role="dialog" aria-modal="true" aria-labelledby="adminLightboxTitle">
    <div class="admin-lightbox-head">
      <strong id="adminLightboxTitle">이미지 미리보기</strong>
      <button type="button" class="admin-lightbox-close js-lightbox-close" aria-label="닫기">×</button>
    </div>
    <div class="admin-lightbox-body">
      <img id="adminLightboxImg" src="" alt="">
    </div>
  </div>
</div>
<script src="<?= js('shop-admin.js') ?>"></script>
