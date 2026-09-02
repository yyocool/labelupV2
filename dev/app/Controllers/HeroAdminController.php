<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\HomeHeroService;

final class HeroAdminController extends BaseController
{
    private AuthService $auth;
    private HomeHeroService $hero;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->hero = new HomeHeroService();
    }

    public function index(): void
    {
        $this->requireAdmin();
        view('admin/layout', [
            'contentTemplate' => 'admin/hero-slides',
            'pageTitle' => '운영관리 › 히어로 이미지 관리 — 라벨업 관리자',
            'activeMenu' => 'ops-hero-slides',
            'menuGroup' => 'ops',
            'crumbTitle' => '운영관리 › 히어로 이미지 관리',
            'user' => $this->auth->admin(),
            'items' => $this->hero->allForAdmin(),
        ]);
    }

    private function requireAdmin(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
