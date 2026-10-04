<?php

/**
 * parse_label_products_xlsx.php 가 만든 JSON을 받아 상품 + 매수 옵션을 등록한다.
 * 기본은 미리보기(dry-run)이고, 실제 반영은 --apply 를 붙여야 한다.
 *
 * 사용:
 *   php import_label_product_groups.php plan.json                  # 미리보기
 *   php import_label_product_groups.php plan.json --apply          # 반영
 *   php import_label_product_groups.php plan.json --apply --status=draft --stock=100
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\ShopProductGroupImportService;

$args = array_slice($argv, 1);
$jsonPath = '';
$flags = [];
foreach ($args as $arg) {
    if (str_starts_with($arg, '--')) {
        $parts = explode('=', substr($arg, 2), 2);
        $flags[$parts[0]] = $parts[1] ?? '1';
        continue;
    }
    $jsonPath = $arg;
}

if ($jsonPath === '' || !is_readable($jsonPath)) {
    fwrite(STDERR, "계획 JSON 을 읽을 수 없습니다: '{$jsonPath}'\n");
    exit(1);
}

$payload = json_decode((string) file_get_contents($jsonPath), true);
if (!is_array($payload) || !is_array($payload['groups'] ?? null)) {
    fwrite(STDERR, "JSON 형식이 올바르지 않습니다. parse_label_products_xlsx.php 결과를 넣어 주세요.\n");
    exit(1);
}

$apply = !empty($flags['apply']);
$batch = (string) ($flags['batch'] ?? date('ymd'));
$status = (string) ($flags['status'] ?? 'active');
$stock = (int) ($flags['stock'] ?? 100);
if (!in_array($status, ['draft', 'active', 'soldout', 'hidden'], true)) {
    fwrite(STDERR, "status 값이 올바르지 않습니다: {$status}\n");
    exit(1);
}

$service = new ShopProductGroupImportService(
    $batch,
    $apply,
    $status,
    $stock,
    empty($flags['no-images']),
    empty($flags['no-detail-page'])
);

printf(
    "%s | 배치=%s 상태=%s 재고=%d | 그룹 %d개\n\n",
    $apply ? '반영(APPLY)' : '미리보기(DRY-RUN)',
    $batch,
    $status,
    $stock,
    count($payload['groups'])
);

$result = $service->import($payload['groups']);

printf("%-4s %-9s %-34s %-7s %-7s %s\n", '', 'SKU', '상품명', '정상가', '판매가', '매수 옵션');
echo str_repeat('-', 118), "\n";
foreach ($result['rows'] as $i => $row) {
    printf(
        "%-4d %-9s %-34s %7s %7s %s%s\n",
        $i + 1,
        $row['sku'],
        mb_strimwidth((string) $row['name'], 0, 34, '…'),
        number_format((int) $row['price']),
        $row['sale_price'] !== null ? number_format((int) $row['sale_price']) : '-',
        implode(' ', $row['options']),
        $row['freed_sku'] !== null ? '  [구 상품 SKU → ' . $row['freed_sku'] . ']' : ''
    );
}

echo "\n########## 집계 ##########\n";
foreach ($result['stats'] as $key => $value) {
    printf("  %-22s %d\n", $key, $value);
}

if ($result['errors'] !== []) {
    echo "\n########## 오류 ", count($result['errors']), "건 ##########\n";
    foreach ($result['errors'] as $error) {
        echo "  - {$error}\n";
    }
}

if (!empty($payload['warnings'])) {
    echo "\n########## 엑셀 경고 ", count($payload['warnings']), "건 ##########\n";
    foreach ($payload['warnings'] as $warning) {
        echo "  - {$warning}\n";
    }
}

if (!$apply) {
    echo "\n반영하지 않았습니다. 실제로 등록하려면 --apply 를 붙여 다시 실행하세요.\n";
    echo "미리보기에서는 구 상품 SKU 되돌리기를 하지 않으므로 이미지 승계 수가 0으로 보일 수 있습니다.\n";
}

exit($result['errors'] === [] ? 0 : 2);
