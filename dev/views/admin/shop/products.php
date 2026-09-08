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
  <div class="admin-head-actions">
    <button type="button" class="admin-btn admin-btn--primary js-shop-add" data-entity="product">+ 상품 추가</button>
    <button type="button" class="admin-btn js-product-page-settings">페이지설정</button>
  </div>
</div>
<form class="admin-filter-bar" method="get" action="<?= url('admin/shop/products') ?>">
  <input class="admin-input admin-input--search" type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= "\u{C0C1}\u{D488}\u{BA85}, SKU \u{AC80}\u{C0C9}" ?>">
  <select class="admin-select" name="category_id">
    <option value=""><?= "\u{C804}\u{CCB4} \u{CE74}\u{D14C}\u{ACE0}\u{B9AC}" ?></option>
    <?php foreach ($categories as $cat): ?>
    <option value="<?= (int) $cat['id'] ?>"<?= ((int) ($filters['category_id'] ?? 0) === (int) $cat['id']) ? ' selected' : '' ?>><?= e((string) $cat['name']) ?></option>
    <?php endforeach; ?>
  </select>
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
    <thead><tr><th>ID</th><th>대표 이미지</th><th>상품명</th><th>SKU</th><th>카테고리</th><th>규격</th><th>가격</th><th>재고</th><th>상태</th><th>관리</th></tr></thead>
    <tbody>
    <?php if (empty($items)): ?><tr><td colspan="10" class="empty"><?= $hasFilter ? "\u{AC80}\u{C0C9} \u{ACB0}\u{ACFC}\u{AC00} \u{C5C6}\u{C2B5}\u{B2C8}\u{B2E4}." : "\u{B4F1}\u{B85D}\u{B41C} \u{C0C1}\u{D488}\u{C774} \u{C5C6}\u{C2B5}\u{B2C8}\u{B2E4}." ?></td></tr><?php else: ?>
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
        <form class="admin-compat-row js-compat-form" data-id="<?= (int) $row['id'] ?>">
          <label><?= "\u{D3FC}\u{D14D}" ?><textarea name="compat_formtec" rows="2" placeholder="한 줄에 하나"><?= e(\App\Helpers\ShopCompatHelper::toMultiline($row['compat_formtec'] ?? null)) ?></textarea></label>
          <label><?= "\u{C544}\u{C774}\u{B77C}\u{BCA8}" ?><textarea name="compat_ilabel" rows="2" placeholder="한 줄에 하나"><?= e(\App\Helpers\ShopCompatHelper::toMultiline($row['compat_ilabel'] ?? null)) ?></textarea></label>
          <label><?= "\u{C560}\u{B2C8}\u{B77C}\u{BCA8}" ?><textarea name="compat_anylabel" rows="2" placeholder="한 줄에 하나"><?= e(\App\Helpers\ShopCompatHelper::toMultiline($row['compat_anylabel'] ?? null)) ?></textarea></label>
          <button type="submit" class="admin-btn admin-btn--sm"><?= "\u{C800}\u{C7A5}" ?></button>
        </form>
      </td>
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
<script>window.SHOP_PAGE_SETTINGS=<?= json_encode($pageSettings ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<?php require view_path('admin/shop/partials/modal.php'); ?>
<div id="productPageSettingsModal" class="admin-modal" hidden>
  <div class="admin-modal-backdrop js-page-settings-close"></div>
  <div class="admin-modal-dialog admin-modal-dialog--product" role="dialog" aria-modal="true" aria-labelledby="productPageSettingsTitle">
    <div class="admin-modal-head">
      <h3 id="productPageSettingsTitle">상품 상세 페이지설정</h3>
      <button type="button" class="admin-modal-close js-page-settings-close" aria-label="닫기">×</button>
    </div>
    <form id="productPageSettingsForm" class="admin-modal-body admin-product-form">
      <section>
        <h4 class="admin-product-section-title">공통 헤더</h4>
        <div class="admin-product-form-grid">
          <div class="admin-field admin-field--full admin-field--images">
            <label>헤더 이미지</label>
            <div class="admin-category-image-wrap" id="pageHeaderImagePreview"></div>
            <input type="file" id="pageHeaderImageInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
            <div class="admin-category-image-actions">
              <button type="button" class="admin-btn admin-btn--sm" id="pageHeaderImageAdd">+ 이미지 업로드</button>
              <button type="button" class="admin-btn admin-btn--sm" id="pageHeaderImageRemove" disabled>삭제</button>
            </div>
            <input type="hidden" name="header_image" id="pageHeaderImagePath" value="">
          </div>
          <div class="admin-field admin-field--full admin-field--editor">
            <label for="pageHeaderHtml">헤더 내용</label>
            <textarea id="pageHeaderHtml" class="js-page-header-html" rows="8" placeholder="상품 상세 상단에 공통으로 표시할 내용을 입력하세요."></textarea>
          </div>
        </div>
      </section>
      <section>
        <h4 class="admin-product-section-title">공통 푸터</h4>
        <div class="admin-product-form-grid">
          <div class="admin-field admin-field--full admin-field--images">
            <label>푸터 이미지</label>
            <div class="admin-category-image-wrap" id="pageFooterImagePreview"></div>
            <input type="file" id="pageFooterImageInput" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
            <div class="admin-category-image-actions">
              <button type="button" class="admin-btn admin-btn--sm" id="pageFooterImageAdd">+ 이미지 업로드</button>
              <button type="button" class="admin-btn admin-btn--sm" id="pageFooterImageRemove" disabled>삭제</button>
            </div>
            <input type="hidden" name="footer_image" id="pageFooterImagePath" value="">
          </div>
          <div class="admin-field admin-field--full admin-field--editor">
            <label for="pageFooterHtml">푸터 내용</label>
            <textarea id="pageFooterHtml" class="js-page-footer-html" rows="8" placeholder="상품 상세 하단에 공통으로 표시할 내용을 입력하세요."></textarea>
          </div>
        </div>
      </section>
      <p class="admin-muted">저장된 헤더·푸터는 모든 사용자 상품 상세 페이지에 공통 적용됩니다.</p>
    </form>
    <div class="admin-modal-foot">
      <button type="button" class="admin-btn js-page-settings-close">취소</button>
      <button type="submit" form="productPageSettingsForm" class="admin-btn admin-btn--primary">저장</button>
    </div>
  </div>
</div>
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
