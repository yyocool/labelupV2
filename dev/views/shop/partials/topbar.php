<?php
/** @var int $cartCount */
$cartCount = (int) ($cartCount ?? 0);
$searchQ = e(trim((string) ($_GET['q'] ?? '')));
?>
<header class="topbar shop-topbar">
  <form class="shop-search" action="<?= url('shop/products') ?>" method="get" role="search">
    <input type="search" name="q" value="<?= $searchQ ?>" placeholder="라벨 규격이나 재질을 검색해 보세요" aria-label="상품 검색">
    <button type="submit" class="shop-search-btn" aria-label="검색">⌕</button>
  </form>
  <div class="shop-topbar-actions">
    <a class="shop-cart-btn" href="<?= url('shop/cart') ?>" aria-label="장바구니">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L6 6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M6 6 5 3H2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="9" cy="20" r="1.2" fill="currentColor"/><circle cx="18" cy="20" r="1.2" fill="currentColor"/></svg>
      <?php if ($cartCount > 0): ?><span class="shop-cart-badge" id="shopCartBadge"><?= $cartCount ?></span><?php else: ?><span class="shop-cart-badge" id="shopCartBadge" hidden>0</span><?php endif; ?>
    </a>
    <?php require view_path('home/partials/credit-display.php'); ?>
    <?php require view_path('home/partials/profile-menu.php'); ?>
  </div>
</header>
