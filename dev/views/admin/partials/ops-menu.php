<?php
$opsMenus = [
    ['key' => 'users', 'label' => '회원 관리', 'href' => 'admin/users', 'ic' => '◎'],
    ['key' => 'settings', 'label' => '운영설정', 'href' => 'admin/settings', 'ic' => '⚙'],
    ['key' => 'ops-hero-slides', 'label' => '히어로 이미지 관리', 'href' => 'admin/ops/hero-slides', 'ic' => '▦'],
    ['key' => 'ops-event-popups', 'label' => '이벤트 팝업관리', 'href' => 'admin/ops/event-popups', 'ic' => '◎'],
    ['key' => 'ops-credit-rewards', 'label' => '크레딧보상 관리', 'href' => 'admin/ops/credit-rewards', 'ic' => '◈'],
    ['key' => 'ops-purchase-credits', 'label' => '구매크레딧', 'href' => 'admin/ops/purchase-credits', 'ic' => '▣'],
];
$isOpsOpen = ($menuGroup ?? '') === 'ops'
    || in_array((string) ($activeMenu ?? ''), ['users', 'settings', 'ops-hero-slides', 'ops-event-popups', 'ops-credit-rewards', 'ops-purchase-credits'], true);
?>
<div class="admin-lnb-group<?= $isOpsOpen ? ' is-open' : '' ?>">
  <button type="button" class="admin-lnb-group-toggle" aria-expanded="<?= $isOpsOpen ? 'true' : 'false' ?>">
    <span class="ic">▤</span><span class="label">운영관리</span><span class="admin-lnb-caret">▾</span>
  </button>
  <div class="admin-lnb-group-items">
    <?php foreach ($opsMenus as $m): ?>
    <a class="admin-lnb-sub<?= ($activeMenu ?? '') === $m['key'] ? ' is-active' : '' ?>" href="<?= url($m['href']) ?>" title="<?= e($m['label']) ?>">
      <span class="ic"><?= $m['ic'] ?></span><span class="label"><?= e($m['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</div>
