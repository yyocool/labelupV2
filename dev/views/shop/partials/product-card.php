<?php
/** @var App\Services\ShopService $shopService */
/** @var array<string, mixed> $product */
$unit = $shopService->unitPrice($product);
$thumb = $shopService->productThumb($product);
$isSoldout = ($product['status'] ?? '') === 'soldout' || (int) ($product['stock_qty'] ?? 0) <= 0;
?>
<article class="shop-product-card">
  <a class="shop-product-link" href="<?= url('shop/products/' . (int) $product['id']) ?>">
    <div class="shop-product-img">
      <img src="<?= e($thumb) ?>" alt="">
      <?php if ($isSoldout): ?><span class="shop-product-badge">품절</span><?php endif; ?>
      <?php if (!empty($product['sale_price']) && (int) $product['sale_price'] < (int) $product['price']): ?>
      <span class="shop-product-badge shop-product-badge--sale">SALE</span>
      <?php endif; ?>
    </div>
    <div class="shop-product-meta">
      <span class="shop-product-cat"><?= e((string) ($product['category_name'] ?? '')) ?></span>
      <h3><?= e((string) $product['name']) ?></h3>
      <?php if (!empty($product['material'])): ?>
      <p class="shop-product-desc"><?= e((string) $product['material']) ?> · <?= e((string) ($product['width_mm'] ?? '')) ?>×<?= e((string) ($product['height_mm'] ?? '')) ?>mm</p>
      <?php elseif (!empty($product['description'])): ?>
      <p class="shop-product-desc"><?= e(mb_strimwidth(strip_tags((string) $product['description']), 0, 48, '…')) ?></p>
      <?php endif; ?>
      <div class="shop-product-price">
        <?php if (!empty($product['sale_price']) && (int) $product['sale_price'] < (int) $product['price']): ?>
        <del><?= e($shopService->formatPrice((int) $product['price'])) ?></del>
        <?php endif; ?>
        <strong><?= e($shopService->formatPrice($unit)) ?></strong>
      </div>
    </div>
  </a>
  <?php if (!$isSoldout): ?>
  <button type="button" class="shop-add-btn" data-add-cart="<?= (int) $product['id'] ?>">장바구니</button>
  <?php endif; ?>
</article>
