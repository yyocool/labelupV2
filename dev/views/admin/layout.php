<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle ?? '관리자 — 라벨업') ?></title>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('admin.css') ?>">
  <?php if (!empty($useSummernote)): ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
  <?php endif; ?>
</head>
<body class="admin-body">
<div class="admin-app" id="adminApp">
<script>try{if(localStorage.getItem('labelup_admin_lnb_collapsed')==='1')document.getElementById('adminApp').classList.add('is-lnb-collapsed')}catch(e){}</script>
  <aside class="admin-lnb" id="adminLnb">
    <div class="admin-lnb-brand">
      <a href="<?= url('/') ?>" class="admin-lnb-brand-link">
        <img class="admin-logo-full" src="<?= asset('logo-admin.svg') ?>" alt="LABEL UP ADMIN">
        <img class="admin-logo-mini" src="<?= asset('logo-admin-mark.svg') ?>" alt="LABEL UP">
      </a>
    </div>
    <nav class="admin-lnb-nav">
      <a class="admin-lnb-item<?= ($activeMenu ?? '') === 'dashboard' ? ' is-active' : '' ?>" href="<?= url('admin') ?>" title="대시보드">
        <span class="ic">▣</span><span class="label">대시보드</span>
      </a>
      <?php require view_path('admin/partials/shop-menu.php'); ?>
      <?php require view_path('admin/partials/content-menu.php'); ?>
      <?php require view_path('admin/partials/ops-menu.php'); ?>
    </nav>
    <div class="admin-lnb-foot">LabelUp Admin v1.0</div>
  </aside>

  <button type="button" class="admin-lnb-toggle" id="adminLnbToggle" aria-label="사이드바 접기" aria-expanded="true" title="사이드바 접기">
    <span class="admin-lnb-toggle-icon" aria-hidden="true">‹</span>
  </button>

  <div class="admin-main">
    <header class="admin-topbar">
      <div class="admin-crumb">
        <?php
          $crumb = $crumbTitle ?? match ($activeMenu ?? '') {
              'users' => '운영관리 › 회원 관리',
              'settings' => '운영관리 › 운영설정',
              'shop-categories' => '쇼핑몰운영 › 카테고리',
              'shop-specs' => '쇼핑몰운영 › 라벨 규격',
              'shop-products' => '쇼핑몰운영 › 상품 관리',
              'shop-orders' => '쇼핑몰운영 › 주문 관리',
              'shop-shipping' => '쇼핑몰운영 › 배송 관리',
              'shop-coupons' => '쇼핑몰운영 › 쿠폰·프로모션',
              'shop-banners' => '쇼핑몰운영 › 배너·전시',
              'content-cliparts' => '컨텐츠관리 › 클립아트관리',
              'ops-credit-rewards' => '운영관리 › 크레딧보상 관리',
              'ops-purchase-credits' => '운영관리 › 구매크레딧',
              'ops-hero-slides' => '운영관리 › 히어로 이미지 관리',
              'ops-event-popups' => '운영관리 › 이벤트 팝업관리',
              default => '대시보드',
          };
        ?>
        관리자 › <b><?= e($crumb) ?></b>
      </div>
      <div class="admin-top-actions">
        <a class="admin-top-link" href="<?= url('/') ?>">사이트 보기</a>
        <a class="admin-top-link" href="<?= url('account') ?>">마이페이지</a>
        <div class="admin-top-user">
          <span class="av"><?= e(mb_substr($user['name'] ?? '관', 0, 1)) ?></span>
          <span><?= e($user['name'] ?? '관리자') ?></span>
        </div>
        <a class="admin-top-link" href="<?= url('admin/logout') ?>">로그아웃</a>
      </div>
    </header>

    <div class="admin-content">
      <?php require view_path(str_replace('.', '/', $contentTemplate) . '.php'); ?>
    </div>
  </div>
</div>
<?php if (!empty($useSummernote)): ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>
<?php endif; ?>
<script src="<?= js('admin.js') ?>"></script>
</body>
</html>
