<?php
/** @var int $cartCount */
$cartCount = (int) ($cartCount ?? 0);
?>
<nav class="shop-float-bar" aria-label="쇼핑몰 빠른 메뉴">
  <a class="shop-float-item" href="<?= url('shop') ?>" title="쇼핑몰 홈">
    <span class="shop-float-ic">🏠</span><span class="shop-float-label">홈</span>
  </a>
  <a class="shop-float-item" href="<?= url('shop/products') ?>" title="상품 검색">
    <span class="shop-float-ic">⌕</span><span class="shop-float-label">검색</span>
  </a>
  <a class="shop-float-item shop-float-item--cart" href="<?= url('shop/cart') ?>" title="장바구니">
    <span class="shop-float-ic">🛒</span>
    <span class="shop-float-label">장바구니</span>
    <span class="shop-float-badge" id="shopFloatBadge" <?= $cartCount > 0 ? '' : 'hidden' ?>><?= $cartCount ?></span>
  </a>
  <a class="shop-float-item" href="<?= url('editor/') ?>" title="라벨 디자인">
    <span class="shop-float-ic">✎</span><span class="shop-float-label">디자인</span>
  </a>
  <a class="shop-float-item" href="<?= url('account') ?>" title="마이페이지">
    <span class="shop-float-ic">◎</span><span class="shop-float-label">MY</span>
  </a>
</nav>

<a class="shop-fab-cart" href="<?= url('shop/cart') ?>" aria-label="장바구니" title="장바구니">
  🛒
  <span class="shop-fab-badge" id="shopFabBadge" <?= $cartCount > 0 ? '' : 'hidden' ?>><?= $cartCount ?></span>
</a>
