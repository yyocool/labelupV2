<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductDetailPageRepository;
use App\Repositories\ShopRepository;
use RuntimeException;

/**
 * 엑셀에서 뽑은 「기본 품번 묶음」을 상품 1건 + 매수 옵션 N건으로 등록한다.
 *
 * 기존 임포트(ShopProductImportService)는 품번 한 줄이 상품 하나였다. 여기서는
 * A101-20 / A101-100 / A101-200 을 상품 A101 하나로 합치고 매수를 옵션으로 돌린다.
 * 기존 상품은 손대지 않으므로 전환이 끝날 때까지 둘이 공존한다.
 */
final class ShopProductGroupImportService
{
    /** 이 배치로 들어온 상품임을 meta_json 에 남겨, 나중에 구 상품만 골라 지울 수 있게 한다. */
    public const BATCH_KEY = ShopRepository::IMPORT_BATCH_META_KEY;

    private ShopRepository $repo;
    private ProductDetailPageRepository $detailPages;

    /** @var array<string, int> 분류명 => category_id */
    private array $categoryCache = [];

    public function __construct(
        private string $batch,
        private bool $apply = false,
        private string $status = 'active',
        private int $stockQty = 100,
        private bool $carryImages = true,
        private bool $carryDetailPage = true
    ) {
        $this->repo = new ShopRepository();
        $this->detailPages = new ProductDetailPageRepository();
    }

    /**
     * @param array<int, array{base:string, group:string, variants:array<int, array<string, mixed>>}> $groups
     * @return array{rows:array<int, array<string, mixed>>, stats:array<string, int>, errors:array<int, string>}
     */
    public function import(array $groups): array
    {
        $stats = [
            'groups' => 0,
            'products_inserted' => 0,
            'products_updated' => 0,
            'options' => 0,
            'specs_inserted' => 0,
            'specs_updated' => 0,
            'images_carried' => 0,
            'detail_pages_carried' => 0,
        ];
        $rows = [];
        $errors = [];
        $sortOrder = 0;

        foreach ($groups as $group) {
            $base = trim((string) ($group['base'] ?? ''));
            $variants = array_values($group['variants'] ?? []);
            if ($base === '' || $variants === []) {
                $errors[] = '기본 품번 또는 변형 목록이 비어 있어 건너뜀';
                continue;
            }

            try {
                $rows[] = $this->importGroup($base, $variants, ++$sortOrder, $stats);
                $stats['groups']++;
            } catch (RuntimeException $e) {
                $errors[] = "{$base}: " . $e->getMessage();
            }
        }

        return ['rows' => $rows, 'stats' => $stats, 'errors' => $errors];
    }

    /**
     * @param array<int, array<string, mixed>> $variants 매수 오름차순
     * @param array<string, int> $stats
     * @return array<string, mixed>
     */
    private function importGroup(string $base, array $variants, int $sortOrder, array &$stats): array
    {
        $reference = $variants[0];
        $categoryId = $this->resolveCategoryId((string) ($reference['group'] ?? ''));
        $specId = $this->resolveSpecId($reference, $stats);
        $name = $this->buildProductName($base, $reference);

        // 기준가는 가장 작은 매수 변형이다. 옵션 추가금은 이 값과의 차액으로 둔다.
        $basePrice = (int) ($reference['price'] ?? 0);
        $baseSale = (int) ($reference['sale_price'] ?? 0);
        $baseUnit = $baseSale > 0 ? $baseSale : $basePrice;
        if ($baseUnit <= 0) {
            throw new RuntimeException('기준 가격을 알 수 없습니다.');
        }

        $options = [];
        foreach ($variants as $index => $variant) {
            $pack = (int) ($variant['pack'] ?? 0);
            if ($pack <= 0) {
                continue;
            }
            $unit = (int) ($variant['sale_price'] ?? 0) ?: (int) ($variant['price'] ?? 0);
            $options[] = [
                'name' => $pack . '매',
                'price_delta' => $unit - $baseUnit,
                'stock_qty' => $this->stockQty,
                // 옵션까지 고른 SKU 가 원래 품번(A101-20)과 같아지도록 맞춘다.
                'sku_suffix' => '-' . $pack,
                'is_active' => 1,
                'sort_order' => $index,
            ];
        }
        if ($options === []) {
            throw new RuntimeException('매수 옵션을 만들 수 없습니다.');
        }

        $smallestSku = $base . '-' . (int) $reference['pack'];
        $existing = $this->repo->findProductBySku($base);
        $freed = null;
        if ($existing && !$this->isBatchProduct($existing)) {
            // 구 상품이 기본 품번 자리를 쓰고 있으면 원래 품번으로 되돌려 비운다. sku 는 UNIQUE 다.
            $freed = $this->freeUpBaseSku($existing, $smallestSku);
            $existing = null;
        }
        $legacy = $this->repo->findProductBySku($smallestSku);

        $payload = [
            'id' => (int) ($existing['id'] ?? 0),
            'category_id' => $categoryId,
            'spec_id' => $specId,
            'name' => $name,
            'sku' => $base,
            'price' => $basePrice > 0 ? $basePrice : $baseUnit,
            'sale_price' => $baseSale > 0 ? $baseSale : null,
            'stock_qty' => $this->stockQty,
            'status' => $this->status,
            'description' => $this->buildDescription($base, $variants),
            'sort_order' => $sortOrder,
            'compat_formtec' => $this->compatList($variants, 'compat_formtec'),
            'compat_anylabel' => $this->compatList($variants, 'compat_anylabel'),
            'compat_ilabel' => $this->compatList($variants, 'compat_ilabel'),
            'meta_json' => $this->buildMeta($base, $variants),
            // 이미지는 syncProductImages 가 thumbnail 까지 맞춰 주므로 여기서는 기존 값만 지킨다.
            'thumbnail' => $existing['thumbnail'] ?? ($legacy['thumbnail'] ?? null),
        ];

        $result = [
            'base' => $base,
            'sku' => $base,
            'name' => $name,
            'category_id' => $categoryId,
            'spec_id' => $specId,
            'price' => $payload['price'],
            'sale_price' => $payload['sale_price'],
            'options' => array_map(
                static fn (array $o): string => $o['name'] . ($o['price_delta'] === 0 ? '' : sprintf('(%+d)', $o['price_delta'])),
                $options
            ),
            'legacy_sku' => $legacy['sku'] ?? null,
            'freed_sku' => $freed,
            'action' => $existing ? 'update' : 'insert',
            'product_id' => (int) ($existing['id'] ?? 0),
        ];

        if (!$this->apply) {
            $stats[$existing ? 'products_updated' : 'products_inserted']++;
            $stats['options'] += count($options);
            return $result;
        }

        $productId = $this->repo->saveProduct($payload);
        $result['product_id'] = $productId;
        $stats[$existing ? 'products_updated' : 'products_inserted']++;

        // 다시 돌려도 옵션 id 가 유지돼야 한다. 바뀌면 장바구니에 담아 둔 옵션이 끊긴다.
        $existingOptions = [];
        foreach ($this->repo->productOptions($productId) as $option) {
            $existingOptions[(string) $option['name']] = (int) $option['id'];
        }
        foreach ($options as &$option) {
            $option['id'] = $existingOptions[$option['name']] ?? 0;
        }
        unset($option);

        $this->repo->syncProductOptions($productId, $options);
        $stats['options'] += count($options);

        if ($this->carryImages && $legacy) {
            $images = $this->repo->productImages((int) $legacy['id']);
            if ($images !== []) {
                $this->repo->syncProductImages(
                    $productId,
                    array_map(
                        static fn (array $img, int $i): array => [
                            'image_path' => (string) $img['image_path'],
                            'sort_order' => (int) ($img['sort_order'] ?? $i),
                            'is_primary' => !empty($img['is_primary']),
                        ],
                        $images,
                        array_keys($images)
                    ),
                    (string) ($legacy['thumbnail'] ?? '')
                );
                $stats['images_carried'] += count($images);
                $result['images'] = count($images);
            }
        }

        if ($this->carryDetailPage && $legacy) {
            $page = $this->detailPages->findByProductId((int) $legacy['id']);
            $html = trim((string) ($page['html_content'] ?? ''));
            if ($html !== '' && !$this->detailPages->findByProductId($productId)) {
                $this->detailPages->saveForProduct($productId, $html, (string) ($page['status'] ?? 'published'));
                $stats['detail_pages_carried']++;
                $result['detail_page'] = true;
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $product */
    private function isBatchProduct(array $product): bool
    {
        $meta = json_decode((string) ($product['meta_json'] ?? ''), true);
        return is_array($meta) && (string) ($meta[self::BATCH_KEY] ?? '') === $this->batch;
    }

    /**
     * 기본 품번을 점유한 구 상품의 SKU 를 원래 품번으로 되돌린다.
     *
     * @param array<string, mixed> $product
     * @return string 되돌린 SKU
     */
    private function freeUpBaseSku(array $product, string $restoredSku): string
    {
        if ($this->repo->findProductBySku($restoredSku)) {
            throw new RuntimeException(sprintf(
                '기본 품번을 쓰는 구 상품(id=%d)을 %s 로 되돌릴 수 없습니다. 그 SKU 가 이미 있습니다.',
                (int) $product['id'],
                $restoredSku
            ));
        }
        if ($this->apply) {
            $this->repo->updateProductSku((int) $product['id'], $restoredSku);
        }
        return $restoredSku;
    }

    private function resolveCategoryId(string $group): int
    {
        $group = trim($group);
        if ($group === '') {
            throw new RuntimeException('분류(구분)가 비어 있습니다.');
        }
        if (isset($this->categoryCache[$group])) {
            return $this->categoryCache[$group];
        }

        $slug = ShopProductImportService::GROUP_SLUGS[$group] ?? '';
        if ($slug === '') {
            throw new RuntimeException("분류 '{$group}' 에 대응하는 카테고리 slug 가 없습니다.");
        }
        $category = $this->repo->findCategoryBySlug($slug);
        if (!$category) {
            throw new RuntimeException("카테고리 slug '{$slug}' 를 찾을 수 없습니다.");
        }

        $id = (int) $category['id'];
        $this->categoryCache[$group] = $id;
        return $id;
    }

    /**
     * 규격은 치수·재질·칸수로 찾아 쓰고, 엑셀에 새로 들어온 배치값(마진·행열·간격·모서리·바탕색)을 채운다.
     * 같은 규격을 쓰는 기존 상품도 함께 보정되는 것이 맞다.
     *
     * @param array<string, mixed> $row
     * @param array<string, int> $stats
     */
    private function resolveSpecId(array $row, array &$stats): ?int
    {
        $width = (float) ($row['width_mm'] ?? 0);
        $height = (float) ($row['height_mm'] ?? 0);
        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $material = trim((string) ($row['color'] ?? '')) ?: '라벨지';
        $labels = $row['labels_per_sheet'] !== null ? (int) $row['labels_per_sheet'] : null;
        $radius = $row['corner_radius_mm'] !== null ? (float) $row['corner_radius_mm'] : null;
        [$columns, $rows] = $this->resolveGrid($row['columns_count'] ?? null, $row['rows_count'] ?? null, $labels);

        $existing = $this->repo->findSpecMatch($width, $height, $material, $labels);
        $payload = [
            'id' => (int) ($existing['id'] ?? 0),
            // 기존 규격을 고칠 때는 관리자가 붙여 둔 이름·이미지·종류를 건드리지 않는다.
            'name' => $existing['name'] ?? $this->buildSpecName($width, $height, $material, $labels),
            'kind' => $existing['kind'] ?? null,
            'image_path' => $existing['image_path'] ?? null,
            'width_mm' => $width,
            'height_mm' => $height,
            'material' => $material,
            'shape' => $existing['shape'] ?? 'rect',
            'labels_per_sheet' => $labels,
            'corner_radius_x_mm' => $radius,
            'corner_radius_y_mm' => $radius,
            'custom_path_svg' => $existing['custom_path_svg'] ?? null,
            'description' => trim((string) ($row['std_size'] ?? '')) !== ''
                ? '표기치수 ' . $row['std_size']
                : (string) ($existing['description'] ?? ''),
            'is_active' => true,
        ];

        // 치수·재질·칸수가 같으면 서로 다른 분류(예: 레이저 투명 / 보호용 필름)도 한 규격을 공유한다.
        // 엑셀에 빈 칸이 있는 줄이 나중에 처리되며 먼저 채워 둔 값을 지우지 않도록, 빈 값은 기존 값을 남긴다.
        foreach ([
            'paper_size' => $row['paper_size'] ?? null,
            'top_margin_mm' => $row['top_margin_mm'] ?? null,
            'left_margin_mm' => $row['left_margin_mm'] ?? null,
            'columns_count' => $columns,
            'rows_count' => $rows,
            'h_gap_mm' => $row['h_gap_mm'] ?? null,
            'v_gap_mm' => $row['v_gap_mm'] ?? null,
            'label_color' => $row['label_color'] ?? null,
        ] as $field => $value) {
            $payload[$field] = ($value === null || $value === '')
                ? ($existing[$field] ?? null)
                : $value;
        }

        if (!$this->apply) {
            $stats[$existing ? 'specs_updated' : 'specs_inserted']++;
            return $existing ? (int) $existing['id'] : null;
        }

        $id = $this->repo->saveSpec($payload);
        $stats[$existing ? 'specs_updated' : 'specs_inserted']++;
        return $id;
    }

    /**
     * 엑셀 행X열 칸이 0 이거나 비어 있는 줄이 있다. 칸수로 메울 수 있으면 메운다.
     * 한 칸짜리 전지는 1x1 이고, 한쪽만 적힌 줄은 칸수를 나눠 다른 쪽을 얻는다.
     *
     * @return array{0: ?int, 1: ?int}
     */
    private function resolveGrid(?int $columns, ?int $rows, ?int $labels): array
    {
        $columns = $columns !== null && $columns > 0 ? $columns : null;
        $rows = $rows !== null && $rows > 0 ? $rows : null;
        if ($labels === null || $labels <= 0 || ($columns !== null && $rows !== null)) {
            return [$columns, $rows];
        }
        if ($labels === 1) {
            return [1, 1];
        }
        if ($columns !== null && $labels % $columns === 0) {
            return [$columns, intdiv($labels, $columns)];
        }
        if ($rows !== null && $labels % $rows === 0) {
            return [intdiv($labels, $rows), $rows];
        }
        return [$columns, $rows];
    }

    private function buildSpecName(float $width, float $height, string $material, ?int $labels): string
    {
        return sprintf(
            '%sx%smm %s%s',
            $this->trimNum($width),
            $this->trimNum($height),
            $material,
            $labels ? " {$labels}칸" : ''
        );
    }

    /**
     * 엑셀 상품명은 「품번 / 설명 / 매수」 꼴이다. 매수는 옵션으로 빠지므로 앞뒤를 떼고
     * 기본 품번으로 다시 붙인다. 예) "A101-20 / 물류관리용 라벨 1칸 / 20매" → "A101 / 물류관리용 라벨 1칸"
     *
     * @param array<string, mixed> $row
     */
    private function buildProductName(string $base, array $row): string
    {
        $parts = array_values(array_filter(
            array_map('trim', explode('/', (string) ($row['name'] ?? ''))),
            static fn (string $p): bool => $p !== ''
        ));
        if (count($parts) >= 2) {
            array_shift($parts);
            $last = end($parts);
            if (preg_match('/^\d+\s*(매|set)$/iu', (string) $last)) {
                array_pop($parts);
            }
            if ($parts !== []) {
                return $base . ' / ' . implode(' / ', $parts);
            }
        }

        // 상품명이 비었거나 형식이 다르면 품명+칸수로 만든다.
        $labels = $row['labels_per_sheet'] !== null ? (int) $row['labels_per_sheet'] . '칸' : '';
        $desc = trim(trim((string) ($row['material_name'] ?? '라벨')) . ' ' . $labels);
        return $base . ' / ' . $desc;
    }

    /**
     * 상세 설명은 관리자 목록·상세 화면에서 바로 읽히는 평문이다. 매수별로 달라지는 값은
     * 옵션 줄에 따로 적는다.
     *
     * @param array<int, array<string, mixed>> $variants
     */
    private function buildDescription(string $base, array $variants): string
    {
        $reference = $variants[0];
        $lines = [];
        $fields = [
            '기본 품번' => $base,
            '제품명' => $reference['material_name'] ?? '',
            '제품규격' => $reference['paper_size'] ?? '',
            '라벨수(칸)' => $reference['labels_per_sheet'] ?? '',
            '표기치수' => $reference['std_size'] ?? '',
            'Spec(mm)' => $reference['spec_size'] ?? '',
            '색상' => $reference['color'] ?? '',
            '마진(상단/좌측 mm)' => $this->pair($reference['top_margin_mm'] ?? null, $reference['left_margin_mm'] ?? null),
            '행X열' => $this->pair($reference['columns_count'] ?? null, $reference['rows_count'] ?? null, 'x'),
            '라벨간격(상/좌 mm)' => $this->pair($reference['v_gap_mm'] ?? null, $reference['h_gap_mm'] ?? null),
            '모서리 R(mm)' => $reference['corner_radius_mm'] ?? '',
            '원산지' => $reference['origin'] ?? '',
            '기타' => $reference['note'] ?? '',
        ];
        foreach ($fields as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $lines[] = $label . ': ' . $value;
        }

        foreach ($variants as $variant) {
            $lines[] = sprintf(
                '%d매 (%s): 팩 %s / 박스 %s / 입수량 %s / 바코드 %s',
                (int) $variant['pack'],
                (string) $variant['sku'],
                (string) ($variant['pack_size'] ?? '-') ?: '-',
                (string) ($variant['box_size'] ?? '-') ?: '-',
                (string) ($variant['qty_per_box'] ?? '-') ?: '-',
                (string) ($variant['barcode_pack'] ?? '-') ?: '-'
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<int, array<string, mixed>> $variants
     * @return array<string, mixed>
     */
    private function buildMeta(string $base, array $variants): array
    {
        $reference = $variants[0];
        $packs = [];
        foreach ($variants as $variant) {
            $packs[] = [
                'pack' => (int) $variant['pack'],
                'sku' => (string) $variant['sku'],
                'price' => (int) ($variant['price'] ?? 0),
                'sale_price' => (int) ($variant['sale_price'] ?? 0),
                'pack_size' => (string) ($variant['pack_size'] ?? ''),
                'box_size' => (string) ($variant['box_size'] ?? ''),
                'qty_per_box' => $variant['qty_per_box'] ?? null,
                'barcode_pack' => (string) ($variant['barcode_pack'] ?? ''),
                'barcode_box' => (string) ($variant['barcode_box'] ?? ''),
                'excel_row' => (int) ($variant['row'] ?? 0),
            ];
        }

        return [
            self::BATCH_KEY => $this->batch,
            'base_sku' => $base,
            'product_group' => (string) ($reference['group'] ?? ''),
            'source_group' => (string) ($reference['group'] ?? ''),
            'origin' => (string) ($reference['origin'] ?? ''),
            'color' => (string) ($reference['color'] ?? ''),
            'label_color' => $reference['label_color'] ?? null,
            'paper_size' => (string) ($reference['paper_size'] ?? ''),
            'labels_per_sheet' => $reference['labels_per_sheet'] ?? null,
            'sheets_per_pack' => (int) $reference['pack'],
            'note' => (string) ($reference['note'] ?? ''),
            'packs' => $packs,
        ];
    }

    /** 변형마다 호환 코드가 조금씩 다를 수 있어 중복을 지우고 모아 둔다. */
    private function compatList(array $variants, string $field): ?string
    {
        $codes = [];
        foreach ($variants as $variant) {
            $value = trim((string) ($variant[$field] ?? ''));
            if ($value === '') {
                continue;
            }
            // 엑셀에서 숫자로 읽혀 3130.0 처럼 들어오는 코드를 정수로 되돌린다.
            if (is_numeric($value)) {
                $value = number_format((float) $value, 0, '.', '');
            }
            $codes[$value] = true;
        }
        return $codes === [] ? null : implode(',', array_keys($codes));
    }

    private function pair(mixed $a, mixed $b, string $glue = ' / '): string
    {
        if (($a === null || $a === '') && ($b === null || $b === '')) {
            return '';
        }
        return ($a === null || $a === '' ? '-' : (string) $a) . $glue . ($b === null || $b === '' ? '-' : (string) $b);
    }

    private function trimNum(float $num): string
    {
        if (abs($num - round($num)) < 0.001) {
            return (string) (int) round($num);
        }
        return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
    }
}
