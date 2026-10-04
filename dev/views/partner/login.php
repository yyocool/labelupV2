<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle ?? '협력사 로그인') ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('admin.css') ?>">
</head>
<body class="admin-login-body">
<div class="admin-login-wrap">
  <div class="admin-login-card">
    <div class="admin-login-brand-bar">
      <img src="<?= asset('logo-admin.svg') ?>" alt="LABEL UP">
    </div>
    <div class="admin-login-inner">
      <h1>협력사 로그인</h1>
      <p class="sub">협력사에 발급된 아이디와 비밀번호로 로그인합니다. 일반 회원 계정과는 별도입니다.</p>
      <?php if (!empty($error)): ?>
      <div class="admin-login-alert show error"><?= e((string) $error) ?></div>
      <?php endif; ?>
      <form method="post" action="<?= url('partner/login') ?>">
        <div class="field">
          <label>아이디</label>
          <input type="text" name="login_id" required autocomplete="username" value="<?= e((string) ($loginId ?? '')) ?>" placeholder="발급된 아이디">
        </div>
        <div class="field">
          <label>비밀번호</label>
          <input type="password" name="password" required autocomplete="current-password" placeholder="비밀번호">
        </div>
        <button class="btn-admin-login" type="submit">로그인</button>
      </form>
      <div class="admin-login-foot">
        <a href="<?= url('/') ?>">← 라벨업 사이트로</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
