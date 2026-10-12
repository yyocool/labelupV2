<?php
/** @var array|null $authUser */
$loggedIn = !empty($authUser);
$balance = $loggedIn ? credit_balance_for_user($authUser) : 0;
$debtClass = $balance < 0 ? ' is-debt' : '';
$here = (string) ($_SERVER['REQUEST_URI'] ?? '/');
?>
<?php if ($loggedIn): ?>
<a class="credit-pill<?= $debtClass ?>" href="<?= url('account') ?>#credits" title="<?= $balance < 0 ? '마이너스 잔액 · 충전 시 자동 차감' : '내 잉크' ?>">
  <span class="credit-pill-ic" aria-hidden="true">◈</span>
  <span class="credit-pill-label"><?= $balance < 0 ? '미정산' : '내 잉크' ?></span>
  <strong class="credit-pill-amount"><?= e(number_format($balance)) ?> C</strong>
</a>
<button type="button" class="bug-report-btn" id="bugReportOpen" data-endpoint="<?= e(url('api/error-reports')) ?>">오류&amp;제안</button>
<?php else: ?>
<a class="bug-report-btn" href="<?= url('login') ?>?redirect=<?= rawurlencode($here) ?>">오류&amp;제안</a>
<?php endif; ?>
<?php if ($loggedIn): ?>
<script src="<?= js('bug-report.js') ?>"></script>
<?php endif; ?>
