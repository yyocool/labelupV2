<?php
/** @var App\Services\ShopService $shopService */
/** @var array<string, mixed> $product */
/** @var array<string, mixed> $pageLayout */
/** @var array<int, array<string, mixed>> $related */
/** @var string $publicUrl */
/** @var bool $isPublic */
/** @var string $statusLabel */
$pageTitle = '미리보기 · ' . (string) ($product['name'] ?? '상품');
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('shop.css') ?>">
  <style>
    body{margin:0;background:#f4f4f7;font-family:Pretendard,sans-serif}
    .preview-banner{
      position:sticky;top:0;z-index:20;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
      padding:10px 16px;background:#1f2430;color:#fff;font-size:13px;
    }
    .preview-banner strong{font-weight:800}
    .preview-banner span{opacity:.82}
    .preview-banner-actions{display:flex;gap:8px;flex-wrap:wrap}
    .preview-banner a,.preview-banner button{
      display:inline-flex;align-items:center;height:32px;padding:0 12px;border-radius:8px;border:0;
      font:inherit;font-weight:700;cursor:pointer;text-decoration:none;color:#1f2430;background:#fff;
    }
    .preview-banner a.is-muted{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.35)}
    .preview-wrap{width:100%;max-width:100%;margin:0;padding:20px 16px 40px;box-sizing:border-box}
  </style>
</head>
<body>
  <div class="preview-banner">
    <div>
      <strong>상품 상세 미리보기</strong>
      <span> · <?= e((string) ($product['name'] ?? '')) ?> · <?= e($statusLabel) ?></span>
    </div>
    <div class="preview-banner-actions">
      <?php if ($isPublic): ?>
      <a href="<?= e($publicUrl) ?>" target="_blank" rel="noopener">실제 페이지 열기</a>
      <?php else: ?>
      <span class="is-muted" style="opacity:.75">공개 상태가 아니어서 실제 페이지는 열리지 않습니다</span>
      <?php endif; ?>
      <button type="button" onclick="window.close()">닫기</button>
    </div>
  </div>
  <div class="preview-wrap shop-content">
    <?php require view_path('shop/product.php'); ?>
  </div>
</body>
</html>
