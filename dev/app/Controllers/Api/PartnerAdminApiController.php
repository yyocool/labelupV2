<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\PartnerAdminService;
use RuntimeException;

final class PartnerAdminApiController extends BaseController
{
    private AuthService $auth;
    private PartnerAdminService $partners;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->partners = new PartnerAdminService();
    }

    public function save(): never
    {
        $this->guard();
        try {
            $result = $this->partners->save(request_json());
            $this->jsonSuccess($result, '협력사가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function resetPassword(): never
    {
        $this->guard();
        try {
            $id = (int) (request_json()['id'] ?? 0);
            $result = $this->partners->resetPassword($id);
            $this->jsonSuccess($result, '비밀번호를 다시 발급했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function delete(): never
    {
        $this->guard();
        try {
            $this->partners->delete((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(null, '협력사를 삭제했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
