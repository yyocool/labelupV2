<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;
use RuntimeException;

final class ShopService
{
    public const SESSION_CART = 'shop_cart';

    private ShopRepository $repo;

    public function __construct()
    {
        $this->repo = new ShopRepository();
    }

    public function homeData(): array
    {
        return [
            'banners' => $this->repo->activeBanners(),
            'categories' => $this->repo->activeCategories(),
            'specs' => $this->repo->activeSpecs(8),
            'materialProducts' => $this->repo->productsGroupedByMaterial(4),
            'featuredProducts' => $this->repo->activeProducts([], 1, 8)['items'],
        ];
    }

    /** @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function listProducts(array $filters, int $page = 1, int $perPage = 12): array
    {
        return $this->repo->activeProducts($filters, $page, $perPage);
    }

    public function productDetail(int $id): ?array
    {
        return $this->repo->findActiveProduct($id);
    }

    public function lookupByCode(string $code): ?array
    {
        $product = $this->repo->findActiveProductByCode($code);
        return $product ? $this->presentPublicProduct($product) : null;
    }

    /** @return array{items: array<int, array<string, mixed>>, categories: array<int, array{id:int,name:string}>} */
    public function editorPapers(): array
    {
        return $this->repo->editorPapers();
    }

    public function categoryBySlug(string $slug): ?array
    {
        return $this->repo->findCategoryBySlug($slug);
    }

    /** @return array<int, array{product_id:int, qty:int}> */
    public function rawCart(): array
    {
        return $_SESSION[self::SESSION_CART] ?? [];
    }

    public function cartCount(): int
    {
        $count = 0;
        foreach ($this->rawCart() as $row) {
            $count += (int) ($row['qty'] ?? 0);
        }
        return $count;
    }

    /** @return array<int, array<string, mixed>> */
    public function cartItems(): array
    {
        $items = [];
        foreach ($this->rawCart() as $row) {
            $product = $this->repo->findActiveProduct((int) $row['product_id']);
            if (!$product) {
                continue;
            }
            $qty = max(1, (int) ($row['qty'] ?? 1));
            $unit = $this->unitPrice($product);
            $items[] = $product + [
                'qty' => $qty,
                'unit_price' => $unit,
                'line_total' => $unit * $qty,
            ];
        }
        return $items;
    }

    public function cartSummary(): array
    {
        $items = $this->cartItems();
        $subtotal = array_sum(array_column($items, 'line_total'));
        $shipping = $subtotal >= 50000 || $subtotal === 0 ? 0 : 3000;
        $presented = [];
        foreach ($items as $item) {
            $row = $this->presentPublicProduct($item);
            $row['qty'] = (int) ($item['qty'] ?? 1);
            $row['unit_price'] = (int) ($item['unit_price'] ?? $row['unit_price']);
            $row['line_total'] = (int) ($item['line_total'] ?? 0);
            $row['line_total_label'] = $this->formatPrice((int) $row['line_total']);
            $presented[] = $row;
        }
        return [
            'items' => $presented,
            'subtotal' => $subtotal,
            'subtotal_label' => $this->formatPrice($subtotal),
            'shipping_fee' => $shipping,
            'shipping_label' => $shipping === 0 ? '무료' : $this->formatPrice($shipping),
            'total' => $subtotal + $shipping,
            'total_label' => $this->formatPrice($subtotal + $shipping),
            'count' => $this->cartCount(),
        ];
    }

    /** @param array<string, mixed> $product */
    public function presentPublicProduct(array $product): array
    {
        $unit = $this->unitPrice($product);
        $list = (int) ($product['price'] ?? 0);
        $sale = $product['sale_price'] ?? null;
        $onSale = $sale !== null && $sale !== '' && (int) $sale > 0 && (int) $sale < $list;
        $thumb = ShopProductImageService::resolveUrl((string) ($product['thumbnail'] ?? ''));
        if ($thumb === '') {
            $thumb = asset('hero-tall-1.webp');
        }
        $w = $product['width_mm'] ?? null;
        $h = $product['height_mm'] ?? null;
        $labels = isset($product['labels_per_sheet']) ? (int) $product['labels_per_sheet'] : 0;
        $soldout = ($product['status'] ?? '') === 'soldout' || (int) ($product['stock_qty'] ?? 0) <= 0;
        $spec = trim(implode(' · ', array_filter([
            (string) ($product['material'] ?? ''),
            (string) ($product['shape'] ?? ''),
            ($w !== null && $h !== null && $w !== '' && $h !== '') ? "{$w}×{$h}mm" : '',
            $labels > 0 ? ($labels . '칸') : '',
        ])));

        return [
            'id' => (int) ($product['id'] ?? 0),
            'name' => (string) ($product['name'] ?? ''),
            'sku' => (string) ($product['sku'] ?? ''),
            'category' => (string) ($product['category_name'] ?? ''),
            'category_slug' => (string) ($product['category_slug'] ?? ''),
            'spec' => $spec,
            'spec_name' => (string) ($product['spec_name'] ?? ''),
            'width_mm' => $w !== null && $w !== '' ? (float) $w : null,
            'height_mm' => $h !== null && $h !== '' ? (float) $h : null,
            'labels_per_sheet' => $labels > 0 ? $labels : null,
            'shape' => (string) ($product['shape'] ?? ''),
            'material' => (string) ($product['material'] ?? ''),
            'price' => $list,
            'sale_price' => $onSale ? (int) $sale : null,
            'unit_price' => $unit,
            'price_label' => $this->formatPrice($unit),
            'list_price_label' => $this->formatPrice($list),
            'on_sale' => $onSale,
            'stock_qty' => (int) ($product['stock_qty'] ?? 0),
            'soldout' => $soldout,
            'thumbnail' => $thumb,
            'description' => (string) ($product['description'] ?? ''),
        ];
    }

    /** @return array{items: array<int, array<string, mixed>>, categories: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function catalog(array $filters, int $page = 1, int $perPage = 12): array
    {
        $list = $this->listProducts($filters, $page, $perPage);
        $items = [];
        foreach ($list['items'] as $row) {
            $items[] = $this->presentPublicProduct($row);
        }
        $categories = [];
        foreach ($this->homeData()['categories'] as $cat) {
            $categories[] = [
                'id' => (int) ($cat['id'] ?? 0),
                'name' => (string) ($cat['name'] ?? ''),
                'slug' => (string) ($cat['slug'] ?? ''),
            ];
        }
        return [
            'items' => $items,
            'categories' => $categories,
            'total' => (int) ($list['total'] ?? 0),
            'page' => (int) ($list['page'] ?? 1),
            'pages' => (int) ($list['pages'] ?? 1),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    public function checkout(array $payload, array $user): array
    {
        $items = $this->cartItems();
        if ($items === []) {
            throw new RuntimeException('장바구니가 비어 있습니다.');
        }
        foreach ($items as $item) {
            if (($item['status'] ?? '') === 'soldout' || (int) ($item['stock_qty'] ?? 0) < (int) ($item['qty'] ?? 1)) {
                throw new RuntimeException(($item['name'] ?? '상품') . '의 재고가 부족합니다.');
            }
        }

        $name = trim((string) ($payload['customer_name'] ?? ($user['name'] ?? '')));
        $email = trim((string) ($payload['customer_email'] ?? ($user['email'] ?? '')));
        $phone = trim((string) ($payload['customer_phone'] ?? ($user['phone'] ?? '')));
        $address = trim((string) ($payload['shipping_address'] ?? ''));
        $memo = trim((string) ($payload['shipping_memo'] ?? ''));
        if ($name === '' || $email === '' || $phone === '' || $address === '') {
            throw new RuntimeException('주문자 이름, 이메일, 연락처, 배송지를 모두 입력해 주세요.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('올바른 이메일을 입력해 주세요.');
        }

        $summary = $this->cartSummary();
        $created = $this->repo->createCustomerOrder([
            'user_id' => isset($user['id']) ? (int) $user['id'] : null,
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'subtotal' => $summary['subtotal'],
            'shipping_fee' => $summary['shipping_fee'],
            'discount_amount' => 0,
            'total_amount' => $summary['total'],
            'shipping_name' => $name,
            'shipping_phone' => $phone,
            'shipping_address' => $address,
            'shipping_memo' => $memo !== '' ? $memo : null,
        ], $items);
        $this->clearCart();

        return [
            'order_id' => $created['id'],
            'order_no' => $created['order_no'],
            'total' => $summary['total'],
            'total_label' => $summary['total_label'],
            'count' => $summary['count'],
        ];
    }

    public function addToCart(int $productId, int $qty = 1): void
    {
        $product = $this->repo->findActiveProduct($productId);
        if (!$product) {
            throw new RuntimeException('판매 중인 상품을 찾을 수 없습니다.');
        }
        if (($product['status'] ?? '') === 'soldout' || (int) ($product['stock_qty'] ?? 0) <= 0) {
            throw new RuntimeException('품절된 상품입니다.');
        }

        $qty = max(1, min($qty, (int) $product['stock_qty']));
        $cart = $this->rawCart();
        $found = false;
        foreach ($cart as &$row) {
            if ((int) $row['product_id'] === $productId) {
                $row['qty'] = min((int) $row['qty'] + $qty, (int) $product['stock_qty']);
                $found = true;
                break;
            }
        }
        unset($row);
        if (!$found) {
            $cart[] = ['product_id' => $productId, 'qty' => $qty];
        }
        $_SESSION[self::SESSION_CART] = $cart;
    }

    public function updateCartItem(int $productId, int $qty): void
    {
        $cart = $this->rawCart();
        if ($qty <= 0) {
            $this->removeFromCart($productId);
            return;
        }
        $product = $this->repo->findActiveProduct($productId);
        if (!$product) {
            throw new RuntimeException('상품을 찾을 수 없습니다.');
        }
        $qty = min($qty, (int) $product['stock_qty']);
        $updated = false;
        foreach ($cart as &$row) {
            if ((int) $row['product_id'] === $productId) {
                $row['qty'] = $qty;
                $updated = true;
                break;
            }
        }
        unset($row);
        if (!$updated) {
            throw new RuntimeException('장바구니에 해당 상품이 없습니다.');
        }
        $_SESSION[self::SESSION_CART] = $cart;
    }

    public function removeFromCart(int $productId): void
    {
        $cart = array_values(array_filter(
            $this->rawCart(),
            static fn (array $row): bool => (int) $row['product_id'] !== $productId
        ));
        $_SESSION[self::SESSION_CART] = $cart;
    }

    public function clearCart(): void
    {
        unset($_SESSION[self::SESSION_CART]);
    }

    public function formatPrice(int $amount): string
    {
        return number_format($amount) . '원';
    }

    /** @param array<string, mixed> $product */
    public function unitPrice(array $product): int
    {
        $sale = $product['sale_price'] ?? null;
        if ($sale !== null && $sale !== '' && (int) $sale > 0) {
            return (int) $sale;
        }
        return (int) ($product['price'] ?? 0);
    }

    public function productThumb(array $product): string
    {
        if (!empty($product['thumbnail'])) {
            return (string) $product['thumbnail'];
        }
        return asset('hero-tall-1.webp');
    }
}
