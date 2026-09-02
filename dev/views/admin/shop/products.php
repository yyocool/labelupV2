<?php use App\Services\ShopAdminService; use App\Services\ShopProductImageService; ?>
<div class="admin-head">
  <div><h1>상품 관리</h1><p>라벨지·소모품 등 쇼핑몰 상품을 관리합니다.</p></div>
  <div class="admin-head-actions"><button type="button" class="admin-btn admin-btn--primary js-shop-add" data-entity="product">+ 상품 추가</button></div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>ID</th><th>대표 이미지</th><th>상품명</th><th>SKU</th><th>카테고리</th><th>규격</th><th>가격</th><th>재고</th><th>상태</th><th>관리</th></tr></thead>
    <tbody>
    <?php if (empty($items)): ?><tr><td colspan="10" class="empty">등록된 상품이 없습니다.</td></tr><?php else: ?>
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
      <td><?= e($row['name']) ?></td>
      <td><code><?= e($row['sku']) ?></code></td>
      <td><?= e($row['category_name'] ?? '-') ?></td>
      <td><?= e($row['spec_name'] ?? '-') ?></td>
      <td><?= number_format((int) $row['price']) ?>원<?php if (!empty($row['sale_price'])): ?> <small class="admin-muted">→ <?= number_format((int) $row['sale_price']) ?>원</small><?php endif; ?></td>
      <td><?= number_format((int) $row['stock_qty']) ?></td>
      <td><?= e(ShopAdminService::productStatusLabel((string) ($row['status'] ?? ''))) ?></td>
      <td>
        <button type="button" class="admin-btn admin-btn--sm js-shop-edit" data-entity="product" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
        <button type="button" class="admin-btn admin-btn--sm js-shop-delete" data-entity="product" data-id="<?= (int) $row['id'] ?>">삭제</button>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
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
