<?php
$page = $page ?? ['mode' => 'generic', 'group' => null, 'products' => [], 'code' => null, 'error' => null, 'should_close' => false];
$group = $page['group'] ?? null;
$codeRow = $page['code'] ?? null;
$authUser = $authUser ?? null;
$isPreview = !empty($isPreview);
$shouldClose = !$isPreview && !empty($page['should_close']);
$hasOffer = $group !== null && !$shouldClose && in_array((string) ($page['mode'] ?? ''), ['code', 'used', 'group'], true);
$couponCode = $codeRow ? (string) ($codeRow['code'] ?? '') : '';
$hasCouponCode = $couponCode !== '';
$creditLabel = ($group && isset($group['credit_amount']) && $group['credit_amount'] !== null)
    ? number_format((int) $group['credit_amount']) . ' C'
    : null;
$accent = (string) ($group['color_hex'] ?? '#e23d2e');
$codeStatus = (string) ($codeRow['status'] ?? '');
$warnMessage = (string) ($page['error'] ?? '쿠폰번호가 링크에 포함되지 않았습니다. 상품에 인쇄된 고유 QR코드로 접속해 주세요.');
$homeUrl = (string) ($homeUrl ?? url('/'));
?>
<!doctype html>
<html lang="ko">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title><?= e($pageTitle ?? '라벨 구매 크레딧 쿠폰 — 라벨업') ?></title>
  <meta name="robots" content="noindex,nofollow">
  <meta name="description" content="라벨용지 구매 고객을 위한 LabelUp 크레딧 쿠폰 페이지">
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= css('brand.css') ?>">
  <link rel="stylesheet" href="<?= css('qr-coupon.css') ?>">
</head>
<body class="qr-event<?= $isPreview ? ' is-preview' : '' ?><?= $hasOffer ? ' has-offer' : '' ?><?= $shouldClose ? ' is-closing' : '' ?>" style="--qr-accent:<?= e($accent) ?>">
  <header class="qr-top">
    <a class="qr-logo" href="<?= url('/') ?>" aria-label="LabelUp 홈">
      <img class="qr-logo-labi" src="<?= asset('labi-icon.png') ?>" alt="" width="56" height="56">
      <img class="qr-logo-mark" src="<?= asset('logo.png') ?>" alt="LABEL UP" width="140" height="38">
    </a>
    <?php if (!$isPreview && !$shouldClose): ?>
    <div class="qr-top-actions">
      <?php if ($authUser): ?>
      <a class="qr-link" href="<?= url('account') ?>"><?= e((string) ($authUser['name'] ?? '마이페이지')) ?></a>
      <?php else: ?>
      <a class="qr-link" href="<?= e($loginUrl ?? url('login')) ?>">로그인</a>
      <a class="qr-chip" href="<?= e($registerUrl ?? url('register')) ?>">회원가입</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </header>

  <main>
    <?php if ($shouldClose): ?>
    <section class="qr-panel qr-panel--warn qr-close-warn" role="alertdialog" aria-labelledby="qrCloseTitle" aria-describedby="qrCloseDesc">
      <h2 id="qrCloseTitle">잘못된 접근입니다</h2>
      <p id="qrCloseDesc"><?= e($warnMessage) ?></p>
      <p class="qr-close-hint">잠시 후 창이 닫힙니다. 닫히지 않으면 아래 버튼을 눌러 주세요.</p>
      <div class="qr-cta-actions">
        <button type="button" class="qr-btn qr-btn--primary" id="qrCloseBtn">창 닫기</button>
        <a class="qr-btn" href="<?= e($homeUrl) ?>">라벨업 홈으로</a>
      </div>
    </section>
    <script>
    (function () {
      var home = <?= json_encode($homeUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
      var msg = <?= json_encode($warnMessage, JSON_UNESCAPED_UNICODE) ?>;
      function goClose() {
        try { window.close(); } catch (e) {}
        setTimeout(function () {
          // 스크립트로 연 창이 아니면 close가 무시되므로 홈으로 이동
          if (!window.closed) {
            location.replace(home);
          }
        }, 250);
      }
      try { window.alert(msg); } catch (e) {}
      var btn = document.getElementById('qrCloseBtn');
      if (btn) btn.addEventListener('click', goClose);
      setTimeout(goClose, 1200);
    })();
    </script>

    <?php elseif ($hasOffer): ?>
    <section class="qr-thanks" aria-label="구매 감사">
      <figure class="qr-thanks-figure">
        <img
          src="<?= asset('qr-coupon/hero.png') ?>"
          alt="라벨업을 구매해 주셔서 감사합니다"
          width="1600"
          height="900"
          fetchpriority="high"
        >
      </figure>
      <div class="qr-thanks-copy">
        <p class="qr-brand">LABEL UP</p>
        <h1>구매해 주셔서<br>감사합니다</h1>
        <p class="qr-lead">
          <?= e((string) ($group['category_name'] ?? '라벨용지')) ?>
          <?php if (!empty($group['sheets_per_pack'])): ?>
          · <?= number_format((int) $group['sheets_per_pack']) ?>매
          <?php endif; ?>
          를 선택해 주셔서 감사드립니다. 아래 쿠폰번호로 크레딧 혜택을 받아 보세요.
        </p>
      </div>
    </section>

    <section class="qr-panel qr-coupon-box">
      <div class="qr-section-head">
        <h2>고객님의 QR 쿠폰번호</h2>
        <p>로그인하시면 이 쿠폰번호로 크레딧이 지급됩니다.</p>
      </div>

      <?php if ($hasCouponCode): ?>
      <div class="qr-coupon-number" aria-label="쿠폰번호">
        <span>쿠폰번호</span>
        <strong><?= e($couponCode) ?></strong>
        <?php if ($codeStatus === 'used'): ?>
        <em class="is-used">사용됨</em>
        <?php elseif ($codeStatus === 'unused'): ?>
        <em class="is-ready">사용 가능</em>
        <?php elseif ($codeStatus === 'disabled'): ?>
        <em class="is-used">사용 불가</em>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <ul class="qr-stats">
        <li>
          <span>상품 분류</span>
          <strong><?= e((string) ($group['category_name'] ?? '-')) ?></strong>
        </li>
        <li>
          <span>매수 / 팩</span>
          <strong><?= number_format((int) ($group['sheets_per_pack'] ?? 0)) ?>매</strong>
        </li>
        <li>
          <span>지급 예정 크레딧</span>
          <strong><?= $creditLabel ? e($creditLabel) : '미정' ?></strong>
        </li>
      </ul>
    </section>

    <section class="qr-panel qr-howto">
      <div class="qr-section-head">
        <h2>크레딧 받는 방법</h2>
        <p>로그인만 하시면 쿠폰번호 기준으로 크레딧이 지급됩니다.</p>
      </div>
      <div class="qr-howto-grid">
        <div class="qr-howto-copy">
          <ol class="qr-steps">
            <li>
              <div class="qr-step-body">
                <strong>상품 내 QR코드 스캔</strong>
                <span>라벨용지 패키지에 인쇄된 QR코드를 스마트폰으로 스캔합니다.</span>
              </div>
            </li>
            <li>
              <div class="qr-step-body">
                <strong>로그인 또는 회원가입</strong>
                <span>라벨업 회원으로 로그인해 주세요. 아직 회원이 아니시면 회원가입을 진행합니다.</span>
              </div>
            </li>
            <li>
              <div class="qr-step-body">
                <strong>크레딧 지급</strong>
                <span>로그인 후 이 페이지로 돌아오면 쿠폰이 적용되어 크레딧이 지급됩니다.</span>
              </div>
            </li>
          </ol>
          <?php if (!$isPreview): ?>
          <div class="qr-cta-actions">
            <?php if ($authUser): ?>
            <p class="qr-howto-note">이미 로그인되어 있습니다. 쿠폰 사용(크레딧 지급) 기능은 곧 제공됩니다.</p>
            <a class="qr-btn qr-btn--primary" href="<?= url('account') ?>">마이페이지에서 크레딧 확인</a>
            <?php else: ?>
            <a class="qr-btn" href="<?= e($loginUrl ?? url('login')) ?>">로그인하고 크레딧 받기</a>
            <a class="qr-btn qr-btn--primary" href="<?= e($registerUrl ?? url('register')) ?>">회원가입 하고 혜택 받기</a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <figure class="qr-howto-figure">
          <img
            src="<?= asset('qr-coupon/howto-credit.png') ?>"
            alt="1 상품 QR 스캔, 2 로그인, 3 크레딧 지급 안내"
            width="1200"
            height="900"
            loading="lazy"
          >
        </figure>
      </div>
    </section>
    <?php endif; ?>
  </main>

  <?php if (!$shouldClose): ?>
  <footer class="qr-foot">
    <nav>
      <a href="<?= url('/') ?>">홈</a>
      <a href="<?= url('shop') ?>">라벨쇼핑</a>
      <a href="<?= url('faq') ?>">FAQ</a>
    </nav>
    <p>© <?= (int) ($year ?? date('Y')) ?> LABEL UP</p>
  </footer>
  <?php endif; ?>
</body>
</html>
