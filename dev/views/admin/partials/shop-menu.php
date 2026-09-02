<?php
$shopMenus = [
    ['key' => 'shop-categories', 'label' => '카테고리', 'href' => 'admin/shop/categories', 'ic' => '▦'],
    ['key' => 'shop-specs', 'label' => '용지 규격', 'href' => 'admin/shop/specs', 'ic' => '▤'],
    ['key' => 'shop-products', 'label' => '상품 관리', 'href' => 'admin/shop/products', 'ic' => '▧'],
    ['key' => 'shop-orders', 'label' => '주문 관리', 'href' => 'admin/shop/orders', 'ic' => '▨'],
    ['key' => 'shop-shipping', 'label' => '배송 관리', 'href' => 'admin/shop/shipping', 'ic' => '▥'],
    ['key' => 'shop-coupons', 'label' => '쿠폰·프로모션', 'href' => 'admin/shop/coupons', 'ic' => '◇'],
    ['key' => 'shop-banners', 'label' => '배너·전시', 'href' => 'admin/shop/banners', 'ic' => '▣'],
];
$shopMenus = admin_filter_menu_items($shopMenus);
$isShopOpen = ($menuGroup ?? '') === 'shop' || str_starts_with((string) ($activeMenu ?? ''), 'shop-');
if ($shopMenus === []) {
    return;
}
?>
<div class="admin-lnb-group<?= $isShopOpen ? ' is-open' : '' ?>">
  <button type="button" class="admin-lnb-group-toggle" aria-expanded="<?= $isShopOpen ? 'true' : 'false' ?>">
    <span class="ic">🛒</span><span class="label">쇼핑몰운영</span><span class="admin-lnb-caret">▾</span>
  </button>
  <div class="admin-lnb-group-items">
    <?php foreach ($shopMenus as $m): ?>
    <a class="admin-lnb-sub<?= ($activeMenu ?? '') === $m['key'] ? ' is-active' : '' ?>" href="<?= url($m['href']) ?>" title="<?= e($m['label']) ?>">
      <span class="ic"><?= $m['ic'] ?></span><span class="label"><?= e($m['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</div>
