<?php
/** @var array|null $authUser */
$isLoggedIn = !empty($authUser);
$userName = (string) ($authUser['name'] ?? '회원');
$userEmail = (string) ($authUser['email'] ?? '');
$userInitial = mb_substr($userName, 0, 1) ?: '회';
$isAdmin = ($authUser['role'] ?? '') === 'admin';
?>
<?php if ($isLoggedIn): ?>
<div class="profile-menu" id="profileMenu">
  <button
    type="button"
    class="profile-trigger"
    id="profileTrigger"
    aria-expanded="false"
    aria-haspopup="true"
    aria-controls="profileDropdown"
  >
    <span class="profile-avatar" aria-hidden="true"><?= e($userInitial) ?></span>
    <span class="profile-name"><?= e($userName) ?></span>
    <span class="profile-caret" aria-hidden="true">▾</span>
  </button>
  <div class="profile-dropdown" id="profileDropdown" hidden>
    <div class="profile-dropdown-head">
      <strong><?= e($userName) ?></strong>
      <span><?= e($userEmail) ?></span>
    </div>
    <nav class="profile-dropdown-nav" aria-label="사용자 메뉴">
      <a class="profile-dropdown-item" href="<?= url('account') ?>">
        <span class="profile-dropdown-ic">◎</span>
        <span>마이페이지</span>
      </a>
      <a class="profile-dropdown-item" href="<?= url('account') ?>#profile">
        <span class="profile-dropdown-ic">✎</span>
        <span>회원정보 수정</span>
      </a>
      <a class="profile-dropdown-item is-disabled" href="#" tabindex="-1" aria-disabled="true" onclick="return false">
        <span class="profile-dropdown-ic">▣</span>
        <span>내 프로젝트</span>
        <em>준비중</em>
      </a>
      <a class="profile-dropdown-item is-disabled" href="#" tabindex="-1" aria-disabled="true" onclick="return false">
        <span class="profile-dropdown-ic">▤</span>
        <span>내 보관함</span>
        <em>준비중</em>
      </a>
      <a class="profile-dropdown-item" href="<?= url('account') ?>#orders">
        <span class="profile-dropdown-ic">▧</span>
        <span>주문·배송 내역</span>
      </a>
      <a class="profile-dropdown-item is-disabled" href="#" tabindex="-1" aria-disabled="true" onclick="return false">
        <span class="profile-dropdown-ic">♧</span>
        <span>알림 설정</span>
        <em>준비중</em>
      </a>
      <?php if ($isAdmin): ?>
      <div class="profile-dropdown-divider" role="separator"></div>
      <a class="profile-dropdown-item profile-dropdown-item--admin" href="<?= url('admin') ?>">
        <span class="profile-dropdown-ic">⚙</span>
        <span>관리자 콘솔</span>
      </a>
      <?php endif; ?>
      <div class="profile-dropdown-divider" role="separator"></div>
      <a class="profile-dropdown-item profile-dropdown-item--logout" href="<?= url('logout') ?>">
        <span class="profile-dropdown-ic">↪</span>
        <span>로그아웃</span>
      </a>
    </nav>
  </div>
</div>
<?php else: ?>
<a class="login" href="<?= url('login') ?>">로그인 / 회원가입</a>
<?php endif; ?>
