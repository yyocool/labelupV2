<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ShopReviewService;

final class ReviewAdminController extends BaseController
{
    private AuthService $auth;
    private ShopReviewService $reviews;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->reviews = new ShopReviewService();
    }

    public function index(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $filters = [
            'status' => (string) ($_GET['status'] ?? ''),
            'type' => (string) ($_GET['type'] ?? ''),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'product_id' => (int) ($_GET['product_id'] ?? 0),
        ];
        view('admin/layout', [
            'contentTemplate' => 'admin/reviews',
            'pageTitle' => '운영관리 › 리뷰관리 — 라벨업 관리자',
            'activeMenu' => 'ops-reviews',
            'menuGroup' => 'ops',
            'crumbTitle' => '운영관리 › 리뷰관리',
            'user' => $this->auth->admin(),
            'filters' => $filters,
            'list' => $this->reviews->adminList($filters, (int) ($_GET['page'] ?? 1)),
            'approvalMode' => $this->reviews->approvalMode(),
            'products' => $this->reviews->productOptions(),
            'useSummernote' => true,
        ]);
    }
}
