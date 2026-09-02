<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\SeoService;

final class SeoAdminController extends BaseController
{
    private AuthService $auth;
    private SeoService $seo;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->seo = new SeoService();
    }

    public function seo(): void
    {
        $this->guard();
        $payload = $this->seo->adminSeoPayload();
        $this->renderAdmin('admin/seo', [
            'pageTitle' => '설정 › SEO 설정 — 라벨업 관리자',
            'activeMenu' => 'settings-seo',
            'menuGroup' => 'settings',
            'crumbTitle' => '설정 › SEO 설정',
            'user' => $this->auth->admin(),
            'seo' => $payload['seo'],
            'pages' => $payload['pages'],
        ]);
    }

    public function marketing(): void
    {
        $this->guard();
        $payload = $this->seo->adminMarketingPayload();
        $this->renderAdmin('admin/marketing', [
            'pageTitle' => '설정 › 광고 스크립트 — 라벨업 관리자',
            'activeMenu' => 'settings-tracking',
            'menuGroup' => 'settings',
            'crumbTitle' => '설정 › 광고 스크립트',
            'user' => $this->auth->admin(),
            'marketing' => $payload['marketing'],
            'files' => $payload['files'],
        ]);
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }

    private function renderAdmin(string $template, array $data = []): void
    {
        $data['contentTemplate'] = $template;
        view('admin/layout', $data);
    }
}
