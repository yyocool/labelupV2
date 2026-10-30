<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class ShopReviewRepository extends BaseModel
{
    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT r.*, p.name AS product_name
             FROM shop_product_reviews r
             LEFT JOIN shop_products p ON p.id = r.product_id
             WHERE r.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @param 'all'|'photo'|'text' $type
     * @return array<int, array<string, mixed>>
     */
    public function approvedForProduct(int $productId, string $type, int $limit, int $offset): array
    {
        [$typeSql, $params] = $this->typeFilter($type);
        return $this->fetchAll(
            "SELECT * FROM shop_product_reviews
             WHERE product_id = :pid AND status = 'approved'{$typeSql}
             ORDER BY created_at DESC, id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            ['pid' => $productId] + $params
        );
    }

    public function countApprovedForProduct(int $productId, string $type): int
    {
        [$typeSql, $params] = $this->typeFilter($type);
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM shop_product_reviews
             WHERE product_id = :pid AND status = 'approved'{$typeSql}",
            ['pid' => $productId] + $params
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array{count: int, photo: int, avg: float, dist: array<int, int>} */
    public function summaryForProduct(int $productId): array
    {
        $rows = $this->fetchAll(
            "SELECT rating, review_type, COUNT(*) AS cnt FROM shop_product_reviews
             WHERE product_id = :pid AND status = 'approved'
             GROUP BY rating, review_type",
            ['pid' => $productId]
        );
        $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $count = 0;
        $photo = 0;
        $sum = 0;
        foreach ($rows as $row) {
            $rating = max(1, min(5, (int) $row['rating']));
            $cnt = (int) $row['cnt'];
            $dist[$rating] += $cnt;
            $count += $cnt;
            $sum += $rating * $cnt;
            if (($row['review_type'] ?? '') === 'photo') {
                $photo += $cnt;
            }
        }
        return [
            'count' => $count,
            'photo' => $photo,
            'avg' => $count > 0 ? round($sum / $count, 1) : 0.0,
            'dist' => $dist,
        ];
    }

    public function userReviewForProduct(int $userId, int $productId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM shop_product_reviews
             WHERE user_id = :uid AND product_id = :pid AND is_admin_created = 0
             ORDER BY id DESC LIMIT 1',
            ['uid' => $userId, 'pid' => $productId]
        );
    }

    public function userPurchased(int $userId, int $productId): bool
    {
        $row = $this->fetchOne(
            "SELECT 1 AS ok FROM shop_order_items i
             INNER JOIN shop_orders o ON o.id = i.order_id
             WHERE o.user_id = :uid AND i.product_id = :pid
               AND o.status IN ('paid','preparing','shipping','delivered')
             LIMIT 1",
            ['uid' => $userId, 'pid' => $productId]
        );
        return $row !== null;
    }

    /**
     * @param array{status?: string, type?: string, q?: string, product_id?: int} $filters
     * @return array<int, array<string, mixed>>
     */
    public function adminList(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->adminWhere($filters);
        return $this->fetchAll(
            "SELECT r.*, p.name AS product_name, u.email AS user_email
             FROM shop_product_reviews r
             LEFT JOIN shop_products p ON p.id = r.product_id
             LEFT JOIN users u ON u.id = r.user_id
             {$where}
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    /** @param array{status?: string, type?: string, q?: string, product_id?: int} $filters */
    public function adminCount(array $filters): int
    {
        [$where, $params] = $this->adminWhere($filters);
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM shop_product_reviews r
             LEFT JOIN shop_products p ON p.id = r.product_id
             LEFT JOIN users u ON u.id = r.user_id
             {$where}",
            $params
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array<string, int> */
    public function statusCounts(): array
    {
        $rows = $this->fetchAll('SELECT status, COUNT(*) AS cnt FROM shop_product_reviews GROUP BY status');
        $out = ['pending' => 0, 'approved' => 0, 'hidden' => 0];
        foreach ($rows as $row) {
            $out[(string) $row['status']] = (int) $row['cnt'];
        }
        return $out;
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO shop_product_reviews
             (product_id, user_id, author_name, rating, content, review_type, photo_count, thumb_url,
              status, is_admin_created, is_purchased, created_at, updated_at)
             VALUES (:product_id, :user_id, :author_name, :rating, :content, :review_type, :photo_count, :thumb_url,
              :status, :is_admin_created, :is_purchased, :created_at, :now)',
            [
                'product_id' => (int) $data['product_id'],
                'user_id' => $data['user_id'] ?? null,
                'author_name' => (string) $data['author_name'],
                'rating' => (int) $data['rating'],
                'content' => (string) $data['content'],
                'review_type' => (string) $data['review_type'],
                'photo_count' => (int) $data['photo_count'],
                'thumb_url' => $data['thumb_url'] ?? null,
                'status' => (string) $data['status'],
                'is_admin_created' => (int) !empty($data['is_admin_created']),
                'is_purchased' => (int) !empty($data['is_purchased']),
                'created_at' => (string) ($data['created_at'] ?? $now),
                'now' => $now,
            ]
        );
        return (int) $this->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $this->execute(
            'UPDATE shop_product_reviews SET product_id=:product_id, author_name=:author_name, rating=:rating,
             content=:content, review_type=:review_type, photo_count=:photo_count, thumb_url=:thumb_url,
             status=:status, created_at=:created_at, updated_at=:now WHERE id=:id',
            [
                'id' => $id,
                'product_id' => (int) $data['product_id'],
                'author_name' => (string) $data['author_name'],
                'rating' => (int) $data['rating'],
                'content' => (string) $data['content'],
                'review_type' => (string) $data['review_type'],
                'photo_count' => (int) $data['photo_count'],
                'thumb_url' => $data['thumb_url'] ?? null,
                'status' => (string) $data['status'],
                'created_at' => (string) $data['created_at'],
                'now' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /** @param array<int, int> $ids */
    public function setStatus(array $ids, string $status): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $v): bool => $v > 0)));
        if ($ids === []) {
            return 0;
        }
        $this->execute(
            'UPDATE shop_product_reviews SET status = :status, updated_at = :now WHERE id IN (' . implode(',', $ids) . ')',
            ['status' => $status, 'now' => date('Y-m-d H:i:s')]
        );
        return count($ids);
    }

    /** @param array<int, int> $ids */
    public function delete(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $v): bool => $v > 0)));
        if ($ids === []) {
            return 0;
        }
        $this->execute('DELETE FROM shop_product_reviews WHERE id IN (' . implode(',', $ids) . ')');
        return count($ids);
    }

    public function productExists(int $productId): bool
    {
        return $this->fetchOne('SELECT id FROM shop_products WHERE id = :id LIMIT 1', ['id' => $productId]) !== null;
    }

    /** @return array<int, array{id: int, name: string}> */
    public function productOptions(): array
    {
        return $this->fetchAll(
            "SELECT id, name, sku FROM shop_products WHERE COALESCE(ink_amount, 0) = 0 ORDER BY name ASC, id ASC"
        );
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function typeFilter(string $type): array
    {
        if ($type === 'photo' || $type === 'text') {
            return [' AND review_type = :rtype', ['rtype' => $type]];
        }
        return ['', []];
    }

    /**
     * @param array{status?: string, type?: string, q?: string, product_id?: int} $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function adminWhere(array $filters): array
    {
        $conds = [];
        $params = [];
        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, ['pending', 'approved', 'hidden'], true)) {
            $conds[] = 'r.status = :status';
            $params['status'] = $status;
        }
        $type = (string) ($filters['type'] ?? '');
        if ($type === 'photo' || $type === 'text') {
            $conds[] = 'r.review_type = :rtype';
            $params['rtype'] = $type;
        }
        if ($type === 'admin') {
            $conds[] = 'r.is_admin_created = 1';
        }
        $productId = (int) ($filters['product_id'] ?? 0);
        if ($productId > 0) {
            $conds[] = 'r.product_id = :pid';
            $params['pid'] = $productId;
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $conds[] = '(p.name LIKE :q1 OR r.author_name LIKE :q2 OR u.email LIKE :q3 OR r.content LIKE :q4)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        return [$conds === [] ? '' : 'WHERE ' . implode(' AND ', $conds), $params];
    }
}
