<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AccountRecoveryService;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\EventPopupService;
use App\Services\NaverSmsSettings;
use App\Services\OAuthService;
use App\Services\ShopService;
use App\Services\SiteModeService;
use App\Services\SmsVerificationService;
use App\Services\UserService;
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
            'smsRequired' => NaverSmsSettings::isSignupRequired(),
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
            if ($provider === 'google') {
                $this->renderGoogleStartPage(
                    $oauth->publicClientId('google'),
                    $state,
                    $oauth->authorizationUrl($provider, $state)
                );
            }
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

        if ($provider === 'google' && $code === '') {
            $this->renderGoogleReturnPage();
        }

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
            $next = $redirectPath === '/' ? '/account' : $redirectPath;
            $oauth->setAfterLoginPath($next);
            $user = $oauth->handleCallback($provider, $code);
            if ($user === null) {
                redirect('/register/phone');
            }
            $this->auth->loginUser($user, true);
            redirect($next);
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

    public function accountOrders(): void
    {
        (new AuthMiddleware($this->auth))->handle();
        $userId = (int) $this->auth->id();
        $accountService = new AccountService();
        $list = $accountService->orders($userId, (int) ($_GET['page'] ?? 1));
        view('account/layout', [
            'pageTitle' => '주문 내역 — 라벨업',
            'contentTemplate' => 'account/orders',
            'authUser' => $this->auth->user(),
            'accountService' => $accountService,
            'orderList' => $list,
            'cartCount' => (new ShopService())->cartCount(),
            'activeNav' => 'account',
            'eventPopups' => (new EventPopupService())->activeForSite(),
        ]);
    }

    public function accountCoupons(): void
    {
        (new AuthMiddleware($this->auth))->handle();
        $accountService = new AccountService();
        $coupons = [];
        try {
            $coupons = $accountService->usableCoupons();
        } catch (\Throwable) {
            $coupons = [];
        }
        view('account/layout', [
            'pageTitle' => '쿠폰함 — 라벨업',
            'contentTemplate' => 'account/coupons',
            'authUser' => $this->auth->user(),
            'accountService' => $accountService,
            'coupons' => $coupons,
            'cartCount' => (new ShopService())->cartCount(),
            'activeNav' => 'account',
            'eventPopups' => (new EventPopupService())->activeForSite(),
        ]);
    }

    public function accountOrder(string $orderNo): void
    {
        (new AuthMiddleware($this->auth))->handle();
        $user = $this->auth->user() ?? [];
        $userId = (int) ($user['id'] ?? 0);
        $orderNo = trim(rawurldecode($orderNo));
        $order = (new ShopService())->completeOrderForUser(
            $userId,
            $orderNo,
            (string) ($user['email'] ?? '')
        );
        if (!$order) {
            redirect('/account/orders');
        }
        $accountService = new AccountService();
        view('account/layout', [
            'pageTitle' => '주문 상세 — 라벨업',
            'contentTemplate' => 'account/order',
            'authUser' => $user,
            'accountService' => $accountService,
            'order' => $order,
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

    public function oauthProviders(): never
    {
        $siteMode = new SiteModeService();
        $this->jsonSuccess(array_merge((new OAuthService())->configuredMap(), [
            'sns_hold' => $siteMode->isTempOpen(),
            'sns_hold_message' => $siteMode->isTempOpen() ? SiteModeService::SNS_HOLD_MESSAGE : '',
        ]));
    }

    public function apiRegister(): never
    {
        $users = new UserService();
        $data = request_json();
        try {
            $user = $users->register(
                (string) ($data['email'] ?? ''),
                (string) ($data['password'] ?? ''),
                (string) ($data['name'] ?? ''),
                (string) ($data['phone'] ?? ''),
                (string) ($data['phone_verify_token'] ?? '')
            );
            $this->auth->loginUser($user, !empty($data['remember']));
            $this->jsonSuccess($users->sanitizeUser($user), '회원가입이 완료되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiLogin(): never
    {
        $users = new UserService();
        $data = request_json();
        try {
            $user = $users->authenticate(
                (string) ($data['email'] ?? ''),
                (string) ($data['password'] ?? '')
            );
            $this->auth->loginUser($user, !empty($data['remember']));
            $this->jsonSuccess($users->sanitizeUser($user), '로그인되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 401);
        }
    }

    public function apiLogout(): never
    {
        $this->auth->logoutUser();
        $this->jsonSuccess(null, '로그아웃되었습니다.');
    }

    public function apiMe(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $user = (new UserService())->sanitizeUser(
            (new \App\Repositories\UserRepository())->findById($this->auth->id()) ?? []
        );
        $this->jsonSuccess($user);
    }

    public function socialPhoneForm(): void
    {
        if ($this->auth->checkUser()) {
            redirect('/account');
        }
        $oauth = new OAuthService();
        if (!NaverSmsSettings::isSignupRequired()) {
            try {
                $user = $oauth->completePendingSignupWithoutPhone();
                $redirect = safe_redirect_path((string) ($user['redirect'] ?? '/account'));
                unset($user['redirect']);
                $this->auth->loginUser($user, true);
                redirect($redirect === '/' ? '/account' : $redirect);
            } catch (RuntimeException $e) {
                $_SESSION['auth_flash'] = $e->getMessage();
                redirect('/register');
            }
        }
        try {
            $pending = $oauth->pendingSignupSummary();
        } catch (RuntimeException $e) {
            $_SESSION['auth_flash'] = $e->getMessage();
            redirect('/register');
        }
        $flash = (string) ($_SESSION['auth_flash'] ?? '');
        unset($_SESSION['auth_flash']);
        $this->render('auth/social-phone', [
            'pageTitle' => '휴대폰 인증 — 라벨업',
            'pending' => $pending,
            'authFlash' => $flash,
        ]);
    }

    public function apiCompleteSocialSignup(): never
    {
        $data = request_json();
        try {
            $oauth = new OAuthService();
            $user = NaverSmsSettings::isSignupRequired()
                ? $oauth->completePendingSignup(
                    (string) ($data['phone'] ?? ''),
                    (string) ($data['phone_verify_token'] ?? '')
                )
                : $oauth->completePendingSignupWithoutPhone();
            $redirect = safe_redirect_path((string) ($user['redirect'] ?? '/account'));
            unset($user['redirect']);
            $this->auth->loginUser($user, true);
            $this->jsonSuccess([
                'redirect' => $redirect === '/' ? '/account' : $redirect,
            ], '회원가입이 완료되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiSendPhoneCode(): never
    {
        $data = request_json();
        try {
            $result = (new SmsVerificationService())->sendSignupCode((string) ($data['phone'] ?? ''));
            $this->jsonSuccess($result, '인증번호를 보냈습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiVerifyPhoneCode(): never
    {
        $data = request_json();
        try {
            $token = (new SmsVerificationService())->verifySignupCode(
                (string) ($data['phone'] ?? ''),
                (string) ($data['code'] ?? '')
            );
            $this->jsonSuccess(['token' => $token], '휴대폰 인증이 완료되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiCheckEmail(): never
    {
        $users = new UserService();
        $email = (string) request_input('email', '');
        $available = $users->emailAvailable($email);
        $this->jsonSuccess([
            'email' => $email,
            'available' => $available,
        ], $available ? '사용 가능한 이메일입니다.' : '이미 사용 중인 이메일입니다.');
    }

    public function apiUpdateProfile(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $users = new UserService();
        $data = request_json();
        try {
            $user = $users->updateProfile($this->auth->id(), $data);
            $_SESSION[AuthService::SESSION_USER_KEY]['name'] = $user['name'] ?? $_SESSION[AuthService::SESSION_USER_KEY]['name'];
            $this->jsonSuccess($users->sanitizeUser($user), '회원정보가 수정되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiChangePassword(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $data = request_json();
        try {
            (new UserService())->changePassword(
                $this->auth->id(),
                (string) ($data['current_password'] ?? ''),
                (string) ($data['new_password'] ?? '')
            );
            $this->jsonSuccess(null, '비밀번호가 변경되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiWithdraw(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $users = new UserService();
        $data = request_json();
        try {
            $users->authenticate(
                (string) (($this->auth->user()['email'] ?? '')),
                (string) ($data['password'] ?? '')
            );
            $users->withdraw($this->auth->id());
            $this->auth->logoutUser();
            $this->jsonSuccess(null, '회원탈퇴가 완료되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiFindEmail(): never
    {
        $data = request_json();
        try {
            $masked = (new AccountRecoveryService())->findEmail(
                (string) ($data['name'] ?? ''),
                (string) ($data['phone'] ?? '')
            );
            $this->jsonSuccess(['masked_email' => $masked], '아이디(이메일)를 찾았습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiRequestPasswordReset(): never
    {
        $data = request_json();
        try {
            $result = (new AccountRecoveryService())->requestPasswordReset(
                (string) ($data['email'] ?? ''),
                (string) ($data['name'] ?? '')
            );
            $this->jsonSuccess(
                array_filter([
                    'reset_url' => $result['reset_url'] ?? null,
                ]),
                $result['message']
            );
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function apiResetPassword(): never
    {
        $data = request_json();
        try {
            (new AccountRecoveryService())->resetPassword(
                (string) ($data['token'] ?? ''),
                (string) ($data['password'] ?? '')
            );
            $this->jsonSuccess(null, '비밀번호가 재설정되었습니다. 새 비밀번호로 로그인해주세요.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function googleTokenLogin(): never
    {
        $siteMode = new SiteModeService();
        if ($siteMode->isTempOpen()) {
            $this->jsonError(SiteModeService::SNS_HOLD_MESSAGE, null, 403);
        }

        $data = request_json();
        $accessToken = trim((string) ($data['access_token'] ?? ''));
        $state = (string) ($data['state'] ?? '');
        $expiresIn = isset($data['expires_in']) && is_numeric($data['expires_in'])
            ? (int) $data['expires_in']
            : null;
        $expected = (string) ($_SESSION['oauth_state'] ?? '');
        $sessionProvider = (string) ($_SESSION['oauth_provider'] ?? '');
        $redirectPath = safe_redirect_path((string) ($_SESSION['oauth_redirect'] ?? '/'));

        if (
            $accessToken === ''
            || $sessionProvider !== 'google'
            || $expected === ''
            || !hash_equals($expected, $state)
        ) {
            $_SESSION['auth_flash'] = '소셜 로그인 세션이 만료되었거나 인증 정보가 올바르지 않습니다. 같은 브라우저에서 다시 시도해 주세요.';
            $this->jsonError($_SESSION['auth_flash'], null, 401);
        }

        unset($_SESSION['oauth_state'], $_SESSION['oauth_provider'], $_SESSION['oauth_redirect']);

        try {
            $next = $redirectPath === '/' ? '/account' : $redirectPath;
            $oauth = new OAuthService();
            $oauth->setAfterLoginPath($next);
            $user = $oauth->loginWithAccessToken('google', $accessToken, $expiresIn);
            if ($user === null) {
                $this->jsonSuccess([
                    'redirect' => '/register/phone',
                ], '휴대폰 인증 후 가입이 완료됩니다.');
            }
            $this->auth->loginUser($user, true);
            $this->jsonSuccess([
                'redirect' => $next,
            ], '로그인되었습니다.');
        } catch (RuntimeException $e) {
            $_SESSION['auth_flash'] = $e->getMessage();
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function renderGoogleStartPage(string $clientId, string $state, string $authorizeUrl): never
    {
        $this->renderGoogleBridgePage(
            '구글 로그인 창을 여는 중...',
            'var clientId=' . json_encode($clientId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
            . 'var state=' . json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
            . 'var authorizeUrl=' . json_encode($authorizeUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
            . 'var apiUrl=' . json_encode(url('api/auth/oauth/google'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
            . 'var failUrl=' . json_encode(url('login'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
            . 'var usedFallback=false;'
            . 'function fallback(){if(usedFallback)return;usedFallback=true;if(authorizeUrl){location.replace(authorizeUrl);return;}location.replace(failUrl);}'
            . 'function finish(token,expiresIn){fetch(apiUrl,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json"},body:JSON.stringify({access_token:token,state:state,expires_in:expiresIn||""})}).then(function(res){return res.json().then(function(data){return{ok:res.ok,data:data};});}).then(function(x){if(!x.ok||!x.data||x.data.success===false){throw new Error((x.data&&x.data.message)||"fail");}location.replace((x.data.data&&x.data.data.redirect)||"/account");}).catch(function(){location.replace(failUrl);});}'
            . 'function startGis(){if(!clientId||!window.google||!google.accounts||!google.accounts.oauth2){fallback();return;}try{var client=google.accounts.oauth2.initTokenClient({client_id:clientId,scope:"email profile",prompt:"select_account",callback:function(resp){if(!resp||resp.error||!resp.access_token){fallback();return;}finish(resp.access_token,resp.expires_in);},error_callback:function(){fallback();}});client.requestAccessToken();}catch(e){fallback();}}'
            . 'var tries=0;var timer=setInterval(function(){tries+=1;if(window.google&&google.accounts&&google.accounts.oauth2){clearInterval(timer);startGis();}else if(tries>=20){clearInterval(timer);fallback();}},150);',
            true
        );
    }

    private function renderGoogleReturnPage(): never
    {
        $this->renderGoogleBridgePage(
            '구글 로그인 확인 중...',
            'var apiUrl=' . json_encode(url('api/auth/oauth/google'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
            . 'var failUrl=' . json_encode(url('login'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';'
            . 'var params=new URLSearchParams((location.hash||"").replace(/^#/,""));'
            . 'var token=params.get("access_token")||"";'
            . 'var state=params.get("state")||"";'
            . 'var expiresIn=params.get("expires_in")||"";'
            . 'var err=params.get("error")||params.get("error_description")||"";'
            . 'history.replaceState(null,"",location.pathname);'
            . 'if(err||!token){location.replace(failUrl);}else{fetch(apiUrl,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json"},body:JSON.stringify({access_token:token,state:state,expires_in:expiresIn})}).then(function(res){return res.json().then(function(data){return{ok:res.ok,data:data};});}).then(function(x){if(!x.ok||!x.data||x.data.success===false){throw new Error((x.data&&x.data.message)||"fail");}location.replace((x.data.data&&x.data.data.redirect)||"/account");}).catch(function(){location.replace(failUrl);});}'
        );
    }

    private function renderGoogleBridgePage(string $status, string $script, bool $loadGis = false): never
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>구글 로그인 — 라벨업</title>'
            . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:Pretendard,system-ui,sans-serif;background:#f7f4ef;color:#3b2f27}.box{text-align:center;padding:28px}.msg{font-size:15px}</style></head><body>'
            . '<div class="box"><p class="msg">' . e($status) . '</p></div>'
            . ($loadGis ? '<script src="https://accounts.google.com/gsi/client" async defer></script>' : '')
            . '<script>' . $script . '</script></body></html>';
        exit;
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
