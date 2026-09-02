<?php
/**
 * 정책관리 PDF(인쇄) 전용 페이지 — 필터된(또는 전체) 정책 본문 포함
 */
require_once APP_ROOT . '/includes/print_layout.php';

$printPolicies = isset($policies) ? $policies : array();
$filename = '정책관리_' . date('Ymd');
$filterLabel = '전체';
if ($filterCategory !== 'all' && isset($categories[$filterCategory])) {
    $filterLabel = $categories[$filterCategory]['label'];
}
if ($filterStatus !== 'all' && isset($statuses[$filterStatus])) {
    $filterLabel .= ' · ' . $statuses[$filterStatus]['label'];
}
$projectName = isset($project['name']) ? $project['name'] : 'Label-UP';

$qs = array('print' => '1');
if ($filterCategory !== 'all') {
    $qs['category'] = $filterCategory;
}
if ($filterStatus !== 'all') {
    $qs['status'] = $filterStatus;
}
$previewQs = $qs;
$previewQs['noprint'] = '1';
$backQs = array();
if ($filterCategory !== 'all') {
    $backQs['category'] = $filterCategory;
}
if ($filterStatus !== 'all') {
    $backQs['status'] = $filterStatus;
}

print_layout_start(array(
    'title' => '정책관리 PDF',
    'filename' => $filename,
    'meta' => $projectName . ' · ' . $filterLabel . ' · ' . count($printPolicies) . '건',
    'back_url' => url('policies.php' . ($backQs ? '?' . http_build_query($backQs) : '')),
    'preview_url' => url('policies.php?' . http_build_query($previewQs)),
    'stylesheet' => false,
    'max_width' => '840px',
));
?>
        <header class="print-doc-head">
            <p class="eyebrow">Policies</p>
            <h1>정책관리</h1>
            <p class="sub"><?= e($filterLabel) ?> · 총 <?= count($printPolicies) ?>건 · <?= e(date('Y.m.d H:i')) ?></p>
        </header>

        <?php if (empty($printPolicies)): ?>
        <p style="color:#64748b;font-size:14px">표시할 정책이 없습니다.</p>
        <?php else: ?>
            <?php foreach ($printPolicies as $policy): ?>
            <?php
                $cat = isset($categories[$policy['category']]) ? $categories[$policy['category']] : array('label' => $policy['category'], 'icon' => '');
                $st = isset($statuses[$policy['status']]) ? $statuses[$policy['status']] : array('label' => $policy['status']);
                $aud = isset($audiences[$policy['audience']]) ? $audiences[$policy['audience']] : $policy['audience'];
            ?>
            <article class="print-policy-item">
                <h2><?= e($policy['title']) ?></h2>
                <div class="print-policy-meta">
                    <span><?= e((isset($cat['icon']) ? $cat['icon'] . ' ' : '') . $cat['label']) ?></span>
                    <span>v<?= e($policy['version']) ?></span>
                    <span><?= e($st['label']) ?></span>
                    <span><?= e($aud) ?></span>
                    <?php if (!empty($policy['related_menu_code'])): ?>
                    <span>메뉴 <?= e($policy['related_menu_code']) ?></span>
                    <?php endif; ?>
                    <span><code><?= e($policy['policy_key']) ?></code></span>
                </div>
                <?php if (!empty($policy['summary'])): ?>
                <p class="print-policy-summary"><?= e($policy['summary']) ?></p>
                <?php endif; ?>
                <div class="print-policy-body"><?= e(isset($policy['content']) ? $policy['content'] : '') ?></div>
            </article>
            <?php endforeach; ?>
        <?php endif; ?>
<?php
print_layout_end(array(
    'foot_left' => '정책관리 · ' . $filterLabel,
    'foot_right' => date('Y.m.d'),
));
