<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class ShopWishlistRepository extends BaseModel
{
    public function exists(int $userId, int $productId): bool
    {
        $row = $this->fetchOne(
            'SELECT id FROM shop_product_wishlists WHERE user_id = :uid AND product_id = :pid LIMIT 1',
            ['uid' => $userId, 'pid' => $productId]
        );
        return $row !== null;
    }

    /** 이미 담겨 있으면 유니크 키에 걸려 조용히 넘어간다. */
    public function add(int $userId, int $productId): void
    {
        $this->execute(
            'INSERT IGNORE INTO shop_product_wishlists (user_id, product_id, created_at)
             VALUES (:uid, :pid, :now)',
            ['uid' => $userId, 'pid' => $productId, 'now' => date('Y-m-d H:i:s')]
        );
    }

    public function remove(int $userId, int $productId): void
    {
        $this->execute(
            'DELETE FROM shop_product_wishlists WHERE user_id = :uid AND product_id = :pid',
            ['uid' => $userId, 'pid' => $productId]
        );
    }

    public function countForUser(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS n FROM shop_product_wishlists WHERE user_id = :uid',
            ['uid' => $userId]
        );
        return (int) ($row['n'] ?? 0);
    }

    /**
     * 찜한 상품 목록. 삭제된 상품은 INNER JOIN 으로 자동 제외된다.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId, int $limit = 0): array
    {
        $limitSql = $limit > 0 ? ' LIMIT ' . $limit : '';
        return $this->fetchAll(
            'SELECT w.id AS wish_id, w.created_at AS wished_at,
                    p.id, p.name, p.sku, p.thumbnail, p.price, p.sale_price, p.status, p.stock_qty,
                    c.name AS category_name, c.is_active AS category_is_active
             FROM shop_product_wishlists w
             INNER JOIN shop_products p ON p.id = w.product_id
             LEFT JOIN shop_categories c ON c.id = p.category_id
             WHERE w.user_id = :uid
             ORDER BY w.id DESC' . $limitSql,
            ['uid' => $userId]
        );
    }

    /**
     * 목록 화면에서 찜 여부를 한 번에 표시할 때 쓴다.
     *
     * @return array<int, int>
     */
    public function productIdsForUser(int $userId): array
    {
        $rows = $this->fetchAll(
            'SELECT product_id FROM shop_product_wishlists WHERE user_id = :uid',
            ['uid' => $userId]
        );
        return array_map(static fn (array $r): int => (int) $r['product_id'], $rows);
    }
}
