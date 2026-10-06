<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\PartnerAdminService;

final class PartnerAdminController extends BaseController
{
    private AuthService $auth;
    private PartnerAdminService $partners;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->partners = new PartnerAdminService();
    }

    public function index(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $search = trim((string) ($_GET['q'] ?? ''));

        view('admin/layout', [
            'contentTemplate' => 'admin/partners',
            'pageTitle' => '협력사 관리 — 라벨업 관리자',
            'activeMenu' => 'partners',
            'crumbTitle' => '협력사 관리',
            'user' => $this->auth->admin(),
            'search' => $search,
            'partners' => $this->partners->list($search),
        ]);
    }
}
