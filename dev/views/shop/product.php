<?php
/** @var App\Services\ShopService $shopService */
/** @var array<string, mixed> $product */
/** @var array<int, array<string, mixed>> $related */
$unit = $shopService->unitPrice($product);
$thumb = $shopService->productThumb($product);
$isSoldout = ($product['status'] ?? '') === 'soldout' || (int) ($product['stock_qty'] ?? 0) <= 0;
?>
<article class="shop-detail">
  <div class="shop-detail-gallery">
    <img src="<?= e($thumb) ?>" alt="<?= e((string) $product['name']) ?>">
  </div>
  <div class="shop-detail-info">
    <span class="shop-product-cat"><?= e((string) ($product['category_name'] ?? '')) ?></span>
    <h1><?= e((string) $product['name']) ?></h1>
    <p class="shop-detail-sku">SKU · <?= e((string) $product['sku']) ?></p>
    <?php if (!empty($product['spec_name'])): ?>
    <ul class="shop-detail-specs">
      <li>규격 <?= e((string) ($product['width_mm'] ?? '')) ?> × <?= e((string) ($product['height_mm'] ?? '')) ?> mm</li>
      <li>재질 <?= e((string) ($product['material'] ?? '-')) ?></li>
      <li><?= e((string) ($product['labels_per_sheet'] ?? '-')) ?>칸 / 시트</li>
    </ul>
    <?php endif; ?>
    <div class="shop-detail-price">
      <?php if (!empty($product['sale_price']) && (int) $product['sale_price'] < (int) $product['price']): ?>
      <del><?= e($shopService->formatPrice((int) $product['price'])) ?></del>
      <?php endif; ?>
      <strong><?= e($shopService->formatPrice($unit)) ?></strong>
    </div>
    <p class="shop-detail-stock"><?= $isSoldout ? '품절' : '재고 ' . number_format((int) $product['stock_qty']) . '개' ?></p>
    <?php if (!$isSoldout): ?>
    <div class="shop-detail-actions">
      <div class="shop-qty">
        <button type="button" class="shop-qty-btn" data-qty-minus>-</button>
        <input type="number" id="productQty" value="1" min="1" max="<?= (int) $product['stock_qty'] ?>">
        <button type="button" class="shop-qty-btn" data-qty-plus>+</button>
      </div>
      <button type="button" class="shop-btn shop-btn--primary shop-btn--block" data-add-cart="<?= (int) $product['id'] ?>" data-qty-input="#productQty">장바구니 담기</button>
      <a class="shop-btn shop-btn--outline shop-btn--block" href="<?= url('shop/cart') ?>">장바구니 바로가기</a>
    </div>
    <?php endif; ?>
    <?php if (!empty($product['description'])): ?>
    <div class="shop-detail-desc">
      <h2>상품 설명</h2>
      <p><?= nl2br(e((string) $product['description'])) ?></p>
    </div>
    <?php endif; ?>
  </div>
</article>

<?php if ($related): ?>
<section class="shop-section">
  <div class="shop-section-head"><h2>같은 카테고리 상품</h2></div>
  <div class="shop-product-grid">
    <?php foreach (array_slice($related, 0, 4) as $product): ?>
    <?php require view_path('shop/partials/product-card.php'); ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
