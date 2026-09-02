<?php
/** @var array|null $authUser */
if (empty($authUser)) {
    return;
}
$balance = credit_balance_for_user($authUser);
?>
<a class="credit-pill" href="<?= url('account') ?>#credits" title="내 크레딧">
  <span class="credit-pill-ic" aria-hidden="true">◈</span>
  <span class="credit-pill-label">내 크레딧</span>
  <strong class="credit-pill-amount"><?= e(number_format($balance)) ?> C</strong>
</a>
