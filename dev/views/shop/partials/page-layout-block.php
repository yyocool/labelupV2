<?php
/** @var array<string, mixed> $block */
/** @var string $kind */
$kind = $kind ?? (string) ($block['kind'] ?? 'header');
$html = trim((string) ($block['html'] ?? ''));
$imageUrl = trim((string) ($block['image_url'] ?? ''));
if ($html === '' && $imageUrl === '') {
    return;
}
$isHeader = $kind !== 'footer';
?>
<div class="shop-detail-page-block shop-detail-page-block--<?= $isHeader ? 'header' : 'footer' ?>">
  <?php if ($isHeader && $imageUrl !== ''): ?>
  <div class="shop-detail-page-media">
    <img src="<?= e($imageUrl) ?>" alt="상품 상세 헤더 이미지">
  </div>
  <?php endif; ?>
  <?php if ($html !== ''): ?>
  <div class="shop-detail-page-html"><?= $html ?></div>
  <?php endif; ?>
  <?php if (!$isHeader && $imageUrl !== ''): ?>
  <div class="shop-detail-page-media">
    <img src="<?= e($imageUrl) ?>" alt="상품 상세 푸터 이미지">
  </div>
  <?php endif; ?>
</div>
