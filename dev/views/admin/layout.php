<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle ?? '관리자 — 라벨업') ?></title>
  <meta name="robots" content="noindex,nofollow">
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('admin.css') ?>">
  <?php if (!empty($useSummernote)): ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
  <?php endif; ?>
</head>
<body class="admin-body">
<div class="admin-app" id="adminApp">
<script>try{if(localStorage.getItem('labelup_admin_lnb_collapsed')==='1')document.getElementById('adminApp').classList.add('is-lnb-collapsed')}catch(e){}</script>
  <aside class="admin-lnb" id="adminLnb">
    <div class="admin-lnb-brand">
      <a href="<?= url('/') ?>" class="admin-lnb-brand-link">
        <img class="admin-logo-full" src="<?= asset('logo-admin.svg') ?>" alt="LABEL UP ADMIN">
        <img class="admin-logo-mini" src="<?= asset('logo-admin-mark.svg') ?>" alt="LABEL UP">
      </a>
    </div>
    <nav class="admin-lnb-nav">
      <a class="admin-lnb-item<?= ($activeMenu ?? '') === 'dashboard' ? ' is-active' : '' ?>" href="<?= url('admin') ?>" title="대시보드">
        <span class="ic">▣</span><span class="label">대시보드</span>
      </a>
      <?php require view_path('admin/partials/shop-menu.php'); ?>
      <?php require view_path('admin/partials/content-menu.php'); ?>
      <?php require view_path('admin/partials/ai-menu.php'); ?>
      <?php require view_path('admin/partials/ops-menu.php'); ?>
      <?php require view_path('admin/partials/settings-menu.php'); ?>
      <?php if (admin_can_menu('qr-coupons')): ?>
      <a class="admin-lnb-item<?= ($activeMenu ?? '') === 'qr-coupons' ? ' is-active' : '' ?>" href="<?= url('admin/qr-coupons') ?>" title="QR쿠폰관리">
        <span class="ic">▦</span><span class="label">QR쿠폰관리</span>
      </a>
      <?php endif; ?>
    </nav>
    <div class="admin-lnb-foot">LabelUp Admin v1.0</div>
  </aside>

  <button type="button" class="admin-lnb-toggle" id="adminLnbToggle" aria-label="사이드바 접기" aria-expanded="true" title="사이드바 접기">
    <span class="admin-lnb-toggle-icon" aria-hidden="true">‹</span>
  </button>

  <div class="admin-main">
    <header class="admin-topbar">
      <?php
        $adminFavoriteSlots = [];
        try {
            if (!empty($user['id'])) {
                $adminFavoriteSlots = (new \App\Services\AdminFavoriteService())->slotsFor((int) $user['id']);
            }
        } catch (\Throwable) {
            $adminFavoriteSlots = [];
        }
        if ($adminFavoriteSlots === []) {
            for ($i = 1; $i <= 10; $i++) {
                $adminFavoriteSlots[] = ['slot' => $i, 'menu_key' => null, 'label' => null, 'href' => null, 'ic' => null];
            }
        }
      ?>
      <div class="admin-favs" id="adminFavs" aria-label="즐겨찾기">
        <?php foreach ($adminFavoriteSlots as $slot): ?>
          <?php if (!empty($slot['href'])): ?>
          <a class="admin-fav-slot is-filled" href="<?= e((string) $slot['href']) ?>" title="<?= e((string) ($slot['label'] ?? '')) ?>" data-slot="<?= (int) $slot['slot'] ?>" data-key="<?= e((string) ($slot['menu_key'] ?? '')) ?>">
            <span class="ic"><?= e((string) ($slot['ic'] ?? '★')) ?></span>
            <span class="lbl"><?= e((string) ($slot['label'] ?? '')) ?></span>
            <button type="button" class="admin-fav-clear js-fav-clear" data-slot="<?= (int) $slot['slot'] ?>" aria-label="즐겨찾기 제거">×</button>
          </a>
          <?php else: ?>
          <button type="button" class="admin-fav-slot js-fav-pick" data-slot="<?= (int) $slot['slot'] ?>" title="즐겨찾기 추가">+</button>
          <?php endif; ?>
        <?php endforeach; ?>
        <button type="button" class="admin-fav-edit js-fav-edit" title="즐겨찾기 편집">편집</button>
      </div>
      <div class="admin-crumb">
        <?php
          $crumb = $crumbTitle ?? match ($activeMenu ?? '') {
              'users' => '운영관리 › 회원 관리',
              'settings' => '운영관리 › 운영설정',
              'shop-categories' => '쇼핑몰운영 › 카테고리',
              'shop-specs' => '쇼핑몰운영 › 용지 규격',
              'shop-products' => '쇼핑몰운영 › 상품 관리',
              'shop-orders' => '쇼핑몰운영 › 주문 관리',
              'shop-shipping' => '쇼핑몰운영 › 배송 관리',
              'shop-coupons' => '쇼핑몰운영 › 쿠폰·프로모션',
              'shop-banners' => '쇼핑몰운영 › 배너·전시',
              'content-cliparts' => '컨텐츠관리 › 클립아트관리',
              'content-user-designs' => '컨텐츠관리 › 사용자디자인',
              'content-templates' => '컨텐츠관리 › 템플릿관리',
              'ops-credit-rewards' => '운영관리 › 크레딧보상 관리',
              'ops-purchase-credits' => '운영관리 › 구매크레딧',
              'ops-hero-slides' => '운영관리 › 히어로 이미지 관리',
              'ops-event-popups' => '운영관리 › 이벤트 팝업관리',
              'ops-faq' => '운영관리 › FAQ 관리',
              'ops-inquiries' => '운영관리 › 1:1 문의',
              'ai-example-prompts' => 'AI 관리 › 예시프롬프트 관리',
              'ai-usage' => 'AI 관리 › 사용량 통계',
              'settings-admins' => '설정 › 관리자',
              'settings-member-grades' => '설정 › 회원등급 설정',
              'settings-seo' => '설정 › SEO 설정',
              'settings-tracking' => '설정 › 광고 스크립트',
              'qr-coupons' => 'QR쿠폰관리',
              default => '대시보드',
          };
        ?>
        관리자 › <b><?= e($crumb) ?></b>
      </div>
      <div class="admin-top-actions">
        <button type="button" class="admin-icon-btn" id="adminFullscreenBtn" title="전체화면" aria-label="전체화면">⛶</button>
        <div class="admin-bell-wrap" id="adminBellWrap">
          <button type="button" class="admin-icon-btn" id="adminBellBtn" title="알림" aria-label="알림">
            <span aria-hidden="true">🔔</span>
            <span class="admin-bell-badge" id="adminBellBadge" hidden>0</span>
          </button>
          <div class="admin-bell-panel" id="adminBellPanel" hidden>
            <div class="admin-bell-head">
              <strong>새 알림</strong>
              <button type="button" class="admin-top-link" id="adminBellAck">모두 읽음</button>
            </div>
            <div class="admin-bell-list" id="adminBellList">
              <p class="admin-bell-empty">새 주문·문의가 없습니다.</p>
            </div>
          </div>
        </div>
        <a class="admin-top-link" href="<?= url('/') ?>">사이트 보기</a>
        <div class="admin-top-user" id="adminProfileWrap">
          <button type="button" class="admin-top-user-btn" id="adminProfileBtn" aria-expanded="false" aria-haspopup="true" aria-controls="adminProfileMenu">
            <span class="av"><?= e(mb_substr($user['name'] ?? '관', 0, 1)) ?></span>
            <span><?= e($user['name'] ?? '관리자') ?></span>
            <span class="admin-top-user-caret" aria-hidden="true">▾</span>
          </button>
          <div class="admin-profile-menu" id="adminProfileMenu" hidden>
            <div class="admin-profile-menu__head">
              <strong><?= e($user['name'] ?? '관리자') ?></strong>
              <span><?= e($user['email'] ?? '') ?></span>
            </div>
            <button type="button" class="admin-profile-menu__item" id="adminPasswordBtn">비밀번호 변경</button>
            <a class="admin-profile-menu__item admin-profile-menu__item--logout" href="<?= url('admin/logout') ?>">로그아웃</a>
          </div>
        </div>
      </div>
    </header>

    <div class="admin-content">
      <div id="adminToast" class="admin-alert admin-alert--toast"></div>
      <?php require view_path(str_replace('.', '/', $contentTemplate) . '.php'); ?>
    </div>
  </div>
</div>
<?php if (!empty($useSummernote)): ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>
<?php endif; ?>
<div class="admin-modal" id="adminPasswordModal" hidden>
  <div class="admin-modal-backdrop" data-close="adminPasswordModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="adminPasswordTitle">
    <div class="admin-modal-head">
      <h2 id="adminPasswordTitle">비밀번호 변경</h2>
      <button type="button" class="admin-modal-close" data-close="adminPasswordModal" aria-label="닫기">×</button>
    </div>
    <form id="adminPasswordForm" class="admin-modal-body">
      <label class="admin-field">
        <span>현재 비밀번호</span>
        <input class="admin-input" type="password" name="current_password" required autocomplete="current-password">
      </label>
      <label class="admin-field">
        <span>새 비밀번호</span>
        <input class="admin-input" type="password" name="new_password" required minlength="8" autocomplete="new-password">
        <small>8자 이상, 영문과 숫자 포함</small>
      </label>
      <label class="admin-field">
        <span>새 비밀번호 확인</span>
        <input class="admin-input" type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password">
      </label>
      <div class="admin-head-actions" style="margin-top:16px">
        <button type="submit" class="admin-btn admin-btn--primary">변경하기</button>
      </div>
    </form>
  </div>
</div>
<div class="admin-modal" id="adminFavModal" hidden>
  <div class="admin-modal-backdrop" data-close="adminFavModal"></div>
  <div class="admin-modal-panel" role="dialog" aria-modal="true">
    <div class="admin-modal-head">
      <h2 id="adminFavModalTitle">즐겨찾기 메뉴 선택</h2>
      <button type="button" class="admin-modal-close" data-close="adminFavModal" aria-label="닫기">×</button>
    </div>
    <div class="admin-modal-body" id="adminFavModalBody"></div>
  </div>
</div>
<script>
window.LABELUP_ADMIN_MENUS = <?= json_encode(array_values(array_filter(admin_menu_catalog(), static fn (array $item): bool => admin_can_menu($item['key']))), JSON_UNESCAPED_UNICODE) ?>;
window.LABELUP_ADMIN_FAVS = <?= json_encode($adminFavoriteSlots ?? [], JSON_UNESCAPED_UNICODE) ?>;
window.LABELUP_ADMIN_ORDERS_URL = <?= json_encode(url('admin/shop/orders'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.LABELUP_ADMIN_INQUIRIES_URL = <?= json_encode(url('admin/ops/inquiries'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= js('admin.js') ?>"></script>
<?php if (in_array((string) ($activeMenu ?? ''), ['settings-seo', 'settings-tracking'], true)): ?>
<script src="<?= js('admin-seo.js') ?>"></script>
<?php endif; ?>
<?php if (($activeMenu ?? '') === 'settings-member-grades'): ?>
<script src="<?= js('admin-grades.js') ?>"></script>
<?php endif; ?>
</body>
</html>
