<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <?php
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }
  ?>
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? null, array_merge($seoOverride ?? [], ['fallback_title' => $pageTitle ?? '쇼핑몰 — 라벨업'])); ?>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('home.css') ?>">
  <link rel="stylesheet" href="<?= css('shop.css') ?>">
</head>
<body class="shop-page<?= !empty($hideShopAside) ? ' shop-page--complete' : '' ?>">
<?php marketing_render_body_start(); ?>
<div class="app shop-app" id="userApp">
<script>(function(){var app=document.getElementById('userApp');if(!app)return;var mobile=window.matchMedia('(max-width:1080px)').matches;var collapsed=false;try{collapsed=localStorage.getItem('labelup_sidebar_collapsed')==='1'}catch(e){}if(mobile||collapsed)app.classList.add('is-sidebar-collapsed')})();</script>
<?php require view_path('shop/partials/sidebar.php'); ?>
<?php require view_path('home/partials/sidebar-toggle.php'); ?>
<main class="main shop-main">
  <?php require view_path('shop/partials/topbar.php'); ?>
  <div class="shop-wrap<?= !empty($hideShopAside) ? ' shop-wrap--complete' : '' ?>">
    <div class="shop-content">
      <?php
        $couponNotice = null;
        if (($contentTemplate ?? '') !== 'shop/cart' && isset($shopService) && is_object($shopService)) {
            $couponNotice = $shopService->selectedCouponNotice();
        }
      ?>
      <?php if ($couponNotice): ?>
      <div class="shop-coupon-picked" role="status">
        <strong><?= e((string) $couponNotice['code']) ?></strong>
        <span><?= e((string) $couponNotice['text']) ?></span>
      </div>
      <?php endif; ?>
      <?php require view_path(str_replace('.', '/', $contentTemplate) . '.php'); ?>
    </div>
    <?php if (empty($hideShopAside)): ?>
    <?php require view_path('shop/partials/aside-panel.php'); ?>
    <?php endif; ?>
  </div>
  <?php render_site_footer(['year' => (int) date('Y')]); ?>
</main>
</div>
<?php require view_path('shop/partials/floating-bar.php'); ?>
<script src="<?= js('daum-address.js') ?>"></script>
<script src="<?= js('address-book.js') ?>"></script>
<script src="<?= js('home.js') ?>"></script>
<script src="<?= js('notifications.js') ?>"></script>
<script src="<?= js('toss-pay.js') ?>"></script>
<script src="<?= js('shop.js') ?>"></script>
<script src="<?= asset('labi-assistant.js') ?>"></script>
<?php require view_path('home/partials/event-popup.php'); ?>
<?php marketing_render_body_end(); ?>
</body>
</html>
