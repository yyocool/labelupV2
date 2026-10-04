<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopWishlistRepository;
use RuntimeException;

final class ShopWishlistService
{
    private ShopWishlistRepository $repo;

    public function __construct()
    {
        $this->repo = new ShopWishlistRepository();
    }

    public function has(int $userId, int $productId): bool
    {
        if ($userId <= 0 || $productId <= 0) {
            return false;
        }
        return $this->repo->exists($userId, $productId);
    }

    public function count(int $userId): int
    {
        return $userId > 0 ? $this->repo->countForUser($userId) : 0;
    }

    /**
     * 찜 상태를 뒤집는다.
     *
     * @return array{wished: bool, count: int}
     */
    public function toggle(int $userId, int $productId): array
    {
        if ($userId <= 0) {
            throw new RuntimeException('로그인이 필요합니다.');
        }
        if ($productId <= 0) {
            throw new RuntimeException('상품을 찾을 수 없습니다.');
        }

        if ($this->repo->exists($userId, $productId)) {
            $this->repo->remove($userId, $productId);
            $wished = false;
        } else {
            $this->repo->add($userId, $productId);
            $wished = true;
        }

        return ['wished' => $wished, 'count' => $this->repo->countForUser($userId)];
    }

    public function remove(int $userId, int $productId): int
    {
        if ($userId <= 0 || $productId <= 0) {
            return $this->count($userId);
        }
        $this->repo->remove($userId, $productId);
        return $this->repo->countForUser($userId);
    }

    /**
     * 마이페이지·API 에서 쓰는 표시용 목록.
     *
     * @return array<int, array<string, mixed>>
     */
    public function items(int $userId, int $limit = 0): array
    {
        if ($userId <= 0) {
            return [];
        }
        $shop = new ShopService();
        $out = [];
        foreach ($this->repo->listForUser($userId, $limit) as $row) {
            $unit = $shop->unitPrice($row);
            $onSale = !empty($row['sale_price']) && (int) $row['sale_price'] < (int) $row['price'];
            // 사용자 페이지는 판매중·품절이고 카테고리까지 활성일 때만 열린다.
            $openable = in_array((string) $row['status'], ['active', 'soldout'], true)
                && !empty($row['category_is_active']);
            $out[] = [
                'product_id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'sku' => (string) ($row['sku'] ?? ''),
                'category_name' => (string) ($row['category_name'] ?? ''),
                'thumb_url' => ShopProductImageService::resolveUrl((string) ($row['thumbnail'] ?? '')),
                'price_label' => $shop->formatPrice($unit),
                'list_price_label' => $onSale ? $shop->formatPrice((int) $row['price']) : '',
                'status' => (string) $row['status'],
                'soldout' => (string) $row['status'] === 'soldout' || (int) $row['stock_qty'] <= 0,
                'openable' => $openable,
                'url' => $openable ? url('shop/products/' . (int) $row['id']) : '',
                'wished_at' => (string) ($row['wished_at'] ?? ''),
            ];
        }
        return $out;
    }
}
