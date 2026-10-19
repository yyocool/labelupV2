<?php
/**
 * PHPS 호스트가 icudt.dat 를 403 으로 막는다. 브라우저가 .dat 를 요청해도
 * 같은 ICU 테이블을 PHP 로 내려 93% 로딩이 멈추지 않게 한다.
 */
$file = __DIR__ . '/_framework/icudt.bin';
if (!is_file($file)) {
    $file = __DIR__ . '/_framework/icudt.dat';
}
if (!is_file($file)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'icu missing';
    exit;
}
header('Content-Type: application/octet-stream');
header('Content-Length: ' . (string) filesize($file));
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
readfile($file);
