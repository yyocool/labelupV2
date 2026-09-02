<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ApiResponse;
use App\Repositories\UserRepository;
use App\Services\AdminAccessService;
use App\Services\AuthService;

final class AuthMiddleware
{
    public function __construct(private AuthService $auth)
    {
    }

    public function handle(bool $adminOnly = false): void
    {
        if ($adminOnly) {
            if (!$this->auth->checkAdmin()) {
                if ($this->isApi()) {
                    ApiResponse::error('관리자 로그인이 필요합니다.', null, 401);
                }
                redirect('/admin/login');
            }
            $this->assertStillAdmin();
            $this->assertMenuAccess();
            return;
        }

        if (!$this->auth->checkUser()) {
            if ($this->isApi()) {
                ApiResponse::error('로그인이 필요합니다.', null, 401);
            }
            redirect('/login');
        }
    }

    private function assertStillAdmin(): void
    {
        $id = (int) ($this->auth->adminId() ?? 0);
        $fresh = $id > 0 ? (new UserRepository())->findById($id) : null;
        if ($fresh && ($fresh['role'] ?? '') === 'admin' && ($fresh['status'] ?? '') === 'active') {
            return;
        }
        $this->auth->logoutAdmin();
        if ($this->isApi()) {
            ApiResponse::error('관리자 권한이 없습니다.', null, 401);
        }
        redirect('/admin/login');
    }

    private function assertMenuAccess(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $menuKey = AdminAccessService::menuKeyForPath($path);
        if ($menuKey === null || $menuKey === 'dashboard') {
            return;
        }
        $adminId = (int) ($this->auth->adminId() ?? 0);
        if ((new AdminAccessService())->can($adminId, $menuKey)) {
            return;
        }
        if ($this->isApi()) {
            ApiResponse::error('이 메뉴에 대한 권한이 없습니다.', null, 403);
        }
        http_response_code(403);
        view('admin/layout', [
            'contentTemplate' => 'admin/denied',
            'pageTitle' => '접근 권한 없음 — 라벨업',
            'activeMenu' => 'dashboard',
            'user' => $this->auth->admin(),
        ]);
        exit;
    }

    private function isApi(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($uri, '/api/');
    }
}
