<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\OpenAiBillingService;
use RuntimeException;

final class OpenAiBillingAdminApiController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function summary(): never
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $refresh = !empty($_GET['refresh']);
        $this->jsonSuccess(['billing' => OpenAiBillingService::summary($refresh)]);
    }

    public function saveConfig(): never
    {
        (new AuthMiddleware($this->auth))->handle(true);
        try {
            $payload = OpenAiBillingService::saveConfig(request_json());
            $this->jsonSuccess(['openaiBilling' => $payload], 'OpenAI 잔액 설정을 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function addTopup(): never
    {
        (new AuthMiddleware($this->auth))->handle(true);
        try {
            $payload = OpenAiBillingService::addTopup(request_json());
            $this->jsonSuccess(['openaiBilling' => $payload], '충전 기록을 추가했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function deleteTopup(): never
    {
        (new AuthMiddleware($this->auth))->handle(true);
        try {
            $payload = OpenAiBillingService::deleteTopup((string) (request_json()['id'] ?? ''));
            $this->jsonSuccess(['openaiBilling' => $payload], '충전 기록을 삭제했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
