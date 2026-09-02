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
        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping_fee' => $shipping,
            'total' => $subtotal + $shipping,
            'count' => $this->cartCount(),
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
