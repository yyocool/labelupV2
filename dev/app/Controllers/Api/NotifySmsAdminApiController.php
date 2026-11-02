<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\OpsSmsNotifier;
use RuntimeException;

final class NotifySmsAdminApiController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function save(): never
    {
        (new AuthMiddleware($this->auth))->handle(true);
        try {
            $payload = OpsSmsNotifier::save(request_json());
            $this->jsonSuccess(['notify' => $payload], '문자알림 설정을 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function test(): never
    {
        (new AuthMiddleware($this->auth))->handle(true);
        try {
            OpsSmsNotifier::sendTest((string) (request_json()['phone'] ?? ''));
            $this->jsonSuccess(['notify' => OpsSmsNotifier::adminPayload()], '테스트 문자를 보냈습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
