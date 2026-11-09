<?php
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
$html = file_get_contents(__DIR__ . '/index.html');
$base = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/editor/index.php'))), '/');
if ($base === '/' || $base === '\\') {
    $base = '';
}
$endpoint = json_encode($base . '/api/access/beacon', JSON_UNESCAPED_SLASHES);
$src = htmlspecialchars($base . '/js/access-beacon.js', ENT_QUOTES, 'UTF-8');
$tag = '<script>window.LABELUP_ACCESS_BEACON=' . $endpoint . ';</script><script src="' . $src . '" defer></script>';
if (!is_string($html)) {
    return;
}
if (stripos($html, '</body>') !== false) {
    echo preg_replace('~</body>~i', $tag . '</body>', $html, 1);
    return;
}
echo $html, $tag;
