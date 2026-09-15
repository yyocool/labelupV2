<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\QrCouponAdminService;

final class QrCouponAdminController extends BaseController
{
    public function index(): void
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);

        $matrix = [
            'rows' => [],
            'category_count' => 0,
            'group_count' => 0,
            'product_count' => 0,
        ];
        $loadError = null;
        try {
            $matrix = (new QrCouponAdminService())->getGroupMatrix();
        } catch (\Throwable $e) {
            $loadError = $e->getMessage();
        }

        view('admin/layout', [
            'contentTemplate' => 'admin/qr-coupons',
            'pageTitle' => 'QR쿠폰관리 — 라벨업 관리자',
            'activeMenu' => 'qr-coupons',
            'crumbTitle' => 'QR쿠폰관리',
            'user' => $auth->admin(),
            'matrix' => $matrix,
            'loadError' => $loadError,
        ]);
    }
}
