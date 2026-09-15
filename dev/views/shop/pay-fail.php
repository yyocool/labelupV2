<?php
$orderNo = (string) ($orderNo ?? '');
$failMessage = (string) ($failMessage ?? '결제가 취소되었거나 실패했습니다.');
$failCode = (string) ($failCode ?? '');
$canRetry = !empty($canRetry);
$source = (string) ($source ?? 'shop');
?>
<section class="shop-page-head">
  <h1>결제 실패</h1>
  <p>결제가 완료되지 않았습니다. 다시 시도하거나 장바구니로 돌아갈 수 있습니다.</p>
</section>

<div class="shop-pay-fail">
  <div class="shop-pay-fail__card">
    <p class="shop-pay-fail__msg"><?= e($failMessage) ?></p>
    <?php if ($failCode !== ''): ?>
    <p class="shop-pay-fail__code">코드: <code><?= e($failCode) ?></code></p>
    <?php endif; ?>
    <?php if ($orderNo !== ''): ?>
    <p class="shop-pay-fail__order">주문번호 <strong><?= e($orderNo) ?></strong></p>
    <?php endif; ?>
    <div class="shop-pay-fail__actions">
      <?php if ($canRetry && $orderNo !== ''): ?>
      <button type="button" class="shop-btn shop-btn--primary" id="shopPayRetryBtn"
        data-order-no="<?= e($orderNo) ?>" data-source="<?= e($source) ?>">다시 결제하기</button>
      <?php endif; ?>
      <a class="shop-btn" href="<?= url('shop/cart') ?>">장바구니</a>
      <a class="shop-btn" href="<?= url('shop/products') ?>">상품 계속 보기</a>
    </div>
  </div>
</div>
