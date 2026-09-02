<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ClipartService;

final class ContentAdminController extends BaseController
{
    private AuthService $auth;
    private ClipartService $cliparts;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->cliparts = new ClipartService();
    }

    public function cliparts(): void
    {
        $this->requireAdmin();
        $this->cliparts->ensureDefaultCategories();

        $q = trim((string) ($_GET['q'] ?? ''));
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $tag = trim((string) ($_GET['tag'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $list = $this->cliparts->list([
            'q' => $q,
            'category_id' => $categoryId ?: null,
            'tag' => $tag,
            'page' => $page,
            'per_page' => 24,
        ]);

        view('admin/layout', [
            'contentTemplate' => 'admin/content/cliparts',
            'pageTitle' => '컨텐츠관리 › 클립아트관리 — 라벨업 관리자',
            'activeMenu' => 'content-cliparts',
            'menuGroup' => 'content',
            'crumbTitle' => '컨텐츠관리 › 클립아트관리',
            'user' => $this->auth->admin(),
            'list' => $list,
            'categories' => $this->cliparts->categories(),
            'filters' => [
                'q' => $q,
                'category_id' => $categoryId,
                'tag' => $tag,
            ],
        ]);
    }

    private function requireAdmin(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
