<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\QrCouponAdminService;
use RuntimeException;
use Throwable;

final class QrCouponAdminApiController extends BaseController
{
    private AuthService $auth;
    private QrCouponAdminService $service;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->service = new QrCouponAdminService();
    }

    public function saveCredit(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $groupNo = (int) ($payload['group_no'] ?? 0);
            $credit = $payload['credit_amount'] ?? null;
            $saved = $this->service->saveCreditAmount($groupNo, $credit);
            $this->jsonSuccess($saved, '지급 크레딧이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function generate(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $groupNo = (int) ($payload['group_no'] ?? 0);
            $quantity = (int) ($payload['quantity'] ?? 0);
            $adminId = (int) ($this->auth->adminId() ?? 0) ?: null;
            $result = $this->service->generateCodes($groupNo, $quantity, $adminId);
            $this->jsonSuccess($result, $quantity . '개의 QR 코드가 생성되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        } catch (Throwable $e) {
            $this->jsonError(APP_DEBUG ? $e->getMessage() : 'QR 코드 생성에 실패했습니다.');
        }
    }

    public function generationHistory(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $groupNo = (int) ($payload['group_no'] ?? $_GET['group_no'] ?? 0);
            $items = $this->service->generationHistory($groupNo);
            $this->jsonSuccess(['items' => $items, 'group_no' => $groupNo]);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function usageHistory(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $groupNo = (int) ($payload['group_no'] ?? $_GET['group_no'] ?? 0);
            $items = $this->service->usageHistory($groupNo);
            $this->jsonSuccess(['items' => $items, 'group_no' => $groupNo]);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
