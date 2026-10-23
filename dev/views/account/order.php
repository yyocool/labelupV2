<?php
/** @var App\Services\AccountService $accountService */
/** @var array<string, mixed> $order */
$steps = is_array($order['steps'] ?? null) ? $order['steps'] : [];
$items = is_array($order['items'] ?? null) ? $order['items'] : [];
$canTrack = ((string) ($order['tracking_no'] ?? '')) !== '' && ((string) ($order['tracking_url'] ?? '')) !== '';
?>
<article class="account-panel card account-order">
  <div class="account-panel-head">
    <div>
      <p class="account-orders__crumb"><a href="<?= url('account') ?>">마이페이지</a> · <a href="<?= url('account/orders') ?>">주문 내역</a></p>
      <h2>주문 상세</h2>
    </div>
    <span class="account-order-status <?= e($accountService->orderStatusClass((string) ($order['status'] ?? ''))) ?>"><?= e((string) ($order['status_label'] ?? '')) ?></span>
  </div>

  <dl class="account-order-summary">
    <div><dt>주문번호</dt><dd><?= e((string) ($order['order_no'] ?? '')) ?></dd></div>
    <div><dt>주문일시</dt><dd><?= e((string) ($order['date_label'] ?? '')) ?></dd></div>
    <div><dt>결제금액</dt><dd><?= e((string) ($order['total_label'] ?? '')) ?></dd></div>
    <div><dt>결제</dt><dd><?= e((string) ($order['payment_label'] ?? '')) ?></dd></div>
  </dl>

  <?php if ($steps): ?>
  <ol class="account-order-steps">
    <?php foreach ($steps as $step): ?>
    <li class="<?= !empty($step['done']) ? 'is-done' : '' ?><?= !empty($step['current']) ? ' is-current' : '' ?>">
      <strong><?= e((string) ($step['label'] ?? '')) ?></strong>
      <?php if (!empty($step['at']) && !empty($step['done'])): ?><em><?= e((string) $step['at']) ?></em><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>
  <?php endif; ?>

  <section class="account-order-block">
    <h3>주문 상품</h3>
    <?php if ($items): ?>
    <ul class="account-order-items">
      <?php foreach ($items as $item): ?>
      <li>
        <img src="<?= e((string) ($item['thumbnail'] ?? '')) ?>" alt="">
        <div>
          <strong><?= e((string) ($item['name'] ?? '상품')) ?></strong>
          <span><?= e((string) (($item['meta'] ?? '') !== '' ? $item['meta'] : ($item['sku'] ?? ''))) ?></span>
          <em>수량 <?= number_format((int) ($item['qty'] ?? 0)) ?>개 · <?= e((string) ($item['line_label'] ?? '')) ?></em>
        </div>
        <?php if ((int) ($item['product_id'] ?? 0) > 0): ?>
        <a class="account-btn account-btn--outline" href="<?= url('shop/products/' . (int) $item['product_id']) ?>">다시 담기</a>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="account-empty">주문 상품을 불러오지 못했습니다.</p>
    <?php endif; ?>
  </section>

  <div class="account-order-grid">
    <section class="account-order-block">
      <h3>결제 정보</h3>
      <dl class="account-order-pay">
        <div><dt>상품금액</dt><dd><?= e((string) ($order['subtotal_label'] ?? '—')) ?></dd></div>
        <div><dt>배송비</dt><dd><?= e((string) ($order['shipping_label'] ?? '—')) ?></dd></div>
        <div><dt>할인금액</dt><dd><?= ((int) ($order['discount_amount'] ?? 0) > 0 ? '-' : '') . e((string) ($order['discount_label'] ?? '0원')) ?></dd></div>
        <div class="is-total"><dt>최종 결제금액</dt><dd><?= e((string) ($order['total_label'] ?? '—')) ?></dd></div>
      </dl>
      <p class="account-meta"><?= e((string) ($order['payment_hint'] ?? '')) ?></p>
    </section>

    <section class="account-order-block">
      <h3>배송 정보</h3>
      <p><strong><?= e((string) ($order['shipping_name'] ?? '')) ?></strong> <?= e((string) ($order['shipping_phone'] ?? '')) ?></p>
      <p><?= e((string) ($order['shipping_address'] ?? '배송지가 등록되지 않았습니다.')) ?></p>
      <?php if (!empty($order['shipping_memo'])): ?>
      <p class="account-meta">요청사항: <?= e((string) $order['shipping_memo']) ?></p>
      <?php endif; ?>
      <dl class="account-order-pay">
        <div><dt>배송방법</dt><dd><?= e((string) ($order['carrier'] ?? '라벨업배송')) ?></dd></div>
        <?php if (!empty($order['tracking_no'])): ?>
        <div><dt>송장번호</dt><dd><?= e((string) $order['tracking_no']) ?></dd></div>
        <?php endif; ?>
      </dl>
      <?php if ($canTrack): ?>
      <a class="account-btn account-btn--primary" href="<?= e((string) $order['tracking_url']) ?>" target="_blank" rel="noopener noreferrer">배송조회</a>
      <?php elseif (in_array((string) ($order['status'] ?? ''), ['shipping', 'preparing'], true)): ?>
      <p class="account-meta">송장이 등록되면 배송조회를 할 수 있습니다.</p>
      <?php endif; ?>
    </section>
  </div>

  <div class="account-order-foot">
    <a class="account-btn account-btn--outline" href="<?= url('account/orders') ?>">목록으로</a>
    <a class="account-btn account-btn--outline" href="<?= url('shop/products') ?>">쇼핑 계속하기</a>
  </div>
</article>
