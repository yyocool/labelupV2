<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\InquiryService;

final class InquiryAdminController extends BaseController
{
    public function index(): void
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);
        $status = trim((string) ($_GET['status'] ?? 'all'));
        view('admin/layout', [
            'contentTemplate' => 'admin/inquiries',
            'pageTitle' => '운영관리 › 1:1 문의 — 라벨업 관리자',
            'activeMenu' => 'ops-inquiries',
            'menuGroup' => 'ops',
            'crumbTitle' => '운영관리 › 1:1 문의',
            'user' => $auth->admin(),
            'items' => (new InquiryService())->allForAdmin($status),
            'filterStatus' => $status,
        ]);
    }
}
