<?php
/** @var array<string, mixed> $dash */
/** @var App\Services\AccountService $accountService */
$user = $dash['user'];
$plan = $dash['plan'];
$grade = $dash['grade'] ?? ['name' => $plan['name'] ?? '일반', 'color' => '#7B2D3E', 'description' => ''];
$stats = $dash['stats'];
$aiUsage = $dash['ai_usage'] ?? ['items' => [], 'used' => 0, 'limit' => 0];
$initial = mb_substr((string) ($user['name'] ?? '회'), 0, 1);
$accIcons = require view_path('home/partials/menu-icons.php');
$accIcon = static fn (string $key, string $cls = ''): string => '<span class="account-ic' . ($cls !== '' ? ' ' . $cls : '') . '" aria-hidden="true"><svg viewBox="0 0 24 24">' . ($accIcons[$key] ?? '') . '</svg></span>';
$chev = '<svg class="mp-chev" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$creditBal = (int) ($dash['credit']['balance'] ?? 0);
$creditTotal = (int) ($dash['credit']['total'] ?? 0);
$creditTx = array_slice($dash['credit']['transactions'] ?? [], 0, 3);
$aiTotal = (int) ($aiUsage['total'] ?? 0);
$clipartCount = (int) ($dash['clipartCount'] ?? 0);
$wishlistCount = (int) ($dash['wishlistCount'] ?? 0);
$myCliparts = array_slice($dash['cliparts'] ?? [], 0, 12);
$quick = [
    ['label' => '주문내역', 'ic' => 'orders', 'href' => url('account/orders'), 'count' => (int) $stats['orders']],
    ['label' => '찜한 상품', 'ic' => 'heart', 'href' => '#wishlist', 'count' => $wishlistCount],
    ['label' => '클립아트', 'ic' => 'clipart', 'href' => '#cliparts', 'count' => $clipartCount],
    ['label' => '배송지', 'ic' => 'pin', 'href' => '#address', 'count' => 0],
    ['label' => '쿠폰함', 'ic' => 'coupon', 'href' => url('account/coupons'), 'count' => (int) $stats['coupons']],
];
?>
<div class="mp">
  <div class="mp-side">
    <section class="mp-top">
      <div class="mp-profile">
        <div class="mp-avatar" aria-hidden="true"><?= e($initial) ?></div>
        <div class="mp-profile__info">
          <div class="mp-profile__name">
            <h1><?= e((string) ($user['name'] ?? '회원')) ?>님</h1>
            <span class="account-plan-badge" style="--grade-color:<?= e((string) ($grade['color'] ?? '#7B2D3E')) ?>"><?= e((string) ($grade['name'] ?? '일반')) ?></span>
          </div>
          <p><?= e((string) ($user['email'] ?? '')) ?></p>
        </div>
        <button type="button" class="mp-gear" data-open-modal="profileModal" aria-label="회원정보 수정" title="회원정보 수정">
          <svg viewBox="0 0 24 24" aria-hidden="true"><?= $accIcons['admin'] ?></svg>
        </button>
      </div>
      <div class="mp-wallet">
        <button type="button" class="mp-wallet__item" data-history-open="credit">
          <span>보유 잉크</span>
          <strong<?= $creditBal < 0 ? ' class="is-debt"' : '' ?>><?= number_format($creditBal) ?> C</strong>
        </button>
        <button type="button" class="mp-wallet__item" data-history-open="ai">
          <span>이번 달 AI</span>
          <strong><?= number_format((int) ($aiUsage['used'] ?? 0)) ?> C</strong>
        </button>
        <a class="mp-wallet__item" href="<?= url('account/coupons') ?>">
          <span>쿠폰</span>
          <strong><?= (int) $stats['coupons'] ?>장</strong>
        </a>
      </div>
      <?php if ($creditBal < 0): ?>
      <p class="mp-warn">AI 사용으로 잉크가 <?= number_format(abs($creditBal)) ?> C 부족합니다. 다음 충전 때 먼저 차감됩니다.</p>
      <?php endif; ?>
      <a class="mp-charge" href="<?= url('shop/products') ?>?category=ink-charge"><?= $accIcon('ink', 'account-ic--sm') ?>잉크 충전하기<?= $chev ?></a>
    </section>

    <nav class="mp-quick" aria-label="마이페이지 바로가기">
      <?php foreach ($quick as $q): ?>
      <a class="mp-quick__item" href="<?= e($q['href']) ?>">
        <?= $accIcon($q['ic'], 'account-ic--lg') ?>
        <span><?= e($q['label']) ?></span>
        <?php if ($q['count'] > 0): ?><em><?= number_format($q['count']) ?></em><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </nav>

    <nav class="mp-menus" aria-label="마이페이지 메뉴">
      <div class="mp-menu">
        <h2>라벨 디자인</h2>
        <a class="mp-row" href="<?= url('editor/?new=1') ?>"><?= $accIcon('plus', 'account-ic--sm') ?><span>새 라벨 만들기</span><?= $chev ?></a>
        <a class="mp-row" href="<?= url('/') ?>"><?= $accIcon('ai', 'account-ic--sm') ?><span>AI 라벨 디자인</span><?= $chev ?></a>
        <a class="mp-row" href="<?= url('projects') ?>"><?= $accIcon('project', 'account-ic--sm') ?><span>프로젝트</span><?= $chev ?></a>
        <a class="mp-row" href="<?= url('locker') ?>"><?= $accIcon('locker', 'account-ic--sm') ?><span>내 보관함</span><?= $chev ?></a>
        <a class="mp-row" href="<?= url('trash') ?>"><?= $accIcon('trash', 'account-ic--sm') ?><span>휴지통</span><?= $chev ?></a>
      </div>
      <div class="mp-menu">
        <h2>나의 쇼핑</h2>
        <a class="mp-row" href="<?= url('account/orders') ?>"><?= $accIcon('orders', 'account-ic--sm') ?><span>주문 내역</span><?php if ((int) $stats['shipping'] > 0): ?><em>배송중 <?= (int) $stats['shipping'] ?></em><?php endif; ?><?= $chev ?></a>
        <a class="mp-row" href="<?= url('shop/cart') ?>"><?= $accIcon('cart', 'account-ic--sm') ?><span>장바구니</span><?= $chev ?></a>
        <a class="mp-row" href="<?= url('account/coupons') ?>"><?= $accIcon('coupon', 'account-ic--sm') ?><span>쿠폰함</span><?php if ((int) $stats['coupons'] > 0): ?><em><?= (int) $stats['coupons'] ?>장</em><?php endif; ?><?= $chev ?></a>
        <a class="mp-row" href="<?= url('shop') ?>"><?= $accIcon('shop', 'account-ic--sm') ?><span>라벨 구매</span><?= $chev ?></a>
      </div>
      <div class="mp-menu">
        <h2>계정</h2>
        <button type="button" class="mp-row" data-open-modal="profileModal"><?= $accIcon('profile', 'account-ic--sm') ?><span>회원정보 수정</span><?= $chev ?></button>
        <button type="button" class="mp-row" data-open-modal="passwordModal"><?= $accIcon('lock', 'account-ic--sm') ?><span>비밀번호 변경</span><?= $chev ?></button>
        <button type="button" class="mp-row" data-open-modal="notifPrefsModal"><?= $accIcon('notify', 'account-ic--sm') ?><span>알림 설정</span><?= $chev ?></button>
      </div>
      <div class="mp-menu">
        <h2>고객센터</h2>
        <button type="button" class="mp-row" data-open-modal="inquiryModal"><?= $accIcon('bug', 'account-ic--sm') ?><span>1:1 문의하기</span><?= $chev ?></button>
        <button type="button" class="mp-row" data-open-modal="inquiriesModal"><?= $accIcon('orders', 'account-ic--sm') ?><span>내 문의 내역</span><?= $chev ?></button>
        <a class="mp-row" href="<?= url('faq') ?>"><?= $accIcon('faq', 'account-ic--sm') ?><span>자주 묻는 질문</span><?= $chev ?></a>
        <?php if (($user['role'] ?? '') === 'admin'): ?>
        <a class="mp-row" href="<?= url('admin') ?>"><?= $accIcon('admin', 'account-ic--sm') ?><span>관리자 콘솔</span><?= $chev ?></a>
        <?php endif; ?>
      </div>
    </nav>
  </div>

  <div class="mp-main">
    <section class="mp-sec">
      <div class="mp-sec__head">
        <h2>최근 디자인</h2>
        <a href="<?= url('projects') ?>">전체보기<?= $chev ?></a>
      </div>
      <div class="mp-designs">
        <a class="mp-design mp-design--new" href="<?= url('editor/?new=1') ?>">
          <span class="mp-design__thumb"><?= $accIcon('plus', 'account-ic--lg') ?></span>
          <strong>새 디자인</strong>
        </a>
        <?php foreach ($dash['recentDesigns'] as $d): ?>
        <div class="mp-design">
          <button type="button" class="mp-del js-lib-act" data-act="trash" data-type="workspace" data-id="<?= (int) ($d['id'] ?? 0) ?>" title="휴지통으로" aria-label="휴지통으로"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $accIcons['trash'] ?></svg></button>
          <a href="<?= e((string) ($d['href'] ?? url('editor/'))) ?>">
            <span class="mp-design__thumb">
              <?php if (!empty($d['preview_svg'])): ?>
              <span class="tpl-preview"><?= $d['preview_svg'] ?></span>
              <?php elseif (!empty($d['thumb'])): ?>
              <img src="<?= e((string) $d['thumb']) ?>" alt="" loading="lazy">
              <?php else: ?>
              <span class="mp-design__fallback"><?= e(mb_substr((string) ($d['name'] ?? '라벨'), 0, 10)) ?></span>
              <?php endif; ?>
            </span>
            <strong><?= e((string) $d['name']) ?></strong>
            <?php if (!empty($d['updated_label'])): ?><em><?= e((string) $d['updated_label']) ?></em><?php endif; ?>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (empty($dash['recentDesigns'])): ?>
      <p class="mp-empty">편집기에서 저장한 디자인이 여기에 모입니다.</p>
      <?php endif; ?>
    </section>

    <section class="mp-sec" id="orders">
      <div class="mp-sec__head">
        <h2>최근 주문<?php if ((int) $stats['shipping'] > 0): ?> <small>배송중 <?= (int) $stats['shipping'] ?></small><?php endif; ?></h2>
        <a href="<?= url('account/orders') ?>">전체보기<?= $chev ?></a>
      </div>
      <?php if (empty($dash['recentOrders'])): ?>
      <p class="mp-empty">아직 주문 내역이 없습니다. <a href="<?= url('shop') ?>">라벨 구매하러 가기</a></p>
      <?php else: ?>
      <ul class="mp-list">
        <?php foreach ($dash['recentOrders'] as $order): ?>
        <li>
          <a class="mp-item" href="<?= e((string) ($order['href'] ?? url('account/orders'))) ?>">
            <img src="<?= e($order['thumb']) ?>" alt="" loading="lazy">
            <span class="mp-item__body">
              <strong><?= e($order['name']) ?></strong>
              <span><?= e($order['date']) ?> · <?= number_format((int) $order['total']) ?>원</span>
            </span>
            <span class="account-order-status <?= e($accountService->orderStatusClass($order['status'])) ?>"><?= e($order['status_label']) ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>

    <section class="mp-sec" id="wishlist">
      <div class="mp-sec__head">
        <h2>찜한 상품<?php if ($wishlistCount > 0): ?> <small><?= $wishlistCount ?></small><?php endif; ?></h2>
        <a href="<?= url('shop/products') ?>">상품 보기<?= $chev ?></a>
      </div>
      <ul class="mp-list" id="accountWishList">
        <?php foreach (($dash['wishlist'] ?? []) as $item): ?>
        <li class="mp-item" data-wish-product-id="<?= (int) $item['product_id'] ?>">
          <?php if ($item['openable']): ?>
          <a href="<?= e($item['url']) ?>"><img src="<?= e($item['thumb_url']) ?>" alt="" loading="lazy"></a>
          <?php else: ?>
          <img src="<?= e($item['thumb_url']) ?>" alt="" loading="lazy">
          <?php endif; ?>
          <span class="mp-item__body">
            <?php if ($item['openable']): ?>
            <a href="<?= e($item['url']) ?>"><strong><?= e($item['name']) ?></strong></a>
            <?php else: ?>
            <strong><?= e($item['name']) ?></strong>
            <?php endif; ?>
            <span>
              <b><?= e($item['price_label']) ?></b>
              <?php if ($item['list_price_label'] !== ''): ?><del><?= e($item['list_price_label']) ?></del><?php endif; ?>
              <?php if ($item['soldout']): ?> · 품절<?php elseif (!$item['openable']): ?> · 판매 중지<?php endif; ?>
            </span>
          </span>
          <button type="button" class="mp-mini-btn js-wish-remove" data-product-id="<?= (int) $item['product_id'] ?>">찜 해제</button>
        </li>
        <?php endforeach; ?>
      </ul>
      <p class="mp-empty" id="accountWishEmpty"<?= empty($dash['wishlist']) ? '' : ' hidden' ?>>상품 상세에서 ♥ 찜하기를 누르면 여기에 모입니다.</p>
    </section>

    <section class="mp-sec" id="cliparts">
      <div class="mp-sec__head">
        <h2>내 클립아트<?php if ($clipartCount > 0): ?> <small><?= number_format($clipartCount) ?></small><?php endif; ?></h2>
        <a href="<?= url('locker') ?>">보관함<?= $chev ?></a>
      </div>
      <?php if (empty($myCliparts)): ?>
      <p class="mp-empty">라비에게 그림을 요청하면 여기에 모입니다. <a href="<?= url('/') ?>">라비에게 요청하기</a></p>
      <?php else: ?>
      <div class="mp-clips">
        <?php foreach ($myCliparts as $clip): ?>
        <?php
          $clipUrl = (string) ($clip['image_url'] ?? '');
          $clipTitle = (string) ($clip['title'] ?? '라비가 그린 클립아트');
          $clipEdit = (string) ($clip['editor_url'] ?? url('editor/'));
        ?>
        <div class="mp-clip">
          <button type="button" class="mp-clip__thumb js-clip-preview" data-src="<?= e($clipUrl) ?>" data-title="<?= e($clipTitle) ?>" data-edit="<?= e($clipEdit) ?>" title="<?= e($clipTitle) ?>">
            <img src="<?= e($clipUrl) ?>" alt="<?= e($clipTitle) ?>" loading="lazy">
          </button>
          <button type="button" class="mp-del js-lib-act" data-act="trash" data-type="clipart" data-id="<?= (int) ($clip['id'] ?? 0) ?>" title="휴지통으로" aria-label="휴지통으로"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $accIcons['trash'] ?></svg></button>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <section class="mp-sec" id="address">
      <div class="mp-sec__head">
        <h2>배송지 관리</h2>
        <button type="button" class="mp-sec__act" data-address-add>＋ 추가</button>
      </div>
      <div id="accountAddressList" class="mp-addrs">
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
      <p class="mp-empty" id="accountAddressEmpty" <?= empty($dash['addresses']) ? '' : 'hidden' ?>>자주 쓰는 주소를 등록해 두면 주문할 때 바로 불러올 수 있어요.</p>
    </section>

    <section class="mp-sec" id="credits">
      <div class="mp-sec__head">
        <h2>잉크 내역<?php if ($creditTotal > 0): ?> <small><?= number_format($creditTotal) ?>건</small><?php endif; ?></h2>
        <?php if ($creditTotal > 0): ?><button type="button" class="mp-sec__act" data-history-open="credit">전체보기<?= $chev ?></button><?php endif; ?>
      </div>
      <?php if (empty($creditTx)): ?>
      <p class="mp-empty">잉크 사용·적립 내역이 없습니다.</p>
      <?php else: ?>
      <ul class="mp-list mp-list--tx">
        <?php foreach ($creditTx as $tx): ?>
        <?php $amt = (int) ($tx['amount'] ?? 0); ?>
        <li class="mp-tx">
          <span class="mp-item__body">
            <strong><?= e($tx['description'] ?? '') ?></strong>
            <span><?= e(substr((string) ($tx['created_at'] ?? ''), 0, 16)) ?> · <?= e(\App\Services\CreditService::txTypeLabel((string) ($tx['tx_type'] ?? ''))) ?></span>
          </span>
          <b class="<?= $amt >= 0 ? 'is-plus' : 'is-minus' ?>"><?= $amt >= 0 ? '+' : '' ?><?= number_format($amt) ?> C</b>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>

    <section class="mp-sec" id="ai-usage">
      <div class="mp-sec__head">
        <h2>AI 사용 이력<?php if ($aiTotal > 0): ?> <small><?= number_format($aiTotal) ?>건</small><?php endif; ?></h2>
        <?php if ($aiTotal > 0): ?><button type="button" class="mp-sec__act" data-history-open="ai">전체보기<?= $chev ?></button><?php endif; ?>
      </div>
      <?php $aiItems = array_slice($aiUsage['items'] ?? [], 0, 3); ?>
      <?php if (empty($aiItems)): ?>
      <p class="mp-empty">아직 AI 사용 이력이 없습니다.</p>
      <?php else: ?>
      <ul class="mp-list mp-list--tx">
        <?php foreach ($aiItems as $row): ?>
        <?php $ok = ((string) ($row['status'] ?? '')) === 'ok'; ?>
        <li class="mp-tx">
          <span class="mp-item__body">
            <strong><?= e((string) ($row['intent_label'] ?? 'AI')) ?></strong>
            <span><?= e(substr((string) ($row['created_at'] ?? ''), 0, 16)) ?><?php if ((string) ($row['surface_label'] ?? '') !== ''): ?> · <?= e((string) $row['surface_label']) ?><?php endif; ?></span>
          </span>
          <em class="account-history-badge<?= $ok ? '' : ' is-error' ?>"><?= $ok ? '성공' : '오류' ?></em>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </section>
  </div>
</div>
