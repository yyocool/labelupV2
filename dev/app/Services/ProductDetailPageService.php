<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductDetailPageRepository;
use App\Repositories\ShopRepository;
use RuntimeException;

final class ProductDetailPageService
{
    private ProductDetailPageRepository $repo;
    private ShopRepository $shopRepo;

    public function __construct()
    {
        $this->repo = new ProductDetailPageRepository();
        $this->shopRepo = new ShopRepository();
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

    /** @return array<string, mixed> */
    public function getForEdit(int $productId): array
    {
        $product = $this->requireProduct($productId);
        $row = $this->repo->findByProductId($productId);
        $html = trim((string) ($row['html_content'] ?? ''));
        return [
            'product_id' => $productId,
            'product_name' => (string) ($product['name'] ?? ''),
            'sku' => (string) ($product['sku'] ?? ''),
            'category_name' => (string) ($product['category_name'] ?? ''),
            'html_content' => $html,
            'status' => (string) ($row['status'] ?? ''),
            'has_detail_page' => $row !== null,
        ];
    }

    /** @return array<string, mixed> */
    public function save(int $productId, string $html): array
    {
        $this->requireProduct($productId);
        $this->repo->saveForProduct($productId, $html, 'published');
        return $this->getForEdit($productId);
    }

    /** @return array<string, mixed> */
    private function requireProduct(int $productId): array
    {
        if ($productId <= 0) {
            throw new RuntimeException('상품을 선택해주세요.');
        }
        $product = $this->shopRepo->findProductForPreview($productId);
        if (!$product) {
            throw new RuntimeException('상품을 찾을 수 없습니다.');
        }
        return $product;
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
