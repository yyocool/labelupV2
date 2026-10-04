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
    /* 실제 상품 페이지와 본문 폭을 맞추기 위해 좌측 사이드바(232px)·우측 사이드 패널(240px) 자리를 그대로 비워 둔다 */
    .preview-stage{margin-left:232px}
    .preview-wrap{
      display:grid;grid-template-columns:minmax(0,1fr) 240px;gap:24px;
      padding:20px 31px 40px 39px;box-sizing:border-box;
    }
    .preview-aside{min-width:0}
    @media (max-width:1080px){
      .preview-stage{margin-left:0}
      .preview-wrap{grid-template-columns:1fr;padding:16px 16px 40px}
      .preview-aside{display:none}
    }
  </style>
</head>
<body class="shop-page">
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
  <div class="preview-stage">
    <div class="preview-wrap">
      <div class="shop-content">
        <?php require view_path('shop/product.php'); ?>
      </div>
      <div class="preview-aside" aria-hidden="true"></div>
    </div>
  </div>
  <!-- 실제 페이지와 같게 보이도록 갤러리 썸네일 전환 스크립트를 함께 싣는다. -->
  <script src="<?= js('shop.js') ?>"></script>
</body>
</html>
