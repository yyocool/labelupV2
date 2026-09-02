<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\CreditAdminService;
use RuntimeException;

final class CreditAdminApiController extends BaseController
{
    private AuthService $auth;
    private CreditAdminService $credits;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->credits = new CreditAdminService();
    }

    public function saveRewardRule(): never
    {
        $this->guard();
        try {
            $id = $this->credits->saveRewardRule(request_json());
            $this->jsonSuccess(['id' => $id], '크레딧 보상 규칙이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function deleteRewardRule(): never
    {
        $this->guard();
        $this->credits->deleteRewardRule((int) (request_json()['id'] ?? 0));
        $this->jsonSuccess(null, '삭제되었습니다.');
    }

    public function savePurchaseProduct(): never
    {
        $this->guard();
        try {
            $id = $this->credits->savePurchaseProduct(request_json());
            $this->jsonSuccess(['id' => $id], '구매크레딧 제품이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function deletePurchaseProduct(): never
    {
        $this->guard();
        $this->credits->deletePurchaseProduct((int) (request_json()['id'] ?? 0));
        $this->jsonSuccess(null, '삭제되었습니다.');
    }

    public function generateCodes(): never
    {
        $this->guard();
        $data = request_json();
        try {
            $count = $this->credits->generateCodes(
                (int) ($data['product_id'] ?? 0),
                (int) ($data['count'] ?? 0),
                (string) ($data['prefix'] ?? 'LU')
            );
            $this->jsonSuccess(['created' => $count], "{$count}개 코드가 생성되었습니다.");
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function adjustCredit(): never
    {
        $this->guard();
        $data = request_json();
        try {
            $balance = $this->credits->adjustUserCredit(
                (int) ($data['user_id'] ?? 0),
                (int) ($data['amount'] ?? 0),
                (string) ($data['description'] ?? '관리자 조정'),
                (int) $this->auth->adminId()
            );
            $this->jsonSuccess(['balance' => $balance], '크레딧이 조정되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function grantCredit(): never
    {
        $this->guard();
        $data = request_json();
        $reason = trim((string) ($data['reason'] ?? $data['description'] ?? ''));
        try {
            $balance = $this->credits->grantUserCredit(
                (int) ($data['user_id'] ?? 0),
                (int) ($data['amount'] ?? 0),
                $reason,
                (int) $this->auth->adminId()
            );
            $this->jsonSuccess(['balance' => $balance], '크레딧이 지급되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function grantHistory(): never
    {
        $this->guard();
        $userId = (int) ($_GET['user_id'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $this->jsonSuccess($this->credits->grantHistory($userId > 0 ? $userId : null, $page, 20));
    }

    public function saveCsLog(): never
    {
        $this->guard();
        try {
            $id = $this->credits->saveCsLog(request_json(), (int) $this->auth->adminId());
            $this->jsonSuccess(['id' => $id], 'CS 이력이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
