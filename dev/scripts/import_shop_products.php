<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\ShopProductImportService;

$jsonPath = $argv[1] ?? (APP_ROOT . '/storage/imports/products_import.json');

if (!is_readable($jsonPath)) {
    fwrite(STDERR, "JSON not found: {$jsonPath}\n");
    exit(1);
}

$rows = json_decode((string) file_get_contents($jsonPath), true);
if (!is_array($rows)) {
    fwrite(STDERR, "Invalid JSON\n");
    exit(1);
}

try {
    $stats = (new ShopProductImportService())->importRows($rows);
    echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
