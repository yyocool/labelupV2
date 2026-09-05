<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? 'login', array_merge($seoOverride ?? [], ['fallback_title' => $pageTitle ?? '로그인'])); ?>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('login.css') ?>">
</head>
<body class="login-page">
<?php marketing_render_body_start(); ?>
<?php require view_path('auth/partials/auth-back.php'); ?>
<div class="login-shell">
  <?php require view_path('auth/partials/login-hero.php'); ?>

  <section class="login-panel" aria-label="로그인">
    <div class="login-card">
      <h2 class="login-card-title">라벨업에 오신 것을 환영합니다</h2>
      <p class="login-card-sub">로그인하여 다양한 기능을 이용해 보세요.</p>

      <div id="authAlert" class="login-alert<?= !empty($authFlash) ? ' show error' : '' ?>"><?= e($authFlash ?? '') ?></div>

      <form id="loginForm" class="login-form" data-redirect="<?= e($redirectUrl ?? url('/')) ?>">
        <div class="login-field">
          <label for="loginEmail">이메일</label>
          <input id="loginEmail" type="email" name="email" required autocomplete="email" placeholder="이메일 주소를 입력하세요">
        </div>
        <div class="login-field">
          <label for="loginPassword">비밀번호</label>
          <div class="login-password-wrap">
            <input id="loginPassword" type="password" name="password" required autocomplete="current-password" placeholder="비밀번호를 입력하세요">
            <button type="button" class="login-password-toggle" id="loginPasswordToggle" aria-label="비밀번호 표시">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
            </button>
          </div>
        </div>

        <div class="login-form-row">
          <label class="login-check"><input type="checkbox" name="remember" value="1"> 로그인 상태 유지</label>
          <div class="login-forgot-links">
            <button type="button" class="login-forgot" data-recovery="find-id">아이디 찾기</button>
            <span class="login-forgot-sep" aria-hidden="true">·</span>
            <button type="button" class="login-forgot" data-recovery="find-password">비밀번호 찾기</button>
          </div>
        </div>

        <button class="login-submit" type="submit">로그인</button>
      </form>

      <div class="login-divider"><span>또는</span></div>

      <div class="login-social">
        <?php
          $oauthEnabled = $oauthEnabled ?? ['naver' => false, 'kakao' => false, 'google' => false];
          $oauthRedirect = rawurlencode((string) ($_GET['redirect'] ?? '/'));
        ?>
        <?php if (!empty($oauthEnabled['naver'])): ?>
        <a class="login-social-btn" href="<?= url('auth/naver') ?>?redirect=<?= e($oauthRedirect) ?>">
          <img src="<?= asset('icon-naver.svg') ?>" alt="">
          <span>네이버로 로그인</span>
        </a>
        <?php else: ?>
        <button type="button" class="login-social-btn" disabled title="키 설정 후 이용 가능">
          <img src="<?= asset('icon-naver.svg') ?>" alt="">
          <span>네이버로 로그인</span>
        </button>
        <?php endif; ?>

        <?php if (!empty($oauthEnabled['kakao'])): ?>
        <a class="login-social-btn" href="<?= url('auth/kakao') ?>?redirect=<?= e($oauthRedirect) ?>">
          <img src="<?= asset('icon-kakao.svg') ?>" alt="">
          <span>카카오로 로그인</span>
        </a>
        <?php else: ?>
        <button type="button" class="login-social-btn" disabled title="키 설정 후 이용 가능">
          <img src="<?= asset('icon-kakao.svg') ?>" alt="">
          <span>카카오로 로그인</span>
        </button>
        <?php endif; ?>

        <?php if (!empty($oauthEnabled['google'])): ?>
        <a class="login-social-btn" href="<?= url('auth/google') ?>?redirect=<?= e($oauthRedirect) ?>">
          <img src="<?= asset('icon-google.svg') ?>" alt="">
          <span>구글로 로그인</span>
        </a>
        <?php else: ?>
        <button type="button" class="login-social-btn" disabled title="키 설정 후 이용 가능">
          <img src="<?= asset('icon-google.svg') ?>" alt="">
          <span>구글로 로그인</span>
        </button>
        <?php endif; ?>
      </div>

      <p class="login-signup">계정이 없으신가요? <a href="<?= url('register') ?>">회원가입</a></p>
      <p class="login-admin-link">관리자는 <a href="<?= url('admin/login') ?>">관리자 로그인</a></p>
    </div>
  </section>
</div>
<?php require view_path('auth/partials/recovery-modal.php'); ?>
<script src="<?= js('auth.js') ?>"></script>
<?php marketing_render_body_end(); ?>
</body>
</html>
