<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Repositories\ShopRepository;

$repo = new ShopRepository();
$updated = 0;

foreach ($repo->allSpecs() as $spec) {
    $id = (int) ($spec['id'] ?? 0);
    if ($id <= 0) {
        continue;
    }

    $path = '/assets/specs/spec_' . $id . '.webp';
    if (!is_file(public_path(ltrim($path, '/')))) {
        continue;
    }

    $repo->saveSpec([
        'id' => $id,
        'name' => (string) $spec['name'],
        'image_path' => $path,
        'width_mm' => (float) $spec['width_mm'],
        'height_mm' => (float) $spec['height_mm'],
        'material' => (string) ($spec['material'] ?? ''),
        'shape' => (string) ($spec['shape'] ?? 'rect'),
        'labels_per_sheet' => $spec['labels_per_sheet'] ?? null,
        'description' => (string) ($spec['description'] ?? ''),
        'is_active' => !empty($spec['is_active']),
    ]);
    $updated++;
}

echo json_encode(['updated' => $updated], JSON_UNESCAPED_UNICODE) . PHP_EOL;
