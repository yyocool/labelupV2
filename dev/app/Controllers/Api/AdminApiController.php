<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AdminService;
use App\Services\AuthService;
use App\Services\CreditAdminService;
use App\Services\LegalDocumentService;
use App\Services\UserService;
use RuntimeException;

final class AdminApiController extends BaseController
{
    private AuthService $auth;
    private AdminService $admin;
    private UserService $users;
    private LegalDocumentService $legal;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->admin = new AdminService();
        $this->users = new UserService();
        $this->legal = new LegalDocumentService();
    }

    public function login(): never
    {
        $data = request_json();
        try {
            $user = $this->users->authenticate(
                (string) ($data['email'] ?? ''),
                (string) ($data['password'] ?? '')
            );
            if (($user['role'] ?? '') !== 'admin') {
                $this->jsonError('관리자 계정만 로그인할 수 있습니다.', null, 403);
            }
            $this->auth->loginAdmin($user, !empty($data['remember']));
            $this->jsonSuccess($this->users->sanitizeUser($user), '관리자 로그인되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 401);
        }
    }

    public function changePassword(): never
    {
        $this->requireAdmin();
        $data = request_json();
        $new = (string) ($data['new_password'] ?? '');
        $confirm = (string) ($data['new_password_confirm'] ?? '');
        if ($confirm !== '' && $new !== $confirm) {
            $this->jsonError('새 비밀번호가 일치하지 않습니다.', null, 422);
        }
        try {
            $this->users->changePassword(
                (int) ($this->auth->adminId() ?? 0),
                (string) ($data['current_password'] ?? ''),
                $new
            );
            $this->jsonSuccess(null, '비밀번호가 변경되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function createUser(): never
    {
        $this->requireAdmin();
        $payload = request_json();

        try {
            $user = $this->users->createByAdmin($payload);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }

        $userId = (int) ($user['id'] ?? 0);
        $credit = (int) ($payload['credit_amount'] ?? 0);
        $message = '회원이 추가되었습니다.';
        if ($credit > 0) {
            // 회원은 이미 만들어졌다. 크레딧 지급만 실패해도 등록은 되돌리지 않고 알린다.
            try {
                (new CreditAdminService())->grantUserCredit(
                    $userId,
                    $credit,
                    trim((string) ($payload['credit_reason'] ?? '')) ?: '관리자 회원 등록 지급',
                    (int) $this->auth->adminId()
                );
                $message = '회원이 추가되고 크레딧 ' . number_format($credit) . ' C가 지급되었습니다.';
            } catch (RuntimeException $e) {
                $message = '회원은 추가되었지만 크레딧 지급에 실패했습니다. ' . $e->getMessage();
            }
        }

        $this->jsonSuccess($this->users->sanitizeUser($user), $message);
    }

    public function updateUser(): never
    {
        $this->requireAdmin();

        $payload = request_json();
        $userId = (int) ($payload['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->jsonError('회원 ID가 필요합니다.');
        }

        try {
            if (array_key_exists('grade_id', $payload)) {
                $this->admin->updateUserGrade($userId, (int) $payload['grade_id']);
            }
            if (isset($payload['status'])) {
                $this->admin->updateUserStatus($userId, (string) $payload['status'], (int) $this->auth->adminId());
            }
        } catch (\InvalidArgumentException $e) {
            $this->jsonError($e->getMessage());
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }

        $this->jsonSuccess(null, '회원 정보가 저장되었습니다.');
    }

    public function updateLegal(): never
    {
        $this->requireAdmin();
        $payload = request_json();
        $docKey = (string) ($payload['doc_key'] ?? '');
        try {
            $doc = $this->legal->update(
                $docKey,
                (string) ($payload['title'] ?? ''),
                (string) ($payload['content'] ?? '')
            );
            $this->jsonSuccess($doc, '약관이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    private function requireAdmin(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
