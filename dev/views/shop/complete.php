<?php
/** @var App\Services\ShopService $shopService */
/** @var array<string, mixed>|null $order */
/** @var string $orderNo */
/** @var array<int, array<string, mixed>> $recentOrders */
/** @var array<int, array<string, mixed>> $featuredProducts */
$order = is_array($order ?? null) ? $order : null;
$displayNo = $orderNo;
if (is_array($order) && trim((string) ($order['order_no'] ?? '')) !== '') {
    $displayNo = (string) $order['order_no'];
}
$steps = (is_array($order) && is_array($order['steps'] ?? null)) ? $order['steps'] : [
    ['key' => 'pending', 'label' => '주문완료', 'done' => true, 'current' => true, 'at' => ''],
    ['key' => 'paid', 'label' => '결제완료', 'done' => false, 'current' => false, 'at' => ''],
    ['key' => 'preparing', 'label' => '상품준비중', 'done' => false, 'current' => false, 'at' => ''],
    ['key' => 'shipping', 'label' => '배송중', 'done' => false, 'current' => false, 'at' => ''],
    ['key' => 'delivered', 'label' => '배송완료', 'done' => false, 'current' => false, 'at' => ''],
];
$items = (is_array($order) && is_array($order['items'] ?? null)) ? $order['items'] : [];
$recentOrders = is_array($recentOrders ?? null) ? $recentOrders : [];
$featuredProducts = is_array($featuredProducts ?? null) ? $featuredProducts : [];
$stepIcons = [
    'pending' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-1.5 9h-12L6 6Z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1.2" fill="currentColor" stroke="none"/><circle cx="18" cy="20" r="1.2" fill="currentColor" stroke="none"/></svg>',
    'paid' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>',
    'preparing' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
    'shipping' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7V10z"/><circle cx="7" cy="19" r="1.6"/><circle cx="18" cy="19" r="1.6"/></svg>',
    'delivered' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>',
];
?>
<article class="shop-oc" id="shopOrderComplete">
  <header class="shop-oc-hero">
    <span class="shop-oc-confetti" aria-hidden="true"></span>
    <div class="shop-oc-hero__copy">
      <span class="shop-oc-check" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
      </span>
      <div>
        <h1>주문이 완료되었습니다!</h1>
        <p class="shop-oc-hero__lead">라벨업을 이용해 주셔서 감사합니다.</p>
        <p class="shop-oc-hero__sub">주문하신 상품은 정성껏 준비하여 빠르게 배송해 드리겠습니다.</p>
      </div>
    </div>
    <div class="shop-oc-hero__mascot">
      <p class="shop-oc-note" aria-hidden="true">좋은 라벨이<br>좋은 하루를 만듭니다!<br>감사합니다 ♡</p>
      <img src="<?= asset('labi-wink.png') ?>" alt="">
    </div>
  </header>

  <section class="shop-oc-summary" aria-label="주문 요약">
    <dl>
      <div>
        <dt>주문번호</dt>
        <dd><?= e($displayNo !== '' ? $displayNo : '확인 중') ?></dd>
      </div>
      <div>
        <dt>주문일시</dt>
        <dd><?= e((string) ($order['date_label'] ?? '—')) ?></dd>
      </div>
      <div>
        <dt>결제금액</dt>
        <dd>
          <?= e((string) ($order['total_label'] ?? '—')) ?>
          <?php if (!empty($order['payment_label'])): ?>
          <small>(<?= e((string) $order['payment_label']) ?>)</small>
          <?php endif; ?>
        </dd>
      </div>
    </dl>
    <div class="shop-oc-summary__actions">
      <a class="shop-btn shop-btn--primary" href="<?= url('account') ?>#orders">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
        주문내역 보기
      </a>
      <button type="button" class="shop-btn shop-btn--outline" id="shopOrderPrint">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
        주문확인서 출력
      </button>
    </div>
  </section>

  <ol class="shop-oc-steps" aria-label="주문 진행 상태">
    <?php foreach ($steps as $step): ?>
    <?php
      $key = (string) ($step['key'] ?? '');
      $cls = !empty($step['done']) ? ' is-done' : '';
      $cls .= !empty($step['current']) ? ' is-current' : '';
    ?>
    <li class="<?= trim($cls) ?>">
      <span class="shop-oc-steps__ico" aria-hidden="true"><?= $stepIcons[$key] ?? $stepIcons['pending'] ?></span>
      <strong><?= e((string) ($step['label'] ?? '')) ?></strong>
      <?php if (!empty($step['at']) && !empty($step['done'])): ?>
      <em><?= e((string) $step['at']) ?></em>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>

  <div class="shop-oc-grid">
    <section class="shop-oc-card">
      <h2>주문 상품 정보</h2>
      <?php if ($items): ?>
      <ul class="shop-oc-items">
        <?php foreach ($items as $item): ?>
        <li>
          <img src="<?= e((string) ($item['thumbnail'] ?? '')) ?>" alt="">
          <div>
            <strong><?= e((string) ($item['name'] ?? '상품')) ?></strong>
            <span><?= e((string) (($item['meta'] ?? '') !== '' ? $item['meta'] : ($item['sku'] ?? ''))) ?></span>
            <b><?= e((string) ($item['unit_label'] ?? '')) ?></b>
          </div>
          <em>수량 <?= number_format((int) ($item['qty'] ?? 0)) ?>개</em>
          <p><?= e((string) ($item['line_label'] ?? '')) ?></p>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?>
      <p class="shop-oc-empty">주문 상품을 불러오지 못했습니다. 마이페이지에서 확인해 주세요.</p>
      <?php endif; ?>
      <div class="shop-oc-files">
        <div>
          <strong>주문하신 디자인 파일</strong>
          <span>제작에 사용하는 디자인은 편집기에서 이어서 작업할 수 있어요.</span>
        </div>
        <a class="shop-btn shop-btn--outline" href="<?= url('editor/') ?>">편집기에서 열기</a>
      </div>
    </section>

    <div class="shop-oc-side">
      <section class="shop-oc-card">
        <h2>결제 정보</h2>
        <dl class="shop-oc-pay">
          <div><dt>상품금액</dt><dd><?= e((string) ($order['subtotal_label'] ?? '—')) ?></dd></div>
          <div><dt>배송비</dt><dd><?= e((string) ($order['shipping_label'] ?? '—')) ?></dd></div>
          <div><dt>할인금액</dt><dd><?= ((int) ($order['discount_amount'] ?? 0) > 0 ? '-' : '') . e((string) ($order['discount_label'] ?? '0원')) ?></dd></div>
          <div class="shop-oc-pay__total"><dt>최종 결제금액</dt><dd><?= e((string) ($order['total_label'] ?? '—')) ?></dd></div>
        </dl>
        <div class="shop-oc-paymethod">
          <span>결제 수단</span>
          <strong><?= e((string) ($order['payment_label'] ?? '결제대기')) ?></strong>
          <em><?= e((string) ($order['payment_hint'] ?? '담당자 확인 후 안내')) ?></em>
        </div>
      </section>

      <section class="shop-oc-card">
        <h2>배송 정보</h2>
        <div class="shop-oc-ship">
          <span class="shop-oc-ship__ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </span>
          <div>
            <strong><?= e((string) ($order['shipping_name'] ?? '')) ?> <?= e((string) ($order['shipping_phone'] ?? '')) ?></strong>
            <p><?= e((string) ($order['shipping_address'] ?? '배송지가 등록되지 않았습니다.')) ?></p>
            <?php if (!empty($order['shipping_memo'])): ?>
            <p class="shop-oc-ship__memo">요청사항: <?= e((string) $order['shipping_memo']) ?></p>
            <?php endif; ?>
          </div>
        </div>
        <dl class="shop-oc-shipmeta">
          <div><dt>배송방법</dt><dd><?= e((string) ($order['carrier'] ?? '라벨업배송 (기본배송)')) ?></dd></div>
          <div><dt>배송비</dt><dd><?= e((string) ($order['shipping_label'] ?? '—')) ?></dd></div>
          <?php if (!empty($order['tracking_no'])): ?>
          <div><dt>송장번호</dt><dd><?= e((string) $order['tracking_no']) ?></dd></div>
          <?php endif; ?>
        </dl>
      </section>
    </div>
  </div>

  <nav class="shop-oc-more" aria-label="다음 할 일">
    <a class="shop-oc-more__card shop-oc-more__card--edit" href="<?= url('editor/') ?>">
      <div>
        <h3>라벨 디자인 이어서 만들기</h3>
        <p>주문하신 디자인을 수정하거나 다른 용도로 활용해 보세요.</p>
        <span>디자인 이어서 편집하기 →</span>
      </div>
      <img src="<?= asset('login-hero-mockup.png') ?>" alt="">
    </a>
    <a class="shop-oc-more__card shop-oc-more__card--orders" href="<?= url('account') ?>#orders">
      <div>
        <h3>최근 주문 내역</h3>
        <p>이전에 주문한 상품을 다시 간편하게 주문하세요.</p>
        <?php if ($recentOrders): ?>
        <ul>
          <?php foreach (array_slice($recentOrders, 0, 2) as $row): ?>
          <li><?= e((string) $row['order_no']) ?> · <?= e((string) $row['status_label']) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <span>주문내역 보기 →</span>
      </div>
    </a>
    <a class="shop-oc-more__card shop-oc-more__card--shop" href="<?= url('shop/products') ?>">
      <div>
        <h3>라벨업 쇼핑 계속하기</h3>
        <p>다양한 라벨 용지와 스티커를 지금 만나보세요.</p>
        <span>쇼핑을 계속하기 →</span>
      </div>
      <div class="shop-oc-more__thumbs" aria-hidden="true">
        <img src="<?= asset('tpl-handmade.webp') ?>" alt="">
        <img src="<?= asset('tpl-price.webp') ?>" alt="">
        <img src="<?= asset('tpl-shipping.webp') ?>" alt="">
      </div>
    </a>
  </nav>
</article>
