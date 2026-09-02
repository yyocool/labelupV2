<?php
/**
 * 요금정책분석 PDF(인쇄) 전용 페이지
 */
require_once APP_ROOT . '/includes/print_layout.php';

$filename = '요금정책분석_' . date('Ymd');
$metaBits = array();
if (!empty($analysis['meta']['version'])) {
    $metaBits[] = 'v' . $analysis['meta']['version'];
}
if (!empty($analysis['meta']['updated'])) {
    $metaBits[] = $analysis['meta']['updated'];
}
$projectName = isset($project['name']) ? $project['name'] : 'Label-UP';

print_layout_start(array(
    'title' => '요금정책분석 PDF',
    'filename' => $filename,
    'meta' => $projectName . ($metaBits ? ' · ' . implode(' · ', $metaBits) : ''),
    'back_url' => url('pricing-analysis.php'),
    'preview_url' => url('pricing-analysis.php?print=1&noprint=1'),
    'stylesheet' => true,
    'body_class' => 'print-hide-ui',
    'max_width' => '960px',
    'landscape' => true,
));
?>
        <header class="print-doc-head">
            <p class="eyebrow">Pricing Policy Analysis</p>
            <h1>요금정책분석</h1>
            <p class="sub">국내·글로벌 대비 Label-UP 요금·AI 추천안<?= !empty($analysis['meta']['basis']) ? ' · ' . e($analysis['meta']['basis']) : '' ?></p>
        </header>
        <div class="print-doc-body">
            <?php include __DIR__ . '/pricing-analysis-document.inc.php'; ?>
        </div>
<?php
print_layout_end(array(
    'foot_left' => '요금정책분석',
    'foot_right' => date('Y.m.d'),
));
