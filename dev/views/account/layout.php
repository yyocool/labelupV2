<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? 'account', array_merge($seoOverride ?? [], ['fallback_title' => $pageTitle ?? '마이페이지 — 라벨업'])); ?>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('home.css') ?>">
  <link rel="stylesheet" href="<?= css('account.css') ?>">
</head>
<body class="account-page">
<?php marketing_render_body_start(); ?>
<div class="app" id="userApp">
<script>try{if(localStorage.getItem('labelup_sidebar_collapsed')==='1')document.getElementById('userApp').classList.add('is-sidebar-collapsed')}catch(e){}</script>
<?php require view_path('home/partials/sidebar.php'); ?>
<?php require view_path('home/partials/sidebar-toggle.php'); ?>
<main class="main account-main">
  <header class="topbar account-topbar">
    <form class="account-search" action="<?= url('shop/products') ?>" method="get" role="search">
      <input type="search" name="q" placeholder="찾고 있는 라벨 규격이나 재질을 검색해보세요" aria-label="검색">
      <button type="submit" aria-label="검색">⌕</button>
    </form>
    <div class="account-topbar-actions">
      <a class="account-icon-btn" href="<?= url('shop/cart') ?>" aria-label="장바구니">
        🛒<?php if (($cartCount ?? 0) > 0): ?><span class="account-badge"><?= (int) $cartCount ?></span><?php endif; ?>
      </a>
      <button class="account-icon-btn" type="button" aria-label="알림" disabled title="준비 중">
        <?php require view_path('home/partials/bell-icon.php'); ?><span class="account-badge">3</span>
      </button>
      <?php require view_path('home/partials/credit-display.php'); ?>
      <?php require view_path('home/partials/profile-menu.php'); ?>
    </div>
  </header>
  <div class="content account-content">
    <?php require view_path(str_replace('.', '/', $contentTemplate) . '.php'); ?>
  </div>
</main>
</div>
<?php require view_path('account/partials/modals.php'); ?>
<script src="<?= js('home.js') ?>"></script>
<script src="<?= js('auth.js') ?>"></script>
<script src="<?= js('account.js') ?>"></script>
<script src="<?= asset('labi-assistant.js') ?>"></script>
<?php require view_path('home/partials/event-popup.php'); ?>
<?php marketing_render_body_end(); ?>
</body>
</html>
