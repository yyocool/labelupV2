<?php
$settingsMenus = admin_filter_menu_items([
    ['key' => 'settings-admins', 'label' => '관리자', 'href' => 'admin/settings/admins', 'ic' => '⚙'],
    ['key' => 'settings-member-grades', 'label' => '회원등급 설정', 'href' => 'admin/settings/member-grades', 'ic' => '◇'],
    ['key' => 'settings-seo', 'label' => 'SEO 설정', 'href' => 'admin/settings/seo', 'ic' => '◎'],
    ['key' => 'settings-tracking', 'label' => '광고 스크립트', 'href' => 'admin/settings/tracking', 'ic' => '◈'],
]);
$isSettingsOpen = ($menuGroup ?? '') === 'settings'
    || in_array((string) ($activeMenu ?? ''), ['settings-admins', 'settings-member-grades', 'settings-seo', 'settings-tracking'], true);
if ($settingsMenus === []) {
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
  </div>
</div>
