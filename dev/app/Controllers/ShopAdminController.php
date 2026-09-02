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
        $this->renderShopPage('admin/shop/products', 'shop-products', '상품 관리', [
            'items' => $this->shop->products(),
            'categories' => $this->shop->categories(),
            'specs' => $this->shop->specs(),
        ], true);
    }

    public function orders(): void
    {
        $status = trim((string) ($_GET['status'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $this->renderShopPage('admin/shop/orders', 'shop-orders', '주문 관리', [
            'list' => $this->shop->orders($status, $page),
            'status' => $status,
        ]);
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

