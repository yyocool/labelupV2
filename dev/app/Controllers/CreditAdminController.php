<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AiCreditService;
use App\Services\AuthService;
use App\Services\CreditAdminService;
use App\Services\QrCouponAdminService;
use Throwable;

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
        $matrix = ['rows' => [], 'category_count' => 0, 'group_count' => 0, 'product_count' => 0, 'generated_qr_count' => 0, 'printed_qr_count' => 0];
        $loadError = null;
        try {
            $matrix = (new QrCouponAdminService())->getGroupMatrix();
        } catch (Throwable $e) {
            $loadError = $e->getMessage();
        }

        $history = ['items' => [], 'total' => 0, 'page' => $page, 'pages' => 1, 'per_page' => 20];
        try {
            $history = (new QrCouponAdminService())->grantHistoryAll($search, $page, 20);
        } catch (Throwable) {
            // QR 테이블 미생성 시 빈 이력
        }

        $this->renderAdmin('admin/purchase-credits', 'ops-purchase-credits', '운영관리 › 구매크레딧', [
            'matrix' => $matrix,
            'loadError' => $loadError,
            'history' => $history,
            'search' => $search,
        ]);
    }

    public function creditUsage(): void
    {
        $this->renderAdmin('admin/credit-usage', 'ops-credit-usage', '운영관리 › 크레딧 사용 설정', [
            'settings' => (new AiCreditService())->adminSettings(),
            'saveUrl' => url('api/admin/ops/credit-usage/save'),
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
