<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AccountRecoveryService;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\EventPopupService;
use App\Services\ShopService;

final class AuthController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function loginForm(): void
    {
        if ($this->auth->checkUser()) {
            redirect(safe_redirect_path((string) ($_GET['redirect'] ?? '/account')));
        }
        $redirectPath = safe_redirect_path((string) ($_GET['redirect'] ?? '/'));
        $this->render('auth/login', [
            'pageTitle' => '로그인 — 라벨업',
            'redirectUrl' => url(ltrim($redirectPath, '/')),
        ]);
    }

    public function registerForm(): void
    {
        if ($this->auth->checkUser()) {
            redirect('/account');
        }
        $this->render('auth/register', ['pageTitle' => '회원가입 — 라벨업']);
    }

    public function account(): void
    {
        (new AuthMiddleware($this->auth))->handle();
        $userId = $this->auth->id();
        $accountService = new AccountService();
        $dash = $accountService->dashboard($userId);

        view('account/layout', [
            'pageTitle' => '마이페이지 — 라벨업',
            'contentTemplate' => 'account/index',
            'authUser' => $dash['user'],
            'dash' => $dash,
            'accountService' => $accountService,
            'cartCount' => (new ShopService())->cartCount(),
            'activeNav' => 'account',
            'eventPopups' => (new EventPopupService())->activeForSite(),
        ]);
    }

    public function logout(): never
    {
        $this->auth->logoutUser();
        redirect('/login');
    }

    public function resetPasswordForm(): void
    {
        if ($this->auth->checkUser()) {
            redirect('/account');
        }
        $token = trim((string) ($_GET['token'] ?? ''));
        $valid = $token !== '' && (new AccountRecoveryService())->tokenValid($token);
        $this->render('auth/reset-password', [
            'pageTitle' => '비밀번호 재설정 — 라벨업',
            'token' => $token,
            'tokenValid' => $valid,
        ]);
    }
}
