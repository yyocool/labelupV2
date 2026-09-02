<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AccountRecoveryService;
use App\Services\AuthService;
use App\Services\UserService;
use RuntimeException;

final class AuthApiController extends BaseController
{
    private UserService $users;
    private AuthService $auth;
    private AccountRecoveryService $recovery;

    public function __construct()
    {
        $this->users = new UserService();
        $this->auth = new AuthService();
        $this->recovery = new AccountRecoveryService();
    }

    public function register(): never
    {
        $data = request_json();
        try {
            $user = $this->users->register(
                (string) ($data['email'] ?? ''),
                (string) ($data['password'] ?? ''),
                (string) ($data['name'] ?? '')
            );
            $this->auth->loginUser($user, !empty($data['remember']));
            $this->jsonSuccess($this->users->sanitizeUser($user), '회원가입이 완료되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function login(): never
    {
        $data = request_json();
        try {
            $user = $this->users->authenticate(
                (string) ($data['email'] ?? ''),
                (string) ($data['password'] ?? '')
            );
            $this->auth->loginUser($user, !empty($data['remember']));
            $this->jsonSuccess($this->users->sanitizeUser($user), '로그인되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 401);
        }
    }

    public function logout(): never
    {
        $this->auth->logoutUser();
        $this->jsonSuccess(null, '로그아웃되었습니다.');
    }

    public function me(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $user = (new UserService())->sanitizeUser(
            (new \App\Repositories\UserRepository())->findById($this->auth->id()) ?? []
        );
        $this->jsonSuccess($user);
    }

    public function checkEmail(): never
    {
        $email = (string) request_input('email', '');
        $available = $this->users->emailAvailable($email);
        $this->jsonSuccess([
            'email' => $email,
            'available' => $available,
        ], $available ? '사용 가능한 이메일입니다.' : '이미 사용 중인 이메일입니다.');
    }

    public function updateProfile(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $data = request_json();
        try {
            $user = $this->users->updateProfile($this->auth->id(), $data);
            $_SESSION[AuthService::SESSION_USER_KEY]['name'] = $user['name'] ?? $_SESSION[AuthService::SESSION_USER_KEY]['name'];
            $this->jsonSuccess($this->users->sanitizeUser($user), '회원정보가 수정되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function changePassword(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $data = request_json();
        try {
            $this->users->changePassword(
                $this->auth->id(),
                (string) ($data['current_password'] ?? ''),
                (string) ($data['new_password'] ?? '')
            );
            $this->jsonSuccess(null, '비밀번호가 변경되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function withdraw(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $data = request_json();
        try {
            $this->users->authenticate(
                (string) (($this->auth->user()['email'] ?? '')),
                (string) ($data['password'] ?? '')
            );
            $this->users->withdraw($this->auth->id());
            $this->auth->logoutUser();
            $this->jsonSuccess(null, '회원탈퇴가 완료되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function findEmail(): never
    {
        $data = request_json();
        try {
            $masked = $this->recovery->findEmail(
                (string) ($data['name'] ?? ''),
                (string) ($data['phone'] ?? '')
            );
            $this->jsonSuccess(['masked_email' => $masked], '아이디(이메일)를 찾았습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function requestPasswordReset(): never
    {
        $data = request_json();
        try {
            $result = $this->recovery->requestPasswordReset(
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

    public function resetPassword(): never
    {
        $data = request_json();
        try {
            $this->recovery->resetPassword(
                (string) ($data['token'] ?? ''),
                (string) ($data['password'] ?? '')
            );
            $this->jsonSuccess(null, '비밀번호가 재설정되었습니다. 새 비밀번호로 로그인해주세요.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
