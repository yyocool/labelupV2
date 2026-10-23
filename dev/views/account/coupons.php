<?php
/** @var array<int, array<string, mixed>> $coupons */
$coupons = $coupons ?? [];
?>
<section class="account-panel card account-coupons">
  <div class="account-panel-head">
    <div>
      <p class="account-orders__crumb"><a href="<?= url('account') ?>">마이페이지</a> · 쿠폰함</p>
      <h2>쿠폰함</h2>
    </div>
    <span class="account-orders__count">사용 가능 <?= number_format(count($coupons)) ?>장</span>
  </div>

  <?php if ($coupons === []): ?>
  <p class="account-empty">지금 사용할 수 있는 쿠폰이 없습니다. 프로모션이 열리면 여기에 모아 드립니다.</p>
  <a class="account-btn account-btn--primary" href="<?= url('shop/products') ?>">상품 보러 가기</a>
  <?php else: ?>
  <ul class="account-coupon-list">
    <?php foreach ($coupons as $coupon): ?>
    <li class="account-coupon-card">
      <div class="account-coupon-card__disc"><?= e((string) $coupon['discount_label']) ?></div>
      <div class="account-coupon-card__body">
        <strong><?= e((string) $coupon['name']) ?></strong>
        <p><?= e((string) $coupon['min_label']) ?></p>
        <p><?= e((string) $coupon['period_label']) ?></p>
        <div class="account-coupon-card__code">
          <code><?= e((string) $coupon['code']) ?></code>
          <button type="button" class="account-btn account-btn--outline" data-copy-coupon="<?= e((string) $coupon['code']) ?>">코드 복사</button>
          <button type="button" class="account-btn account-btn--primary" data-use-coupon="<?= e((string) $coupon['code']) ?>">사용하기</button>
        </div>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
  <p class="account-meta">사용하기를 누르면 쿠폰이 선택되고 상품 목록으로 이동합니다. 주문 금액이 조건에 맞으면 결제할 때 자동으로 적용됩니다.</p>
  <a class="account-btn account-btn--primary" href="<?= url('shop/products') ?>">상품 보러 가기</a>
  <?php endif; ?>
</section>
