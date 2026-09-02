<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>접근 권한 없음 — 라벨업</title>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('auth.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card" style="text-align:center">
    <a class="brand-link" href="<?= url('/') ?>"><img src="<?= asset('logo.png') ?>" alt="LABEL UP"> 라벨업</a>
    <h1>403</h1>
    <p class="sub">관리자 권한이 필요한 페이지입니다.</p>
    <div class="auth-links" style="margin-top:24px">
      <a href="<?= url('/') ?>">홈으로</a> · <a href="<?= url('admin/login') ?>">관리자 로그인</a>
    </div>
  </div>
</div>
</body>
</html>
