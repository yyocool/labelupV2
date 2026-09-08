<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ClipartService;
use App\Services\UserAiClipartService;

final class EditorClipartApiController extends BaseController
{
    private ClipartService $cliparts;
    private UserAiClipartService $userCliparts;
    private AuthService $auth;

    public function __construct()
    {
        $this->cliparts = new ClipartService();
        $this->userCliparts = new UserAiClipartService();
        $this->auth = new AuthService();
    }

    public function index(): never
    {
        $filters = [
            'page' => (int) ($_GET['page'] ?? 1),
            'per_page' => (int) ($_GET['per_page'] ?? 48),
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
        ];
        $this->jsonSuccess($this->cliparts->publicCatalog($filters));
    }

    /** 로그인한 사용자의 등록 클립아트 (라비/AI 등). */
    public function mine(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $this->jsonSuccess($this->userCliparts->editorCatalogForUser((int) $this->auth->id()));
    }
}
