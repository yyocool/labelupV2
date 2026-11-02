<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\OpsSmsNotifier;

final class NotifySmsAdminController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function index(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        view('admin/layout', [
            'pageTitle' => '설정 › 알림 설정 › 문자알림 설정 — 라벨업 관리자',
            'activeMenu' => 'settings-notify-sms',
            'menuGroup' => 'settings',
            'crumbTitle' => '설정 › 알림 설정 › 문자알림 설정',
            'user' => $this->auth->admin(),
            'contentTemplate' => 'admin/notify-sms',
            'notify' => OpsSmsNotifier::adminPayload(),
        ]);
    }
}
