<?php
$companyRows = $companyRows ?? [];
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? 'about', array_merge($seoOverride ?? [], [
    'fallback_title' => $pageTitle ?? '서비스 소개 — 라벨업',
    'description' => '라벨업은 AI로 라벨을 디자인하고 규격 용지를 구매·출력까지 이어주는 라벨 디자인·쇼핑 서비스입니다. 로그인 없이 서비스 흐름을 확인할 수 있습니다.',
  ])); ?>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('home.css') ?>">
  <link rel="stylesheet" href="<?= css('legal.css') ?>">
</head>
<body class="legal-page about-page">
<?php marketing_render_body_start(); ?>
<header class="legal-top">
  <a class="legal-brand" href="<?= url('/') ?>">
    <img src="<?= asset('logo.png') ?>" alt="labelup" width="120" height="32">
  </a>
  <nav class="legal-nav" aria-label="소개 메뉴">
    <a href="<?= url('about') ?>" aria-current="page">서비스 소개</a>
    <a href="<?= url('shop') ?>">라벨쇼핑</a>
    <a href="<?= url('terms') ?>">이용약관</a>
    <a href="<?= url('privacy') ?>">개인정보 처리방침</a>
    <a href="<?= e($loginUrl ?? url('login')) ?>">로그인</a>
  </nav>
</header>

<main class="legal-main about-main">
  <section class="about-hero legal-card">
    <p class="about-eyebrow">labelup · 서비스 소개</p>
    <h1>labelup<br>라벨 디자인부터 인쇄·구매까지<br>한곳에서</h1>
    <p class="about-lead">
      <b>labelup(라벨업)</b>은 AI 도우미 라비와 함께 라벨을 만들고, 규격 용지를 쇼핑·주문할 수 있는 웹 서비스입니다.
      아래 이용 흐름은 <b>로그인 없이</b> 확인할 수 있습니다.
    </p>
    <div class="about-cta">
      <a class="about-btn about-btn--primary" href="<?= e($kakaoLoginUrl ?? url('auth/kakao')) ?>">카카오로 시작하기</a>
      <a class="about-btn" href="<?= e($loginUrl ?? url('login')) ?>">이메일 로그인</a>
      <a class="about-btn" href="<?= e($shopUrl ?? url('shop')) ?>">쇼핑몰 둘러보기</a>
    </div>
  </section>

  <section class="legal-card">
    <h2>이용 흐름 (User Flow)</h2>
    <ol class="about-flow">
      <li>
        <strong>1. 홈에서 원하는 라벨 설명</strong>
        <span>메인(`/`)의 「라비와 라벨 만들기」에서 용도·규격을 입력하거나 예시 칩을 눌러 시작합니다.</span>
      </li>
      <li>
        <strong>2. 카카오/이메일 로그인</strong>
        <span>AI 생성·작업 저장·주문 시 로그인이 필요합니다. 로그인 화면에서 <b>카카오 로그인</b> 버튼을 사용할 수 있습니다.</span>
      </li>
      <li>
        <strong>3. 템플릿·편집기에서 디자인</strong>
        <span>추천 템플릿을 고르거나 편집기(`/editor/`)에서 텍스트·QR·바코드·이미지를 배치합니다.</span>
      </li>
      <li>
        <strong>4. 라벨지 쇼핑·주문</strong>
        <span>쇼핑몰(`/shop`)에서 규격 라벨지를 담고 주문합니다. 상품 목록·상세는 비회원도 열람할 수 있습니다.</span>
      </li>
      <li>
        <strong>5. 마이페이지·고객지원</strong>
        <span>계정·주문·크레딧은 마이페이지에서, 이용 방법은 FAQ(`/faq`)에서 확인합니다.</span>
      </li>
    </ol>
  </section>

  <section class="legal-card">
    <h2>주요 화면</h2>
    <div class="about-screens">
      <article>
        <h3>홈 · AI 라벨 추천</h3>
        <p>라비 채팅으로 라벨 용지를 추천받고 템플릿/클립아트를 탐색합니다.</p>
        <a href="<?= url('/') ?>">홈 바로가기 →</a>
      </article>
      <article>
        <h3>라벨 쇼핑몰</h3>
        <p>카테고리·규격별 라벨지를 비교하고 장바구니에 담습니다.</p>
        <a href="<?= e($shopUrl ?? url('shop')) ?>">쇼핑 바로가기 →</a>
      </article>
      <article>
        <h3>로그인 · 카카오</h3>
        <p>카카오 계정으로 간편 가입/로그인 후 작업을 이어갑니다.</p>
        <a href="<?= e($loginUrl ?? url('login')) ?>">로그인 화면 →</a>
      </article>
      <article>
        <h3>편집기</h3>
        <p>브라우저에서 라벨 레이아웃을 편집하고 저장합니다.</p>
        <a href="<?= e($editorUrl ?? url('editor/')) ?>">편집기 열기 →</a>
      </article>
    </div>
  </section>

  <section class="legal-card" id="company">
    <h2>사업자 정보</h2>
    <?php if ($companyRows !== []): ?>
    <ul class="about-company">
      <?php foreach ($companyRows as $row): ?>
      <li><span><?= e((string) $row['label']) ?></span><strong><?= e((string) $row['value']) ?></strong></li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="about-note">사업자정보는 관리자 <b>SEO 설정 › 사업자정보</b>에 등록하면 이 페이지와 사이트 푸터에 표시됩니다. 카카오 비즈니스 앱에 등록한 내용과 동일해야 합니다.</p>
    <?php endif; ?>
    <p class="about-note">
      약관: <a href="<?= e($termsUrl ?? url('terms')) ?>">이용약관</a> ·
      <a href="<?= e($privacyUrl ?? url('privacy')) ?>">개인정보 처리방침</a>
      (로그인 없이 열람 가능)
    </p>
  </section>

  <?php render_site_footer(['year' => $year ?? date('Y')]); ?>
</main>
<?php marketing_render_body_end(); ?>
</body>
</html>
