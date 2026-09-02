<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Validator;
use App\Repositories\RememberTokenRepository;
use App\Repositories\UserLoginLogRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class UserService
{
    private UserRepository $users;
    private UserProfileRepository $profiles;
    private UserLoginLogRepository $loginLogs;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->profiles = new UserProfileRepository();
        $this->loginLogs = new UserLoginLogRepository();
    }

    public function register(string $email, string $password, string $name): array
    {
        $email = strtolower(trim($email));
        if (!Validator::email($email)) {
            throw new RuntimeException('올바른 이메일 형식이 아닙니다.');
        }
        if ($err = Validator::password($password)) {
            throw new RuntimeException($err);
        }
        if ($err = Validator::name($name)) {
            throw new RuntimeException($err);
        }
        if ($this->users->emailExists($email)) {
            throw new RuntimeException('이미 사용 중인 이메일입니다.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->users->create($email, $hash);
        $this->profiles->create($userId, trim($name));
        (new MemberGradeService())->assignDefault($userId);

        return $this->users->findById($userId) ?? [];
    }

    public function authenticate(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $user = $this->users->findByEmail($email);

        if (!$user || $user['status'] !== 'active') {
            $this->loginLogs->log(null, $email, false, 'invalid_credentials');
            throw new RuntimeException('이메일 또는 비밀번호가 올바르지 않습니다.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            $this->loginLogs->log((int) $user['id'], $email, false, 'wrong_password');
            throw new RuntimeException('이메일 또는 비밀번호가 올바르지 않습니다.');
        }

        $this->users->updateLastLogin((int) $user['id']);
        $this->loginLogs->log((int) $user['id'], $email, true, 'login_success');

        return $this->users->findById((int) $user['id']) ?? [];
    }

    public function updateProfile(int $userId, array $data): array
    {
        if ($err = Validator::name($data['name'] ?? '')) {
            throw new RuntimeException($err);
        }
        $this->profiles->update($userId, [
            'name' => trim($data['name']),
            'phone' => trim($data['phone'] ?? '') ?: null,
            'company' => trim($data['company'] ?? '') ?: null,
        ]);
        return $this->users->findById($userId) ?? [];
    }

    public function changePassword(int $userId, string $current, string $newPassword): void
    {
        $user = $this->users->findById($userId);
        if (!$user || !password_verify($current, $user['password_hash'])) {
            throw new RuntimeException('현재 비밀번호가 올바르지 않습니다.');
        }
        if ($err = Validator::password($newPassword)) {
            throw new RuntimeException($err);
        }
        $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));
    }

    public function withdraw(int $userId): void
    {
        $this->users->softDelete($userId);
        (new RememberTokenRepository())->deleteByUser($userId);
    }

    public function emailAvailable(string $email, ?int $excludeUserId = null): bool
    {
        return !$this->users->emailExists(strtolower(trim($email)), $excludeUserId);
    }

    public function ensureAdminExists(): void
    {
        $email = 'admin@labelup.kr';
        if ($this->users->emailExists($email)) {
            return;
        }
        $userId = $this->users->create($email, password_hash('admin1234!', PASSWORD_DEFAULT), 'admin');
        $this->profiles->create($userId, '관리자');
        $this->users->setSuperAdmin($userId, true);
        (new MemberGradeService())->assignDefault($userId);
    }

    public function sanitizeUser(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }
}
