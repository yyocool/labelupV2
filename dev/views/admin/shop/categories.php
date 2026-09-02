<?php use App\Services\ShopAdminService; use App\Services\ShopProductImageService; ?>
<div class="admin-head">
  <div>
    <h1>카테고리 관리</h1>
    <p>쇼핑몰 상품 분류 카테고리를 관리합니다.</p>
  </div>
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary js-shop-add" data-entity="category">+ 카테고리 추가</button>
  </div>
</div>
<div id="adminAlert" class="admin-alert"></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>ID</th><th>이미지</th><th>이름</th><th>슬러그</th><th>정렬</th><th>상태</th><th>관리</th></tr></thead>
    <tbody>
    <?php if (empty($items)): ?><tr><td colspan="7" class="empty">등록된 카테고리가 없습니다.</td></tr><?php else: ?>
    <?php foreach ($items as $row): ?>
    <tr>
      <td><?= (int) $row['id'] ?></td>
      <td>
        <?php if (!empty($row['image_path'])): ?>
        <button type="button" class="admin-thumb-btn js-image-preview" data-src="<?= e(ShopProductImageService::resolveUrl((string) $row['image_path'])) ?>" data-title="<?= e($row['name']) ?>">
          <img class="admin-thumb admin-thumb--square" src="<?= e(ShopProductImageService::resolveUrl((string) $row['image_path'])) ?>" alt="<?= e($row['name']) ?>">
        </button>
        <?php else: ?><span class="admin-muted">-</span><?php endif; ?>
      </td>
      <td><?= e($row['name']) ?></td>
      <td><code><?= e($row['slug']) ?></code></td>
      <td><?= (int) $row['sort_order'] ?></td>
      <td><?= ($row['is_active'] ?? false) ? '<span class="admin-badge admin-badge--ok">사용</span>' : '<span class="admin-badge admin-badge--err">중지</span>' ?></td>
      <td>
        <button type="button" class="admin-btn admin-btn--sm js-shop-edit" data-entity="category" data-row='<?= e(json_encode($row, JSON_UNESCAPED_UNICODE)) ?>'>수정</button>
        <button type="button" class="admin-btn admin-btn--sm js-shop-delete" data-entity="category" data-id="<?= (int) $row['id'] ?>">삭제</button>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>
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
