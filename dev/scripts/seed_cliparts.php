<?php

declare(strict_types=1);

/**
 * CLI: seed clipart categories + 500 seed items from manifest.
 * Usage: php scripts/seed_cliparts.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\ClipartService;

$service = new ClipartService();
$createdCats = $service->ensureDefaultCategories();

$manifestPath = storage_path('imports/clipart_seed_manifest.json');
if (!is_file($manifestPath)) {
    fwrite(STDERR, "Manifest not found: {$manifestPath}\n");
    exit(1);
}

$raw = file_get_contents($manifestPath);
$data = json_decode((string) $raw, true);
if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
    fwrite(STDERR, "Invalid manifest\n");
    exit(1);
}

$result = $service->importSeedItems($data['items']);
echo json_encode([
    'categories_created' => $createdCats,
    'inserted' => $result['inserted'],
    'skipped' => $result['skipped'],
    'total' => $service->count(),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
