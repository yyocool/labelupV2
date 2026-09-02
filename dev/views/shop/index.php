<?php
/** @var App\Services\ShopService $shopService */
/** @var array<int, array<string, mixed>> $banners */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<string, mixed>> $specs */
/** @var array<string, array<int, array<string, mixed>>> $materialGroups */
/** @var array<int, array<string, mixed>> $featuredProducts */

$hero = $banners[0] ?? null;
$categoryIcons = [
    'label-paper' => '▦',
    'thermal-paper' => '▤',
    'packaging' => '📦',
    'supplies' => '🖨',
    'default' => '▧',
];
?>
<section class="shop-hero">
  <div class="shop-hero-inner">
    <div class="shop-hero-copy">
      <span class="shop-hero-badge">🛒 라벨업 쇼핑몰</span>
      <h1><?= e($hero['title'] ?? '필요한 라벨지를 빠르고 쉽게 찾아보세요') ?></h1>
      <p><?= e($hero['subtitle'] ?? '다양한 규격과 재질의 라벨지를 검색하고, 바로 주문까지 진행할 수 있습니다.') ?></p>
      <div class="shop-hero-actions">
        <a class="shop-btn shop-btn--primary" href="<?= url('shop/products') ?>">라벨 검색하기</a>
        <a class="shop-btn shop-btn--outline" href="<?= url('shop/products') ?>?shape=rect">규격으로 찾기</a>
      </div>
    </div>
    <div class="shop-hero-visual">
      <?php if ($hero && !empty($hero['image_url'])): ?>
      <img src="<?= e($hero['image_url']) ?>" alt="">
      <?php else: ?>
      <img src="<?= asset('hero-tall-1.webp') ?>" alt="라벨 상품 미리보기">
      <?php endif; ?>
    </div>
  </div>
  <?php if (count($banners) > 1): ?>
  <div class="shop-hero-dots" aria-hidden="true">
    <?php foreach ($banners as $i => $_): ?>
    <span class="<?= $i === 0 ? 'is-active' : '' ?>"></span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<nav class="shop-cat-icons" aria-label="카테고리">
  <?php foreach ($categories as $cat): ?>
  <a class="shop-cat-icon" href="<?= url('shop/products') ?>?category=<?= e($cat['slug']) ?>">
    <span class="shop-cat-icon-box">
      <?php if (!empty($cat['image_path'])): ?>
      <img src="<?= e(\App\Services\ShopProductImageService::resolveUrl((string) $cat['image_path'])) ?>" alt="<?= e($cat['name']) ?>" loading="lazy" decoding="async">
      <?php else: ?>
      <span class="shop-cat-icon-fallback"><?= e($categoryIcons[$cat['slug']] ?? $categoryIcons['default']) ?></span>
      <?php endif; ?>
    </span>
    <span><?= e($cat['name']) ?></span>
  </a>
  <?php endforeach; ?>
</nav>

<section class="shop-section">
  <div class="shop-section-head">
    <h2>자주 찾는 라벨 규격</h2>
    <a class="shop-more" href="<?= url('shop/products') ?>">전체 보기 →</a>
  </div>
  <div class="shop-spec-grid">
    <?php foreach ($specs as $spec): ?>
    <a class="shop-spec-card" href="<?= url('shop/products') ?>?q=<?= urlencode((string) $spec['name']) ?>">
      <?php if (!empty($spec['image_path'])): ?>
      <div class="shop-spec-preview shop-spec-preview--image">
        <img src="<?= e(\App\Services\ShopProductImageService::resolveUrl((string) $spec['image_path'])) ?>" alt="<?= e((string) $spec['name']) ?>" loading="lazy" decoding="async">
      </div>
      <?php else: ?>
      <div class="shop-spec-preview" data-cols="<?= min(6, max(2, (int) ($spec['labels_per_sheet'] ?? 4))) ?>">
        <?php for ($i = 0; $i < min(12, (int) ($spec['labels_per_sheet'] ?? 6)); $i++): ?>
        <span></span>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
      <strong><?= e((string) round((float) $spec['width_mm'])) ?> × <?= e((string) round((float) $spec['height_mm'])) ?> mm</strong>
      <small><?= e((string) ($spec['labels_per_sheet'] ?? '-')) ?>칸 · <?= e((string) $spec['material']) ?></small>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<?php foreach ($materialGroups as $material => $products): ?>
<section class="shop-section">
  <div class="shop-section-head">
    <h2>재질별 추천 · <?= e($material) ?></h2>
    <a class="shop-more" href="<?= url('shop/products') ?>?material=<?= urlencode($material) ?>">전체 보기 →</a>
  </div>
  <div class="shop-product-grid">
    <?php foreach (array_slice($products, 0, 4) as $product): ?>
    <?php require view_path('shop/partials/product-card.php'); ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<?php if ($featuredProducts): ?>
<section class="shop-section">
  <div class="shop-section-head">
    <h2>인기 상품</h2>
    <a class="shop-more" href="<?= url('shop/products') ?>">전체 보기 →</a>
  </div>
  <div class="shop-product-grid">
    <?php foreach ($featuredProducts as $product): ?>
    <?php require view_path('shop/partials/product-card.php'); ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
