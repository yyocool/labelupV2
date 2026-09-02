<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Repositories\ShopRepository;

$manifestPath = $argv[1] ?? '';
if ($manifestPath === '' || !is_file($manifestPath)) {
    fwrite(STDERR, "Usage: php import_product_images.php <manifest.json>\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (!is_array($manifest)) {
    fwrite(STDERR, "Invalid manifest JSON\n");
    exit(1);
}

$repo = new ShopRepository();
$linked = 0;
$missing = 0;

foreach ($manifest as $item) {
    $sku = trim((string) ($item['sku'] ?? ''));
    $path = trim((string) ($item['image_path'] ?? ''));
    if ($sku === '' || $path === '') {
        continue;
    }

    $product = $repo->findProductBySku($sku);
    if (!$product) {
        $missing++;
        continue;
    }

    $repo->syncProductImages((int) $product['id'], [
        ['image_path' => $path, 'sort_order' => 0, 'is_primary' => 1],
    ], $path);
    $linked++;
}

echo json_encode(['linked' => $linked, 'missing_sku' => $missing], JSON_UNESCAPED_UNICODE) . PHP_EOL;
