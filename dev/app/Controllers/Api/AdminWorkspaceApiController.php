<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AdminAlertService;
use App\Services\AdminFavoriteService;
use App\Services\AuthService;
use RuntimeException;

final class AdminWorkspaceApiController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function favorites(): never
    {
        $adminId = $this->requireAdminId();
        $this->jsonSuccess([
            'slots' => (new AdminFavoriteService())->slotsFor($adminId),
            'menus' => (new \App\Services\AdminAccessService())->allowedCatalog($adminId),
        ]);
    }

    public function saveFavorites(): never
    {
        $adminId = $this->requireAdminId();
        $payload = request_json();
        $slots = $payload['slots'] ?? [];
        if (!is_array($slots)) {
            $this->jsonError('슬롯 데이터가 올바르지 않습니다.', null, 422);
        }
        try {
            (new AdminFavoriteService())->save($adminId, $slots);
            $this->jsonSuccess([
                'slots' => (new AdminFavoriteService())->slotsFor($adminId),
            ], '즐겨찾기를 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function alerts(): never
    {
        $adminId = $this->requireAdminId();
        $this->jsonSuccess((new AdminAlertService())->snapshot($adminId));
    }

    public function ackAlerts(): never
    {
        $adminId = $this->requireAdminId();
        $payload = request_json();
        (new AdminAlertService())->ack(
            $adminId,
            (int) ($payload['last_seen_order_id'] ?? 0),
            (int) ($payload['last_seen_inquiry_id'] ?? 0)
        );
        $this->jsonSuccess((new AdminAlertService())->snapshot($adminId), '알림을 확인했습니다.');
    }

    private function requireAdminId(): int
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $id = (int) ($this->auth->adminId() ?? 0);
        if ($id <= 0) {
            $this->jsonError('관리자 로그인이 필요합니다.', null, 401);
        }
        return $id;
    }
}
