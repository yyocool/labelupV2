#!/usr/bin/env php
<?php
/**
 * products_import.json 의 group → 제품분류(11) slug 로 shop_products.category_id 재할당.
 * 썸네일/호환코드 등 다른 필드는 유지한다.
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Helpers\Database;

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

/** @var array<string, string> */
$groupSlugs = [
    '물류관리용/주소용/바코드용/인덱스용' => 'logistics-label',
    '물류관리용 라벨' => 'logistics-label',
    '인덱스용라벨' => 'logistics-label',
    '주소용라벨' => 'logistics-label',
    '바코드용라벨' => 'logistics-label',
    '정부문서화일 라벨' => 'government-doc',
    '정부문서' => 'government-doc',
    '광택 라벨' => 'gloss-label',
    '광택라벨' => 'gloss-label',
    '방수 라벨' => 'waterproof-label',
    '방수라벨' => 'waterproof-label',
    '반투명 라벨' => 'translucent-label',
    '반투명라벨' => 'translucent-label',
    '잉크젯 투명 라벨' => 'inkjet-clear-label',
    '잉크젯 투명라벨' => 'inkjet-clear-label',
    '레이저 투명 라벨' => 'laser-clear-label',
    '레이저 투명라벨' => 'laser-clear-label',
    '보호용 필름' => 'protective-film',
    '컬러 라벨(형광)' => 'color-label',
    '컬러라벨' => 'color-label',
    '파스텔 컬러 라벨' => 'pastel-color-label',
    '크라프트 라벨' => 'kraft-label',
];

/** slug → 표시명 (정렬용) */
$canonicalNames = [
    'logistics-label' => '물류관리용/주소용/바코드용/인덱스용',
    'government-doc' => '정부문서화일 라벨',
    'gloss-label' => '광택 라벨',
    'waterproof-label' => '방수 라벨',
    'translucent-label' => '반투명 라벨',
    'inkjet-clear-label' => '잉크젯 투명 라벨',
    'laser-clear-label' => '레이저 투명 라벨',
    'protective-film' => '보호용 필름',
    'color-label' => '컬러 라벨(형광)',
    'pastel-color-label' => '파스텔 컬러 라벨',
    'kraft-label' => '크라프트 라벨',
];

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $catStmt = $pdo->query('SELECT id, slug, sort_order FROM shop_categories');
    $cats = [];
    while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
        $cats[(string) $row['slug']] = [
            'id' => (int) $row['id'],
            'sort' => (int) $row['sort_order'],
        ];
    }

    foreach ($canonicalNames as $slug => $name) {
        if (!isset($cats[$slug])) {
            throw new RuntimeException("카테고리 없음: {$slug} ({$name})");
        }
    }

    $find = $pdo->prepare('SELECT id, category_id, sort_order, meta_json FROM shop_products WHERE sku = ? LIMIT 1');
    $update = $pdo->prepare(
        'UPDATE shop_products
         SET category_id = ?, sort_order = ?, meta_json = ?, updated_at = NOW()
         WHERE id = ?'
    );
    $delete = $pdo->prepare('DELETE FROM shop_products WHERE sku = ?');

    $stats = [
        'updated' => 0,
        'unchanged' => 0,
        'missing' => 0,
        'deleted' => 0,
        'skipped' => 0,
        'by_slug' => [],
    ];

    $seqBySlug = [];
    $junkSkus = ['127 sku'];

    foreach ($junkSkus as $junk) {
        $delete->execute([$junk]);
        if ($delete->rowCount() > 0) {
            $stats['deleted']++;
            echo "DELETE sku={$junk}\n";
        }
    }

    foreach ($rows as $row) {
        $sku = trim((string) ($row['sku'] ?? ''));
        $group = trim((string) ($row['group'] ?? ''));
        if ($sku === '' || in_array($sku, $junkSkus, true)) {
            $stats['skipped']++;
            continue;
        }
        if ($group === '' || !isset($groupSlugs[$group])) {
            echo "SKIP unmapped group sku={$sku} group={$group}\n";
            $stats['skipped']++;
            continue;
        }

        $slug = $groupSlugs[$group];
        $cat = $cats[$slug];
        $seqBySlug[$slug] = ($seqBySlug[$slug] ?? 0) + 1;
        $sortOrder = ($cat['sort'] * 1000) + $seqBySlug[$slug];

        $find->execute([$sku]);
        $product = $find->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            echo "MISSING sku={$sku}\n";
            $stats['missing']++;
            continue;
        }

        $meta = [];
        if (!empty($product['meta_json'])) {
            $decoded = json_decode((string) $product['meta_json'], true);
            if (is_array($decoded)) {
                $meta = $decoded;
            }
        }
        $meta['product_group'] = $canonicalNames[$slug];
        $meta['source_group'] = $group;
        if (isset($row['sheets_per_pack']) && $row['sheets_per_pack'] !== null && $row['sheets_per_pack'] !== '') {
            $meta['sheets_per_pack'] = (int) $row['sheets_per_pack'];
        }

        $newCatId = $cat['id'];
        $changed = ((int) $product['category_id'] !== $newCatId)
            || ((int) $product['sort_order'] !== $sortOrder);

        $update->execute([
            $newCatId,
            $sortOrder,
            json_encode($meta, JSON_UNESCAPED_UNICODE),
            (int) $product['id'],
        ]);

        $stats['by_slug'][$slug] = ($stats['by_slug'][$slug] ?? 0) + 1;
        if ($changed) {
            $stats['updated']++;
            echo "UPDATE {$sku} → {$slug} (#{$newCatId}) sort={$sortOrder}\n";
        } else {
            $stats['unchanged']++;
        }
    }

    $pdo->commit();
    echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    echo "OK\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
