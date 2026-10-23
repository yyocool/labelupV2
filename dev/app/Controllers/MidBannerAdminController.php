<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\HomeMidBannerService;

final class MidBannerAdminController extends BaseController
{
    private AuthService $auth;
    private HomeMidBannerService $banners;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->banners = new HomeMidBannerService();
    }

    public function index(): void
    {
        $this->requireAdmin();
        view('admin/layout', [
            'contentTemplate' => 'admin/mid-banners',
            'pageTitle' => '운영관리 › 중간배너 관리 — 라벨업 관리자',
            'activeMenu' => 'ops-mid-banners',
            'menuGroup' => 'ops',
            'crumbTitle' => '운영관리 › 중간배너 관리',
            'user' => $this->auth->admin(),
            'items' => $this->banners->allForAdmin(),
        ]);
    }

    private function requireAdmin(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
