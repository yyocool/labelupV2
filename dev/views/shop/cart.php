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
    <form id="shopCheckoutForm" class="shop-checkout-form" data-checkout-address>
      <fieldset class="lu-checkout-block">
        <legend>구매자</legend>
        <label>이름<input name="customer_name" required maxlength="80" value="<?= e((string) ($authUser['name'] ?? '')) ?>"></label>
        <label>이메일<input type="email" name="customer_email" required maxlength="190" value="<?= e((string) ($authUser['email'] ?? '')) ?>"></label>
        <label>연락처<input name="customer_phone" required maxlength="30" value="<?= e((string) ($authUser['phone'] ?? '')) ?>" placeholder="010-0000-0000"></label>
      </fieldset>
      <fieldset class="lu-checkout-block">
        <legend>수취인</legend>
        <div class="lu-checkout-block__tools">
          <label class="lu-check"><input type="checkbox" data-same-as-buyer> 구매자와 동일</label>
          <button type="button" class="lu-addr-book-btn" data-addr-book-open>주소 불러오기</button>
        </div>
        <label>수취인 이름<input name="shipping_name" required maxlength="80" placeholder="받는 분 이름"></label>
        <label>수취인 연락처<input name="shipping_phone" required maxlength="30" placeholder="010-0000-0000"></label>
        <div class="lu-addr" data-daum-address>
          <span class="lu-addr__legend">배송지</span>
          <div class="lu-addr__row">
            <input type="text" name="shipping_zip" data-addr-zip readonly required maxlength="10" placeholder="우편번호" autocomplete="postal-code">
            <button type="button" class="lu-addr__search" data-addr-search>주소 검색</button>
          </div>
          <input type="text" name="shipping_base" data-addr-base readonly required maxlength="300" placeholder="주소 검색으로 선택하세요">
          <input type="text" name="shipping_detail" data-addr-detail maxlength="200" placeholder="상세주소 (동·호수 등)">
          <textarea name="shipping_address" data-addr-combined hidden maxlength="500"></textarea>
        </div>
        <label class="lu-check"><input type="checkbox" name="save_address" value="1"> 이 주소를 배송지에 저장</label>
        <input type="text" name="address_label" maxlength="40" placeholder="배송지 이름 (집, 회사 등)">
      </fieldset>
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
