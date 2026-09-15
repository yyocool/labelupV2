<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;
use App\Repositories\ShopProductPageSettingsRepository;
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

    /** @return array{header_html:string,footer_html:string,header_image:string,footer_image:string,header_image_url:string,footer_image_url:string,has_header:bool,has_footer:bool} */
    public function productPageLayout(): array
    {
        $row = (new ShopProductPageSettingsRepository())->get();
        $headerHtml = trim($row['header_html']);
        $footerHtml = trim($row['footer_html']);
        $headerImage = trim($row['header_image']);
        $footerImage = trim($row['footer_image']);
        return [
            'header_html' => $headerHtml,
            'footer_html' => $footerHtml,
            'header_image' => $headerImage,
            'footer_image' => $footerImage,
            'header_image_url' => ShopProductImageService::resolveUrl($headerImage),
            'footer_image_url' => ShopProductImageService::resolveUrl($footerImage),
            'has_header' => $headerHtml !== '' || $headerImage !== '',
            'has_footer' => $footerHtml !== '' || $footerImage !== '',
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
            $meta = trim(implode(' / ', array_filter([
                (string) ($presented['spec'] ?? ''),
                (string) ($presented['sku'] ?? $item['sku'] ?? ''),
            ])));
            $items[] = [
                'name' => (string) ($item['product_name'] ?? $presented['name'] ?? '상품'),
                'sku' => (string) ($presented['sku'] ?? $item['sku'] ?? ''),
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
