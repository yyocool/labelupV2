<?php
$aiMenus = [
    ['key' => 'ai-example-prompts', 'label' => '예시프롬프트 관리', 'href' => 'admin/ai/example-prompts', 'ic' => '✦'],
    ['key' => 'ai-token-logs', 'label' => '토큰사용로그', 'href' => 'admin/ai/token-logs', 'ic' => '◎'],
    ['key' => 'ai-member-usage', 'label' => '회원별 사용', 'href' => 'admin/ai/member-usage', 'ic' => '◇'],
    ['key' => 'ai-usage', 'label' => '사용량 통계', 'href' => 'admin/ai/usage', 'ic' => '▣'],
];
$aiMenus = admin_filter_menu_items($aiMenus);
$isAiOpen = ($menuGroup ?? '') === 'ai'
    || in_array((string) ($activeMenu ?? ''), ['ai-example-prompts', 'ai-token-logs', 'ai-member-usage', 'ai-usage'], true);
if ($aiMenus === []) {
    return;
}
?>
<div class="admin-lnb-group<?= $isAiOpen ? ' is-open' : '' ?>">
  <button type="button" class="admin-lnb-group-toggle" aria-expanded="<?= $isAiOpen ? 'true' : 'false' ?>">
    <span class="ic">✦</span><span class="label">AI 관리</span><span class="admin-lnb-caret">▾</span>
  </button>
  <div class="admin-lnb-group-items">
    <?php foreach ($aiMenus as $m): ?>
    <a class="admin-lnb-sub<?= ($activeMenu ?? '') === $m['key'] ? ' is-active' : '' ?>" href="<?= url($m['href']) ?>" title="<?= e($m['label']) ?>">
      <span class="ic"><?= $m['ic'] ?></span><span class="label"><?= e($m['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</div>
