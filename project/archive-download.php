<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$id = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
$doc = ArchiveService::getById($id);

if (!$doc) {
    flash('error', '파일을 찾을 수 없습니다.');
    redirect('archive.php');
}

$project = ProjectService::getOrCreateDefault();
if ((int) $doc['project_id'] !== (int) $project['id']) {
    flash('error', '접근 권한이 없습니다.');
    redirect('archive.php');
}

$path = ArchiveService::getFilePath($doc);
if (!is_file($path)) {
    flash('error', '파일이 존재하지 않습니다.');
    redirect('archive.php');
}

$mime = $doc['mime_type'] ? $doc['mime_type'] : 'application/octet-stream';
$filename = $doc['original_name'];

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-cache');

readfile($path);
exit;
