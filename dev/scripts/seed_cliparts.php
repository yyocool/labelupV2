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
$service->retireLowQualitySeeds();

$manifests = [
    storage_path('imports/clipart_hq_manifest.json'),
    storage_path('imports/clipart_pattern_manifest.json'),
    storage_path('imports/clipart_signboard_manifest.json'),
    storage_path('imports/clipart_character_manifest.json'),
    storage_path('imports/clipart_friends_manifest.json'),
    storage_path('imports/clipart_badge_manifest.json'),
    storage_path('imports/clipart_workphrase_manifest.json'),
    storage_path('imports/clipart_logistics_phrase_manifest.json'),
    storage_path('imports/clipart_traffic_manifest.json'),
];
$inserted = 0;
$skipped = 0;
foreach ($manifests as $manifestPath) {
    if (!is_file($manifestPath)) {
        continue;
    }
    $raw = file_get_contents($manifestPath);
    $data = json_decode((string) $raw, true);
    if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
        fwrite(STDERR, "Invalid manifest: {$manifestPath}\n");
        continue;
    }
    $result = $service->importSeedItems($data['items']);
    $inserted += (int) $result['inserted'];
    $skipped += (int) $result['skipped'];
}
echo json_encode([
    'categories_created' => $createdCats,
    'inserted' => $inserted,
    'skipped' => $skipped,
    'total' => $service->count(),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
