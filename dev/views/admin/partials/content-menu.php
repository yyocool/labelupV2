<?php
$contentMenus = [
    ['key' => 'content-cliparts', 'label' => '클립아트관리', 'href' => 'admin/content/cliparts', 'ic' => '✦'],
    ['key' => 'content-user-designs', 'label' => '사용자디자인', 'href' => 'admin/content/user-designs', 'ic' => '★'],
    ['key' => 'content-templates', 'label' => '템플릿관리', 'href' => 'admin/content/templates', 'ic' => '▦'],
];
$contentMenus = admin_filter_menu_items($contentMenus);
$isContentOpen = ($menuGroup ?? '') === 'content'
    || in_array((string) ($activeMenu ?? ''), ['content-cliparts', 'content-user-designs', 'content-templates'], true);
if ($contentMenus === []) {
    return;
}
?>
<div class="admin-lnb-group<?= $isContentOpen ? ' is-open' : '' ?>">
  <button type="button" class="admin-lnb-group-toggle" aria-expanded="<?= $isContentOpen ? 'true' : 'false' ?>">
    <span class="ic">◇</span><span class="label">컨텐츠관리</span><span class="admin-lnb-caret">▾</span>
  </button>
  <div class="admin-lnb-group-items">
    <?php foreach ($contentMenus as $m): ?>
    <a class="admin-lnb-sub<?= ($activeMenu ?? '') === $m['key'] ? ' is-active' : '' ?>" href="<?= url($m['href']) ?>" title="<?= e($m['label']) ?>">
      <span class="ic"><?= $m['ic'] ?></span><span class="label"><?= e($m['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</div>
