<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$dataFile = __DIR__ . '/includes/data/competitive_analysis.php';
$analysis = file_exists($dataFile) ? require $dataFile : array();

$pageTitle = '경쟁서비스 분석';
$currentPage = 'competitive-analysis';

$printMode = isset($_GET['print']) && $_GET['print'] === '1';
if ($printMode) {
    include __DIR__ . '/views/competitive-analysis-print.php';
    exit;
}

render_page(__DIR__ . '/views/competitive-analysis.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker', 'analysis'
));
