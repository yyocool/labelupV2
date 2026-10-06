<?php
$partner = $partner ?? [];
$activeMenu = (string) ($activeMenu ?? 'images');
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle ?? '협력사 — 라벨업') ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('admin.css') ?>">
  <link rel="stylesheet" href="<?= css('partner.css') ?>">
</head>
<body class="admin-body">
<div class="admin-app" id="partnerApp">
  <aside class="admin-lnb">
    <div class="admin-lnb-brand">
      <a href="<?= url('partner/images') ?>" class="admin-lnb-brand-link">
        <img class="admin-logo-full" src="<?= asset('logo-admin.svg') ?>" alt="LABEL UP">
        <img class="admin-logo-mini" src="<?= asset('logo-admin-mark.svg') ?>" alt="LABEL UP">
      </a>
    </div>
    <nav class="admin-lnb-nav">
      <a class="admin-lnb-item<?= $activeMenu === 'images' ? ' is-active' : '' ?>" href="<?= url('partner/images') ?>">
        <span class="ic">▣</span><span class="label">이미지DB</span>
      </a>
    </nav>
    <div class="admin-lnb-foot">협력사 전용</div>
  </aside>
  <div class="admin-main">
    <header class="admin-topbar">
      <div class="admin-crumb">협력사 › <b>이미지DB</b></div>
      <div class="admin-top-actions">
        <span class="partner-company"><?= e((string) ($partner['company_name'] ?? '')) ?></span>
        <a class="admin-top-link" href="<?= url('partner/logout') ?>">로그아웃</a>
      </div>
    </header>
    <main class="admin-content">
      <?php require view_path(str_replace('.', '/', (string) $contentTemplate) . '.php'); ?>
    </main>
  </div>
</div>
</body>
</html>
