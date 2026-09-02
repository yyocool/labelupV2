<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Repositories\ShopRepository;

$repo = new ShopRepository();
$updated = 0;
$missing = 0;

foreach ($repo->allProducts() as $product) {
    $sku = trim((string) ($product['sku'] ?? ''));
    if ($sku === '') {
        continue;
    }

    $safe = preg_replace('/[^\w\-]+/', '_', $sku) ?: $sku;
    $safe = substr((string) $safe, 0, 80);
    $webp = '/assets/products/prod_' . $safe . '.webp';
    $png = '/assets/products/prod_' . $safe . '.png';
    $legacy = '/assets/products/spec_' . $safe . '.png';

    $path = null;
    foreach ([$webp, $png, $legacy] as $candidate) {
        if (is_file(public_path(ltrim($candidate, '/')))) {
            $path = $candidate;
            break;
        }
    }
    if ($path === null) {
        $missing++;
        continue;
    }

    $repo->syncProductImages((int) $product['id'], [
        ['image_path' => $path, 'sort_order' => 0, 'is_primary' => 1],
    ], $path);
    $updated++;
}

echo json_encode(['updated' => $updated, 'missing' => $missing], JSON_UNESCAPED_UNICODE) . PHP_EOL;
