<?php
/** @var array<string, mixed> $dash */
/** @var App\Services\AccountService $accountService */
$user = $dash['user'];
$plan = $dash['plan'];
$grade = $dash['grade'] ?? ['name' => $plan['name'] ?? '일반', 'color' => '#7B2D3E', 'description' => ''];
$stats = $dash['stats'];
$usage = $dash['usage'];
$initial = mb_substr((string) ($user['name'] ?? '회'), 0, 1);
$usagePct = min(100, (int) round(($usage['used'] / max(1, $usage['limit'])) * 100));
?>
<section class="account-hero card">
  <div class="account-hero-user">
    <div class="account-avatar"><?= e($initial) ?></div>
    <div>
      <div class="account-name-row">
        <h1><?= e((string) ($user['name'] ?? '회원')) ?>님</h1>
        <span class="account-plan-badge" style="--grade-color:<?= e((string) ($grade['color'] ?? '#7B2D3E')) ?>"><?= e((string) ($grade['name'] ?? '일반')) ?></span>
      </div>
      <p class="account-meta"><?= e((string) ($user['email'] ?? '')) ?></p>
      <p class="account-meta"><?= e((string) ($user['phone'] ?? '연락처 미등록')) ?></p>
      <div class="account-hero-btns">
        <button type="button" class="account-btn account-btn--outline" data-open-modal="profileModal">회원정보 수정</button>
        <button type="button" class="account-btn account-btn--outline" data-open-modal="passwordModal">비밀번호 변경</button>
      </div>
    </div>
  </div>
  <div class="account-hero-plan">
    <p class="account-grade-kicker">회원등급</p>
    <h2><?= e((string) ($grade['name'] ?? '일반')) ?></h2>
    <p class="account-plan-period"><?= e((string) (($grade['description'] ?? '') !== '' ? $grade['description'] : '현재 적용 중인 회원등급입니다.')) ?></p>
    <div class="account-usage">
      <div class="account-usage-head">
        <span><?= e($usage['label']) ?></span>
        <span><?= (int) $usage['used'] ?> / <?= (int) $usage['limit'] ?></span>
      </div>
      <div class="account-usage-bar"><span style="width:<?= $usagePct ?>%"></span></div>
    </div>
  </div>
  <div class="account-hero-stats">
    <a class="account-stat" href="#credits"><span class="account-stat-ic purple">C</span><span class="account-stat-val"><?= number_format((int) $stats['points']) ?> C</span><span class="account-stat-label">크레딧</span></a>
    <a class="account-stat" href="<?= url('shop/cart') ?>"><span class="account-stat-ic green">🎫</span><span class="account-stat-val"><?= (int) $stats['coupons'] ?></span><span class="account-stat-label">쿠폰</span></a>
    <a class="account-stat" href="#orders"><span class="account-stat-ic yellow">📦</span><span class="account-stat-val"><?= (int) $stats['orders'] ?></span><span class="account-stat-label">최근 주문</span></a>
    <a class="account-stat" href="#orders"><span class="account-stat-ic blue">🚚</span><span class="account-stat-val"><?= (int) $stats['shipping'] ?></span><span class="account-stat-label">배송중</span></a>
  </div>
  <div class="account-quick-links">
    <?php foreach ($dash['quickLinks'] as $link): ?>
    <a class="account-quick-link<?= !empty($link['disabled']) ? ' is-disabled' : '' ?>" href="<?= e($link['href'] ?? '#') ?>">
      <span class="ic"><?= $link['ic'] ?></span>
      <span><?= e($link['label']) ?></span>
      <?php if (!empty($link['badge'])): ?><span class="account-quick-badge"><?= (int) $link['badge'] ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="account-shortcuts card">
  <h2 class="account-section-title">바로가기</h2>
  <div class="account-shortcut-row">
    <?php foreach ($dash['shortcuts'] as $s): ?>
    <a class="account-shortcut<?= !empty($s['disabled']) ? ' is-disabled' : '' ?>" href="<?= e($s['href']) ?>">
      <span class="ic"><?= $s['ic'] ?></span>
      <span><?= e($s['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<div class="account-grid-main">
  <section class="account-panel card account-panel--wide">
    <div class="account-panel-head">
      <h2>최근 디자인</h2>
      <a href="<?= url('editor/') ?>">전체 보기 →</a>
    </div>
    <div class="account-design-grid">
      <?php if (empty($dash['recentDesigns'])): ?>
      <p class="account-empty account-design-empty">아직 저장된 디자인이 없습니다. 편집기에서 저장하면 여기에 미리보기가 표시됩니다.</p>
      <?php endif; ?>
      <?php foreach ($dash['recentDesigns'] as $d): ?>
      <a class="account-design-card" href="<?= e((string) ($d['href'] ?? url('editor/'))) ?>">
        <div class="account-design-thumb">
          <?php if (!empty($d['thumb'])): ?>
          <img src="<?= e((string) $d['thumb']) ?>" alt="<?= e((string) $d['name']) ?>">
          <?php else: ?>
          <span class="account-design-fallback">라벨</span>
          <?php endif; ?>
        </div>
        <span class="account-design-status is-editing">편집중</span>
        <strong><?= e((string) $d['name']) ?></strong>
        <?php if (!empty($d['updated_label'])): ?>
        <em class="account-design-updated"><?= e((string) $d['updated_label']) ?></em>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
      <a class="account-design-card account-design-new" href="<?= url('editor/') ?>">
        <span>＋</span>
        <strong>새 디자인</strong>
      </a>
    </div>
  </section>

  <section class="account-panel card" id="orders">
    <div class="account-panel-head">
      <h2>최근 주문 내역</h2>
      <a href="<?= url('shop/cart') ?>">더보기 →</a>
    </div>
    <ul class="account-order-list">
      <?php foreach ($dash['recentOrders'] as $order): ?>
      <li>
        <img src="<?= e($order['thumb']) ?>" alt="">
        <div class="account-order-info">
          <strong><?= e($order['name']) ?></strong>
          <span><?= e($order['date']) ?> · <?= number_format((int) $order['total']) ?>원</span>
        </div>
        <span class="account-order-status <?= e($accountService->orderStatusClass($order['status'])) ?>"><?= e($order['status_label']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<section class="account-panel card" id="cliparts">
  <div class="account-panel-head">
    <h2>내 클립아트</h2>
    <a href="<?= url('/') ?>">AI로 더 만들기 →</a>
  </div>
  <?php $myCliparts = $dash['cliparts'] ?? []; ?>
  <?php if (empty($myCliparts)): ?>
  <p class="account-empty">아직 생성한 클립아트가 없습니다. 홈에서 라비에게 그림을 요청하면 여기에 모아집니다.</p>
  <?php else: ?>
  <p class="account-meta" style="margin-top:-6px;margin-bottom:12px">총 <b><?= number_format((int) ($dash['clipartCount'] ?? count($myCliparts))) ?></b>개</p>
  <div class="account-clip-grid">
    <?php foreach ($myCliparts as $clip): ?>
    <?php
      $clipUrl = (string) ($clip['image_url'] ?? '');
      $clipTitle = (string) ($clip['title'] ?? '라비가 그린 클립아트');
      $clipEdit = (string) ($clip['editor_url'] ?? url('editor/'));
      $clipDate = substr((string) ($clip['created_at'] ?? ''), 0, 16);
    ?>
    <article class="account-clip-card">
      <button type="button" class="account-clip-thumb js-clip-preview" data-src="<?= e($clipUrl) ?>" data-title="<?= e($clipTitle) ?>" data-edit="<?= e($clipEdit) ?>">
        <img src="<?= e($clipUrl) ?>" alt="<?= e($clipTitle) ?>">
      </button>
      <strong><?= e($clipTitle) ?></strong>
      <span class="account-meta"><?= e($clipDate) ?></span>
      <div class="account-clip-actions">
        <button type="button" class="account-btn account-btn--outline js-clip-preview" data-src="<?= e($clipUrl) ?>" data-title="<?= e($clipTitle) ?>" data-edit="<?= e($clipEdit) ?>">확대보기</button>
        <a class="account-btn account-btn--primary" href="<?= e($clipEdit) ?>">바로편집</a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<div class="account-grid-sub">
  <section class="account-panel card">
    <h2 class="account-section-title">라벨 편집 도구</h2>
    <ul class="account-link-list">
      <?php foreach ($dash['tools'] as $tool): ?>
      <li><a class="<?= !empty($tool['disabled']) ? 'is-disabled' : '' ?>" href="<?= e($tool['href']) ?>"><span><?= $tool['ic'] ?></span><?= e($tool['label']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="account-panel card">
    <h2 class="account-section-title">내 템플릿</h2>
    <ul class="account-template-list">
      <?php foreach ($dash['templates'] as $tpl): ?>
      <li>
        <img src="<?= e($tpl['thumb']) ?>" alt="">
        <span><?= e($tpl['name']) ?></span>
        <em><?= (int) $tpl['count'] ?></em>
      </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="account-panel card">
    <h2 class="account-section-title">브랜드 관리</h2>
    <ul class="account-brand-list">
      <?php foreach ($dash['brands'] as $brand): ?>
      <li><span class="account-brand-mark"><?= e($brand['initial']) ?></span><?= e($brand['name']) ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="account-btn account-btn--outline account-btn--block" disabled>＋ 새 브랜드 추가</button>
  </section>

  <section class="account-panel card" id="address">
    <h2 class="account-section-title">배송지 관리</h2>
    <div id="accountAddressList">
      <?php foreach (($dash['addresses'] ?? []) as $addr): ?>
      <article class="account-address" data-address-id="<?= (int) $addr['id'] ?>">
        <span class="account-address-label"><?= e($addr['label']) ?><?= !empty($addr['is_default']) ? ' · 기본' : '' ?></span>
        <strong><?= e($addr['recipient_name']) ?></strong>
        <p><?= e($addr['recipient_phone']) ?></p>
        <p><?= e($addr['address_line']) ?></p>
        <div class="account-address-actions">
          <?php if (empty($addr['is_default'])): ?>
          <button type="button" class="account-btn account-btn--outline" data-address-default="<?= (int) $addr['id'] ?>">기본</button>
          <?php endif; ?>
          <button type="button" class="account-btn account-btn--outline" data-address-edit="<?= (int) $addr['id'] ?>">수정</button>
          <button type="button" class="account-btn account-btn--danger" data-address-del="<?= (int) $addr['id'] ?>">삭제</button>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <p class="account-empty" id="accountAddressEmpty" <?= empty($dash['addresses']) ? '' : 'hidden' ?>>저장된 배송지가 없습니다. 자주 쓰는 주소를 등록해 두세요.</p>
    <button type="button" class="account-btn account-btn--outline account-btn--block" data-address-add>＋ 배송지 추가</button>
  </section>

  <section class="account-panel card">
    <h2 class="account-section-title">계정 · 설정</h2>
    <div class="account-settings-grid">
      <button type="button" data-open-modal="profileModal"><span>◎</span>회원정보</button>
      <button type="button" data-open-modal="notifPrefsModal"><span>♧</span>알림 설정</button>
      <button type="button" disabled title="준비 중"><span>💳</span>결제·구독</button>
      <button type="button" data-open-modal="passwordModal"><span>🔒</span>보안 설정</button>
    </div>
  </section>
</div>

<section class="account-panel card" id="notifications">
  <div class="account-panel-head">
    <h2>알림 설정</h2>
    <button type="button" class="account-btn account-btn--outline" data-open-modal="notifPrefsModal">설정 변경</button>
  </div>
  <p class="account-meta">받고 싶은 알림 종류와 크레딧 부족 기준을 직접 조절할 수 있습니다.</p>
  <ul class="account-notif-summary" id="accountNotifSummary">
    <li>설정을 불러오는 중…</li>
  </ul>
</section>

<section class="account-panel card" id="credits">
  <h2 class="account-section-title">크레딧 내역</h2>
  <p class="account-meta">보유 크레딧 <strong><?= number_format((int) ($dash['credit']['balance'] ?? 0)) ?> C</strong></p>
  <?php $creditTx = $dash['credit']['transactions'] ?? []; ?>
  <?php if (empty($creditTx)): ?>
  <p class="account-empty">크레딧 사용·적립 내역이 없습니다.</p>
  <?php else: ?>
  <div class="account-credit-list">
    <?php foreach ($creditTx as $tx): ?>
    <div class="account-credit-row">
      <div>
        <strong><?= e($tx['description'] ?? '') ?></strong>
        <span class="account-meta"><?= e(substr((string) ($tx['created_at'] ?? ''), 0, 16)) ?> · <?= e(\App\Services\CreditService::txTypeLabel((string) ($tx['tx_type'] ?? ''))) ?></span>
      </div>
      <span class="account-credit-amt<?= (int) ($tx['amount'] ?? 0) >= 0 ? ' is-plus' : ' is-minus' ?>">
        <?= (int) ($tx['amount'] ?? 0) >= 0 ? '+' : '' ?><?= number_format((int) ($tx['amount'] ?? 0)) ?> C
      </span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<section class="account-support card">
  <div>
    <strong>도움이 필요하신가요?</strong>
    <p>1:1 문의와 FAQ에서 빠르게 답변을 받아보세요.</p>
  </div>
  <div class="account-support-actions">
    <button type="button" class="account-btn account-btn--primary" data-open-modal="inquiryModal">1:1 문의</button>
    <a class="account-btn account-btn--outline" href="<?= url('faq') ?>">FAQ</a>
  </div>
</section>

<?php if (($user['role'] ?? '') === 'admin'): ?>
<div class="account-admin-banner card">
  <a href="<?= url('admin') ?>">관리자 콘솔로 이동 →</a>
</div>
<?php endif; ?>
