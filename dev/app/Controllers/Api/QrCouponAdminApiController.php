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
            $this->jsonSuccess($saved, '지급 잉크가 저장되었습니다.');
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

    public function batchCodes(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $batchId = (int) ($payload['batch_id'] ?? $_GET['batch_id'] ?? 0);
            $this->jsonSuccess($this->service->batchCodes($batchId));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function groupCodes(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $groupNo = (int) ($payload['group_no'] ?? $_GET['group_no'] ?? 0);
            $this->jsonSuccess($this->service->groupCodes($groupNo));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function markPrinted(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $ids = $payload['ids'] ?? [];
            if (!is_array($ids)) {
                $ids = [];
            }
            $this->jsonSuccess($this->service->markPrinted($ids), '인쇄 완료로 표시했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function deleteCodes(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $ids = $payload['ids'] ?? [];
            if (!is_array($ids)) {
                $ids = [];
            }
            $result = $this->service->deleteCodes($ids);
            $deleted = (int) ($result['deleted'] ?? 0);
            $skipped = (int) ($result['skipped'] ?? 0);
            $msg = $deleted . '개 미사용 QR을 삭제했습니다.';
            if ($skipped > 0) {
                $msg .= ' (사용·중지 ' . $skipped . '개는 제외)';
            }
            $this->jsonSuccess($result, $msg);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        } catch (Throwable $e) {
            $this->jsonError(APP_DEBUG ? $e->getMessage() : 'QR 삭제에 실패했습니다.');
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

    public function printTemplate(): never
    {
        $this->guard();
        try {
            $key = $this->service->resolveTemplateKeyFromRequest(
                isset($_GET['key']) ? (string) $_GET['key'] : null,
                $_GET['group_no'] ?? null,
                $_GET['category_no'] ?? null
            );
            $this->jsonSuccess($this->service->getPrintTemplate($key));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        } catch (Throwable $e) {
            $this->jsonError(APP_DEBUG ? $e->getMessage() : '출력템플릿을 불러오지 못했습니다.');
        }
    }

    public function savePrintTemplate(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $adminId = (int) ($this->auth->adminId() ?? 0) ?: null;
            $saved = $this->service->savePrintTemplate($payload, $adminId);
            $this->jsonSuccess($saved, '출력템플릿이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        } catch (Throwable $e) {
            $this->jsonError(APP_DEBUG ? $e->getMessage() : '출력템플릿 저장에 실패했습니다.');
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
