<?php

/**
 * 신규 임포트 배치로 등록되지 않은 「구 상품」을 골라 지운다.
 *
 * 새 상품(기본 품번 + 매수 옵션)이 자리를 잡은 뒤에 쓰는 정리용 스크립트다.
 * 기본은 미리보기(dry-run)이고, 실제 삭제는 --apply 를 붙여야 한다.
 * 주문 내역(shop_order_items)은 주문 당시 스냅샷이라 지우지 않는다.
 *
 * 사용:
 *   php cleanup_legacy_shop_products.php --batch=261002            # 지울 목록만 확인
 *   php cleanup_legacy_shop_products.php --batch=261002 --apply    # 실제 삭제
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Repositories\ShopRepository;

$flags = [];
foreach (array_slice($argv, 1) as $arg) {
    if (!str_starts_with($arg, '--')) {
        continue;
    }
    $parts = explode('=', substr($arg, 2), 2);
    $flags[$parts[0]] = $parts[1] ?? '1';
}

$batch = trim((string) ($flags['batch'] ?? ''));
if ($batch === '') {
    fwrite(STDERR, "--batch=<임포트 배치> 를 반드시 지정해야 합니다. (예: --batch=261002)\n");
    exit(1);
}
$apply = !empty($flags['apply']);

$repo = new ShopRepository();
$legacy = $repo->productsNotInImportBatch($batch);
$kept = count($repo->allProducts()) - count($legacy);

printf(
    "%s | 배치=%s\n신규 등록분 %d건 유지 / 구 상품 %d건 대상\n\n",
    $apply ? '삭제(APPLY)' : '미리보기(DRY-RUN)',
    $batch,
    $kept,
    count($legacy)
);

// 신규 등록분이 없으면 전체를 날려 버리는 사고가 되므로 멈춘다.
if ($kept <= 0) {
    fwrite(STDERR, "배치 '{$batch}' 로 등록된 상품이 없습니다. 임포트를 먼저 실행하세요.\n");
    exit(1);
}
if ($legacy === []) {
    echo "지울 구 상품이 없습니다.\n";
    exit(0);
}

$totals = [];
$orderLinked = [];
printf("%-5s %-14s %-40s %s\n", 'id', 'SKU', '상품명', '옵션/이미지/상세/찜/주문');
echo str_repeat('-', 110), "\n";

foreach ($legacy as $product) {
    $id = (int) $product['id'];
    $counts = $repo->productRelationCounts($id);
    foreach ($counts as $table => $n) {
        $totals[$table] = ($totals[$table] ?? 0) + $n;
    }
    if ($counts['shop_order_items'] > 0) {
        $orderLinked[] = sprintf('%s(id=%d, %d건)', (string) $product['sku'], $id, $counts['shop_order_items']);
    }

    printf(
        "%-5d %-14s %-40s %d/%d/%d/%d/%d\n",
        $id,
        (string) $product['sku'],
        mb_strimwidth((string) $product['name'], 0, 40, '…'),
        $counts['shop_product_options'],
        $counts['shop_product_images'],
        $counts['shop_product_detail_pages'],
        $counts['shop_product_wishlists'],
        $counts['shop_order_items']
    );

    if ($apply) {
        $repo->purgeProduct($id);
    }
}

echo "\n########## 딸린 행 합계 ##########\n";
foreach ($totals as $table => $n) {
    printf("  %-28s %d\n", $table, $n);
}

if ($orderLinked !== []) {
    echo "\n주문 내역이 걸린 상품 ", count($orderLinked), "건 — 주문 항목은 스냅샷이라 남지만 상품 링크는 끊깁니다:\n";
    echo '  ', implode(', ', $orderLinked), "\n";
}

echo $apply
    ? "\n구 상품 " . count($legacy) . "건을 지웠습니다.\n"
    : "\n아무것도 지우지 않았습니다. 실제로 지우려면 --apply 를 붙여 다시 실행하세요.\n";
