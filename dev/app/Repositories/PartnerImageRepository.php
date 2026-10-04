<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class PartnerImageRepository extends BaseModel
{
    /**
     * @param array{q?:string,category_id?:int,product_status?:string} $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int, summary: array{total:int,with_images:int}}
     */
    public function catalog(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        [$where, $params] = $this->filterClause($filters);

        $countRow = $this->fetchOne(
            "SELECT COUNT(*) AS cnt,
                    SUM(CASE
                        WHEN (p.header_image IS NOT NULL AND p.header_image <> '')
                          OR (p.spec_sheet_image IS NOT NULL AND p.spec_sheet_image <> '')
                          OR EXISTS (
                            SELECT 1 FROM shop_product_images i
                            WHERE i.product_id = p.id AND i.image_path LIKE '/assets/products/mockups/%'
                          )
                        THEN 1 ELSE 0 END) AS with_images
             FROM shop_products p
             LEFT JOIN shop_categories c ON c.id = p.category_id
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
            "SELECT p.id, p.name, p.sku, p.price, p.sale_price, p.status AS product_status,
                    c.name AS category_name,
                    s.width_mm, s.height_mm, s.material, s.labels_per_sheet,
                    (SELECT i.image_path
                     FROM shop_product_images i
                     WHERE i.product_id = p.id
                       AND i.image_path LIKE '/assets/products/mockups/%'
                     ORDER BY i.sort_order ASC, i.id ASC
                     LIMIT 1) AS representative_image
             FROM shop_products p
             LEFT JOIN shop_categories c ON c.id = p.category_id
             LEFT JOIN label_specs s ON s.id = p.spec_id
             WHERE {$where}
             ORDER BY p.sort_order ASC, p.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'summary' => [
                'total' => $total,
                'with_images' => (int) ($countRow['with_images'] ?? 0),
            ],
            'filters' => $filters,
        ];
    }

    /** @param array{q?:string,category_id?:int,product_status?:string} $filters
     *  @return array<int, int>
     */
    public function idsForFilters(array $filters, int $limit): array
    {
        $limit = max(1, $limit);
        [$where, $params] = $this->filterClause($filters);
        $rows = $this->fetchAll(
            "SELECT p.id
             FROM shop_products p
             LEFT JOIN shop_categories c ON c.id = p.category_id
             WHERE {$where}
             ORDER BY p.sort_order ASC, p.id DESC
             LIMIT {$limit}",
            $params
        );

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function findSellableByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $params = [];
        $holders = [];
        foreach ($ids as $i => $id) {
            $key = 'id' . $i;
            $holders[] = ':' . $key;
            $params[$key] = $id;
        }
        $in = implode(', ', $holders);

        return $this->fetchAll(
            "SELECT p.id, p.name, p.sku, p.spec_sheet_image, p.header_image,
                    (SELECT i.image_path
                     FROM shop_product_images i
                     WHERE i.product_id = p.id
                       AND i.image_path LIKE '/assets/products/mockups/%'
                     ORDER BY i.sort_order ASC, i.id ASC
                     LIMIT 1) AS representative_image
             FROM shop_products p
             WHERE p.id IN ({$in})
               AND p.status IN ('active', 'soldout')
             ORDER BY p.sort_order ASC, p.id DESC",
            $params
        );
    }

    /**
     * @param array{q?:string,category_id?:int,product_status?:string} $filters
     * @return array{0:string,1:array<string, mixed>}
     */
    private function filterClause(array $filters): array
    {
        $where = "p.status IN ('active', 'soldout')";
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
            $where .= ' AND (p.category_id = :category_id OR c.parent_id = :category_parent)';
            $params['category_id'] = $categoryId;
            $params['category_parent'] = $categoryId;
        }

        $productStatus = trim((string) ($filters['product_status'] ?? ''));
        if (in_array($productStatus, ['active', 'soldout'], true)) {
            $where .= ' AND p.status = :product_status';
            $params['product_status'] = $productStatus;
        }

        return [$where, $params];
    }
}
