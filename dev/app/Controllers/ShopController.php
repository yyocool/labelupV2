<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\EventPopupService;
use App\Services\ShopService;

final class ShopController extends BaseController
{
    private AuthService $auth;
    private ShopService $shop;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->shop = new ShopService();
    }

    public function index(): void
    {
        $data = $this->shop->homeData();
        $byMaterial = [];
        foreach ($data['materialProducts'] as $product) {
            $key = (string) ($product['material'] ?? '기타');
            $byMaterial[$key][] = $product;
        }
        $this->renderShop('shop/index', '쇼핑몰 — 라벨업', [
            'seoPage' => 'shop',
            'banners' => $data['banners'],
            'categories' => $data['categories'],
            'specs' => $data['specs'],
            'materialGroups' => $byMaterial,
            'featuredProducts' => $data['featuredProducts'],
        ]);
    }

    public function products(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $categorySlug = trim((string) ($_GET['category'] ?? ''));
        $material = trim((string) ($_GET['material'] ?? ''));
        $shape = trim((string) ($_GET['shape'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $filters = array_filter([
            'q' => $q,
            'category_slug' => $categorySlug,
            'material' => $material,
            'shape' => $shape,
        ]);

        $category = $categorySlug !== '' ? $this->shop->categoryBySlug($categorySlug) : null;
        $list = $this->shop->listProducts($filters, $page, 12);

        $this->renderShop('shop/products', '상품 목록 — 라벨업 쇼핑몰', [
            'seoPage' => 'shop-products',
            'list' => $list,
            'filters' => $filters,
            'category' => $category,
            'categories' => $this->shop->homeData()['categories'],
            'q' => $q,
        ]);
    }

    public function product(string $id): void
    {
        $product = $this->shop->productDetail((int) $id);
        if (!$product) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $related = $this->shop->listProducts(
            ['category_slug' => (string) ($product['category_slug'] ?? '')],
            1,
            4
        );

        $desc = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($product['description'] ?? ''))));
        if ($desc === '') {
            $desc = trim((string) ($product['spec'] ?? ''));
        }
        $this->renderShop('shop/product', e($product['name']) . ' — 라벨업 쇼핑몰', [
            'seoPage' => 'shop-product',
            'seoOverride' => [
                'title' => (string) $product['name'] . ' — 라벨업',
                'description' => mb_substr($desc, 0, 160),
                'og_image' => (string) ($product['thumbnail'] ?? ''),
                'og_type' => 'product',
                'canonical_path' => '/shop/products/' . (int) $product['id'],
                'jsonld' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => (string) $product['name'],
                    'description' => mb_substr($desc, 0, 300),
                    'image' => (string) ($product['thumbnail'] ?? ''),
                    'sku' => (string) ($product['sku'] ?? ''),
                    'offers' => [
                        '@type' => 'Offer',
                        'priceCurrency' => 'KRW',
                        'price' => (int) ($product['unit_price'] ?? $product['price'] ?? 0),
                        'availability' => !empty($product['soldout'])
                            ? 'https://schema.org/OutOfStock'
                            : 'https://schema.org/InStock',
                    ],
                ],
            ],
            'product' => $product,
            'related' => array_values(array_filter(
                $related['items'],
                static fn (array $row): bool => (int) $row['id'] !== (int) $product['id']
            )),
        ]);
    }

    public function cart(): void
    {
        $this->renderShop('shop/cart', '장바구니 — 라벨업 쇼핑몰', [
            'seoPage' => 'shop-cart',
            'cart' => $this->shop->cartSummary(),
        ]);
    }

    private function renderShop(string $template, string $title, array $data = []): void
    {
        $categories = $data['categories'] ?? $this->shop->homeData()['categories'];
        view('shop/layout', array_merge($data, [
            'contentTemplate' => $template,
            'pageTitle' => $title,
            'authUser' => $this->auth->user(),
            'cartCount' => $this->shop->cartCount(),
            'activeNav' => 'shop',
            'shopService' => $this->shop,
            'shopCategories' => $categories,
            'shopSubNav' => $this->resolveShopSubNav($template, $data),
            'eventPopups' => (new EventPopupService())->activeForSite(),
        ]));
    }

    private function resolveShopSubNav(string $template, array $data): string
    {
        if ($template === 'shop/index') {
            return 'home';
        }
        if ($template === 'shop/cart') {
            return 'cart';
        }
        if ($template === 'shop/product') {
            return 'product';
        }
        if ($template === 'shop/products') {
            $slug = (string) (($data['category']['slug'] ?? '') ?: '');
            return $slug !== '' ? 'cat-' . $slug : 'products';
        }
        return 'home';
    }
}
