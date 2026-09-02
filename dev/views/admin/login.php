<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle ?? '관리자 로그인') ?></title>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('admin.css') ?>">
</head>
<body class="admin-login-body">
<div class="admin-login-wrap">
  <div class="admin-login-card">
    <div class="admin-login-brand-bar">
      <img src="<?= asset('logo-admin.svg') ?>" alt="LABEL UP ADMIN">
    </div>
    <div class="admin-login-inner">
    <h1>관리자 로그인</h1>
    <p class="sub">관리자 전용 페이지입니다. 일반 회원은 <a href="<?= url('login') ?>">사용자 로그인</a>을 이용하세요.</p>

    <?php if (!empty($memberLoggedIn)): ?>
    <div class="admin-login-notice">
      일반 회원으로 로그인 중입니다. 관리자 계정으로 다시 로그인해 주세요.
      <a href="<?= url('logout') ?>">사용자 로그아웃</a>
    </div>
    <?php endif; ?>

    <div id="adminLoginAlert" class="admin-login-alert"></div>
    <form id="adminLoginForm" data-redirect="<?= url('admin') ?>">
      <div class="field">
        <label>관리자 이메일</label>
        <input type="email" name="email" required autocomplete="username" placeholder="admin@labelup.kr">
      </div>
      <div class="field">
        <label>비밀번호</label>
        <input type="password" name="password" required autocomplete="current-password" placeholder="비밀번호">
      </div>
      <label class="check-row"><input type="checkbox" name="remember" value="1"> 로그인 상태 유지</label>
      <button class="btn-admin-login" type="submit">관리자 로그인</button>
    </form>
    <div class="admin-login-foot">
      <a href="<?= url('/') ?>">← 사용자 사이트로</a>
    </div>
    </div>
  </div>
</div>
<script src="<?= js('admin-auth.js') ?>"></script>
</body>
</html>
