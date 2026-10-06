<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;
use App\Repositories\ShopProductPageSettingsRepository;
use App\Repositories\ShopProductPageCategorySettingsRepository;
use RuntimeException;

final class ShopAdminService
{
    private ShopRepository $repo;

    public function __construct()
    {
        $this->repo = new ShopRepository();
    }

    public function dashboardStats(): array
    {
        return $this->repo->dashboardStats();
    }

    /** 표준 용지 크기(mm). 편집기 PaperCatalog.StandardPaperSizes 와 같은 값이어야 한다. */
    private const PAPER_SIZES_MM = [
        'A3' => [297.0, 420.0],
        'A4' => [210.0, 297.0],
        'A5' => [148.0, 210.0],
        'A6' => [105.0, 148.0],
        'B4' => [257.0, 364.0],
        'B5' => [182.0, 257.0],
        'B6' => [128.0, 182.0],
        'LETTER' => [215.9, 279.4],
        'LEGAL' => [215.9, 355.6],
    ];

    /** 용지 크기 오차 허용치(mm). 소수점 반올림 차이를 오류로 보지 않기 위한 값. */
    private const SIZE_TOLERANCE_MM = 0.5;

    /**
     * 편집기 배치에 쓰는 규격값이 서로 어긋나는 항목을 찾는다.
     * 상품이 걸려 있는 규격을 먼저 보여 준다.
     *
     * @return array<int, array{id:int, name:string, sku:string, products:int, messages:array<int,string>}>
     */
    public function specGeometryIssues(): array
    {
        $issues = [];
        foreach ($this->repo->specsForGeometryCheck() as $row) {
            $messages = $this->specGeometryMessages($row);
            if ($messages === []) {
                continue;
            }
            $issues[] = [
                'id' => (int) $row['id'],
                'name' => (string) ($row['name'] ?? ''),
                'sku' => (string) ($row['sample_sku'] ?? ''),
                'products' => (int) ($row['product_count'] ?? 0),
                'messages' => $messages,
            ];
        }
        return $issues;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<int, string>
     */
    private function specGeometryMessages(array $row): array
    {
        $cols = (int) ($row['columns_count'] ?? 0);
        $rows = (int) ($row['rows_count'] ?? 0);
        $products = (int) ($row['product_count'] ?? 0);

        // 상품이 걸리지 않은 규격은 편집기에 뜨지 않으므로 경고하지 않는다.
        if ($cols <= 0 || $rows <= 0) {
            return $products > 0
                ? ['열·행 값이 없습니다. 편집기가 칸 배치를 추정하므로 실제 용지와 어긋날 수 있습니다.']
                : [];
        }

        $messages = [];
        $labelW = (float) ($row['width_mm'] ?? 0);
        $labelH = (float) ($row['height_mm'] ?? 0);
        $hGap = max(0.0, (float) ($row['h_gap_mm'] ?? 0));
        $vGap = max(0.0, (float) ($row['v_gap_mm'] ?? 0));
        $left = max(0.0, (float) ($row['left_margin_mm'] ?? 0));
        $top = max(0.0, (float) ($row['top_margin_mm'] ?? 0));

        $needW = $left + $labelW * $cols + $hGap * ($cols - 1);
        $needH = $top + $labelH * $rows + $vGap * ($rows - 1);
        [$pageW, $pageH] = $this->paperSizeMm((string) ($row['paper_size'] ?? ''));

        if ($needW > $pageW + self::SIZE_TOLERANCE_MM || $needH > $pageH + self::SIZE_TOLERANCE_MM) {
            $messages[] = sprintf(
                '용지 여백과 라벨 크기의 합이 용지 규격보다 큽니다. 필요 %s×%smm > 용지 %s×%smm',
                $this->mm($needW),
                $this->mm($needH),
                $this->mm($pageW),
                $this->mm($pageH)
            );
        }

        $perSheet = $row['labels_per_sheet'] === null ? 0 : (int) $row['labels_per_sheet'];
        if ($perSheet > 0 && $cols * $rows !== $perSheet) {
            $messages[] = sprintf(
                '열 × 행 수가 칸수와 맞지 않습니다. %d열 × %d행 = %d칸 ≠ %d칸',
                $cols,
                $rows,
                $cols * $rows,
                $perSheet
            );
        }

        return $messages;
    }

    /** @return array{0: float, 1: float} */
    private function paperSizeMm(string $paperSize): array
    {
        $key = strtoupper(trim($paperSize));
        $landscape = false;
        foreach (['가로', 'LANDSCAPE', '-L'] as $marker) {
            if ($key !== '' && str_contains($key, $marker)) {
                $landscape = true;
                $key = trim(str_replace($marker, '', $key));
            }
        }
        $size = self::PAPER_SIZES_MM[$key] ?? self::PAPER_SIZES_MM['A4'];
        return $landscape ? [$size[1], $size[0]] : $size;
    }

    private function mm(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    /** @return array<int, array<string, mixed>> */
    public function categories(): array
    {
        return $this->repo->allCategories();
    }

    public function saveCategory(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($name === '' || $slug === '') {
            throw new RuntimeException('카테고리명과 슬러그를 입력해주세요.');
        }
        $parentId = (int) ($data['parent_id'] ?? 0);
        $id = (int) ($data['id'] ?? 0);
        if ($parentId > 0) {
            if ($id > 0 && $parentId === $id) {
                throw new RuntimeException('자기 자신을 상위 카테고리로 지정할 수 없습니다.');
            }
            $parent = $this->repo->findCategoryById($parentId);
            if (!$parent) {
                throw new RuntimeException('상위 카테고리를 찾을 수 없습니다.');
            }
            if ((int) ($parent['parent_id'] ?? 0) > 0) {
                throw new RuntimeException('카테고리는 2단계까지만 등록할 수 있습니다.');
            }
            if ($id > 0 && $this->repo->countCategoryChildren($id) > 0) {
                throw new RuntimeException('하위 카테고리가 있는 항목은 1차로 유지해야 합니다.');
            }
        }
        return $this->repo->saveCategory([
            'id' => $id,
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']),
            'image_path' => ShopProductImageService::normalizePublicPath((string) ($data['image_path'] ?? '')) ?: null,
        ]);
    }

    /** @return array<int, string> */
    public function uploadCategoryImages(array $files): array
    {
        return ShopProductImageService::storeCategoryUploads($files);
    }

    public function deleteCategory(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('잘못된 요청입니다.');
        }
        if ($this->repo->countCategoryChildren($id) > 0) {
            throw new RuntimeException('하위 카테고리를 먼저 삭제해주세요.');
        }
        if ($this->repo->countProductsInCategory($id) > 0) {
            throw new RuntimeException('이 카테고리에 상품이 있어 삭제할 수 없습니다.');
        }
        $this->repo->deleteCategory($id);
    }

    /** @return array<int, array<string, mixed>> */
    public function specs(): array
    {
        return $this->repo->allSpecs();
    }

    /**
     * 규격 목록의 product_skus(GROUP_CONCAT 결과)를 SKU 배열로 쪼갠다.
     * 빈 값·중복을 걸러 내므로 화면에서는 그대로 돌리기만 하면 된다.
     *
     * @return list<string>
     */
    public static function splitSkuList(mixed $raw): array
    {
        $text = trim((string) ($raw ?? ''));
        if ($text === '') {
            return [];
        }
        $skus = [];
        foreach (preg_split('/[,\s]+/', $text) ?: [] as $part) {
            $sku = trim((string) $part);
            if ($sku !== '' && !in_array($sku, $skus, true)) {
                $skus[] = $sku;
            }
        }
        return $skus;
    }

    public function saveSpec(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('규격명을 입력해주세요.');
        }
        $width = (float) ($data['width_mm'] ?? 0);
        $height = (float) ($data['height_mm'] ?? 0);
        $radiusLimit = $width > 0 && $height > 0 ? min($width, $height) / 2 : null;

        return $this->repo->saveSpec([
            'id' => (int) ($data['id'] ?? 0),
            'name' => $name,
            'kind' => $data['kind'] ?? null,
            'image_path' => ShopProductImageService::normalizePublicPath((string) ($data['image_path'] ?? '')) ?: null,
            'width_mm' => $width,
            'height_mm' => $height,
            'paper_size' => self::normalizePaperSize($data['paper_size'] ?? null),
            'material' => trim((string) ($data['material'] ?? '')),
            'shape' => self::normalizeShape($data['shape'] ?? null),
            'labels_per_sheet' => $data['labels_per_sheet'] ?? null,
            'top_margin_mm' => self::normalizeMm($data['top_margin_mm'] ?? null),
            'left_margin_mm' => self::normalizeMm($data['left_margin_mm'] ?? null),
            'columns_count' => $data['columns_count'] ?? null,
            'rows_count' => $data['rows_count'] ?? null,
            'h_gap_mm' => self::normalizeMm($data['h_gap_mm'] ?? null),
            'v_gap_mm' => self::normalizeMm($data['v_gap_mm'] ?? null),
            'corner_radius_x_mm' => self::normalizeMm($data['corner_radius_x_mm'] ?? null, $radiusLimit),
            'corner_radius_y_mm' => self::normalizeMm($data['corner_radius_y_mm'] ?? null, $radiusLimit),
            'label_color' => self::normalizeHexColor($data['label_color'] ?? null),
            'custom_path_svg' => self::sanitizeSvgPath($data['custom_path_svg'] ?? null),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active' => !empty($data['is_active']),
        ]);
    }

    /**
     * 라벨 형태. DB가 ENUM이라 목록 밖 값이 오면 저장 자체가 실패하므로 여기서 걸러 둔다.
     * custom은 '맞춤 일반'(안쪽 칼선 안만 편집), custom_donut은 '맞춤 도넛'(칼선 사이만 편집).
     */
    private const SHAPES = ['rect', 'round', 'custom', 'custom_donut'];

    private static function normalizeShape(mixed $value): string
    {
        $text = strtolower(trim((string) ($value ?? '')));
        return in_array($text, self::SHAPES, true) ? $text : 'rect';
    }

    /** 용지 규격은 목록 밖 값도 받되 기호는 막는다. 예: A4, A3, Letter, 100x150. */
    private static function normalizePaperSize(mixed $value): ?string
    {
        $text = strtoupper(trim((string) ($value ?? '')));
        if ($text === '') {
            return null;
        }
        $text = preg_replace('/[^A-Z0-9 ._\-×X]/u', '', $text) ?? '';
        $text = trim($text);
        return $text === '' ? null : mb_substr($text, 0, 20);
    }

    /** mm 값은 소수 셋째 자리까지. 모서리 반경은 min(가로, 세로)/2를 넘을 수 없다. */
    private static function normalizeMm(mixed $value, ?float $limit = null): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        $mm = max(0.0, min(9999.999, (float) $value));
        if ($limit !== null && $limit > 0) {
            $mm = min($mm, $limit);
        }
        return round($mm, 3);
    }

    private static function normalizeHexColor(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }
        if (preg_match('/^#?([0-9a-fA-F]{3})$/', $text, $m)) {
            $short = $m[1];
            $text = '#' . $short[0] . $short[0] . $short[1] . $short[1] . $short[2] . $short[2];
        }
        return preg_match('/^#[0-9a-fA-F]{6}$/', $text) ? strtoupper($text) : null;
    }

    /**
     * 커스텀 외곽선은 SVG path 데이터(d 속성) 또는 svg 마크업을 받는다.
     * 관리자 입력이라도 편집기·상점 화면에 그대로 그려지므로 스크립트 실행 경로는 걷어낸다.
     */
    private static function sanitizeSvgPath(mixed $value): ?string
    {
        $svg = trim((string) ($value ?? ''));
        if ($svg === '') {
            return null;
        }
        if (mb_strlen($svg) > 200000) {
            throw new RuntimeException('커스텀 외곽 Path가 너무 깁니다. (최대 200,000자)');
        }

        // path 데이터만 들어온 경우: 명령 문자와 숫자만 허용.
        if (!str_contains($svg, '<')) {
            if (!preg_match('/^[MmLlHhVvCcSsQqTtAaZz0-9.,\-+eE\s]+$/', $svg)) {
                throw new RuntimeException('Path 데이터에 허용되지 않는 문자가 있습니다.');
            }
            return $svg;
        }

        $patterns = [
            '#<\s*(script|foreignObject|iframe|object|embed)\b[^>]*>.*?<\s*/\s*\1\s*>#is' => '',
            '#<\s*(script|foreignObject|iframe|object|embed)\b[^>]*/?>#is' => '',
            '#\son[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)#is' => '',
            '#(href|xlink:href|src)\s*=\s*([\'"])\s*(javascript|data)\s*:[^\'"]*\2#is' => '',
        ];
        $clean = preg_replace(array_keys($patterns), array_values($patterns), $svg);
        if ($clean === null) {
            throw new RuntimeException('커스텀 외곽 Path를 처리하지 못했습니다.');
        }

        $clean = trim($clean);
        return $clean === '' ? null : $clean;
    }

    /** @return array<int, string> */
    public function uploadSpecImages(array $files): array
    {
        return ShopProductImageService::storeSpecUploads($files);
    }

    public function deleteSpec(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('잘못된 요청입니다.');
        }
        $this->repo->deleteSpec($id);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int}
     */
    public function products(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $list = $this->repo->adminProducts($filters, $page, $perPage);
        $images = $this->repo->allProductImagesGrouped();
        $options = $this->repo->allProductOptionsGrouped();
        foreach ($list['items'] as &$row) {
            $pid = (int) ($row['id'] ?? 0);
            $row['images'] = $images[$pid] ?? [];
            $row['options'] = $options[$pid] ?? [];
            if (empty($row['thumbnail']) && !empty($row['images'])) {
                foreach ($row['images'] as $img) {
                    if (!empty($img['is_primary'])) {
                        $row['thumbnail'] = $img['image_path'];
                        break;
                    }
                }
                if (empty($row['thumbnail'])) {
                    $row['thumbnail'] = $row['images'][0]['image_path'] ?? null;
                }
            }
            $row['meta'] = self::resolveProductMeta($row);
        }
        unset($row);
        return $list;
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    public static function resolveProductMeta(array $row): array
    {
        if (!empty($row['meta_json'])) {
            $decoded = json_decode((string) $row['meta_json'], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return self::parseDescriptionMeta((string) ($row['description'] ?? ''));
    }

    /** @return array<string, string> */
    public static function parseDescriptionMeta(string $description): array
    {
        if ($description === '') {
            return [];
        }

        $labelMap = [
            '품번' => 'sku_note',
            '제품명' => 'material_name',
            '제품규격' => 'paper_size',
            '라벨수(칸)' => 'labels_per_sheet',
            '표준치수' => 'std_size',
            'Spec(mm)' => 'spec_mm',
            '재질' => 'material',
            '패키지' => 'pack_size',
            '박스' => 'box_size',
            '입수량' => 'qty_per_box',
            'Sheets/PACK' => 'sheets_per_pack',
            '바코드' => 'barcode',
            '아트라No' => 'art_no',
            '원산지' => 'origin',
        ];

        $meta = [];
        foreach (preg_split('/\R/u', $description) as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }
            [$label, $value] = array_map('trim', explode(':', $line, 2));
            if ($label === '' || $value === '') {
                continue;
            }
            $key = $labelMap[$label] ?? null;
            if ($key) {
                $meta[$key] = $value;
            }
        }

        return $meta;
    }

    public function saveProduct(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        $sku = trim((string) ($data['sku'] ?? ''));
        $category = $this->repo->findCategoryById((int) ($data['category_id'] ?? 0));
        $isInk = (string) ($category['slug'] ?? '') === 'ink-charge';
        $inkAmount = $isInk ? (int) ($data['ink_amount'] ?? 0) : 0;
        if ($isInk && $sku === '' && $inkAmount > 0) {
            $sku = 'INK-' . $inkAmount;
        }
        if ($name === '' || $sku === '') {
            throw new RuntimeException('상품명과 SKU를 입력해주세요.');
        }
        if ($isInk && $inkAmount <= 0) {
            throw new RuntimeException('지급 잉크를 입력해주세요.');
        }

        $images = is_array($data['images'] ?? null) ? $data['images'] : [];
        $thumbnail = ShopProductImageService::normalizePublicPath((string) ($data['thumbnail'] ?? ''));
        if ($thumbnail === '' && $images !== []) {
            $thumbnail = ShopProductImageService::normalizePublicPath((string) ($images[0]['image_path'] ?? ''));
        }
        if ($isInk && $thumbnail === '' && $images === []) {
            $thumbnail = '/assets/categories/cat_ink-charge.png';
            $images = [['image_path' => $thumbnail, 'sort_order' => 0, 'is_primary' => 1]];
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        if ($isInk) {
            $data['spec_id'] = null;
            $data['stock_qty'] = 999999;
            $data['options'] = [];
        }

        $id = $this->repo->saveProduct([
            'id' => (int) ($data['id'] ?? 0),
            'category_id' => (int) ($data['category_id'] ?? 0),
            'spec_id' => $data['spec_id'] ?? null,
            'name' => $name,
            'sku' => $sku,
            'price' => (int) ($data['price'] ?? 0),
            'sale_price' => $data['sale_price'] ?? null,
            'ink_amount' => $isInk ? $inkAmount : null,
            'stock_qty' => (int) ($data['stock_qty'] ?? 0),
            'status' => (string) ($data['status'] ?? 'draft'),
            'description' => trim((string) ($data['description'] ?? '')),
            'meta_json' => $meta,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'thumbnail' => $thumbnail !== '' ? $thumbnail : null,
            'compat_formtec' => $data['compat_formtec'] ?? ($meta['compat_formtec'] ?? null),
            'compat_ilabel' => $data['compat_ilabel'] ?? ($meta['compat_ilabel'] ?? null),
            'compat_anylabel' => $data['compat_anylabel'] ?? ($meta['compat_anylabel'] ?? null),
        ]);

        if (array_key_exists('images', $data)) {
            $this->repo->syncProductImages($id, $images, $thumbnail !== '' ? $thumbnail : null);
        } elseif ($thumbnail !== '') {
            $this->repo->syncProductImages($id, [['image_path' => $thumbnail, 'sort_order' => 0, 'is_primary' => 1]], $thumbnail);
        }

        if (array_key_exists('options', $data)) {
            $this->repo->syncProductOptions($id, $this->normalizeOptionInput($data['options']));
        }

        return $id;
    }

    /**
     * 모달에서 올라온 옵션 행을 정리한다. 이름이 빈 줄은 버린다.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOptionInput(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach (array_values($raw) as $index => $option) {
            if (!is_array($option)) {
                continue;
            }
            $name = trim((string) ($option['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $out[] = [
                'id' => (int) ($option['id'] ?? 0),
                'name' => $name,
                'price_delta' => (int) ($option['price_delta'] ?? 0),
                'stock_qty' => max(0, (int) ($option['stock_qty'] ?? 0)),
                'sku_suffix' => trim((string) ($option['sku_suffix'] ?? '')),
                'is_active' => !empty($option['is_active']) ? 1 : 0,
                'sort_order' => (int) ($option['sort_order'] ?? $index),
            ];
        }
        return $out;
    }

    /** @return array<int, string> */
    public function uploadProductImages(array $files): array
    {
        return ShopProductImageService::storeProductUploads($files);
    }

    public function deleteProduct(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('잘못된 요청입니다.');
        }
        $this->repo->deleteProduct($id);
    }

    public function saveProductCompat(int $id, array $codes): void
    {
        if ($id <= 0) {
            throw new RuntimeException('잘못된 요청입니다.');
        }
        $this->repo->updateProductCompat($id, $codes);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function orders(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->repo->paginateAdminOrders($filters, $page, $perPage);
    }

    /** @return array<string, int> */
    public function orderStatusCounts(array $filters = []): array
    {
        return $this->repo->orderStatusCounts($filters);
    }

    public function orderDetail(int $id): array
    {
        $row = $this->repo->findAdminOrder($id);
        if (!$row) {
            throw new RuntimeException('주문을 찾을 수 없습니다.');
        }
        return $row;
    }

    /**
     * @param array<int, int> $ids
     */
    public function bulkUpdateOrders(array $ids, array $data): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            throw new RuntimeException('선택된 주문이 없습니다.');
        }
        $updated = 0;
        foreach ($ids as $id) {
            $current = $this->repo->findAdminOrder($id);
            if (!$current) {
                continue;
            }
            $payload = [
                'status' => (string) ($data['status'] ?? $current['status'] ?? 'pending'),
                'payment_status' => (string) ($data['payment_status'] ?? $current['payment_status'] ?? 'pending'),
                'admin_memo' => array_key_exists('admin_memo', $data)
                    ? trim((string) $data['admin_memo'])
                    : (string) ($current['admin_memo'] ?? ''),
                'carrier' => array_key_exists('carrier', $data)
                    ? trim((string) $data['carrier'])
                    : (string) ($current['carrier'] ?? ''),
                'tracking_no' => array_key_exists('tracking_no', $data)
                    ? trim((string) $data['tracking_no'])
                    : (string) ($current['tracking_no'] ?? ''),
            ];
            if ($payload['status'] === 'shipping' && $payload['tracking_no'] === '') {
                throw new RuntimeException('배송중 처리 시 송장번호가 필요합니다.');
            }
            $this->repo->updateOrder($id, $payload);
            $this->notifyOrderStatusChange($current, $payload['status'], $payload['tracking_no']);
            if ($payload['payment_status'] === 'paid' && (string) ($current['payment_status'] ?? '') !== 'paid') {
                (new ShopService())->grantPurchasedInk($id);
            }
            $updated++;
        }
        return $updated;
    }

    /** @return array<int, array<string, mixed>> */
    public function shippingOrders(): array
    {
        return $this->repo->shippingOrders();
    }

    public function updateOrder(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new RuntimeException('잘못된 요청입니다.');
        }
        $before = $this->repo->findAdminOrder($id);
        $status = (string) ($data['status'] ?? 'pending');
        $tracking = trim((string) ($data['tracking_no'] ?? ''));
        if ($status === 'shipping' && $tracking === '') {
            throw new RuntimeException('배송중 처리 시 송장번호가 필요합니다.');
        }
        $this->repo->updateOrder($id, [
            'status' => $status,
            'payment_status' => (string) ($data['payment_status'] ?? 'pending'),
            'admin_memo' => trim((string) ($data['admin_memo'] ?? '')),
            'carrier' => trim((string) ($data['carrier'] ?? '')),
            'tracking_no' => $tracking,
        ]);
        $this->notifyOrderStatusChange($before, $status, $tracking);
        $payStatus = (string) ($data['payment_status'] ?? 'pending');
        if ($payStatus === 'paid' && (string) ($before['payment_status'] ?? '') !== 'paid') {
            (new ShopService())->grantPurchasedInk($id);
        }
    }

    /** @param array<string, mixed>|null $before */
    private function notifyOrderStatusChange(?array $before, string $newStatus, string $trackingNo = ''): void
    {
        if (!$before) {
            return;
        }
        $userId = (int) ($before['user_id'] ?? 0);
        $oldStatus = (string) ($before['status'] ?? '');
        if ($userId <= 0 || $oldStatus === $newStatus) {
            return;
        }
        (new NotificationService())->notifyOrderStatus(
            $userId,
            (string) ($before['order_no'] ?? ''),
            $newStatus,
            $trackingNo
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function coupons(): array
    {
        return $this->repo->allCoupons();
    }

    public function saveCoupon(array $data): int
    {
        $code = trim((string) ($data['code'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));
        if ($code === '' || $name === '') {
            throw new RuntimeException('쿠폰 코드와 이름을 입력해주세요.');
        }
        return $this->repo->saveCoupon([
            'id' => (int) ($data['id'] ?? 0),
            'code' => $code,
            'name' => $name,
            'discount_type' => (string) ($data['discount_type'] ?? 'fixed'),
            'discount_value' => (int) ($data['discount_value'] ?? 0),
            'min_order_amount' => (int) ($data['min_order_amount'] ?? 0),
            'max_uses' => $data['max_uses'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => !empty($data['is_active']),
        ]);
    }

    public function deleteCoupon(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('잘못된 요청입니다.');
        }
        $this->repo->deleteCoupon($id);
    }

    /** @return array<int, array<string, mixed>> */
    public function banners(): array
    {
        return $this->repo->allBanners();
    }

    public function saveBanner(array $data): int
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException('배너 제목을 입력해주세요.');
        }
        return $this->repo->saveBanner([
            'id' => (int) ($data['id'] ?? 0),
            'title' => $title,
            'subtitle' => trim((string) ($data['subtitle'] ?? '')),
            'image_url' => trim((string) ($data['image_url'] ?? '')),
            'link_url' => trim((string) ($data['link_url'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']),
        ]);
    }

    public function deleteBanner(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('잘못된 요청입니다.');
        }
        $this->repo->deleteBanner($id);
    }

    /** @return array<int, array<string, mixed>> */
    public function orderItems(int $orderId): array
    {
        return $this->repo->orderItems($orderId);
    }

    public static function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => '접수대기',
            'paid' => '결제완료',
            'preparing' => '상품준비',
            'shipping' => '배송중',
            'delivered' => '배송완료',
            'cancelled' => '취소',
            'refunded' => '환불',
            default => $status,
        };
    }

    public static function productStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => '임시저장',
            'active' => '판매중',
            'soldout' => '품절',
            'hidden' => '숨김',
            default => $status,
        };
    }

    public static function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => '결제대기',
            'paid' => '결제완료',
            'failed' => '결제실패',
            'refunded' => '환불완료',
            default => $status,
        };
    }

    /** @return array<string, mixed> */
    public function productPageSettings(): array
    {
        $row = (new ShopProductPageSettingsRepository())->get();
        return [
            'header_html' => $row['header_html'],
            'footer_html' => $row['footer_html'],
            'header_image' => $row['header_image'],
            'footer_image' => $row['footer_image'],
            'header_image_url' => ShopProductImageService::resolveUrl($row['header_image']),
            'footer_image_url' => ShopProductImageService::resolveUrl($row['footer_image']),
        ];
    }

    public function saveProductPageSettings(array $data): array
    {
        $headerImage = ShopProductImageService::normalizePublicPath((string) ($data['header_image'] ?? ''));
        $footerImage = ShopProductImageService::normalizePublicPath((string) ($data['footer_image'] ?? ''));
        (new ShopProductPageSettingsRepository())->save([
            'header_html' => (string) ($data['header_html'] ?? ''),
            'footer_html' => (string) ($data['footer_html'] ?? ''),
            'header_image' => $headerImage,
            'footer_image' => $footerImage,
        ]);
        return $this->productPageSettings();
    }

    /** @return array{categories:list<array<string,mixed>>} */
    public function productPageCategorySettingsList(): array
    {
        $indexed = (new ShopProductPageCategorySettingsRepository())->allIndexed();
        $categories = [];
        foreach ($this->categories() as $cat) {
            $id = (int) ($cat['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $custom = $indexed[$id] ?? null;
            $slug = (string) ($cat['slug'] ?? '');
            $name = (string) ($cat['name'] ?? '');
            $storedTags = is_array($custom) ? ($custom['hashtags'] ?? null) : null;
            $hashtags = ShopCategoryHashtag::resolve($storedTags, $slug, $name);
            $categories[] = [
                'id' => $id,
                'name' => $name,
                'label' => (string) ($cat['label'] ?? $name),
                'slug' => $slug,
                'parent_id' => (int) ($cat['parent_id'] ?? 0),
                'parent_name' => (string) ($cat['parent_name'] ?? ''),
                'depth' => (int) ($cat['depth'] ?? 0),
                'is_active' => (int) ($cat['is_active'] ?? 0) === 1,
                'has_custom' => $this->categorySettingsCustom($custom, $slug, $name),
                'header_html' => (string) ($custom['header_html'] ?? ''),
                'footer_html' => (string) ($custom['footer_html'] ?? ''),
                'header_image' => (string) ($custom['header_image'] ?? ''),
                'footer_image' => (string) ($custom['footer_image'] ?? ''),
                'header_image_url' => ShopProductImageService::resolveUrl((string) ($custom['header_image'] ?? '')),
                'footer_image_url' => ShopProductImageService::resolveUrl((string) ($custom['footer_image'] ?? '')),
                'hashtags' => $hashtags,
            ];
        }
        return ['categories' => $categories];
    }

    /** @return array<string, mixed> */
    public function productPageCategorySettings(int $categoryId): array
    {
        if ($categoryId <= 0) {
            throw new RuntimeException('카테고리를 선택해주세요.');
        }
        $cat = null;
        foreach ($this->categories() as $row) {
            if ((int) ($row['id'] ?? 0) === $categoryId) {
                $cat = $row;
                break;
            }
        }
        if (!$cat) {
            throw new RuntimeException('카테고리를 찾을 수 없습니다.');
        }
        $custom = (new ShopProductPageCategorySettingsRepository())->findByCategoryId($categoryId) ?? [
            'header_html' => '',
            'footer_html' => '',
            'header_image' => '',
            'footer_image' => '',
            'hashtags' => null,
        ];
        $parentName = (string) ($cat['parent_name'] ?? '');
        $name = (string) ($cat['name'] ?? '');
        $slug = (string) ($cat['slug'] ?? '');
        $depth = (int) ($cat['depth'] ?? 0);
        $displayName = $parentName !== '' ? $parentName . ' › ' . $name : $name;
        $hashtags = ShopCategoryHashtag::resolve($custom['hashtags'] ?? null, $slug, $name);
        return [
            'category_id' => $categoryId,
            'category_name' => $displayName,
            'category_own_name' => $name,
            'slug' => $slug,
            'parent_id' => (int) ($cat['parent_id'] ?? 0),
            'parent_name' => $parentName,
            'depth' => $depth,
            'header_html' => $custom['header_html'],
            'footer_html' => $custom['footer_html'],
            'header_image' => $custom['header_image'],
            'footer_image' => $custom['footer_image'],
            'header_image_url' => ShopProductImageService::resolveUrl($custom['header_image']),
            'footer_image_url' => ShopProductImageService::resolveUrl($custom['footer_image']),
            'hashtags' => $hashtags,
            'has_custom' => $this->categorySettingsCustom($custom, $slug, $name),
        ];
    }

    public function saveProductPageCategorySettings(array $data): array
    {
        $categoryId = (int) ($data['category_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new RuntimeException('카테고리를 선택해주세요.');
        }
        $headerImage = ShopProductImageService::normalizePublicPath((string) ($data['header_image'] ?? ''));
        $footerImage = ShopProductImageService::normalizePublicPath((string) ($data['footer_image'] ?? ''));
        (new ShopProductPageCategorySettingsRepository())->save($categoryId, [
            'header_html' => (string) ($data['header_html'] ?? ''),
            'footer_html' => (string) ($data['footer_html'] ?? ''),
            'header_image' => $headerImage,
            'footer_image' => $footerImage,
            'hashtags' => $data['hashtags'] ?? [],
        ]);
        return $this->productPageCategorySettings($categoryId);
    }

    /**
     * 헤더·푸터·이미지가 있거나, 해시태그를 기본값과 다르게 저장한 경우.
     *
     * @param array<string, mixed>|null $custom
     */
    private function categorySettingsCustom(?array $custom, string $slug, string $name): bool
    {
        if ($custom === null) {
            return false;
        }
        $repo = new ShopProductPageCategorySettingsRepository();
        $layout = $repo->hasContent(
            (string) ($custom['header_html'] ?? ''),
            (string) ($custom['footer_html'] ?? ''),
            (string) ($custom['header_image'] ?? ''),
            (string) ($custom['footer_image'] ?? '')
        );
        if ($layout) {
            return true;
        }
        if (!array_key_exists('hashtags', $custom) || $custom['hashtags'] === null) {
            return false;
        }
        $tags = ShopCategoryHashtag::normalize($custom['hashtags']);
        return !ShopCategoryHashtag::same($tags, ShopCategoryHashtag::defaultsFor($slug, $name));
    }

    /** @return array<int, string> */
    /** @return array<int, array{name: string, size: int, path: string, error: string}> */
    public function uploadProductPageImages(array $files, int $fitWidth = 0): array
    {
        return ShopProductImageService::storePageSettingUploads($files, $fitWidth);
    }

    /** @return array<int, string> */
    public static function carriers(): array
    {
        return ['CJ대한통운', '우체국택배', '한진택배', '롯데택배', '로젠택배', '대신택배', '경동택배', 'GS25편의점택배', 'CU편의점택배'];
    }
}

