<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\HomeHeroService;
use RuntimeException;

final class HeroAdminApiController extends BaseController
{
    private AuthService $auth;
    private HomeHeroService $hero;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->hero = new HomeHeroService();
    }

    public function save(): never
    {
        $this->guard();
        try {
            $id = $this->hero->save(request_json());
            $this->jsonSuccess(['id' => $id], '히어로 슬라이드가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function delete(): never
    {
        $this->guard();
        $this->hero->delete((int) (request_json()['id'] ?? 0));
        $this->jsonSuccess(null, '삭제되었습니다.');
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
