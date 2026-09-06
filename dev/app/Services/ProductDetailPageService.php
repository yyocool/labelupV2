<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductDetailPageRepository;

final class ProductDetailPageService
{
    private ProductDetailPageRepository $repo;

    public function __construct()
    {
        $this->repo = new ProductDetailPageRepository();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *   items: array<int, array<string, mixed>>,
     *   total: int,
     *   page: int,
     *   pages: int,
     *   summary: array{total:int,registered:int,unregistered:int},
     *   filters: array<string, mixed>
     * }
     */
    public function adminList(array $filters): array
    {
        $normalized = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'registered' => $this->normalizeRegistered((string) ($filters['registered'] ?? '')),
            'category_id' => (int) ($filters['category_id'] ?? 0),
            'product_status' => $this->normalizeProductStatus((string) ($filters['product_status'] ?? '')),
        ];
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));

        $list = $this->repo->adminList($normalized, $page, $perPage);
        $list['summary'] = $this->repo->summary($normalized);
        $list['filters'] = $normalized + ['page' => $list['page'], 'per_page' => $perPage];

        return $list;
    }

    private function normalizeRegistered(string $value): string
    {
        return in_array($value, ['yes', 'no'], true) ? $value : '';
    }

    private function normalizeProductStatus(string $value): string
    {
        return in_array($value, ['draft', 'active', 'soldout', 'hidden'], true) ? $value : '';
    }
}
