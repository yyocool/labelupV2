<?php
/**
 * labelup 이용약관 v2 적용 스크립트
 * 사용: php scripts/apply_legal_terms_v2.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\LegalDocumentService;

$path = dirname(__DIR__) . '/database/data/legal_terms_v2.html';
if (!is_readable($path)) {
    fwrite(STDERR, "Missing: {$path}\n");
    exit(1);
}

$html = file_get_contents($path);
if ($html === false || trim($html) === '') {
    fwrite(STDERR, "Empty terms content\n");
    exit(1);
}

$svc = new LegalDocumentService();
$saved = $svc->update('terms', '이용약관', $html);
echo 'OK terms version=' . ($saved['version'] ?? '?') . ' length=' . strlen($html) . PHP_EOL;
