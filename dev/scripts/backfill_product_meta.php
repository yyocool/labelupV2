<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Repositories\ShopRepository;

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

$repo = new ShopRepository();
$updated = 0;

foreach ($rows as $row) {
    $sku = trim((string) ($row['sku'] ?? ''));
    if ($sku === '') {
        continue;
    }

    $product = $repo->findProductBySku($sku);
    if (!$product) {
        continue;
    }

    $meta = array_filter([
        'product_no' => $row['product_no'] ?? null,
        'art_no' => $row['art_no'] ?? null,
        'barcode_no' => $row['barcode_no'] ?? null,
        'material_name' => $row['material_name'] ?? null,
        'barcode' => $row['barcode'] ?? null,
        'box_barcode' => $row['box_barcode'] ?? null,
        'paper_size' => $row['paper_size'] ?? null,
        'labels_per_sheet' => $row['labels_per_sheet'] ?? null,
        'std_size' => $row['std_size'] ?? null,
        'spec_mm' => $row['spec_mm'] ?? null,
        'pack_size' => $row['pack_size'] ?? null,
        'box_size' => $row['box_size'] ?? null,
        'sheets_per_pack' => $row['sheets_per_pack'] ?? null,
        'qty_per_box' => $row['qty_per_box'] ?? null,
        'material' => $row['material'] ?? null,
        'weight' => $row['weight'] ?? null,
        'thickness' => $row['thickness'] ?? null,
        'origin' => $row['origin'] ?? null,
        'etc' => $row['etc'] ?? null,
    ], static fn ($v) => $v !== null && $v !== '');

    if ($meta === []) {
        continue;
    }

    $repo->saveProduct([
        'id' => (int) $product['id'],
        'category_id' => (int) $product['category_id'],
        'spec_id' => $product['spec_id'] ?? null,
        'name' => (string) $product['name'],
        'sku' => (string) $product['sku'],
        'price' => (int) $product['price'],
        'sale_price' => $product['sale_price'] ?? null,
        'stock_qty' => (int) $product['stock_qty'],
        'status' => (string) $product['status'],
        'description' => (string) ($product['description'] ?? ''),
        'meta_json' => $meta,
        'sort_order' => (int) ($product['sort_order'] ?? 0),
        'thumbnail' => $product['thumbnail'] ?? null,
    ]);
    $updated++;
}

echo json_encode(['updated' => $updated], JSON_UNESCAPED_UNICODE) . PHP_EOL;
