<?php
$statsMenus = [
    ['key' => 'stats-overview', 'label' => '개요', 'href' => 'admin/stats', 'ic' => '▣'],
    ['key' => 'stats-trend', 'label' => '기간 추이', 'href' => 'admin/stats/trend', 'ic' => '↗'],
    ['key' => 'stats-pages', 'label' => '페이지', 'href' => 'admin/stats/pages', 'ic' => '▦'],
    ['key' => 'stats-referrers', 'label' => '유입 경로', 'href' => 'admin/stats/referrers', 'ic' => '◎'],
    ['key' => 'stats-visitors', 'label' => '방문자', 'href' => 'admin/stats/visitors', 'ic' => '◇'],
    ['key' => 'stats-environment', 'label' => '접속 환경', 'href' => 'admin/stats/environment', 'ic' => '◐'],
    ['key' => 'stats-hours', 'label' => '시간대', 'href' => 'admin/stats/hours', 'ic' => '◷'],
    ['key' => 'stats-live', 'label' => '실시간', 'href' => 'admin/stats/live', 'ic' => '●'],
    ['key' => 'stats-logs', 'label' => '접속 로그', 'href' => 'admin/stats/logs', 'ic' => '≡'],
];
$statsMenus = admin_filter_menu_items($statsMenus);
$statsKeys = array_column($statsMenus, 'key');
$isStatsOpen = ($menuGroup ?? '') === 'stats' || in_array((string) ($activeMenu ?? ''), $statsKeys, true);
if ($statsMenus === []) {
    return;
}
?>
<div class="admin-lnb-group<?= $isStatsOpen ? ' is-open' : '' ?>">
  <button type="button" class="admin-lnb-group-toggle" aria-expanded="<?= $isStatsOpen ? 'true' : 'false' ?>">
    <span class="ic">▤</span><span class="label">접속통계</span><span class="admin-lnb-caret">▾</span>
  </button>
  <div class="admin-lnb-group-items">
    <?php foreach ($statsMenus as $m): ?>
    <a class="admin-lnb-sub<?= ($activeMenu ?? '') === $m['key'] ? ' is-active' : '' ?>" href="<?= url($m['href']) ?>" title="<?= e($m['label']) ?>">
      <span class="ic"><?= $m['ic'] ?></span><span class="label"><?= e($m['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</div>
