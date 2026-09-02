<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!file_exists(APP_ROOT . '/storage/installed.lock')) {
    redirect('install.php');
}

require_login();
extract(init_project_context());

$user = current_user();
$userId = $user ? $user['id'] : null;

$doc = FeatureMapService::ensureDefaults($project['id'], $userId);
$scope = FeatureMapService::getScopeData($doc);
$phaseSummaries = FeatureMapService::listScopePhaseSummaries($scope);

$phaseKey = isset($_GET['phase']) ? $_GET['phase'] : 'phase-1';
$phase = FeatureMapService::findScopePhase($scope, $phaseKey);

if (!$phase) {
    flash('error', '구축 범위 단계를 찾을 수 없습니다.');
    redirect('feature-map.php#fmap-scope');
}

$deckSlides = FeatureMapService::buildPhaseDeckSlides($phase);
$tone = (isset($phase['tone']) && $phase['tone'] === 'amber') ? 'amber' : 'teal';
$phaseId = isset($phase['id']) ? $phase['id'] : $phaseKey;

$pageTitle = '구축 범위 · ' . (isset($phase['name']) ? $phase['name'] : '');
$currentPage = 'feature-map';

render_page(__DIR__ . '/views/feature-map-scope.php', compact(
    'pageTitle', 'currentPage', 'project', 'menuTree', 'phaseTracker',
    'scope', 'phase', 'phaseSummaries', 'deckSlides', 'tone', 'phaseId'
));
