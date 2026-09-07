<?php
/** @var string $activeNav */
$activeNav = $activeNav ?? '';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim((string) $path, '/') ?: '/';
$isHomeActive = $activeNav === 'home' || $path === '/' || $path === '';
$isDesignActive = str_starts_with($path, '/editor');
$isShopBuyActive = $activeNav === 'shop' && !str_contains($path, '/cart');
$isCartActive = str_contains($path, '/shop/cart');
$isAccountActive = $activeNav === 'account';
$isFaqActive = $activeNav === 'faq';
?>
<aside class="sidebar">
  <div class="brand">
    <a href="<?= url('/') ?>"><img class="brand-img" src="<?= asset('logo.png') ?>" alt="LABEL UP"></a>
    <small>with AI - 라벨업</small>
  </div>
  <a class="create" href="<?= url('editor/') ?>">✎ &nbsp;새 디자인 만들기</a>

  <div class="group">
    <nav class="menu">
      <a class="<?= $isHomeActive ? 'is-active' : '' ?>" href="<?= url('/') ?>"><span class="ico">⌂</span>홈</a>
      <a class="<?= $isDesignActive ? 'is-active' : '' ?>" href="<?= url('editor/') ?>"><span class="ico">▧</span>라벨디자인</a>
    </nav>
  </div>

  <div class="group">
    <div class="group-title">쇼핑 &amp; 주문</div>
    <nav class="menu">
      <a class="<?= $isShopBuyActive ? 'is-active' : '' ?>" href="<?= url('shop') ?>"><span class="ico">🛒</span>라벨 구매</a>
      <a class="<?= $isCartActive ? 'is-active' : '' ?>" href="<?= url('shop/cart') ?>"><span class="ico">▧</span>장바구니</a>
      <a href="<?= url('account') ?>#orders"><span class="ico">◎</span>간편주문</a>
    </nav>
  </div>

  <div class="group">
    <div class="group-title">마이라벨업</div>
    <nav class="menu">
      <a class="<?= $isAccountActive ? 'is-account-active' : '' ?>" href="<?= url('account') ?>"><span class="ico">◎</span>마이페이지</a>
      <a class="<?= $isFaqActive ? 'is-account-active' : '' ?>" href="<?= url('faq') ?>"><span class="ico">?</span>FAQ</a>
      <a href="#"><span class="ico">▱</span>프로젝트</a>
      <a href="#"><span class="ico">▱</span>내 보관함</a>
      <a href="#"><span class="ico">♲</span>휴지통</a>
    </nav>
  </div>

  <div class="sidebar-bottom">
    <?php $sidebarGrade = member_grade_for_user($authUser ?? null); ?>
    <?php if ($sidebarGrade): ?>
    <div class="premium premium--grade" style="--grade-color:<?= e((string) ($sidebarGrade['color'] ?? '#7B2D3E')) ?>">
      <b>회원등급 <?= e((string) $sidebarGrade['name']) ?></b>
      <p><?= e((string) ($sidebarGrade['description'] !== '' ? $sidebarGrade['description'] : '현재 적용 중인 회원등급입니다.')) ?></p>
      <a href="<?= url('account') ?>">마이페이지에서 보기 →</a>
    </div>
    <?php else: ?>
    <div class="premium">
      <b>👑 프리미엄 이용권</b>
      <p>더 많은 기능과 혜택을<br>경험해보세요!</p>
      <a href="<?= url('login') ?>">로그인하고 확인 →</a>
    </div>
    <?php endif; ?>
  </div>
</aside>
