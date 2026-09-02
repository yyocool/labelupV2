<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AiExamplePromptService;
use App\Services\AiUsageService;
use App\Services\AuthService;

final class AiAdminController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function examplePrompts(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        view('admin/layout', [
            'contentTemplate' => 'admin/ai-example-prompts',
            'pageTitle' => 'AI 관리 › 예시프롬프트 관리 — 라벨업 관리자',
            'activeMenu' => 'ai-example-prompts',
            'menuGroup' => 'ai',
            'crumbTitle' => 'AI 관리 › 예시프롬프트 관리',
            'user' => $this->auth->admin(),
            'items' => (new AiExamplePromptService())->allForAdmin(),
        ]);
    }

    public function usage(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $period = trim((string) ($_GET['period'] ?? '7d'));
        view('admin/layout', [
            'contentTemplate' => 'admin/ai-usage',
            'pageTitle' => 'AI 관리 › 사용량 통계 — 라벨업 관리자',
            'activeMenu' => 'ai-usage',
            'menuGroup' => 'ai',
            'crumbTitle' => 'AI 관리 › 사용량 통계',
            'user' => $this->auth->admin(),
            'stats' => (new AiUsageService())->dashboard($period),
        ]);
    }
}
