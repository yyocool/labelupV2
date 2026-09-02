<?php
/**
 * 회의 첨부파일 다운로드 (attachs 폴더만 허용)
 * GET name=파일명  → 단일 파일
 * POST names[]=…  → 선택 파일 ZIP (복수)
 */
$attachDir = __DIR__ . DIRECTORY_SEPARATOR . 'attachs';
if (!is_dir($attachDir)) {
    http_response_code(404);
    exit('첨부 폴더가 없습니다.');
}

function meeting_safe_basename($name)
{
    $name = basename(str_replace(array('\\', "\0"), '', (string) $name));
    if ($name === '' || $name === '.' || $name === '..' || $name[0] === '.') {
        return '';
    }
    if (!preg_match('/^[A-Za-z0-9가-힣_\-\.\(\)\[\]\s]+$/u', $name)) {
        return '';
    }
    return $name;
}

function meeting_list_requested_names()
{
    $names = array();
    if (isset($_POST['names']) && is_array($_POST['names'])) {
        $names = $_POST['names'];
    } elseif (isset($_GET['name'])) {
        $names = array($_GET['name']);
    } elseif (isset($_POST['name'])) {
        $names = array($_POST['name']);
    }
    $clean = array();
    foreach ($names as $n) {
        $b = meeting_safe_basename($n);
        if ($b !== '') {
            $clean[$b] = true;
        }
    }
    return array_keys($clean);
}

$requested = meeting_list_requested_names();
if (empty($requested)) {
    http_response_code(400);
    exit('다운로드할 파일이 없습니다.');
}

$paths = array();
foreach ($requested as $name) {
    $path = $attachDir . DIRECTORY_SEPARATOR . $name;
    $realAttach = realpath($attachDir);
    $realFile = realpath($path);
    if ($realFile === false || $realAttach === false) {
        continue;
    }
    if (strpos($realFile, $realAttach) !== 0 || !is_file($realFile)) {
        continue;
    }
    $paths[$name] = $realFile;
}

if (empty($paths)) {
    http_response_code(404);
    exit('파일을 찾을 수 없습니다.');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

if (count($paths) === 1) {
    $name = key($paths);
    $path = $paths[$name];
    $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
    if (!$mime) {
        $mime = 'application/octet-stream';
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
    header('Cache-Control: no-cache, must-revalidate');
    readfile($path);
    exit;
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('ZIP 확장이 없어 여러 파일을 한 번에 받을 수 없습니다. 파일을 하나씩 받아 주세요.');
}

$zipName = 'meeting-04-attachments-' . date('Ymd') . '.zip';
$tmp = tempnam(sys_get_temp_dir(), 'mtgzip');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
    @unlink($tmp);
    http_response_code(500);
    exit('ZIP을 만들 수 없습니다.');
}
foreach ($paths as $name => $path) {
    $zip->addFile($path, $name);
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Length: ' . filesize($tmp));
header('Content-Disposition: attachment; filename="' . rawurlencode($zipName) . '"; filename*=UTF-8\'\'' . rawurlencode($zipName));
header('Cache-Control: no-cache, must-revalidate');
readfile($tmp);
@unlink($tmp);
exit;
