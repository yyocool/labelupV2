<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\InquiryService;
use RuntimeException;

final class InquiryApiController extends BaseController
{
    public function submit(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle();
        $user = $auth->user() ?? [];
        $payload = request_json();
        try {
            $id = (new InquiryService())->submit($payload + [
                'name' => $payload['name'] ?? ($user['name'] ?? ''),
                'email' => $payload['email'] ?? ($user['email'] ?? ''),
            ], $auth->id());
            $this->jsonSuccess(['id' => $id], '문의가 접수되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function update(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);
        $payload = request_json();
        try {
            (new InquiryService())->updateStatus(
                (int) ($payload['id'] ?? 0),
                (string) ($payload['status'] ?? ''),
                isset($payload['admin_memo']) ? (string) $payload['admin_memo'] : null
            );
            $this->jsonSuccess(null, '문의 상태가 변경되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
