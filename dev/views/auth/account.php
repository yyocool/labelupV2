<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle ?? '마이페이지') ?></title>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('auth.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card" style="width:min(560px,100%)">
    <a class="brand-link" href="<?= url('/') ?>"><img src="<?= asset('logo.png') ?>" alt="LABEL UP"> 라벨업</a>
    <h1>마이페이지</h1>
    <p class="sub"><?= e($user['email'] ?? '') ?> · <?= e($user['role'] === 'admin' ? '관리자' : '일반회원') ?></p>

    <?php if (($user['role'] ?? '') === 'admin'): ?>
    <div class="admin-banner">
      <a href="<?= url('admin') ?>">관리자 콘솔로 이동 →</a>
    </div>
    <?php endif; ?>

    <div class="account-grid">
      <section class="account-section" id="profile">
        <h2>회원정보</h2>
        <div id="profileAlert" class="auth-alert"></div>
        <form id="profileForm">
          <div class="field">
            <label>이름</label>
            <input type="text" name="name" value="<?= e($user['name'] ?? '') ?>" required>
          </div>
          <div class="field">
            <label>연락처</label>
            <input type="text" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="010-0000-0000">
          </div>
          <div class="field">
            <label>회사/상호</label>
            <input type="text" name="company" value="<?= e($user['company'] ?? '') ?>">
          </div>
          <button class="btn-primary" type="submit">정보 저장</button>
        </form>
      </section>

      <section class="account-section">
        <h2>비밀번호 변경</h2>
        <div id="passwordAlert" class="auth-alert"></div>
        <form id="passwordForm">
          <div class="field">
            <label>현재 비밀번호</label>
            <input type="password" name="current_password" required>
          </div>
          <div class="field">
            <label>새 비밀번호</label>
            <input type="password" name="new_password" required minlength="8">
          </div>
          <button class="btn-secondary" type="submit">비밀번호 변경</button>
        </form>
      </section>

      <section class="account-section">
        <h2>회원탈퇴</h2>
        <div id="withdrawAlert" class="auth-alert"></div>
        <form id="withdrawForm">
          <div class="field">
            <label>비밀번호 확인</label>
            <input type="password" name="password" required>
          </div>
          <button class="btn-secondary btn-danger" type="submit">회원탈퇴</button>
        </form>
      </section>
    </div>

    <div class="auth-links" style="margin-top:20px">
      <a href="<?= url('/') ?>">홈으로</a> · <a href="<?= url('logout') ?>">로그아웃</a>
    </div>
  </div>
</div>
<script src="<?= js('auth.js') ?>"></script>
</body>
</html>
