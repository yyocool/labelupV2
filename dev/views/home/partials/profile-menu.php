<?php
/** @var array|null $authUser */
$isLoggedIn = !empty($authUser);
$userName = (string) ($authUser['name'] ?? '회원');
$userEmail = (string) ($authUser['email'] ?? '');
$userInitial = mb_substr($userName, 0, 1) ?: '회';
$isAdmin = ($authUser['role'] ?? '') === 'admin';
$memberGrade = $isLoggedIn ? member_grade_for_user($authUser) : null;
$menuBalance = $isLoggedIn ? credit_balance_for_user($authUser) : 0;
$menuIcons = require view_path('home/partials/menu-icons.php');
$menuIcon = static fn (string $key): string => '<span class="profile-dropdown-ic" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22">' . $menuIcons[$key] . '</svg></span>';
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
      <?php if ($memberGrade): ?>
      <em class="profile-grade-badge" style="--grade-color:<?= e((string) ($memberGrade['color'] ?? '#7B2D3E')) ?>"><?= e((string) $memberGrade['name']) ?></em>
      <?php endif; ?>
    </div>
    <nav class="profile-dropdown-nav" aria-label="사용자 메뉴">
      <div class="profile-dropdown-mobile">
        <a class="profile-dropdown-item profile-dropdown-credit<?= $menuBalance < 0 ? ' is-debt' : '' ?>" href="<?= url('account') ?>#credits">
          <?= $menuIcon('ink') ?>
          <span><?= $menuBalance < 0 ? '미정산' : '내 잉크' ?></span>
          <strong><?= e(number_format($menuBalance)) ?> C</strong>
        </a>
        <button type="button" class="profile-dropdown-item" data-bug-report-open>
          <?= $menuIcon('bug') ?>
          <span>오류&amp;제안</span>
        </button>
        <div class="profile-dropdown-divider" role="separator"></div>
      </div>
      <a class="profile-dropdown-item" href="<?= url('account') ?>">
        <?= $menuIcon('mypage') ?>
        <span>마이페이지</span>
      </a>
      <a class="profile-dropdown-item" href="<?= url('account') ?>#profile">
        <?= $menuIcon('profile') ?>
        <span>회원정보 수정</span>
      </a>
      <a class="profile-dropdown-item" href="<?= url('projects') ?>">
        <?= $menuIcon('project') ?>
        <span>내 프로젝트</span>
      </a>
      <a class="profile-dropdown-item" href="<?= url('locker') ?>">
        <?= $menuIcon('locker') ?>
        <span>내 보관함</span>
      </a>
      <a class="profile-dropdown-item" href="<?= url('account/orders') ?>">
        <?= $menuIcon('order') ?>
        <span>주문·배송 내역</span>
      </a>
      <a class="profile-dropdown-item" href="<?= url('account') ?>#notifications">
        <?= $menuIcon('notify') ?>
        <span>알림 설정</span>
      </a>
      <?php if ($isAdmin): ?>
      <div class="profile-dropdown-divider" role="separator"></div>
      <a class="profile-dropdown-item profile-dropdown-item--admin" href="<?= url('admin') ?>">
        <?= $menuIcon('admin') ?>
        <span>관리자 콘솔</span>
      </a>
      <?php endif; ?>
      <div class="profile-dropdown-divider" role="separator"></div>
      <a class="profile-dropdown-item profile-dropdown-item--logout" href="<?= url('logout') ?>">
        <?= $menuIcon('logout') ?>
        <span>로그아웃</span>
      </a>
    </nav>
  </div>
</div>
<?php else: ?>
<a class="login" href="<?= url('login') ?>">로그인 / 회원가입</a>
<?php endif; ?>
