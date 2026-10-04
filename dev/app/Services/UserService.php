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
        (new NotificationService())->notifyWelcome($userId, trim($name));

        return $this->users->findById($userId) ?? [];
    }

    /**
     * 관리자가 회원을 대신 등록한다.
     * 가입 화면과 달리 연락처·회사·등급·상태까지 한 번에 정할 수 있다.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function createByAdmin(array $payload): array
    {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $name = trim((string) ($payload['name'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $phone = trim((string) ($payload['phone'] ?? ''));
        $company = trim((string) ($payload['company'] ?? ''));
        $status = trim((string) ($payload['status'] ?? 'active'));
        $gradeId = (int) ($payload['grade_id'] ?? 0);

        if (!Validator::email($email)) {
            throw new RuntimeException('올바른 이메일 형식이 아닙니다.');
        }
        if (mb_strlen($email) > 190) {
            throw new RuntimeException('이메일은 190자 이내로 입력해주세요.');
        }
        if ($err = Validator::name($name)) {
            throw new RuntimeException($err);
        }
        if (mb_strlen($name) > 100) {
            throw new RuntimeException('이름은 100자 이내로 입력해주세요.');
        }
        if ($err = Validator::password($password)) {
            throw new RuntimeException($err);
        }
        if (mb_strlen($phone) > 30) {
            throw new RuntimeException('연락처는 30자 이내로 입력해주세요.');
        }
        if (mb_strlen($company) > 150) {
            throw new RuntimeException('회사명은 150자 이내로 입력해주세요.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new RuntimeException('잘못된 상태입니다.');
        }
        if ($this->users->emailExists($email)) {
            throw new RuntimeException('이미 사용 중인 이메일입니다.');
        }

        $userId = $this->users->create($email, password_hash($password, PASSWORD_DEFAULT));
        $this->profiles->create($userId, $name);
        if ($phone !== '' || $company !== '') {
            $this->profiles->update($userId, [
                'phone' => $phone !== '' ? $phone : null,
                'company' => $company !== '' ? $company : null,
            ]);
        }

        $grades = new MemberGradeService();
        if ($gradeId > 0) {
            $grades->assign($userId, $gradeId);
        } else {
            $grades->assignDefault($userId);
        }
        if ($status !== 'active') {
            $this->users->updateStatus($userId, $status);
        }
        // 관리자가 대신 만든 계정이라 환영 알림은 선택이다.
        if (!empty($payload['send_welcome'])) {
            (new NotificationService())->notifyWelcome($userId, $name);
        }

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
