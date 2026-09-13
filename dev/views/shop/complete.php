<?php
/** @var App\Services\ShopService $shopService */
/** @var array<string, mixed>|null $order */
/** @var string $orderNo */
$order = is_array($order ?? null) ? $order : null;
$displayNo = $orderNo;
if (is_array($order) && trim((string) ($order['order_no'] ?? '')) !== '') {
    $displayNo = (string) $order['order_no'];
}
$totalLabel = $order ? $shopService->formatPrice((int) ($order['total_amount'] ?? 0)) : '';
$shipName = $order ? trim((string) ($order['shipping_name'] ?? '')) : '';
$itemQty = $order ? (int) ($order['item_qty'] ?? 0) : 0;
$items = ($order && is_array($order['items'] ?? null)) ? $order['items'] : [];
?>
<article class="shop-done">
  <aside class="shop-done__banner" aria-hidden="true">
    <img class="shop-done__banner-bg" src="<?= asset('hero-labi-home.webp') ?>" alt="">
    <div class="shop-done__banner-shade"></div>
    <div class="shop-done__banner-copy">
      <span>라비가 주문을 받았어요</span>
      <p>결제 확인 후 라벨 제작을 시작합니다.</p>
    </div>
  </aside>
  <div class="shop-done__body">
    <p class="shop-done__kicker">ORDER RECEIVED</p>
    <h1>주문이 접수되었습니다</h1>
    <p class="shop-done__lead">담당자가 결제를 확인하면 바로 제작에 들어가고, 출고·배송 진행은 마이페이지에서 알려 드려요. 입금 안내가 필요하면 등록하신 연락처로 연락드립니다.</p>
    <dl class="shop-done__meta">
      <div>
        <dt>주문번호</dt>
        <dd><?= e($displayNo !== '' ? $displayNo : '확인 중') ?></dd>
      </div>
      <?php if ($totalLabel !== '' && $order): ?>
      <div>
        <dt>결제 예정 금액</dt>
        <dd><em><?= e($totalLabel) ?></em></dd>
      </div>
      <?php endif; ?>
      <?php if ($shipName !== ''): ?>
      <div>
        <dt>수취인</dt>
        <dd><?= e($shipName) ?></dd>
      </div>
      <?php endif; ?>
      <?php if ($itemQty > 0): ?>
      <div>
        <dt>주문 수량</dt>
        <dd><?= number_format($itemQty) ?>개</dd>
      </div>
      <?php endif; ?>
    </dl>
    <?php if ($items): ?>
    <ul class="shop-done__items">
      <?php foreach ($items as $item): ?>
      <li>
        <strong><?= e((string) ($item['product_name'] ?? '상품')) ?></strong>
        <span><?= (int) ($item['qty'] ?? 0) ?>개 · <?= e($shopService->formatPrice((int) ($item['line_total'] ?? 0))) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <ol class="shop-done__steps">
      <li class="is-done"><b>1</b><span>주문 접수</span></li>
      <li class="is-now"><b>2</b><span>결제 확인</span></li>
      <li><b>3</b><span>제작·배송</span></li>
    </ol>
    <p class="shop-done__hint">주문 내용과 배송 상태는 마이페이지 › 내 주문에서 확인할 수 있어요.</p>
    <div class="shop-done__actions">
      <a class="shop-btn shop-btn--outline" href="<?= url('shop/products') ?>">쇼핑 계속</a>
      <a class="shop-btn shop-btn--primary" href="<?= url('account') ?>#orders">내 주문 보기</a>
    </div>
  </div>
</article>
