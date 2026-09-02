<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\EventPopupService;
use RuntimeException;

final class EventPopupAdminApiController extends BaseController
{
    private AuthService $auth;
    private EventPopupService $popups;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->popups = new EventPopupService();
    }

    public function save(): never
    {
        $this->guard();
        try {
            $id = $this->popups->save(request_json());
            $this->jsonSuccess(['id' => $id], '이벤트 팝업이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function delete(): never
    {
        $this->guard();
        try {
            $this->popups->delete((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(null, '삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
