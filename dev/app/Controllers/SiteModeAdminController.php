<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\SiteModeService;

final class SiteModeAdminController extends BaseController
{
    private AuthService $auth;
    private SiteModeService $siteMode;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->siteMode = new SiteModeService();
    }

    public function index(): void
    {
        $this->guard();
        view('admin/layout', [
            'pageTitle' => '설정 › 환경설정 — 라벨업 관리자',
            'activeMenu' => 'settings-environment',
            'menuGroup' => 'settings',
            'crumbTitle' => '설정 › 환경설정',
            'user' => $this->auth->admin(),
            'contentTemplate' => 'admin/environment',
            'siteMode' => $this->siteMode->adminPayload(),
        ]);
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
