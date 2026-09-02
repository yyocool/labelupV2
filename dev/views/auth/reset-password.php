<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? 'reset-password', array_merge($seoOverride ?? [], ['fallback_title' => $pageTitle ?? '비밀번호 재설정'])); ?>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('login.css') ?>">
</head>
<body class="login-page">
<?php marketing_render_body_start(); ?>
<?php require view_path('auth/partials/auth-back.php'); ?>
<div class="login-shell">
  <?php require view_path('auth/partials/login-hero.php'); ?>

  <section class="login-panel" aria-label="비밀번호 재설정">
    <div class="login-card">
      <h2 class="login-card-title">비밀번호 재설정</h2>
      <?php if (empty($tokenValid)): ?>
        <p class="login-card-sub">링크가 만료되었거나 유효하지 않습니다.<br>로그인 화면에서 비밀번호 찾기를 다시 진행해주세요.</p>
        <a class="login-submit recovery-result-login" href="<?= url('login') ?>">로그인으로 돌아가기</a>
      <?php else: ?>
        <p class="login-card-sub">새 비밀번호를 입력해주세요. (8자 이상, 영문·숫자 포함)</p>
        <div id="authAlert" class="login-alert"></div>
        <form id="resetPasswordForm" class="login-form" data-token="<?= e($token ?? '') ?>" data-redirect="<?= url('login') ?>">
          <div class="login-field">
            <label for="resetPassword">새 비밀번호</label>
            <div class="login-password-wrap">
              <input id="resetPassword" type="password" name="password" required autocomplete="new-password" placeholder="새 비밀번호">
              <button type="button" class="login-password-toggle js-pwd-toggle" data-target="resetPassword" aria-label="비밀번호 표시">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
              </button>
            </div>
          </div>
          <div class="login-field">
            <label for="resetPasswordConfirm">새 비밀번호 확인</label>
            <div class="login-password-wrap">
              <input id="resetPasswordConfirm" type="password" name="password_confirm" required autocomplete="new-password" placeholder="비밀번호 확인">
              <button type="button" class="login-password-toggle js-pwd-toggle" data-target="resetPasswordConfirm" aria-label="비밀번호 표시">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
              </button>
            </div>
          </div>
          <button class="login-submit" type="submit">비밀번호 변경</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</div>
<script src="<?= js('auth.js') ?>"></script>
<?php marketing_render_body_end(); ?>
</body>
</html>
