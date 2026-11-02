<?php
$settingsMenus = admin_filter_menu_items([
    ['key' => 'settings-admins', 'label' => '관리자', 'href' => 'admin/settings/admins', 'ic' => '⚙'],
    ['key' => 'settings-member-grades', 'label' => '회원등급 설정', 'href' => 'admin/settings/member-grades', 'ic' => '◇'],
    ['key' => 'settings-environment', 'label' => '환경설정', 'href' => 'admin/settings/environment', 'ic' => '◐'],
    ['key' => 'settings-intro', 'label' => '인트로설정', 'href' => 'admin/settings/intro', 'ic' => '▶'],
    ['key' => 'settings-seo', 'label' => 'SEO 설정', 'href' => 'admin/settings/seo', 'ic' => '◎'],
    ['key' => 'settings-tracking', 'label' => '광고 스크립트', 'href' => 'admin/settings/tracking', 'ic' => '◈'],
]);
$notifyMenus = admin_filter_menu_items([
    ['key' => 'settings-notify-sms', 'label' => '문자알림 설정', 'href' => 'admin/settings/notify-sms', 'ic' => '✉'],
]);
$isSettingsOpen = ($menuGroup ?? '') === 'settings'
    || in_array((string) ($activeMenu ?? ''), ['settings-admins', 'settings-member-grades', 'settings-environment', 'settings-intro', 'settings-seo', 'settings-tracking', 'settings-notify-sms'], true);
if ($settingsMenus === [] && $notifyMenus === []) {
    return;
}
?>
<div class="admin-lnb-group<?= $isSettingsOpen ? ' is-open' : '' ?>">
  <button type="button" class="admin-lnb-group-toggle" aria-expanded="<?= $isSettingsOpen ? 'true' : 'false' ?>">
    <span class="ic">⚙</span><span class="label">설정</span><span class="admin-lnb-caret">▾</span>
  </button>
  <div class="admin-lnb-group-items">
    <?php foreach ($settingsMenus as $m): ?>
    <a class="admin-lnb-sub<?= ($activeMenu ?? '') === $m['key'] ? ' is-active' : '' ?>" href="<?= url($m['href']) ?>" title="<?= e($m['label']) ?>">
      <span class="ic"><?= $m['ic'] ?></span><span class="label"><?= e($m['label']) ?></span>
    </a>
    <?php endforeach; ?>
    <?php if ($notifyMenus !== []): ?>
    <div class="admin-lnb-subhead"><span class="ic">🔔</span><span class="label">알림 설정</span></div>
    <?php foreach ($notifyMenus as $m): ?>
    <a class="admin-lnb-sub admin-lnb-sub--nested<?= ($activeMenu ?? '') === $m['key'] ? ' is-active' : '' ?>" href="<?= url($m['href']) ?>" title="알림 설정 › <?= e($m['label']) ?>">
      <span class="ic"><?= $m['ic'] ?></span><span class="label"><?= e($m['label']) ?></span>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
