<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;
use App\Repositories\ShopProductPageSettingsRepository;
use App\Repositories\ShopProductPageCategorySettingsRepository;
use App\Repositories\ProductDetailPageRepository;
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
        $tree = $this->repo->activeCategories();
        $roots = [];
        foreach (self::groupCategoryTree($tree) as $group) {
            $parent = $group['parent'];
            $parent['children'] = $group['children'];
            $roots[] = $parent;
        }

        return [
            'banners' => $this->repo->activeBanners(),
            'categories' => $roots,
            'allCategories' => $tree,
            'specs' => $this->repo->activeSpecs(8),
            'materialProducts' => $this->repo->productsGroupedByMaterial(4),
            'featuredProducts' => $this->repo->activeProducts([], 1, 8)['items'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $categories
     * @return list<array{parent: array<string, mixed>, children: list<array<string, mixed>>}>
     */
    public static function groupCategoryTree(array $categories): array
    {
        $groups = [];
        $current = null;
        foreach ($categories as $cat) {
            $depth = (int) ($cat['depth'] ?? 0);
            $parentId = (int) ($cat['parent_id'] ?? 0);
            if ($depth === 0 || $parentId <= 0) {
                if ($current !== null) {
                    $groups[] = $current;
                }
                $current = ['parent' => $cat, 'children' => []];
                continue;
            }
            if ($current === null) {
                $current = ['parent' => $cat, 'children' => []];
                continue;
            }
            $current['children'][] = $cat;
        }
        if ($current !== null) {
            $groups[] = $current;
        }

        return $groups;
    }

    /** @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int} */
    public function listProducts(array $filters, int $page = 1, int $perPage = 12): array
    {
        return $this->repo->activeProducts($filters, $page, $perPage);
    }

    public function productDetail(int $id): ?array
    {
        $product = $this->repo->findActiveProduct($id);
        return $product ? $this->withDetailHtml($product, true) : null;
    }

    public function productPreviewDetail(int $id): ?array
    {
        $product = $this->repo->findProductForPreview($id);
        return $product ? $this->withDetailHtml($product, false) : null;
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function withDetailHtml(array $product, bool $publishedOnly): array
    {
        $html = (new ProductDetailPageRepository())->findHtmlByProductId((int) ($product['id'] ?? 0), $publishedOnly);
        $product['detail_html'] = $html ?? '';
        return $product;
    }

    public function lookupByCode(string $code, ?float $widthMm = null, ?float $heightMm = null, ?int $labels = null): ?array
    {
        $product = $this->repo->findActiveProductByCode($code, $widthMm, $heightMm, $labels);
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

    /** @return array<int, array{product_id:int, option_id:int, qty:int}> */
    public function rawCart(): array
    {
        $out = [];
        foreach ($_SESSION[self::SESSION_CART] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $productId = (int) ($row['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            // 옵션 도입 전에 담긴 장바구니는 option_id가 없다.
            $out[] = [
                'product_id' => $productId,
                'option_id' => max(0, (int) ($row['option_id'] ?? 0)),
                'qty' => max(1, (int) ($row['qty'] ?? 1)),
            ];
        }
        return $out;
    }

    /** 같은 상품이라도 옵션이 다르면 장바구니에서 다른 줄로 다룬다. */
    public static function cartLineKey(int $productId, int $optionId = 0): string
    {
        return $productId . '-' . max(0, $optionId);
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
            $optionId = (int) $row['option_id'];
            $option = $optionId > 0 ? $this->repo->findProductOption((int) $product['id'], $optionId) : null;
            if ($optionId > 0 && $option === null) {
                // 담아둔 뒤 옵션이 내려갔으면 장바구니에서 빼고 보여준다.
                continue;
            }
            $qty = max(1, (int) $row['qty']);
            $unit = max(0, $this->unitPrice($product) + (int) ($option['price_delta'] ?? 0));
            $suffix = trim((string) ($option['sku_suffix'] ?? ''));

            $item = $product;
            $item['qty'] = $qty;
            $item['unit_price'] = $unit;
            $item['line_total'] = $unit * $qty;
            $item['option_id'] = $optionId;
            $item['option_name'] = (string) ($option['name'] ?? '');
            $item['option_price_delta'] = (int) ($option['price_delta'] ?? 0);
            $item['available_qty'] = $option !== null
                ? (int) $option['stock_qty']
                : (int) ($product['stock_qty'] ?? 0);
            $item['line_key'] = self::cartLineKey((int) $product['id'], $optionId);
            if ($suffix !== '') {
                $item['sku'] = (string) $product['sku'] . '-' . $suffix;
            }
            $items[] = $item;
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
            $row['price_label'] = $this->formatPrice((int) $row['unit_price']);
            $row['line_total'] = (int) ($item['line_total'] ?? 0);
            $row['line_total_label'] = $this->formatPrice((int) $row['line_total']);
            $row['sku'] = (string) ($item['sku'] ?? $row['sku']);
            $row['option_id'] = (int) ($item['option_id'] ?? 0);
            $row['option_name'] = (string) ($item['option_name'] ?? '');
            $row['option_price_delta'] = (int) ($item['option_price_delta'] ?? 0);
            $row['available_qty'] = (int) ($item['available_qty'] ?? $row['stock_qty']);
            $row['line_key'] = (string) ($item['line_key'] ?? self::cartLineKey((int) $row['id'], $row['option_id']));
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
            'compat_formtec' => \App\Helpers\ShopCompatHelper::parse($product['compat_formtec'] ?? null),
            'compat_ilabel' => \App\Helpers\ShopCompatHelper::parse($product['compat_ilabel'] ?? null),
            'compat_anylabel' => \App\Helpers\ShopCompatHelper::parse($product['compat_anylabel'] ?? null),
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
        foreach ($this->repo->activeCategories() as $cat) {
            $categories[] = [
                'id' => (int) ($cat['id'] ?? 0),
                'name' => (string) ($cat['name'] ?? ''),
                'slug' => (string) ($cat['slug'] ?? ''),
                'parent_id' => (int) ($cat['parent_id'] ?? 0),
                'depth' => (int) ($cat['depth'] ?? 0),
                'parent_name' => (string) ($cat['parent_name'] ?? ''),
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
            $available = (int) ($item['available_qty'] ?? $item['stock_qty'] ?? 0);
            if (($item['status'] ?? '') === 'soldout' || $available < (int) ($item['qty'] ?? 1)) {
                $label = (string) ($item['name'] ?? '상품');
                $option = trim((string) ($item['option_name'] ?? ''));
                if ($option !== '') {
                    $label .= ' (' . $option . ')';
                }
                throw new RuntimeException($label . '의 재고가 부족합니다.');
            }
        }

        $name = trim((string) ($payload['customer_name'] ?? ($user['name'] ?? '')));
        $email = trim((string) ($payload['customer_email'] ?? ($user['email'] ?? '')));
        $phone = trim((string) ($payload['customer_phone'] ?? ($user['phone'] ?? '')));
        $shipName = trim((string) ($payload['shipping_name'] ?? ''));
        $shipPhone = trim((string) ($payload['shipping_phone'] ?? ''));
        $zip = trim((string) ($payload['shipping_zip'] ?? ''));
        $base = trim((string) ($payload['shipping_base'] ?? ''));
        $detail = trim((string) ($payload['shipping_detail'] ?? ''));
        $address = trim((string) ($payload['shipping_address'] ?? ''));
        if ($address === '' && ($zip !== '' || $base !== '')) {
            $address = trim(implode(' ', array_filter([$zip, $base, $detail], static fn ($v) => $v !== '')));
        }
        $memo = trim((string) ($payload['shipping_memo'] ?? ''));
        if ($name === '' || $email === '' || $phone === '') {
            throw new RuntimeException('구매자 이름, 이메일, 연락처를 모두 입력해 주세요.');
        }
        if ($shipName === '' || $shipPhone === '' || $address === '') {
            throw new RuntimeException('수취인 이름, 연락처, 배송지를 모두 입력해 주세요.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('올바른 이메일을 입력해 주세요.');
        }

        $userId = isset($user['id']) ? (int) $user['id'] : 0;
        $summary = $this->cartSummary();
        $created = $this->repo->createCustomerOrder([
            'user_id' => $userId > 0 ? $userId : null,
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'subtotal' => $summary['subtotal'],
            'shipping_fee' => $summary['shipping_fee'],
            'discount_amount' => 0,
            'total_amount' => $summary['total'],
            'shipping_name' => $shipName,
            'shipping_phone' => $shipPhone,
            'shipping_address' => $address,
            'shipping_memo' => $memo !== '' ? $memo : null,
        ], $items);
        if ($userId > 0) {
            (new UserAddressService())->saveFromCheckout($userId, array_merge($payload, [
                'shipping_name' => $shipName,
                'shipping_phone' => $shipPhone,
                'shipping_zip' => $zip,
                'shipping_base' => $base !== '' ? $base : $address,
                'shipping_detail' => $detail,
            ]));
        }
        $this->clearCart();

        if ($userId > 0) {
            (new NotificationService())->notifyOrderPlaced(
                $userId,
                (string) $created['order_no'],
                (int) $summary['total']
            );
        }

        $orderName = $this->buildOrderName($items);
        $result = [
            'order_id' => $created['id'],
            'order_no' => $created['order_no'],
            'total' => $summary['total'],
            'total_label' => $summary['total_label'],
            'count' => $summary['count'],
            'order_name' => $orderName,
            'requires_payment' => false,
            'payment' => null,
        ];

        $source = trim((string) ($payload['source'] ?? 'shop'));
        if ($source === '') {
            $source = 'shop';
        }
        $toss = new TossPaymentsService();
        if ($toss->isEnabled() && $summary['total'] > 0) {
            $result['requires_payment'] = true;
            $result['payment'] = $toss->buildWidgetPayload([
                'order_no' => $created['order_no'],
                'total_amount' => $summary['total'],
                'order_name' => $orderName,
                'user_id' => $userId > 0 ? $userId : null,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
            ], $source);
        }

        return $result;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function buildOrderName(array $items): string
    {
        $first = (string) ($items[0]['name'] ?? '라벨업 상품');
        $option = trim((string) ($items[0]['option_name'] ?? ''));
        if ($option !== '') {
            // 옵션만 다른 동일 상품이 섞이면 주문명으로 구분이 안 된다.
            $first .= ' (' . $option . ')';
        }
        $extra = max(0, count($items) - 1);
        if ($extra > 0) {
            return mb_substr($first, 0, 60) . ' 외 ' . $extra . '건';
        }
        return mb_substr($first, 0, 80);
    }

    /**
     * @return array{order_no:string,complete_url:string,payment:array<string,mixed>}
     */
    public function confirmTossPayment(string $paymentKey, string $orderId, int $amount, string $source = 'shop'): array
    {
        $order = $this->repo->findOrderByNo($orderId);
        if (!$order) {
            throw new RuntimeException('주문을 찾을 수 없습니다.');
        }
        if ((string) ($order['payment_status'] ?? '') === 'paid') {
            return [
                'order_no' => (string) $order['order_no'],
                'complete_url' => absolute_url('shop/complete') . '?order=' . rawurlencode((string) $order['order_no']),
                'payment' => ['already_paid' => true],
            ];
        }
        if ((int) ($order['total_amount'] ?? 0) !== $amount) {
            throw new RuntimeException('결제 금액이 주문 금액과 일치하지 않습니다.');
        }

        $toss = new TossPaymentsService();
        $confirmed = $toss->confirmPayment($paymentKey, $orderId, $amount);
        $this->repo->markOrderPaid((int) $order['id'], [
            'payment_key' => (string) ($confirmed['paymentKey'] ?? $paymentKey),
            'payment_method' => TossPaymentsService::methodLabel($confirmed),
            'raw' => $confirmed,
        ]);

        return [
            'order_no' => (string) $order['order_no'],
            'complete_url' => absolute_url('shop/complete') . '?order=' . rawurlencode((string) $order['order_no']),
            'payment' => [
                'method' => TossPaymentsService::methodLabel($confirmed),
                'approved_at' => $confirmed['approvedAt'] ?? null,
            ],
            'source' => $source,
        ];
    }

    /**
     * 미결제 주문 재결제용 위젯 페이로드
     * @return array<string, mixed>
     */
    public function paymentPayloadForOrder(string $orderNo, ?int $userId = null, string $source = 'shop'): array
    {
        $order = $this->repo->findOrderByNo($orderNo);
        if (!$order) {
            throw new RuntimeException('주문을 찾을 수 없습니다.');
        }
        if ($userId !== null && $userId > 0 && (int) ($order['user_id'] ?? 0) !== $userId) {
            throw new RuntimeException('주문에 대한 권한이 없습니다.');
        }
        if ((string) ($order['payment_status'] ?? '') === 'paid') {
            throw new RuntimeException('이미 결제가 완료된 주문입니다.');
        }
        $items = $this->repo->orderItems((int) $order['id']);
        $order['order_name'] = $this->buildOrderName(array_map(static function ($row) {
            return ['name' => $row['product_name'] ?? '상품'];
        }, $items));
        return (new TossPaymentsService())->buildWidgetPayload($order, $source);
    }

    public function markPaymentFailed(string $orderNo, ?string $reason = null): void
    {
        $order = $this->repo->findOrderByNo($orderNo);
        if (!$order) {
            return;
        }
        $this->repo->markOrderPaymentFailed((int) $order['id'], $reason);
    }

    /** @return array<string, mixed>|null */
    public function completeOrderForUser(int $userId, string $orderNo, string $email = ''): ?array
    {
        $orderNo = trim($orderNo);
        if ($userId < 1 || $orderNo === '') {
            return null;
        }
        $row = $this->repo->findUserOrderByNo($userId, $orderNo, $email);
        if (!$row) {
            return null;
        }
        return $this->presentCompleteOrder($row);
    }

    /** @return array<int, array<string, mixed>> */
    public function recentOrdersForUser(int $userId, string $exceptOrderNo = '', int $limit = 3): array
    {
        if ($userId < 1) {
            return [];
        }
        $rows = $this->repo->ordersByUser($userId, max(3, $limit + 2));
        $out = [];
        foreach ($rows as $row) {
            if ($exceptOrderNo !== '' && (string) ($row['order_no'] ?? '') === $exceptOrderNo) {
                continue;
            }
            $out[] = [
                'order_no' => (string) ($row['order_no'] ?? ''),
                'status' => (string) ($row['status'] ?? 'pending'),
                'status_label' => ShopAdminService::orderStatusLabel((string) ($row['status'] ?? 'pending')),
                'total_label' => $this->formatPrice((int) ($row['total_amount'] ?? 0)),
                'date_label' => $this->formatOrderDate((string) ($row['created_at'] ?? '')),
            ];
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }

    public function addToCart(int $productId, int $qty = 1, int $optionId = 0): void
    {
        $product = $this->repo->findActiveProduct($productId);
        if (!$product) {
            throw new RuntimeException('판매 중인 상품을 찾을 수 없습니다.');
        }
        if (($product['status'] ?? '') === 'soldout' || (int) ($product['stock_qty'] ?? 0) <= 0) {
            throw new RuntimeException('품절된 상품입니다.');
        }

        $option = $this->resolveRequiredOption($productId, $optionId);
        $optionId = (int) ($option['id'] ?? 0);
        $available = $option !== null ? (int) $option['stock_qty'] : (int) $product['stock_qty'];
        if ($available <= 0) {
            throw new RuntimeException($option !== null ? '선택한 옵션이 품절되었습니다.' : '품절된 상품입니다.');
        }

        $qty = max(1, min($qty, $available));
        $cart = $this->rawCart();
        $found = false;
        foreach ($cart as &$row) {
            if ((int) $row['product_id'] === $productId && (int) $row['option_id'] === $optionId) {
                $row['qty'] = min((int) $row['qty'] + $qty, $available);
                $found = true;
                break;
            }
        }
        unset($row);
        if (!$found) {
            $cart[] = ['product_id' => $productId, 'option_id' => $optionId, 'qty' => $qty];
        }
        $_SESSION[self::SESSION_CART] = $cart;
    }

    public function updateCartItem(int $productId, int $qty, int $optionId = 0): void
    {
        if ($qty <= 0) {
            $this->removeFromCart($productId, $optionId);
            return;
        }
        $product = $this->repo->findActiveProduct($productId);
        if (!$product) {
            throw new RuntimeException('상품을 찾을 수 없습니다.');
        }
        $option = $optionId > 0 ? $this->repo->findProductOption($productId, $optionId) : null;
        if ($optionId > 0 && $option === null) {
            throw new RuntimeException('선택할 수 없는 옵션입니다.');
        }
        $available = $option !== null ? (int) $option['stock_qty'] : (int) $product['stock_qty'];
        $qty = max(1, min($qty, $available));

        $cart = $this->rawCart();
        $updated = false;
        foreach ($cart as &$row) {
            if ((int) $row['product_id'] === $productId && (int) $row['option_id'] === $optionId) {
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

    public function removeFromCart(int $productId, int $optionId = 0): void
    {
        $cart = array_values(array_filter(
            $this->rawCart(),
            static fn (array $row): bool => !(
                (int) $row['product_id'] === $productId && (int) $row['option_id'] === $optionId
            )
        ));
        $_SESSION[self::SESSION_CART] = $cart;
    }

    /**
     * 판매 중인 옵션이 하나라도 있으면 선택이 필수다.
     *
     * @return array<string, mixed>|null 옵션이 없는 상품이면 null
     */
    private function resolveRequiredOption(int $productId, int $optionId): ?array
    {
        $options = $this->repo->productOptions($productId, true);
        if ($options === []) {
            return null;
        }
        if ($optionId <= 0) {
            throw new RuntimeException('옵션을 선택해 주세요.');
        }
        foreach ($options as $option) {
            if ((int) $option['id'] === $optionId) {
                return $option;
            }
        }
        throw new RuntimeException('선택할 수 없는 옵션입니다.');
    }

    /**
     * 상품 상세에서 쓰는 옵션 목록. 기준 단가에 증감액을 더해 실제 결제가를 함께 준다.
     *
     * @param array<string, mixed> $product
     * @return array<int, array<string, mixed>>
     */
    public function productOptions(array $product): array
    {
        $productId = (int) ($product['id'] ?? 0);
        if ($productId <= 0) {
            return [];
        }
        $base = $this->unitPrice($product);
        $out = [];
        foreach ($this->repo->productOptions($productId, true) as $option) {
            $delta = (int) $option['price_delta'];
            $unit = max(0, $base + $delta);
            $stock = (int) $option['stock_qty'];
            $out[] = [
                'id' => (int) $option['id'],
                'name' => (string) $option['name'],
                'price_delta' => $delta,
                'delta_label' => $delta === 0
                    ? ''
                    : ($delta > 0 ? '+' : '−') . $this->formatPrice(abs($delta)),
                'unit_price' => $unit,
                'price_label' => $this->formatPrice($unit),
                'stock_qty' => $stock,
                'soldout' => $stock <= 0,
            ];
        }
        return $out;
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

    /**
     * 상품 상세 갤러리용 이미지 목록. 대표 이미지가 항상 첫 장이다.
     * 등록된 이미지가 없으면 썸네일 한 장만 돌려준다.
     *
     * @param array<string, mixed> $product
     * @return array<int, array{url: string, is_primary: bool}>
     */
    public function productGallery(array $product): array
    {
        $productId = (int) ($product['id'] ?? 0);
        $out = [];
        $seen = [];
        if ($productId > 0) {
            foreach ($this->repo->productImages($productId) as $image) {
                $url = ShopProductImageService::resolveUrl((string) ($image['image_path'] ?? ''));
                if ($url === '' || isset($seen[$url])) {
                    continue;
                }
                $seen[$url] = true;
                $out[] = ['url' => $url, 'is_primary' => !empty($image['is_primary'])];
            }
        }

        usort($out, static fn (array $a, array $b): int => ($b['is_primary'] ? 1 : 0) <=> ($a['is_primary'] ? 1 : 0));

        if ($out === []) {
            $out[] = ['url' => $this->productThumb($product), 'is_primary' => true];
        }
        return $out;
    }

    /** Editor boot URL for this product's label paper/spec. */
    public function editorUrlForProduct(array $product): string
    {
        $params = [];
        $sku = trim((string) ($product['sku'] ?? ''));
        if ($sku !== '') {
            $params['paper'] = $sku;
        }
        $w = $product['width_mm'] ?? null;
        $h = $product['height_mm'] ?? null;
        if ($w !== null && $w !== '' && $h !== null && $h !== ''
            && (float) $w > 0 && (float) $h > 0) {
            $params['w'] = (string) (0 + (float) $w);
            $params['h'] = (string) (0 + (float) $h);
        }
        $labels = (int) ($product['labels_per_sheet'] ?? 0);
        if ($labels > 0) {
            $params['labels'] = (string) $labels;
        }
        $shape = trim((string) ($product['shape'] ?? ''));
        if ($shape !== '') {
            $params['shape'] = $shape;
        }
        $name = trim((string) ($product['name'] ?? ''));
        if ($name !== '') {
            $params['name'] = $name;
        }
        $qs = http_build_query($params);
        return url('editor/') . ($qs !== '' ? ('?' . $qs) : '');
    }

    public function hasEditableSpec(array $product): bool
    {
        $sku = trim((string) ($product['sku'] ?? ''));
        if ($sku !== '') {
            return true;
        }
        $w = $product['width_mm'] ?? null;
        $h = $product['height_mm'] ?? null;
        return $w !== null && $w !== '' && $h !== null && $h !== ''
            && (float) $w > 0 && (float) $h > 0;
    }

    /**
     * 상품 상세 레이아웃 순서:
     * 공통 헤더 → 카테고리 헤더(1차→2차) → 상세 내용 → 카테고리 푸터(2차→1차) → 공통 푸터
     *
     * @return array{
     *   header_html:string,footer_html:string,header_image:string,footer_image:string,
     *   header_image_url:string,footer_image_url:string,has_header:bool,has_footer:bool,
     *   source:string,category_id:int,category_ids:list<int>,
     *   header_blocks:list<array<string,mixed>>,footer_blocks:list<array<string,mixed>>,
     *   hashtags:list<string>
     * }
     */
    public function productPageLayout(?int $categoryId = null): array
    {
        $global = (new ShopProductPageSettingsRepository())->get();
        $cid = $categoryId !== null && $categoryId > 0 ? $categoryId : 0;
        $chain = $this->categoryLayoutChain($cid);

        $headerBlocks = [];
        $categoryFooters = [];
        $hashtags = [];
        $settingsRepo = new ShopProductPageCategorySettingsRepository();

        $globalMeta = ['id' => 0, 'name' => '', 'depth' => -1];
        $globalHeader = $this->pageLayoutBlockFromSettings($global, 'header', $globalMeta, 'global');
        if ($globalHeader !== null) {
            $headerBlocks[] = $globalHeader;
        }

        foreach ($chain as $cat) {
            $id = (int) ($cat['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            try {
                $custom = $settingsRepo->findByCategoryId($id);
            } catch (\Throwable) {
                $custom = null;
            }
            if (is_array($custom)) {
                $header = $this->pageLayoutBlockFromSettings($custom, 'header', $cat);
                if ($header !== null) {
                    $headerBlocks[] = $header;
                }
                $footer = $this->pageLayoutBlockFromSettings($custom, 'footer', $cat);
                if ($footer !== null) {
                    $categoryFooters[] = $footer;
                }
            }
            $resolvedTags = ShopCategoryHashtag::resolve(
                is_array($custom) ? ($custom['hashtags'] ?? null) : null,
                (string) ($cat['slug'] ?? ''),
                (string) ($cat['name'] ?? '')
            );
            if ($resolvedTags !== []) {
                $hashtags = $resolvedTags;
            }
        }

        $footerBlocks = array_reverse($categoryFooters);
        $globalFooter = $this->pageLayoutBlockFromSettings($global, 'footer', $globalMeta, 'global');
        if ($globalFooter !== null) {
            $footerBlocks[] = $globalFooter;
        }

        $hasCategory = false;
        foreach (array_merge($headerBlocks, $footerBlocks) as $block) {
            if (($block['source'] ?? '') === 'category') {
                $hasCategory = true;
                break;
            }
        }
        $source = 'none';
        if ($headerBlocks !== [] || $footerBlocks !== []) {
            $source = $hasCategory ? 'stack' : 'global';
        }

        $firstHeader = $headerBlocks[0] ?? null;
        $firstFooter = $footerBlocks[0] ?? null;

        return [
            'header_blocks' => $headerBlocks,
            'footer_blocks' => $footerBlocks,
            'header_html' => (string) ($firstHeader['html'] ?? ''),
            'footer_html' => (string) ($firstFooter['html'] ?? ''),
            'header_image' => (string) ($firstHeader['image'] ?? ''),
            'footer_image' => (string) ($firstFooter['image'] ?? ''),
            'header_image_url' => (string) ($firstHeader['image_url'] ?? ''),
            'footer_image_url' => (string) ($firstFooter['image_url'] ?? ''),
            'has_header' => $headerBlocks !== [],
            'has_footer' => $footerBlocks !== [],
            'source' => $source,
            'category_id' => $cid,
            'category_ids' => array_values(array_map(
                static fn (array $row): int => (int) ($row['id'] ?? 0),
                $chain
            )),
            'hashtags' => $hashtags,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categoryLayoutChain(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $seen = [];
        $stack = [];
        $id = $categoryId;
        for ($i = 0; $i < 3 && $id > 0; $i++) {
            if (isset($seen[$id])) {
                break;
            }
            $seen[$id] = true;
            $cat = $this->repo->findCategoryById($id);
            if (!$cat) {
                break;
            }
            array_unshift($stack, $cat);
            $id = (int) ($cat['parent_id'] ?? 0);
        }

        foreach ($stack as $index => $row) {
            $stack[$index]['depth'] = $index;
        }

        return $stack;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $category
     * @return array<string, mixed>|null
     */
    private function pageLayoutBlockFromSettings(
        array $settings,
        string $kind,
        array $category,
        string $source = 'category'
    ): ?array {
        $htmlKey = $kind === 'footer' ? 'footer_html' : 'header_html';
        $imageKey = $kind === 'footer' ? 'footer_image' : 'header_image';
        $html = trim((string) ($settings[$htmlKey] ?? ''));
        $image = trim((string) ($settings[$imageKey] ?? ''));
        if ($html === '' && $image === '') {
            return null;
        }

        return [
            'kind' => $kind,
            'source' => $source,
            'html' => $html,
            'image' => $image,
            'image_url' => ShopProductImageService::resolveUrl($image),
            'category_id' => (int) ($category['id'] ?? 0),
            'category_name' => (string) ($category['name'] ?? ''),
            'depth' => (int) ($category['depth'] ?? 0),
        ];
    }

    /** @param array<string, mixed> $order */
    /** @return array<string, mixed> */
    private function presentCompleteOrder(array $order): array
    {
        $status = (string) ($order['status'] ?? 'pending');
        $payStatus = (string) ($order['payment_status'] ?? 'pending');
        $items = [];
        foreach (($order['items'] ?? []) as $item) {
            $pid = (int) ($item['product_id'] ?? 0);
            $product = $pid > 0 ? $this->repo->findProductForPreview($pid) : null;
            $presented = $product ? $this->presentPublicProduct($product) : [
                'thumbnail' => asset('hero-tall-1.webp'),
                'spec' => '',
                'sku' => (string) ($item['sku'] ?? ''),
            ];
            $qty = (int) ($item['qty'] ?? 1);
            $unit = (int) ($item['unit_price'] ?? 0);
            $line = (int) ($item['line_total'] ?? ($unit * $qty));
            // SKU는 주문 시점 스냅샷을 우선한다. 옵션 접미사가 붙어 있을 수 있다.
            $sku = (string) ($item['sku'] ?? $presented['sku'] ?? '');
            $optionName = trim((string) ($item['option_name'] ?? ''));
            $meta = trim(implode(' / ', array_filter([
                $optionName,
                (string) ($presented['spec'] ?? ''),
                $sku,
            ])));
            $items[] = [
                'name' => (string) ($item['product_name'] ?? $presented['name'] ?? '상품'),
                'sku' => $sku,
                'option_name' => $optionName,
                'spec' => (string) ($presented['spec'] ?? ''),
                'meta' => $meta,
                'thumbnail' => (string) ($presented['thumbnail'] ?? asset('hero-tall-1.webp')),
                'qty' => $qty,
                'unit_price' => $unit,
                'unit_label' => $this->formatPrice($unit),
                'line_total' => $line,
                'line_label' => $this->formatPrice($line),
                'product_id' => $pid,
            ];
        }

        $createdAt = (string) ($order['created_at'] ?? '');
        $stepKeys = ['pending', 'paid', 'preparing', 'shipping', 'delivered'];
        $current = array_search($status, $stepKeys, true);
        if ($current === false) {
            $current = 0;
        }
        $steps = [
            ['key' => 'pending', 'label' => '주문완료', 'at' => $this->formatOrderClock($createdAt)],
            ['key' => 'paid', 'label' => '결제완료', 'at' => ''],
            ['key' => 'preparing', 'label' => '상품준비중', 'at' => ''],
            ['key' => 'shipping', 'label' => '배송중', 'at' => ''],
            ['key' => 'delivered', 'label' => '배송완료', 'at' => ''],
        ];
        foreach ($steps as $i => &$step) {
            $step['done'] = $i <= $current;
            $step['current'] = $i === $current;
        }
        unset($step);

        $subtotal = (int) ($order['subtotal'] ?? 0);
        $shipping = (int) ($order['shipping_fee'] ?? 0);
        $discount = (int) ($order['discount_amount'] ?? 0);
        $total = (int) ($order['total_amount'] ?? ($subtotal + $shipping - $discount));
        $payLabel = ShopAdminService::paymentStatusLabel($payStatus);
        $methodLabel = trim((string) ($order['payment_method'] ?? ''));
        if ($payStatus === 'pending') {
            $payLabel = '결제대기';
            $payHint = (new TossPaymentsService())->isEnabled()
                ? '토스페이먼츠 결제 대기 중'
                : '담당자 확인 후 안내';
        } elseif ($payStatus === 'paid') {
            $payLabel = $methodLabel !== '' ? $methodLabel : '결제완료';
            $payHint = '토스페이먼츠 결제 확인 완료';
        } elseif ($payStatus === 'failed') {
            $payLabel = '결제실패';
            $payHint = '다시 결제를 시도해 주세요';
        } else {
            $payHint = $payLabel;
        }

        return [
            'id' => (int) ($order['id'] ?? 0),
            'order_no' => (string) ($order['order_no'] ?? ''),
            'status' => $status,
            'status_label' => ShopAdminService::orderStatusLabel($status),
            'payment_status' => $payStatus,
            'payment_label' => $payLabel,
            'payment_method' => $methodLabel,
            'payment_hint' => $payHint,
            'created_at' => $createdAt,
            'date_label' => $this->formatOrderDate($createdAt),
            'clock_label' => $this->formatOrderClock($createdAt),
            'items' => $items,
            'item_qty' => (int) ($order['item_qty'] ?? array_sum(array_column($items, 'qty'))),
            'subtotal' => $subtotal,
            'subtotal_label' => $this->formatPrice($subtotal),
            'shipping_fee' => $shipping,
            'shipping_label' => $shipping === 0 ? '무료' : $this->formatPrice($shipping),
            'discount_amount' => $discount,
            'discount_label' => $this->formatPrice($discount),
            'total' => $total,
            'total_label' => $this->formatPrice($total),
            'shipping_name' => (string) ($order['shipping_name'] ?? ''),
            'shipping_phone' => (string) ($order['shipping_phone'] ?? ''),
            'shipping_address' => (string) ($order['shipping_address'] ?? ''),
            'shipping_memo' => (string) ($order['shipping_memo'] ?? ''),
            'carrier' => trim((string) ($order['carrier'] ?? '')) !== '' ? (string) $order['carrier'] : '라벨업배송 (기본배송)',
            'tracking_no' => (string) ($order['tracking_no'] ?? ''),
            'steps' => $steps,
        ];
    }

    public function formatOrderDate(string $datetime): string
    {
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }
        $week = ['일', '월', '화', '수', '목', '금', '토'][(int) date('w', $ts)];
        return date('Y년 n월 j일', $ts) . ' (' . $week . ') ' . date('H:i', $ts);
    }

    public function formatOrderClock(string $datetime): string
    {
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }
        return date('m.d H:i', $ts);
    }
}
