<?php
/** @var App\Services\ShopService $shopService */
/** @var array{items: array, total: int, page: int, pages: int} $list */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<string, mixed>|null $category */
/** @var string $q */
$pageTitleHead = $category['name'] ?? ($q !== '' ? '"' . $q . '" 검색' : '전체 상품');
?>
<section class="shop-page-head">
  <?php if (!empty($category['image_path'])): ?>
  <div class="shop-category-hero">
    <img src="<?= e(\App\Services\ShopProductImageService::resolveUrl((string) $category['image_path'])) ?>" alt="<?= e((string) $category['name']) ?>" loading="lazy" decoding="async">
  </div>
  <?php endif; ?>
  <div>
    <h1><?= e((string) $pageTitleHead) ?></h1>
    <p>총 <?= number_format((int) $list['total']) ?>개 상품</p>
  </div>
</section>

<div class="shop-filter-bar">
  <a class="shop-filter-chip<?= empty($_GET['category']) ? ' is-active' : '' ?>" href="<?= url('shop/products') ?>">전체</a>
  <?php foreach ($categories as $cat): ?>
  <a class="shop-filter-chip<?= (($category['slug'] ?? '') === $cat['slug']) ? ' is-active' : '' ?>" href="<?= url('shop/products') ?>?category=<?= e($cat['slug']) ?>">
    <?php if (!empty($cat['image_path'])): ?>
    <img class="shop-filter-chip-img" src="<?= e(\App\Services\ShopProductImageService::resolveUrl((string) $cat['image_path'])) ?>" alt="" loading="lazy" decoding="async">
    <?php endif; ?>
    <?= e($cat['name']) ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if ($list['items']): ?>
<div class="shop-product-grid shop-product-grid--list">
  <?php foreach ($list['items'] as $product): ?>
  <?php require view_path('shop/partials/product-card.php'); ?>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="shop-empty">
  <p>조건에 맞는 상품이 없습니다.</p>
  <a class="shop-btn shop-btn--primary" href="<?= url('shop') ?>">쇼핑몰 홈으로</a>
</div>
<?php endif; ?>

<?php if ($list['pages'] > 1): ?>
<nav class="shop-pagination" aria-label="페이지">
  <?php for ($p = 1; $p <= $list['pages']; $p++): ?>
  <?php
    $qs = $_GET;
    $qs['page'] = (string) $p;
    $href = url('shop/products') . '?' . http_build_query($qs);
  ?>
  <a class="<?= $p === $list['page'] ? 'is-active' : '' ?>" href="<?= e($href) ?>"><?= $p ?></a>
  <?php endfor; ?>
</nav>
<?php endif; ?>
