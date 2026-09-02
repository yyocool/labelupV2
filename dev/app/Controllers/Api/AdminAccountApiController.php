<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AdminAccountService;
use App\Services\AuthService;
use RuntimeException;

final class AdminAccountApiController extends BaseController
{
    private AuthService $auth;
    private AdminAccountService $accounts;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->accounts = new AdminAccountService();
    }

    public function lookup(): never
    {
        $actorId = $this->requireAdminId();
        try {
            $this->jsonSuccess($this->accounts->lookupByEmail((string) ($_GET['email'] ?? '')));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function save(): never
    {
        $actorId = $this->requireAdminId();
        try {
            $admin = $this->accounts->save(request_json(), $actorId);
            $this->jsonSuccess(['admin' => $admin], '관리자를 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function revoke(): never
    {
        $actorId = $this->requireAdminId();
        $payload = request_json();
        try {
            $this->accounts->revoke((int) ($payload['id'] ?? 0), $actorId);
            $this->jsonSuccess(null, '관리자 권한을 해제했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
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
