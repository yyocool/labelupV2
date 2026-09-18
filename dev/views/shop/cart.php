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
  <div class="shop-cart-main">
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

    <form id="shopCheckoutForm" class="shop-checkout-form" data-checkout-address>
      <header class="shop-co-pagehead">
        <h2>주문서</h2>
        <p>구매자 · 수취인 · 배송 정보를 나눠 확인한 뒤 주문을 접수해 주세요.</p>
      </header>

      <section class="shop-co-block" aria-labelledby="shop-co-buyer">
        <header class="shop-co-block__head">
          <span class="shop-co-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </span>
          <div>
            <h3 id="shop-co-buyer">구매자</h3>
            <p>주문 확인과 결제 안내를 받을 정보입니다.</p>
          </div>
        </header>
        <div class="shop-co-block__body">
          <div class="shop-co-grid">
            <label class="shop-co-field"><span class="shop-co-lab">이름</span><input name="customer_name" required maxlength="80" value="<?= e((string) ($authUser['name'] ?? '')) ?>" placeholder="이름" autocomplete="name"></label>
            <label class="shop-co-field"><span class="shop-co-lab">연락처</span><input name="customer_phone" required maxlength="30" value="<?= e((string) ($authUser['phone'] ?? '')) ?>" placeholder="010-0000-0000" autocomplete="tel"></label>
          </div>
          <label class="shop-co-field"><span class="shop-co-lab">이메일</span><input type="email" name="customer_email" required maxlength="190" value="<?= e((string) ($authUser['email'] ?? '')) ?>" placeholder="you@email.com" autocomplete="email"></label>
        </div>
      </section>

      <section class="shop-co-block" aria-labelledby="shop-co-ship">
        <header class="shop-co-block__head">
          <span class="shop-co-ico shop-co-ico--rose" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          </span>
          <div>
            <h3 id="shop-co-ship">수취인</h3>
            <p>라벨이 도착할 받는 분과 배송지입니다.</p>
          </div>
          <div class="shop-co-block__tools">
            <label class="lu-check"><input type="checkbox" data-same-as-buyer> 구매자와 동일</label>
            <button type="button" class="lu-addr-book-btn" data-addr-book-open>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8M8 11h5"/></svg>
              주소 불러오기
            </button>
          </div>
        </header>
        <div class="shop-co-block__body">
          <div class="shop-co-grid">
            <label class="shop-co-field"><span class="shop-co-lab">수취인 이름</span><input name="shipping_name" data-ship-name required maxlength="80" placeholder="받는 분 이름"></label>
            <label class="shop-co-field"><span class="shop-co-lab">수취인 연락처</span><input name="shipping_phone" data-ship-phone required maxlength="30" placeholder="010-0000-0000"></label>
          </div>
          <div class="lu-addr" data-daum-address>
            <span class="lu-addr__legend">배송지</span>
            <div class="lu-addr__row">
              <input type="text" name="shipping_zip" data-addr-zip readonly required maxlength="10" placeholder="우편번호" autocomplete="postal-code">
              <button type="button" class="lu-addr__search" data-addr-search>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
                주소 검색
              </button>
            </div>
            <input type="text" name="shipping_base" data-addr-base readonly required maxlength="300" placeholder="주소 검색으로 선택하세요">
            <input type="text" name="shipping_detail" data-addr-detail maxlength="200" placeholder="상세주소 (동·호수 등)">
            <textarea name="shipping_address" data-addr-combined hidden maxlength="500"></textarea>
          </div>
          <div class="shop-co-save">
            <label class="lu-check"><input type="checkbox" name="save_address" value="1" data-save-address> 이 주소를 배송지에 저장</label>
            <input class="lu-addr-label" type="text" name="address_label" data-addr-label maxlength="40" placeholder="배송지 이름 (집, 회사 등)">
          </div>
        </div>
      </section>

      <section class="shop-co-block" aria-labelledby="shop-co-memo">
        <header class="shop-co-block__head">
          <span class="shop-co-ico shop-co-ico--gold" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
          </span>
          <div>
            <h3 id="shop-co-memo">배송 요청</h3>
            <p>선택 사항입니다. 비워도 주문이 접수됩니다.</p>
          </div>
        </header>
        <div class="shop-co-block__body">
          <label class="shop-co-field"><span class="shop-co-lab">배송 메모</span><textarea name="shipping_memo" maxlength="255" rows="2" placeholder="문 앞, 경비실, 연락 후 배송 등"></textarea></label>
        </div>
      </section>
    </form>
  </div>

  <aside class="shop-cart-summary">
    <header class="shop-co-summary__head">
      <span class="shop-co-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
      </span>
      <div>
        <h2>주문 요약</h2>
        <p>결제 전 수량과 금액을 확인하세요.</p>
      </div>
    </header>
    <ul class="shop-co-items">
      <?php foreach ($cart['items'] as $item): ?>
      <li>
        <img src="<?= e($shopService->productThumb($item)) ?>" alt="">
        <div>
          <strong><?= e((string) $item['name']) ?></strong>
          <span><?= (int) $item['qty'] ?>개 · <?= e($shopService->formatPrice((int) $item['unit_price'])) ?></span>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
    <dl>
      <div><dt>상품 금액</dt><dd id="cartSubtotal"><?= e($shopService->formatPrice($cart['subtotal'])) ?></dd></div>
      <div><dt>배송비</dt><dd id="cartShipping"><?= $cart['shipping_fee'] === 0 ? '무료' : e($shopService->formatPrice($cart['shipping_fee'])) ?></dd></div>
      <div class="shop-cart-total"><dt>결제 예정</dt><dd id="cartTotal"><?= e($shopService->formatPrice($cart['total'])) ?></dd></div>
    </dl>
    <p class="shop-cart-note">5만원 이상 구매 시 배송비 무료</p>
    <?php $tossEnabled = !empty($tossEnabled); ?>
    <button type="submit" class="shop-btn shop-btn--primary shop-btn--block" form="shopCheckoutForm">
      <?= $tossEnabled ? '주문하고 결제하기' : '주문 접수' ?>
    </button>
    <a class="shop-btn shop-btn--outline shop-btn--block" href="<?= url('shop/products') ?>">쇼핑 계속하기</a>
  </aside>
</div>
<?php else: ?>
<div class="shop-empty">
  <p>장바구니가 비어 있습니다.</p>
  <a class="shop-btn shop-btn--primary" href="<?= url('shop/products') ?>">상품 보러가기</a>
</div>
<?php endif; ?>
