<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$dataFile = __DIR__ . '/includes/data/pricing_analysis.php';
$analysis = file_exists($dataFile) ? require $dataFile : array();

$relatedPolicies = array();
if (!empty($analysis['related_policies'])) {
    PolicyService::ensureDefaults($project['id']);
    foreach ($analysis['related_policies'] as $ref) {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, title, policy_key FROM policies WHERE project_id = ? AND policy_key = ? LIMIT 1');
        $stmt->execute(array($project['id'], $ref['key']));
        $row = $stmt->fetch();
        if ($row) {
            $relatedPolicies[] = $row;
        }
    }
}

$pageTitle = '요금정책분석';
$currentPage = 'pricing-analysis';

$printMode = isset($_GET['print']) && $_GET['print'] === '1';
if ($printMode) {
    include __DIR__ . '/views/pricing-analysis-print.php';
    exit;
}

render_page(__DIR__ . '/views/pricing-analysis.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'analysis', 'relatedPolicies'
));
