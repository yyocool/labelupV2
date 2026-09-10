#!/usr/bin/env php
<?php
/**
 * 무료배포 QR 제품분류(11개) 기준으로 shop_categories 재정리.
 * - 물류/주소/바코드/인덱스 → 1개 카테고리로 상품 병합
 * - 명칭·정렬 순서를 제품분류 No.에 맞춤
 * - 시드용 레거시 카테고리(라벨지 등) 비활성
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$pdo = App\Helpers\Database::connection();
$pdo->beginTransaction();

try {
    $targets = [
        [
            'slug' => 'logistics-label',
            'name' => '물류관리용/주소용/바코드용/인덱스용',
            'sort' => 1,
            'image' => '/assets/categories/cat_logistics-label.webp',
        ],
        [
            'slug' => 'government-doc',
            'name' => '정부문서화일 라벨',
            'sort' => 2,
            'image' => '/assets/categories/cat_government-doc.webp',
        ],
        [
            'slug' => 'gloss-label',
            'name' => '광택 라벨',
            'sort' => 3,
            'image' => '/assets/categories/cat_gloss-label.webp',
        ],
        [
            'slug' => 'waterproof-label',
            'name' => '방수 라벨',
            'sort' => 4,
            'image' => '/assets/categories/cat_waterproof-label.webp',
        ],
        [
            'slug' => 'translucent-label',
            'name' => '반투명 라벨',
            'sort' => 5,
            'image' => '/assets/categories/cat_translucent-label.webp',
        ],
        [
            'slug' => 'inkjet-clear-label',
            'name' => '잉크젯 투명 라벨',
            'sort' => 6,
            'image' => '/assets/categories/cat_inkjet-clear-label.webp',
        ],
        [
            'slug' => 'laser-clear-label',
            'name' => '레이저 투명 라벨',
            'sort' => 7,
            'image' => '/assets/categories/cat_laser-clear-label.webp',
        ],
        [
            'slug' => 'protective-film',
            'name' => '보호용 필름',
            'sort' => 8,
            'image' => '/assets/categories/cat_protective-film.webp',
        ],
        [
            'slug' => 'color-label',
            'name' => '컬러 라벨(형광)',
            'sort' => 9,
            'image' => '/assets/categories/cat_color-label.webp',
        ],
        [
            'slug' => 'pastel-color-label',
            'name' => '파스텔 컬러 라벨',
            'sort' => 10,
            'image' => '/assets/categories/cat_pastel-color-label.webp',
        ],
        [
            'slug' => 'kraft-label',
            'name' => '크라프트 라벨',
            'sort' => 11,
            'image' => '/assets/categories/cat_kraft-label.webp',
        ],
    ];

    $upsert = $pdo->prepare(
        'INSERT INTO shop_categories (name, slug, image_path, sort_order, is_active, created_at, updated_at)
         VALUES (:name, :slug, :image_path, :sort_order, 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            image_path = COALESCE(VALUES(image_path), image_path),
            sort_order = VALUES(sort_order),
            is_active = 1,
            updated_at = NOW()'
    );

    foreach ($targets as $row) {
        $upsert->execute([
            'name' => $row['name'],
            'slug' => $row['slug'],
            'image_path' => $row['image'],
            'sort_order' => $row['sort'],
        ]);
        echo "UPSERT {$row['sort']}. {$row['name']} ({$row['slug']})\n";
    }

    $idBySlug = static function (PDO $pdo, string $slug): ?int {
        $st = $pdo->prepare('SELECT id FROM shop_categories WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
        $id = $st->fetchColumn();
        return $id === false ? null : (int) $id;
    };

    $mainId = $idBySlug($pdo, 'logistics-label');
    if ($mainId === null) {
        throw new RuntimeException('logistics-label category missing after upsert');
    }

    $mergeSlugs = ['address-label', 'barcode-label', 'index-label'];
    $mergeIds = [];
    foreach ($mergeSlugs as $slug) {
        $id = $idBySlug($pdo, $slug);
        if ($id !== null && $id !== $mainId) {
            $mergeIds[] = $id;
        }
    }

    $moved = 0;
    if ($mergeIds !== []) {
        $in = implode(',', array_map('intval', $mergeIds));
        $moved = $pdo->exec(
            "UPDATE shop_products SET category_id = {$mainId}, updated_at = NOW()
             WHERE category_id IN ({$in})"
        );
        echo "MOVED products into logistics-label: {$moved}\n";
    }

    $deactivateSlugs = array_merge(
        $mergeSlugs,
        ['label-paper', 'thermal-paper', 'packaging', 'supplies']
    );
    $find = $pdo->prepare(
        'SELECT id,
                (SELECT COUNT(*) FROM shop_products p WHERE p.category_id = c.id) AS cnt
         FROM shop_categories c
         WHERE slug = ? AND id <> ?
         LIMIT 1'
    );
    $del = $pdo->prepare('DELETE FROM shop_categories WHERE id = ?');
    foreach ($deactivateSlugs as $slug) {
        $find->execute([$slug, $mainId]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            continue;
        }
        if ((int) $row['cnt'] > 0) {
            $pdo->prepare('UPDATE shop_categories SET is_active = 0, updated_at = NOW() WHERE id = ?')
                ->execute([(int) $row['id']]);
            echo "DEACTIVATE {$slug} (has products)\n";
            continue;
        }
        $del->execute([(int) $row['id']]);
        echo "DELETE {$slug}\n";
    }

    $pdo->commit();

    $rows = $pdo->query(
        'SELECT c.id, c.name, c.slug, c.sort_order, c.is_active, COUNT(p.id) AS product_count
         FROM shop_categories c
         LEFT JOIN shop_products p ON p.category_id = c.id
         GROUP BY c.id
         ORDER BY c.is_active DESC, c.sort_order ASC, c.id ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    echo "\n=== RESULT ===\n";
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    echo "OK\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
