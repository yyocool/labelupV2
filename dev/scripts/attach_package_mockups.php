<?php

declare(strict_types=1);

/**
 * 패키지 목업을 상품 이미지에 추가한다.
 * 파일명의 하이픈(-) 앞부분만 SKU로 보고, 같은 SKU에 파일이 여러 장이면 이름순 첫 장만 쓴다.
 * 이미 등록된 이미지와 대표 이미지는 바꾸지 않고 뒤에 붙인다.
 *
 * 사용:
 *   php scripts/attach_package_mockups.php
 *   php scripts/attach_package_mockups.php --source "C:\...\패키지목업"
 */

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    require dirname(__DIR__) . '/bootstrap.php';

    $source = getenv('MOCKUP_SOURCE') ?: '';
    foreach ($argv ?? [] as $i => $arg) {
        if ($arg === '--source' && isset($argv[$i + 1])) {
            $source = $argv[$i + 1];
        }
    }
    if ($source === '' || !is_dir($source)) {
        fwrite(STDERR, "목업 폴더가 없습니다.\n");
        exit(1);
    }

    $destDir = dirname(__DIR__) . '/public/assets/products/mockups';
    if (!is_dir($destDir) && !mkdir($destDir, 0775, true) && !is_dir($destDir)) {
        fwrite(STDERR, "저장 폴더를 만들 수 없습니다.\n");
        exit(1);
    }

    $copied = copyMockups($source, $destDir);
    echo "복사 {$copied}장 → {$destDir}\n";

    $pdo = App\Helpers\Database::connection();
    $summary = attachMockups($pdo, $destDir);
    echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
}

/**
 * @return int 복사한 파일 수
 */
function copyMockups(string $source, string $destDir): int
{
    if (!is_dir($source)) {
        fwrite(STDERR, "목업 폴더가 없습니다: {$source}\n");
        exit(1);
    }

    $groups = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            continue;
        }
        $stem = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $sku = trim(explode('-', $stem, 2)[0]);
        if ($sku === '') {
            continue;
        }
        $groups[$sku][] = $file->getPathname();
    }

    $count = 0;
    foreach ($groups as $sku => $paths) {
        sort($paths, SORT_STRING);
        $src = $paths[0];
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $dest = $destDir . DIRECTORY_SEPARATOR . $sku . '.' . $ext;
        if (!copy($src, $dest)) {
            fwrite(STDERR, "복사 실패: {$src}\n");
            exit(1);
        }
        $count++;
    }
    return $count;
}

/**
 * @return array<string, mixed>
 */
function attachMockups(PDO $pdo, string $destDir): array
{
    $files = [];
    foreach (scandir($destDir) ?: [] as $name) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $files[] = $destDir . DIRECTORY_SEPARATOR . $name;
        }
    }
    sort($files, SORT_STRING);

    $bySku = [];
    foreach ($files as $path) {
        $sku = pathinfo($path, PATHINFO_FILENAME);
        $bySku[strtolower($sku)] = '/assets/products/mockups/' . basename($path);
    }

    $products = $pdo->query('SELECT id, sku, thumbnail FROM shop_products')->fetchAll(PDO::FETCH_ASSOC);
    $imageStmt = $pdo->prepare(
        'SELECT image_path, sort_order, is_primary FROM shop_product_images WHERE product_id = :id ORDER BY sort_order ASC, id ASC'
    );
    $insert = $pdo->prepare(
        'INSERT INTO shop_product_images (product_id, image_path, sort_order, is_primary, created_at, updated_at)
         VALUES (:product_id, :image_path, :sort_order, :is_primary, NOW(), NOW())'
    );
    $touchThumb = $pdo->prepare(
        'UPDATE shop_products SET thumbnail = :thumb, updated_at = NOW() WHERE id = :id AND (thumbnail IS NULL OR thumbnail = \'\')'
    );

    $added = 0;
    $skipped = 0;
    $unmatchedFiles = array_fill_keys(array_keys($bySku), true);
    $missing = [];

    foreach ($products as $product) {
        $sku = strtolower(trim((string) ($product['sku'] ?? '')));
        if ($sku === '' || !isset($bySku[$sku])) {
            if ($sku !== '') {
                $missing[] = (string) $product['sku'];
            }
            continue;
        }
        unset($unmatchedFiles[$sku]);
        $publicPath = $bySku[$sku];
        $imageStmt->execute(['id' => (int) $product['id']]);
        $rows = $imageStmt->fetchAll(PDO::FETCH_ASSOC);
        $exists = false;
        $maxSort = -1;
        $hasPrimary = false;
        foreach ($rows as $row) {
            if ((string) $row['image_path'] === $publicPath) {
                $exists = true;
            }
            $maxSort = max($maxSort, (int) $row['sort_order']);
            if ((int) $row['is_primary'] === 1) {
                $hasPrimary = true;
            }
        }
        if ($exists) {
            $skipped++;
            continue;
        }
        $thumbnail = trim((string) ($product['thumbnail'] ?? ''));
        $makePrimary = $rows === [] && $thumbnail === '';
        $insert->execute([
            'product_id' => (int) $product['id'],
            'image_path' => $publicPath,
            'sort_order' => $maxSort + 1,
            'is_primary' => $makePrimary ? 1 : 0,
        ]);
        if ($makePrimary) {
            $touchThumb->execute(['thumb' => $publicPath, 'id' => (int) $product['id']]);
        }
        $added++;
    }

    return [
        'added' => $added,
        'already' => $skipped,
        'files' => count($bySku),
        'unmatched_files' => array_keys($unmatchedFiles),
        'products_without_mockup' => count($missing),
        'missing_skus' => array_slice($missing, 0, 40),
    ];
}
