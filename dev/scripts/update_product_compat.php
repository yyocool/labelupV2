#!/usr/bin/env php
<?php
/**
 * products_import.json 기준으로 호환코드만 갱신
 * 폼텍 No → compat_formtec
 * 애니라벨No → compat_anylabel
 * 아이라벨 No → compat_ilabel
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Helpers\Database;
use App\Helpers\ShopCompatHelper;

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

$pdo = Database::connection();
$find = $pdo->prepare(
    'SELECT id, compat_formtec, compat_ilabel, compat_anylabel FROM shop_products WHERE sku = ? LIMIT 1'
);
$update = $pdo->prepare(
    'UPDATE shop_products
     SET compat_formtec = ?, compat_ilabel = ?, compat_anylabel = ?, updated_at = NOW()
     WHERE id = ?'
);

$stats = [
    'updated' => 0,
    'unchanged' => 0,
    'missing' => 0,
    'skipped' => 0,
    'with_formtec' => 0,
    'with_ilabel' => 0,
    'with_anylabel' => 0,
];

$pdo->beginTransaction();
try {
    foreach ($rows as $row) {
        $sku = trim((string) ($row['sku'] ?? ''));
        if ($sku === '' || $sku === '127 sku') {
            $stats['skipped']++;
            continue;
        }

        $formtec = ShopCompatHelper::normalize(
            $row['compat_formtec'] ?? ($row['product_no'] ?? null)
        );
        $anylabel = ShopCompatHelper::normalize(
            $row['compat_anylabel'] ?? ($row['art_no'] ?? null)
        );
        $ilabel = ShopCompatHelper::normalize(
            $row['compat_ilabel'] ?? ($row['barcode_no'] ?? null)
        );

        if ($formtec !== null) {
            $stats['with_formtec']++;
        }
        if ($ilabel !== null) {
            $stats['with_ilabel']++;
        }
        if ($anylabel !== null) {
            $stats['with_anylabel']++;
        }

        $find->execute([$sku]);
        $product = $find->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            echo "MISSING {$sku}\n";
            $stats['missing']++;
            continue;
        }

        $oldF = $product['compat_formtec'] !== null && $product['compat_formtec'] !== ''
            ? (string) $product['compat_formtec'] : null;
        $oldI = $product['compat_ilabel'] !== null && $product['compat_ilabel'] !== ''
            ? (string) $product['compat_ilabel'] : null;
        $oldA = $product['compat_anylabel'] !== null && $product['compat_anylabel'] !== ''
            ? (string) $product['compat_anylabel'] : null;

        if ($oldF === $formtec && $oldI === $ilabel && $oldA === $anylabel) {
            $stats['unchanged']++;
            continue;
        }

        $update->execute([$formtec, $ilabel, $anylabel, (int) $product['id']]);
        $stats['updated']++;
        echo "UPDATE {$sku} formtec=" . ($formtec ?? '-') .
            " ilabel=" . ($ilabel ?? '-') .
            " anylabel=" . ($anylabel ?? '-') . "\n";
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
echo "OK\n";
