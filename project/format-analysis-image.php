<?php
require_once __DIR__ . '/includes/bootstrap.php';

// <img> 요청에서 로그인 리다이렉트(HTML)가 나가면 깨진 이미지로 보임
if (!is_logged_in() || !is_super_admin()) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unauthorized';
    exit;
}

$analysisId = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
$file = isset($_GET['file']) ? $_GET['file'] : '';

$row = FormatAnalysisService::getAnalysisById($analysisId);
if (!$row) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$project = ProjectService::getOrCreateDefault();
if ((int) $row['project_id'] !== (int) $project['id']) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden';
    exit;
}

$payload = FormatAnalysisService::getImagePayload($row['project_id'], $analysisId, $file);
if (!$payload) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Image not found';
    exit;
}

header('Content-Type: ' . $payload['mime']);
header('Content-Length: ' . strlen($payload['bytes']));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $payload['bytes'];
exit;
