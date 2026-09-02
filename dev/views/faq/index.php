<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? 'faq', array_merge($seoOverride ?? [], ['fallback_title' => $pageTitle ?? '자주 묻는 질문 — 라벨업'])); ?>
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('home.css') ?>">
  <link rel="stylesheet" href="<?= css('account.css') ?>">
  <link rel="stylesheet" href="<?= css('faq.css') ?>">
</head>
<body class="account-page faq-page">
<?php marketing_render_body_start(); ?>
<div class="app" id="userApp">
<script>try{if(localStorage.getItem('labelup_sidebar_collapsed')==='1')document.getElementById('userApp').classList.add('is-sidebar-collapsed')}catch(e){}</script>
<?php require view_path('home/partials/sidebar.php'); ?>
<?php require view_path('home/partials/sidebar-toggle.php'); ?>
<main class="main account-main">
  <header class="topbar account-topbar">
    <form class="account-search faq-top-search" action="<?= url('faq') ?>" method="get" role="search">
      <input type="search" id="faqSearch" name="q" value="<?= e((string) ($_GET['q'] ?? '')) ?>" placeholder="궁금한 내용을 검색해 보세요" aria-label="FAQ 검색">
      <button type="submit" aria-label="검색">⌕</button>
    </form>
    <div class="account-topbar-actions">
      <a class="account-icon-btn" href="<?= url('shop/cart') ?>" aria-label="장바구니">
        🛒<?php if (($cartCount ?? 0) > 0): ?><span class="account-badge"><?= (int) $cartCount ?></span><?php endif; ?>
      </a>
      <?php require view_path('home/partials/credit-display.php'); ?>
      <?php require view_path('home/partials/profile-menu.php'); ?>
    </div>
  </header>
  <div class="content account-content">
    <section class="faq-hero card">
      <div>
        <p class="faq-kicker">고객센터</p>
        <h1>자주 묻는 질문</h1>
        <p>라벨 디자인, 쇼핑, 크레딧, 주문까지 자주 묻는 내용을 모아 두었습니다.</p>
      </div>
      <a class="account-btn account-btn--primary" href="<?= url('editor/') ?>">새 디자인 만들기</a>
    </section>

    <?php $groups = $groups ?? []; ?>
    <?php if (empty($groups)): ?>
    <section class="card faq-empty">
      <p>등록된 FAQ가 없습니다. 조금만 기다려 주세요.</p>
    </section>
    <?php else: ?>
    <nav class="faq-tabs" aria-label="FAQ 분류">
      <button type="button" class="faq-tab is-active" data-cat="all">전체</button>
      <?php foreach ($groups as $group): ?>
      <button type="button" class="faq-tab" data-cat="<?= e($group['slug']) ?>"><?= e($group['name']) ?></button>
      <?php endforeach; ?>
    </nav>

    <?php foreach ($groups as $group): ?>
    <section class="faq-group" data-cat="<?= e($group['slug']) ?>">
      <h2 class="faq-group-title"><?= e($group['name']) ?></h2>
      <div class="faq-list">
        <?php foreach ($group['items'] as $item): ?>
        <details class="faq-item" data-q="<?= e(mb_strtolower((string) ($item['question'] ?? ''))) ?>">
          <summary><?= e($item['question'] ?? '') ?></summary>
          <div class="faq-answer"><?= $item['answer'] ?? '' ?></div>
        </details>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endforeach; ?>
    <p class="faq-empty-search" hidden>검색 결과가 없습니다. 다른 키워드로 찾아 보세요.</p>
    <?php endif; ?>

    <footer class="page-footer">
      <div class="faq-foot-links">
        <a href="<?= url('faq') ?>">FAQ</a>
        <a href="<?= url('shop') ?>">라벨쇼핑</a>
        <a href="<?= url('account') ?>">마이페이지</a>
      </div>
      <div class="copy">© <?= (int) ($year ?? date('Y')) ?> LABEL UP. All rights reserved.</div>
    </footer>
  </div>
</main>
</div>
<script src="<?= js('home.js') ?>"></script>
<script src="<?= js('faq.js') ?>"></script>
<script src="<?= asset('labi-assistant.js') ?>"></script>
<?php require view_path('home/partials/event-popup.php'); ?>
<?php marketing_render_body_end(); ?>
</body>
</html>
