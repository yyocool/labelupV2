<?php
/** @var int $cartCount */
$cartCount = (int) ($cartCount ?? 0);
$floatIcons = require view_path('home/partials/menu-icons.php');
$floatIcon = static fn (string $key): string => '<span class="shop-float-ic" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20">' . $floatIcons[$key] . '</svg></span>';
?>
<nav class="shop-float-bar" aria-label="쇼핑몰 빠른 메뉴">
  <a class="shop-float-item" href="<?= url('shop') ?>" title="쇼핑몰 홈">
    <?= $floatIcon('home') ?><span class="shop-float-label">홈</span>
  </a>
  <a class="shop-float-item" href="<?= url('shop/products') ?>" title="상품 검색">
    <?= $floatIcon('search') ?><span class="shop-float-label">검색</span>
  </a>
  <a class="shop-float-item shop-float-item--cart" href="<?= url('shop/cart') ?>" title="장바구니">
    <?= $floatIcon('cart') ?>
    <span class="shop-float-label">장바구니</span>
    <span class="shop-float-badge" id="shopFloatBadge" <?= $cartCount > 0 ? '' : 'hidden' ?>><?= $cartCount ?></span>
  </a>
  <a class="shop-float-item" href="<?= url('editor/') ?>" title="라벨 디자인">
    <?= $floatIcon('design') ?><span class="shop-float-label">디자인</span>
  </a>
  <a class="shop-float-item" href="<?= url('account') ?>" title="마이페이지">
    <?= $floatIcon('mypage') ?><span class="shop-float-label">MY</span>
  </a>
</nav>

<a class="shop-fab-cart" href="<?= url('shop/cart') ?>" aria-label="장바구니" title="장바구니">
  🛒
  <span class="shop-fab-badge" id="shopFabBadge" <?= $cartCount > 0 ? '' : 'hidden' ?>><?= $cartCount ?></span>
</a>
