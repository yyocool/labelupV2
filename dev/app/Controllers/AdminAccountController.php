<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AdminAccessService;
use App\Services\AdminAccountService;
use App\Services\AuthService;

final class AdminAccountController extends BaseController
{
    public function index(): void
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);
        $admin = $auth->admin() ?? [];
        $access = new AdminAccessService();
        view('admin/layout', [
            'contentTemplate' => 'admin/admins',
            'pageTitle' => '설정 › 관리자 — 라벨업 관리자',
            'activeMenu' => 'settings-admins',
            'menuGroup' => 'settings',
            'crumbTitle' => '설정 › 관리자',
            'user' => $admin,
            'admins' => (new AdminAccountService())->listAdmins(),
            'actorIsSuper' => $access->isSuper((int) ($admin['id'] ?? 0)),
            'actorId' => (int) ($admin['id'] ?? 0),
            'permissionMenus' => admin_menu_catalog(),
        ]);
    }
}
