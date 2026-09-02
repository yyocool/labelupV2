<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$dataFile = __DIR__ . '/includes/data/feature_spec.php';
$spec = file_exists($dataFile) ? require $dataFile : array();

$pageTitle = '기능 명세표';
$currentPage = 'feature-spec';

render_page(__DIR__ . '/views/feature-spec.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker', 'spec'
));
