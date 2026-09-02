<?php
/**
 * 경쟁서비스 분석 PDF(인쇄) 전용 페이지
 */
require_once APP_ROOT . '/includes/print_layout.php';

$filename = '경쟁서비스분석_' . date('Ymd');
$metaBits = array();
if (!empty($analysis['meta']['version'])) {
    $metaBits[] = 'v' . $analysis['meta']['version'];
}
if (!empty($analysis['meta']['updated'])) {
    $metaBits[] = $analysis['meta']['updated'];
}
$projectName = isset($project['name']) ? $project['name'] : 'Label-UP';

print_layout_start(array(
    'title' => '경쟁서비스 분석 PDF',
    'filename' => $filename,
    'meta' => $projectName . ($metaBits ? ' · ' . implode(' · ', $metaBits) : ''),
    'back_url' => url('competitive-analysis.php'),
    'preview_url' => url('competitive-analysis.php?print=1&noprint=1'),
    'stylesheet' => true,
    'body_class' => 'print-hide-ui',
    'max_width' => '960px',
));
?>
        <header class="print-doc-head">
            <p class="eyebrow">Competitive Analysis</p>
            <h1>경쟁서비스 분석</h1>
            <p class="sub">경쟁사 분석 · SWOT · 라벨 GPT · Label-UP 전략<?= !empty($analysis['meta']['basis']) ? ' · ' . e($analysis['meta']['basis']) : '' ?></p>
        </header>
        <div class="print-doc-body">
            <?php include __DIR__ . '/competitive-analysis-document.inc.php'; ?>
        </div>
<?php
print_layout_end(array(
    'foot_left' => '경쟁서비스 분석',
    'foot_right' => date('Y.m.d'),
));
