<?php
/** @var array|null $authUser */
if (empty($authUser)) {
    return;
}
$balance = credit_balance_for_user($authUser);
$debtClass = $balance < 0 ? ' is-debt' : '';
?>
<a class="credit-pill<?= $debtClass ?>" href="<?= url('account') ?>#credits" title="<?= $balance < 0 ? '마이너스 잔액 · 충전 시 자동 차감' : '내 크레딧' ?>">
  <span class="credit-pill-ic" aria-hidden="true">◈</span>
  <span class="credit-pill-label"><?= $balance < 0 ? '미정산' : '내 크레딧' ?></span>
  <strong class="credit-pill-amount"><?= e(number_format($balance)) ?> C</strong>
</a>
