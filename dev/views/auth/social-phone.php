<?php
/** @var array{provider:string,provider_label:string,email:string,name:string} $pending */
$pending = $pending ?? ['provider' => '', 'provider_label' => '소셜', 'email' => '', 'name' => ''];
$providerLabel = (string) ($pending['provider_label'] ?? '소셜');
$email = (string) ($pending['email'] ?? '');
$showEmail = $email !== '' && !str_ends_with($email, '@oauth.labelup.local');
$name = (string) ($pending['name'] ?? '');
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <?php marketing_render_head(); ?>
  <?php seo_render_head($seoPage ?? 'register', array_merge($seoOverride ?? [], ['fallback_title' => $pageTitle ?? '휴대폰 인증'])); ?>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('login.css') ?>">
</head>
<body class="login-page register-page">
<?php marketing_render_body_start(); ?>
<?php require view_path('auth/partials/auth-back.php'); ?>

<div class="login-shell">
  <?php require view_path('auth/partials/login-hero.php'); ?>

  <section class="login-panel register-panel" aria-label="소셜 가입 휴대폰 인증">
    <div class="register-card">
      <div class="register-card-top">
        <h1 class="register-section-title register-section-title--main">휴대폰 인증</h1>
        <p class="register-login-link"><a href="<?= url('login') ?>">로그인</a></p>
      </div>

      <div id="authAlert" class="login-alert<?= !empty($authFlash) ? ' show error' : '' ?>"><?= e($authFlash ?? '') ?></div>

      <p class="register-hint" style="margin:0 0 18px">
        <?= e($providerLabel) ?> 계정<?= $name !== '' ? ' · ' . e($name) : '' ?><?= $showEmail ? ' · ' . e($email) : '' ?>
        으로 가입하려면 문자 인증이 필요합니다. 인증 후 회원가입이 완료됩니다.
      </p>

      <form id="socialPhoneForm">
        <div class="login-field">
          <label for="registerPhone">휴대폰 번호 <em class="register-req">(필수)</em></label>
          <div class="register-phone-row">
            <input id="registerPhone" type="tel" name="phone" required inputmode="tel" autocomplete="tel" maxlength="20" placeholder="010-1234-5678">
            <button type="button" class="register-sms-btn" id="sendSmsBtn">인증번호 받기</button>
          </div>
          <div id="phoneHint" class="register-hint">문자로 받은 인증번호를 확인해야 가입이 완료됩니다.</div>
        </div>

        <div class="login-field" id="smsCodeField" hidden>
          <label for="registerSmsCode">인증번호</label>
          <div class="register-phone-row">
            <input id="registerSmsCode" type="text" name="sms_code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6자리 숫자">
            <button type="button" class="register-sms-btn" id="verifySmsBtn">확인</button>
          </div>
          <div id="smsHint" class="register-hint"></div>
          <input type="hidden" name="phone_verify_token" id="phoneVerifyToken" value="">
        </div>

        <div class="register-agreements">
          <div class="register-agreement-row">
            <label class="register-check">
              <input type="checkbox" name="terms" value="1" required>
              <span>이용약관에 동의합니다. <em>(필수)</em></span>
            </label>
            <a class="register-agree-view" href="<?= url('terms') ?>" target="_blank" rel="noopener">보기</a>
          </div>
          <div class="register-agreement-row">
            <label class="register-check">
              <input type="checkbox" name="privacy" value="1" required>
              <span>개인정보 수집 및 이용에 동의합니다. <em>(필수)</em></span>
            </label>
            <a class="register-agree-view" href="<?= url('privacy') ?>" target="_blank" rel="noopener">보기</a>
          </div>
        </div>

        <button class="login-submit register-submit" type="submit">인증 후 가입 완료</button>
      </form>
    </div>
  </section>
</div>
<?php render_site_footer(['year' => (int) date('Y')]); ?>
<script src="<?= js('auth.js') ?>"></script>
<?php marketing_render_body_end(); ?>
</body>
</html>
