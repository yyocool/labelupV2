<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\MemberGradeService;
use RuntimeException;

final class MemberGradeAdminApiController extends BaseController
{
    private AuthService $auth;
    private MemberGradeService $grades;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->grades = new MemberGradeService();
    }

    public function save(): never
    {
        $this->guard();
        try {
            $id = $this->grades->save(request_json());
            $this->jsonSuccess(['id' => $id, 'grades' => $this->grades->listAll()], '회원등급이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function delete(): never
    {
        $this->guard();
        try {
            $this->grades->delete((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(['grades' => $this->grades->listAll()], '회원등급이 삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
