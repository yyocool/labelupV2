<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\ApiResponse;
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
            return;
        }

        if (!$this->auth->checkUser()) {
            if ($this->isApi()) {
                ApiResponse::error('로그인이 필요합니다.', null, 401);
            }
            redirect('/login');
        }
    }

    private function isApi(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($uri, '/api/');
    }
}
