<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AccountRecoveryService;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\EventPopupService;
use App\Services\OAuthService;
use App\Services\ShopService;
use App\Services\SiteModeService;
use RuntimeException;

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
        $oauth = new OAuthService();
        $flash = (string) ($_SESSION['auth_flash'] ?? '');
        unset($_SESSION['auth_flash']);
        $siteMode = new SiteModeService();
        $this->render('auth/login', [
            'pageTitle' => '로그인 — 라벨업',
            'redirectUrl' => url(ltrim($redirectPath, '/')),
            'oauthEnabled' => $oauth->configuredMap(),
            'snsHold' => $siteMode->isTempOpen(),
            'authFlash' => $flash,
        ]);
    }

    public function registerForm(): void
    {
        if ($this->auth->checkUser()) {
            redirect('/account');
        }
        $oauth = new OAuthService();
        $flash = (string) ($_SESSION['auth_flash'] ?? '');
        unset($_SESSION['auth_flash']);
        $siteMode = new SiteModeService();
        $this->render('auth/register', [
            'pageTitle' => '회원가입 — 라벨업',
            'oauthEnabled' => $oauth->configuredMap(),
            'snsHold' => $siteMode->isTempOpen(),
            'authFlash' => $flash,
        ]);
    }

    public function oauthRedirect(string $provider): never
    {
        $provider = strtolower(trim($provider));
        $oauth = new OAuthService();
        if ((new SiteModeService())->isTempOpen()) {
            $_SESSION['auth_flash'] = SiteModeService::SNS_HOLD_MESSAGE;
            redirect($this->snsHoldBackPath());
        }
        if (!$oauth->isSupported($provider)) {
            $_SESSION['auth_flash'] = '지원하지 않는 소셜 로그인입니다.';
            redirect('/login');
        }
        if (!$oauth->isConfigured($provider)) {
            $_SESSION['auth_flash'] = $oauth->providerLabel($provider) . ' 로그인 키가 아직 설정되지 않았습니다.';
            redirect('/login');
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_provider'] = $provider;
        $redirect = safe_redirect_path((string) ($_GET['redirect'] ?? '/'));
        $_SESSION['oauth_redirect'] = $redirect;

        try {
            redirect($oauth->authorizationUrl($provider, $state));
        } catch (RuntimeException $e) {
            $_SESSION['auth_flash'] = $e->getMessage();
            redirect('/login');
        }
    }

    public function oauthCallback(string $provider): never
    {
        $provider = strtolower(trim($provider));
        $oauth = new OAuthService();
        if ((new SiteModeService())->isTempOpen()) {
            $_SESSION['auth_flash'] = SiteModeService::SNS_HOLD_MESSAGE;
            redirect($this->snsHoldBackPath());
        }
        $error = trim((string) ($_GET['error'] ?? $_GET['error_description'] ?? ''));
        if ($error !== '') {
            $_SESSION['auth_flash'] = '소셜 로그인이 취소되었거나 실패했습니다.';
            redirect('/login');
        }

        $state = (string) ($_GET['state'] ?? '');
        $code = trim((string) ($_GET['code'] ?? ''));
        $expected = (string) ($_SESSION['oauth_state'] ?? '');
        $sessionProvider = (string) ($_SESSION['oauth_provider'] ?? '');
        $redirectPath = safe_redirect_path((string) ($_SESSION['oauth_redirect'] ?? '/'));

        unset($_SESSION['oauth_state'], $_SESSION['oauth_provider'], $_SESSION['oauth_redirect']);

        if (
            !$oauth->isSupported($provider)
            || $sessionProvider !== $provider
            || $expected === ''
            || !hash_equals($expected, $state)
            || $code === ''
        ) {
            $_SESSION['auth_flash'] = '소셜 로그인 세션이 만료되었거나 인증 정보가 올바르지 않습니다. 팝업/시크릿 창이 아닌 같은 브라우저에서 다시 시도해 주세요.';
            redirect('/login');
        }

        try {
            $user = $oauth->handleCallback($provider, $code);
            $this->auth->loginUser($user, true);
            redirect($redirectPath === '/' ? '/account' : $redirectPath);
        } catch (RuntimeException $e) {
            $_SESSION['auth_flash'] = $e->getMessage();
            redirect('/login');
        }
    }

    private function snsHoldBackPath(): string
    {
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $path = (string) (parse_url($ref, PHP_URL_PATH) ?? '');
        if (str_contains($path, '/register')) {
            return '/register';
        }
        return '/login';
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
