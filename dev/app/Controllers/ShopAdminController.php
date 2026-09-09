<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ShopAdminService;

final class ShopAdminController extends BaseController
{
    private AuthService $auth;
    private ShopAdminService $shop;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->shop = new ShopAdminService();
    }

    public function categories(): void
    {
        $this->renderShopPage('admin/shop/categories', 'shop-categories', '카테고리 관리', [
            'items' => $this->shop->categories(),
        ]);
    }

    public function specs(): void
    {
        $this->renderShopPage('admin/shop/specs', 'shop-specs', '라벨 규격', [
            'items' => $this->shop->specs(),
        ]);
    }

    public function products(): void
    {
        $this->requireAdmin();
        $allowedStatus = ['draft', 'active', 'soldout', 'hidden'];
        $status = trim((string) ($_GET['status'] ?? ''));
        if (!in_array($status, $allowedStatus, true)) {
            $status = '';
        }
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'spec_id' => (int) ($_GET['spec_id'] ?? 0),
            'status' => $status,
            'compat_missing' => trim((string) ($_GET['compat'] ?? '')) === 'missing',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $this->renderShopPage('admin/shop/products', 'shop-products', '상품 관리', [
            'list' => $this->shop->products($filters, $page),
            'filters' => $filters,
            'categories' => $this->shop->categories(),
            'specs' => $this->shop->specs(),
        ], true);
    }

    public function orders(): void
    {
        $this->requireAdmin();
        $filters = $this->orderFiltersFromRequest();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $this->renderShopPage('admin/shop/orders', 'shop-orders', '주문 관리', [
            'list' => $this->shop->orders($filters, $page),
            'counts' => $this->shop->orderStatusCounts($filters),
            'filters' => $filters,
            'carriers' => ShopAdminService::carriers(),
        ]);
    }

    public function ordersExport(): void
    {
        $this->requireAdmin();
        $filters = $this->orderFiltersFromRequest();
        $list = $this->shop->orders($filters, 1, 5000);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="orders-' . date('Ymd-His') . '.csv"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['주문번호', '주문상태', '결제상태', '주문자', '이메일', '연락처', '상품', '수량', '결제금액', '택배사', '송장번호', '배송지', '주문일']);
        foreach ($list['items'] as $row) {
            $names = [];
            $qty = 0;
            foreach ($row['items'] ?? [] as $item) {
                $names[] = (string) ($item['product_name'] ?? '') . ' x' . (int) ($item['qty'] ?? 0);
                $qty += (int) ($item['qty'] ?? 0);
            }
            fputcsv($out, [
                $row['order_no'] ?? '',
                ShopAdminService::orderStatusLabel((string) ($row['status'] ?? '')),
                ShopAdminService::paymentStatusLabel((string) ($row['payment_status'] ?? '')),
                $row['customer_name'] ?? '',
                $row['customer_email'] ?? '',
                $row['customer_phone'] ?? '',
                implode(' / ', $names),
                $qty,
                (int) ($row['total_amount'] ?? 0),
                $row['carrier'] ?? '',
                $row['tracking_no'] ?? '',
                $row['shipping_address'] ?? '',
                $row['created_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    /** @return array<string, mixed> */
    private function orderFiltersFromRequest(): array
    {
        $allowed = ['pending', 'paid', 'preparing', 'shipping', 'delivered', 'cancelled', 'refunded', 'cancel_group'];
        $status = trim((string) ($_GET['status'] ?? ''));
        if (!in_array($status, $allowed, true)) {
            $status = '';
        }
        $pay = trim((string) ($_GET['payment_status'] ?? ''));
        if (!in_array($pay, ['pending', 'paid', 'failed', 'refunded'], true)) {
            $pay = '';
        }
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => $status,
            'payment_status' => $pay,
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'missing_tracking' => trim((string) ($_GET['missing_tracking'] ?? '')) === '1',
        ];
    }

    public function shipping(): void
    {
        $this->renderShopPage('admin/shop/shipping', 'shop-shipping', '배송 관리', [
            'items' => $this->shop->shippingOrders(),
        ]);
    }

    public function coupons(): void
    {
        $this->renderShopPage('admin/shop/coupons', 'shop-coupons', '쿠폰·프로모션', [
            'items' => $this->shop->coupons(),
        ]);
    }

    public function banners(): void
    {
        $this->renderShopPage('admin/shop/banners', 'shop-banners', '배너·전시', [
            'items' => $this->shop->banners(),
        ]);
    }

    private function renderShopPage(string $template, string $activeMenu, string $title, array $data = [], bool $useSummernote = false): void
    {
        $this->requireAdmin();
        view('admin/layout', array_merge($data, [
            'contentTemplate' => $template,
            'pageTitle' => $title . ' — 라벨업 관리자',
            'activeMenu' => $activeMenu,
            'menuGroup' => 'shop',
            'user' => $this->auth->admin(),
            'crumbTitle' => $title,
            'useSummernote' => $useSummernote,
        ]));
    }

    private function requireAdmin(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}

