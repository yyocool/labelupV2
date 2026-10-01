<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\SiteModeService;
use RuntimeException;

final class SiteModeAdminApiController extends BaseController
{
    private AuthService $auth;
    private SiteModeService $siteMode;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->siteMode = new SiteModeService();
    }

    public function save(): never
    {
        (new AuthMiddleware($this->auth))->handle(true);
        try {
            $payload = $this->siteMode->save(request_json());
            $this->jsonSuccess(['siteMode' => $payload], '환경설정을 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
