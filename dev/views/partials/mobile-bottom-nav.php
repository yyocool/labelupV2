<?php
$bnavPath = rtrim((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/') ?: '/';
$bnavCart = 0;
try {
    $bnavCart = (new \App\Services\ShopService())->cartCount();
} catch (\Throwable) {
    $bnavCart = 0;
}
$bnavIcons = require view_path('home/partials/menu-icons.php');
$bnavItems = [
    ['key' => 'home', 'label' => '홈', 'title' => '라벨업 홈', 'href' => url('/'), 'on' => $bnavPath === '/'],
    ['key' => 'search', 'label' => '검색', 'title' => '상품 검색', 'href' => url('shop/products'), 'on' => false],
    ['key' => 'cart', 'label' => '장바구니', 'title' => '장바구니', 'href' => url('shop/cart'), 'on' => false, 'badge' => $bnavCart],
    ['key' => 'design', 'label' => '디자인', 'title' => '라벨 디자인', 'href' => url('editor/'), 'on' => false],
    ['key' => 'mypage', 'label' => 'MY', 'title' => '마이페이지', 'href' => url('account'), 'on' => str_starts_with($bnavPath, '/account')],
];
?>
<link rel="stylesheet" href="<?= css('mobile-bottom-nav.css') ?>">
<div class="lu-bnav-spacer" aria-hidden="true"></div>
<nav class="lu-bnav" aria-label="빠른 메뉴">
  <?php foreach ($bnavItems as $item): ?>
  <a class="lu-bnav__item<?= $item['on'] ? ' is-on' : '' ?>" href="<?= e($item['href']) ?>" title="<?= e($item['title']) ?>"<?= $item['on'] ? ' aria-current="page"' : '' ?>>
    <span class="lu-bnav__ic" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20"><?= $bnavIcons[$item['key']] ?></svg></span>
    <span class="lu-bnav__label"><?= e($item['label']) ?></span>
    <?php if (!empty($item['badge'])): ?><span class="lu-bnav__badge"><?= (int) $item['badge'] ?></span><?php endif; ?>
  </a>
  <?php endforeach; ?>
</nav>
