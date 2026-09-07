<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\SiteIntroService;

final class SiteIntroAdminController extends BaseController
{
    private AuthService $auth;
    private SiteIntroService $intro;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->intro = new SiteIntroService();
    }

    public function index(): void
    {
        $this->guard();
        view('admin/layout', [
            'pageTitle' => '설정 › 인트로설정 — 라벨업 관리자',
            'activeMenu' => 'settings-intro',
            'menuGroup' => 'settings',
            'crumbTitle' => '설정 › 인트로설정',
            'user' => $this->auth->admin(),
            'contentTemplate' => 'admin/intro',
            'intro' => $this->intro->get(),
        ]);
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
