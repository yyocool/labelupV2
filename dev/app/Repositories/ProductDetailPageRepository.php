<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class ProductDetailPageRepository extends BaseModel
{
    /**
     * @param array{q?:string,registered?:string,category_id?:int,product_status?:string} $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int}
     */
    public function adminList(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        [$where, $params] = $this->filterClause($filters);

        $countRow = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM shop_products p
             LEFT JOIN shop_categories c ON c.id = p.category_id
             LEFT JOIN shop_product_detail_pages d ON d.product_id = p.id
             WHERE {$where}",
            $params
        );
        $total = (int) ($countRow['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT p.id, p.name, p.sku, p.status AS product_status, p.category_id,
                    c.name AS category_name,
                    d.id AS detail_page_id, d.status AS detail_status, d.generated_at,
                    CASE WHEN d.id IS NULL THEN 0 ELSE 1 END AS has_detail_page
             FROM shop_products p
             LEFT JOIN shop_categories c ON c.id = p.category_id
             LEFT JOIN shop_product_detail_pages d ON d.product_id = p.id
             WHERE {$where}
             ORDER BY p.sort_order ASC, p.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /**
     * @param array{q?:string,category_id?:int,product_status?:string} $filters
     * @return array{total:int,registered:int,unregistered:int}
     */
    public function summary(array $filters): array
    {
        $summaryFilters = $filters;
        unset($summaryFilters['registered']);
        [$where, $params] = $this->filterClause($summaryFilters);

        $row = $this->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN d.id IS NULL THEN 0 ELSE 1 END) AS registered
             FROM shop_products p
             LEFT JOIN shop_categories c ON c.id = p.category_id
             LEFT JOIN shop_product_detail_pages d ON d.product_id = p.id
             WHERE {$where}",
            $params
        );

        $total = (int) ($row['total'] ?? 0);
        $registered = (int) ($row['registered'] ?? 0);

        return [
            'total' => $total,
            'registered' => $registered,
            'unregistered' => max(0, $total - $registered),
        ];
    }

    /**
     * @param array{q?:string,registered?:string,category_id?:int,product_status?:string} $filters
     * @return array{0:string,1:array<string, mixed>}
     */
    private function filterClause(array $filters): array
    {
        $where = '1=1';
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where .= ' AND (p.name LIKE :q_name OR p.sku LIKE :q_sku)';
            $like = '%' . $q . '%';
            $params['q_name'] = $like;
            $params['q_sku'] = $like;
        }

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where .= ' AND p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $productStatus = trim((string) ($filters['product_status'] ?? ''));
        if (in_array($productStatus, ['draft', 'active', 'soldout', 'hidden'], true)) {
            $where .= ' AND p.status = :product_status';
            $params['product_status'] = $productStatus;
        }

        $registered = trim((string) ($filters['registered'] ?? ''));
        if ($registered === 'yes') {
            $where .= ' AND d.id IS NOT NULL';
        } elseif ($registered === 'no') {
            $where .= ' AND d.id IS NULL';
        }

        return [$where, $params];
    }
}
