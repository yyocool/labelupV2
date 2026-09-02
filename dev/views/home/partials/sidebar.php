<?php
/** @var string $activeNav */
$activeNav = $activeNav ?? '';
$isShopActive = $activeNav === 'shop';
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
    <div class="group-title">디자인 도구</div>
    <nav class="menu">
      <a href="<?= url('editor/') ?>"><span class="ico">▧</span>라벨 디자인</a>
      <a href="#"><span class="ico">▦</span>템플릿</a>
      <a href="<?= url('shop/products') ?>?q="><span class="ico">⌕</span>규격 검색</a>
      <a href="#"><span class="ico">⌘</span>바코드 / QR</a>
      <a href="#"><span class="ico">⌘</span>데이터 연동</a>
      <a href="#"><span class="ico">⊠</span>이미지 편집</a>
    </nav>
  </div>

  <div class="group">
    <div class="group-title">쇼핑 & 주문</div>
    <nav class="menu">
      <a class="<?= $isShopActive ? 'is-active' : '' ?>" href="<?= url('shop') ?>"><span class="ico">🛒</span>쇼핑몰</a>
      <a href="#"><span class="ico">◇</span>맞춤 제작</a>
      <a href="<?= url('shop/cart') ?>"><span class="ico">▧</span>간편 주문</a>
    </nav>
  </div>

  <div class="group">
    <div class="group-title">관리</div>
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
