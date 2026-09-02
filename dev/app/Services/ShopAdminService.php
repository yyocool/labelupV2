<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;
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
        return $this->repo->saveCategory([
            'id' => (int) ($data['id'] ?? 0),
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
        $this->repo->deleteCategory($id);
    }

    /** @return array<int, array<string, mixed>> */
    public function specs(): array
    {
        return $this->repo->allSpecs();
    }

    public function saveSpec(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('규격명을 입력해주세요.');
        }
        return $this->repo->saveSpec([
            'id' => (int) ($data['id'] ?? 0),
            'name' => $name,
            'kind' => $data['kind'] ?? null,
            'image_path' => ShopProductImageService::normalizePublicPath((string) ($data['image_path'] ?? '')) ?: null,
            'width_mm' => (float) ($data['width_mm'] ?? 0),
            'height_mm' => (float) ($data['height_mm'] ?? 0),
            'material' => trim((string) ($data['material'] ?? '')),
            'shape' => (string) ($data['shape'] ?? 'rect'),
            'labels_per_sheet' => $data['labels_per_sheet'] ?? null,
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active' => !empty($data['is_active']),
        ]);
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
        foreach ($list['items'] as &$row) {
            $pid = (int) ($row['id'] ?? 0);
            $row['images'] = $images[$pid] ?? [];
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
        if ($name === '' || $sku === '') {
            throw new RuntimeException('상품명과 SKU를 입력해주세요.');
        }

        $images = is_array($data['images'] ?? null) ? $data['images'] : [];
        $thumbnail = ShopProductImageService::normalizePublicPath((string) ($data['thumbnail'] ?? ''));
        if ($thumbnail === '' && $images !== []) {
            $thumbnail = ShopProductImageService::normalizePublicPath((string) ($images[0]['image_path'] ?? ''));
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        $id = $this->repo->saveProduct([
            'id' => (int) ($data['id'] ?? 0),
            'category_id' => (int) ($data['category_id'] ?? 0),
            'spec_id' => $data['spec_id'] ?? null,
            'name' => $name,
            'sku' => $sku,
            'price' => (int) ($data['price'] ?? 0),
            'sale_price' => $data['sale_price'] ?? null,
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

        return $id;
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

    /** @return array<int, string> */
    public static function carriers(): array
    {
        return ['CJ대한통운', '우체국택배', '한진택배', '롯데택배', '로젠택배', '대신택배', '경동택배', 'GS25편의점택배', 'CU편의점택배'];
    }
}

