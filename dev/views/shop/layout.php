<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? null, array_merge($seoOverride ?? [], ['fallback_title' => $pageTitle ?? '쇼핑몰 — 라벨업'])); ?>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('home.css') ?>">
  <link rel="stylesheet" href="<?= css('shop.css') ?>">
</head>
<body class="shop-page">
<?php marketing_render_body_start(); ?>
<div class="app shop-app" id="userApp">
<script>try{if(localStorage.getItem('labelup_sidebar_collapsed')==='1')document.getElementById('userApp').classList.add('is-sidebar-collapsed')}catch(e){}</script>
<?php require view_path('shop/partials/sidebar.php'); ?>
<?php require view_path('home/partials/sidebar-toggle.php'); ?>
<main class="main shop-main">
  <?php require view_path('shop/partials/topbar.php'); ?>
  <div class="shop-wrap">
    <div class="shop-content">
      <?php require view_path(str_replace('.', '/', $contentTemplate) . '.php'); ?>
    </div>
    <?php require view_path('shop/partials/aside-panel.php'); ?>
  </div>
</main>
</div>
<?php require view_path('shop/partials/floating-bar.php'); ?>
<script src="<?= js('home.js') ?>"></script>
<script src="<?= js('shop.js') ?>"></script>
<script src="<?= asset('labi-assistant.js') ?>"></script>
<?php require view_path('home/partials/event-popup.php'); ?>
<?php marketing_render_body_end(); ?>
</body>
</html>
