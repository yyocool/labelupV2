<?php
/** @var App\Services\ShopService $shopService */
/** @var array{items: array, subtotal: int, shipping_fee: int, total: int, count: int} $cart */
?>
<section class="shop-page-head">
  <h1>장바구니</h1>
  <p><?= number_format($cart['count']) ?>개 상품</p>
</section>

<?php if ($cart['items']): ?>
<div class="shop-cart-layout">
  <div class="shop-cart-list">
    <?php foreach ($cart['items'] as $item): ?>
    <article class="shop-cart-item" data-product-id="<?= (int) $item['id'] ?>">
      <a class="shop-cart-thumb" href="<?= url('shop/products/' . (int) $item['id']) ?>">
        <img src="<?= e($shopService->productThumb($item)) ?>" alt="">
      </a>
      <div class="shop-cart-info">
        <a href="<?= url('shop/products/' . (int) $item['id']) ?>"><strong><?= e((string) $item['name']) ?></strong></a>
        <span class="shop-cart-sku"><?= e((string) $item['sku']) ?></span>
        <div class="shop-cart-price"><?= e($shopService->formatPrice((int) $item['unit_price'])) ?></div>
      </div>
      <div class="shop-cart-controls">
        <div class="shop-qty">
          <button type="button" class="shop-qty-btn" data-cart-minus="<?= (int) $item['id'] ?>">-</button>
          <input type="number" value="<?= (int) $item['qty'] ?>" min="1" max="<?= (int) $item['stock_qty'] ?>" data-cart-qty="<?= (int) $item['id'] ?>" readonly>
          <button type="button" class="shop-qty-btn" data-cart-plus="<?= (int) $item['id'] ?>">+</button>
        </div>
        <strong class="shop-cart-line"><?= e($shopService->formatPrice((int) $item['line_total'])) ?></strong>
        <button type="button" class="shop-cart-remove" data-cart-remove="<?= (int) $item['id'] ?>">삭제</button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <aside class="shop-cart-summary">
    <h2>주문 요약</h2>
    <dl>
      <div><dt>상품 금액</dt><dd id="cartSubtotal"><?= e($shopService->formatPrice($cart['subtotal'])) ?></dd></div>
      <div><dt>배송비</dt><dd id="cartShipping"><?= $cart['shipping_fee'] === 0 ? '무료' : e($shopService->formatPrice($cart['shipping_fee'])) ?></dd></div>
      <div class="shop-cart-total"><dt>결제 예정</dt><dd id="cartTotal"><?= e($shopService->formatPrice($cart['total'])) ?></dd></div>
    </dl>
    <p class="shop-cart-note">5만원 이상 구매 시 배송비 무료</p>
    <form id="shopCheckoutForm" class="shop-checkout-form">
      <label>이름<input name="customer_name" required maxlength="80" value="<?= e((string) ($authUser['name'] ?? '')) ?>"></label>
      <label>이메일<input type="email" name="customer_email" required maxlength="190" value="<?= e((string) ($authUser['email'] ?? '')) ?>"></label>
      <label>연락처<input name="customer_phone" required maxlength="30" value="<?= e((string) ($authUser['phone'] ?? '')) ?>" placeholder="010-0000-0000"></label>
      <label>배송지<textarea name="shipping_address" required maxlength="500" rows="3" placeholder="주소와 상세주소를 입력하세요"></textarea></label>
      <label>배송 메모<textarea name="shipping_memo" maxlength="255" rows="2" placeholder="문 앞, 경비실 등"></textarea></label>
      <button type="submit" class="shop-btn shop-btn--primary shop-btn--block">주문 접수</button>
    </form>
    <a class="shop-btn shop-btn--outline shop-btn--block" href="<?= url('shop/products') ?>">쇼핑 계속하기</a>
  </aside>
</div>
<?php else: ?>
<div class="shop-empty">
  <p>장바구니가 비어 있습니다.</p>
  <a class="shop-btn shop-btn--primary" href="<?= url('shop/products') ?>">상품 보러가기</a>
</div>
<?php endif; ?>
