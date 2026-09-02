<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle ?? '회원가입') ?></title>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('login.css') ?>">
</head>
<body class="login-page register-page">
<?php require view_path('auth/partials/auth-back.php'); ?>

<div class="login-shell">
  <?php require view_path('auth/partials/login-hero.php'); ?>

  <section class="login-panel register-panel" aria-label="회원가입">
    <div class="register-card register-card--wide">
      <div class="register-card-top">
        <h1 class="register-section-title register-section-title--main">간편 가입</h1>
        <p class="register-login-link">이미 계정이 있으신가요? <a href="<?= url('login') ?>">로그인</a></p>
      </div>

      <div id="authAlert" class="login-alert"></div>

      <div class="login-social login-social--row">
        <button type="button" class="login-social-btn login-social-btn--compact" disabled title="준비 중">
          <img src="<?= asset('icon-naver.svg') ?>" alt="">
          <span>네이버로 가입</span>
        </button>
        <button type="button" class="login-social-btn login-social-btn--compact" disabled title="준비 중">
          <img src="<?= asset('icon-kakao.svg') ?>" alt="">
          <span>카카오톡으로 가입</span>
        </button>
        <button type="button" class="login-social-btn login-social-btn--compact" disabled title="준비 중">
          <img src="<?= asset('icon-google.svg') ?>" alt="">
          <span>구글로 가입</span>
        </button>
      </div>

      <div class="login-divider register-divider"><span>또는</span></div>

      <div class="register-body">
        <form id="registerForm" class="register-form-col">
          <div class="login-field">
            <label for="registerName">이름</label>
            <input id="registerName" type="text" name="name" required maxlength="100" placeholder="이름을 입력하세요">
          </div>

          <div class="login-field">
            <label for="registerEmail">이메일</label>
            <input id="registerEmail" type="email" name="email" required autocomplete="email" placeholder="이메일 주소를 입력하세요">
            <div id="emailHint" class="register-hint"></div>
          </div>

          <div class="login-field">
            <label for="registerPassword">비밀번호</label>
            <div class="login-password-wrap">
              <input id="registerPassword" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="비밀번호를 입력하세요">
              <button type="button" class="login-password-toggle js-pwd-toggle" data-target="registerPassword" aria-label="비밀번호 표시">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
              </button>
            </div>
            <div id="passwordStrength" class="register-pwd-strength" aria-live="polite">
              <div class="register-pwd-strength-head">
                <span class="register-pwd-strength-title">보안 강도</span>
                <span id="passwordStrengthLabel" class="register-pwd-strength-label">-</span>
              </div>
              <div class="register-pwd-strength-bar" role="progressbar" aria-valuemin="0" aria-valuemax="4" aria-valuenow="0">
                <span id="passwordStrengthFill" class="register-pwd-strength-fill"></span>
              </div>
              <ul class="register-pwd-rules">
                <li id="pwdRuleLength"><span class="ic">○</span> 8~20자</li>
                <li id="pwdRuleLetter"><span class="ic">○</span> 영문</li>
                <li id="pwdRuleNumber"><span class="ic">○</span> 숫자</li>
                <li id="pwdRuleSpecial"><span class="ic">○</span> 특수</li>
                <li id="pwdRuleTypes"><span class="ic">○</span> 2종↑</li>
              </ul>
            </div>
          </div>

          <div class="login-field">
            <label for="registerPasswordConfirm">비밀번호 확인</label>
            <div class="login-password-wrap">
              <input id="registerPasswordConfirm" type="password" name="password_confirm" required minlength="8" autocomplete="new-password" placeholder="비밀번호를 다시 입력하세요">
              <button type="button" class="login-password-toggle js-pwd-toggle" data-target="registerPasswordConfirm" aria-label="비밀번호 표시">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/></svg>
              </button>
            </div>
          </div>

          <div class="register-agreements">
            <div class="register-agreement-row">
              <label class="register-check">
                <input type="checkbox" name="terms" value="1" required>
                <span>이용약관에 동의합니다. <em>(필수)</em></span>
              </label>
              <button type="button" class="register-agree-view" data-doc="terms">보기</button>
            </div>
            <div class="register-agreement-row">
              <label class="register-check">
                <input type="checkbox" name="privacy" value="1" required>
                <span>개인정보 수집 및 이용에 동의합니다. <em>(필수)</em></span>
              </label>
              <button type="button" class="register-agree-view" data-doc="privacy">보기</button>
            </div>
            <div class="register-agreement-row">
              <label class="register-check">
                <input type="checkbox" name="marketing" value="1">
                <span>마케팅 정보 수신에 동의합니다. <em>(선택)</em></span>
              </label>
              <button type="button" class="register-agree-view" data-doc="marketing">보기</button>
            </div>
          </div>

          <button class="login-submit register-submit" type="submit">회원가입</button>
        </form>

        <aside class="register-benefits" aria-label="라벨업 혜택">
          <h2 class="register-benefits-title">라벨업에서 누릴 수 있는 혜택</h2>
          <ul class="register-benefits-list">
            <li>
              <span class="register-benefits-ic" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 3l1.4 4.3H18l-3.6 2.6 1.4 4.3L12 11.6 8.2 14.2l1.4-4.3L6 7.3h4.6L12 3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
              </span>
              <span>AI 라벨 디자인</span>
            </li>
            <li>
              <span class="register-benefits-ic" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="4" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="4" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/><rect x="13" y="13" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.6"/></svg>
              </span>
              <span>다양한 템플릿</span>
            </li>
            <li>
              <span class="register-benefits-ic" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6"/><path d="M16 16l4.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
              </span>
              <span>정확한 규격 검색</span>
            </li>
            <li>
              <span class="register-benefits-ic" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M7 18H4V8h16v10h-3" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><rect x="7" y="14" width="10" height="6" rx="1" stroke="currentColor" stroke-width="1.6"/><path d="M7 11h10" stroke="currentColor" stroke-width="1.6"/></svg>
              </span>
              <span>빠른 인쇄 서비스</span>
            </li>
            <li>
              <span class="register-benefits-ic" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 3l7 3v6c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V6l7-3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </span>
              <span>안전한 데이터 관리</span>
            </li>
          </ul>
        </aside>
      </div>

      <p class="register-legal">가입하면 라벨업의 <button type="button" class="register-agree-link" data-doc="terms">이용약관</button> 및 <button type="button" class="register-agree-link" data-doc="privacy">개인정보 처리방침</button>에 동의하게 됩니다.</p>
    </div>
  </section>
</div>

<div id="legalModal" class="legal-modal" hidden aria-hidden="true">
  <div class="legal-modal-backdrop" data-close="legal"></div>
  <div class="legal-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="legalModalTitle">
    <div class="legal-modal-head">
      <h3 id="legalModalTitle">약관</h3>
      <button type="button" class="legal-modal-close" data-close="legal" aria-label="닫기">×</button>
    </div>
    <div id="legalModalBody" class="legal-modal-body"></div>
    <div class="legal-modal-foot">
      <button type="button" class="login-submit legal-modal-ok" data-close="legal">확인</button>
    </div>
  </div>
</div>
<script src="<?= js('auth.js') ?>"></script>
</body>
</html>
