<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\CreditAdminService;

final class CreditAdminController extends BaseController
{
    private AuthService $auth;
    private CreditAdminService $credits;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->credits = new CreditAdminService();
    }

    public function rewardRules(): void
    {
        $this->renderAdmin('admin/credit-rewards', 'ops-credit-rewards', '운영관리 › 크레딧보상 관리', [
            'items' => $this->credits->rewardRules(),
        ]);
    }

    public function purchaseCredits(): void
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $this->renderAdmin('admin/purchase-credits', 'ops-purchase-credits', '운영관리 › 구매크레딧', [
            'products' => $this->credits->purchaseProducts(),
            'codes' => $this->credits->purchaseCodes(),
            'history' => $this->credits->redemptionHistory($search, $page),
            'search' => $search,
        ]);
    }

    private function renderAdmin(string $template, string $menu, string $crumb, array $data = []): void
    {
        $this->requireAdmin();
        view('admin/layout', array_merge($data, [
            'contentTemplate' => $template,
            'pageTitle' => $crumb . ' — 라벨업 관리자',
            'activeMenu' => $menu,
            'menuGroup' => 'ops',
            'crumbTitle' => $crumb,
            'user' => $this->auth->admin(),
        ]));
    }

    private function requireAdmin(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
