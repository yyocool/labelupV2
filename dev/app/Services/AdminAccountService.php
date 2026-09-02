<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Validator;
use App\Repositories\AdminPermissionRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class AdminAccountService
{
    private UserRepository $users;
    private UserProfileRepository $profiles;
    private AdminPermissionRepository $perms;
    private AdminAccessService $access;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->profiles = new UserProfileRepository();
        $this->perms = new AdminPermissionRepository();
        $this->access = new AdminAccessService();
    }

    /** @return array<int, array<string, mixed>> */
    public function listAdmins(): array
    {
        $items = [];
        foreach ($this->users->listAdmins() as $row) {
            $id = (int) $row['id'];
            $super = !empty($row['is_super_admin']);
            $items[] = $row + [
                'is_super_admin' => $super ? 1 : 0,
                'menu_keys' => $super ? array_column(admin_menu_catalog(), 'key') : $this->access->allowedKeys($id),
            ];
        }
        return $items;
    }

    /** @return array<string, mixed> */
    public function lookupByEmail(string $email): array
    {
        $email = strtolower(trim($email));
        if (!Validator::email($email)) {
            throw new RuntimeException('올바른 이메일 형식이 아닙니다.');
        }
        $user = $this->users->findByEmail($email);
        if (!$user || !empty($user['deleted_at'])) {
            throw new RuntimeException('해당 이메일의 회원을 찾을 수 없습니다.');
        }
        $full = $this->users->findById((int) $user['id']) ?? $user;
        return [
            'id' => (int) $full['id'],
            'email' => (string) $full['email'],
            'name' => (string) ($full['name'] ?? ''),
            'role' => (string) ($full['role'] ?? 'member'),
            'status' => (string) ($full['status'] ?? ''),
            'is_admin' => ($full['role'] ?? '') === 'admin',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function save(array $payload, int $actorId): array
    {
        $actorSuper = $this->access->isSuper($actorId);
        $id = (int) ($payload['id'] ?? 0);
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $name = trim((string) ($payload['name'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $status = trim((string) ($payload['status'] ?? 'active'));
        $wantSuper = !empty($payload['is_super_admin']);
        $menuKeys = $this->sanitizeMenuKeys($payload['menu_keys'] ?? []);

        if ($wantSuper && !$actorSuper) {
            throw new RuntimeException('최고관리자만 최고관리자를 지정할 수 있습니다.');
        }
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new RuntimeException('잘못된 상태입니다.');
        }
        if ($err = Validator::name($name)) {
            throw new RuntimeException($err);
        }

        if ($id > 0) {
            $target = $this->users->findById($id);
            if (!$target) {
                throw new RuntimeException('관리자를 찾을 수 없습니다.');
            }
            $wasSuper = !empty($target['is_super_admin']);
            if ($wasSuper && !$actorSuper) {
                throw new RuntimeException('최고관리자 계정은 수정할 수 없습니다.');
            }
            if ($id === $actorId && !$actorSuper) {
                throw new RuntimeException('본인 권한은 변경할 수 없습니다.');
            }
            if ($wasSuper && !$wantSuper && $this->users->countSuperAdmins($id) < 1) {
                throw new RuntimeException('마지막 최고관리자의 권한은 해제할 수 없습니다.');
            }
            if ($id === $actorId && $status !== 'active') {
                throw new RuntimeException('본인 계정은 비활성화할 수 없습니다.');
            }
            $this->users->updateRole($id, 'admin');
            $this->users->updateStatus($id, $status);
            $this->users->setSuperAdmin($id, $actorSuper && $wantSuper);
            $this->profiles->update($id, ['name' => $name]);
            if ($password !== '') {
                if ($err = Validator::password($password)) {
                    throw new RuntimeException($err);
                }
                $this->users->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
            }
            $this->syncMenus($id, $actorSuper && $wantSuper, $menuKeys);
            return $this->findAdmin($id);
        }

        if (!Validator::email($email)) {
            throw new RuntimeException('올바른 이메일 형식이 아닙니다.');
        }
        $existing = $this->users->findByEmail($email);
        if ($existing) {
            throw new RuntimeException('이미 가입된 이메일입니다. 신규 관리자 계정만 등록할 수 있습니다.');
        }

        if ($err = Validator::password($password)) {
            throw new RuntimeException($err);
        }
        $id = $this->users->create($email, password_hash($password, PASSWORD_DEFAULT), 'admin');
        $this->profiles->create($id, $name);
        (new MemberGradeService())->assignDefault($id);
        $this->users->updateStatus($id, $status);
        $this->users->setSuperAdmin($id, $actorSuper && $wantSuper);
        $this->syncMenus($id, $actorSuper && $wantSuper, $menuKeys);
        return $this->findAdmin($id);
    }

    public function revoke(int $userId, int $actorId): void
    {
        if ($userId <= 0) {
            throw new RuntimeException('관리자가 없습니다.');
        }
        if ($userId === $actorId) {
            throw new RuntimeException('본인 계정은 해제할 수 없습니다.');
        }
        $target = $this->users->findById($userId);
        if (!$target || ($target['role'] ?? '') !== 'admin') {
            throw new RuntimeException('관리자를 찾을 수 없습니다.');
        }
        if (!empty($target['is_super_admin']) && !$this->access->isSuper($actorId)) {
            throw new RuntimeException('최고관리자 계정은 해제할 수 없습니다.');
        }
        if (!empty($target['is_super_admin']) && $this->users->countSuperAdmins($userId) < 1) {
            throw new RuntimeException('마지막 최고관리자는 해제할 수 없습니다.');
        }
        $this->users->setSuperAdmin($userId, false);
        $this->users->updateRole($userId, 'member');
        $this->perms->deleteFor($userId);
    }

    public function grantAllMenus(int $adminUserId): void
    {
        $keys = array_values(array_filter(
            array_column(admin_menu_catalog(), 'key'),
            static fn (string $key): bool => $key !== 'settings-admins'
        ));
        $this->perms->replaceAll($adminUserId, $keys);
    }

    public function clearMenus(int $adminUserId): void
    {
        $this->perms->deleteFor($adminUserId);
    }

    /** @return array<string, mixed> */
    private function findAdmin(int $id): array
    {
        foreach ($this->listAdmins() as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }
        throw new RuntimeException('관리자를 찾을 수 없습니다.');
    }

    /** @param array<int, string> $menuKeys */
    private function syncMenus(int $id, bool $isSuper, array $menuKeys): void
    {
        if ($isSuper) {
            $this->perms->deleteFor($id);
            return;
        }
        $this->perms->replaceAll($id, $menuKeys);
    }

    /** @return array<int, string> */
    private function sanitizeMenuKeys(mixed $incoming): array
    {
        if (!is_array($incoming)) {
            return [];
        }
        $valid = array_column(admin_menu_catalog(), 'key');
        $out = [];
        foreach ($incoming as $key) {
            $key = trim((string) $key);
            if (in_array($key, $valid, true)) {
                $out[] = $key;
            }
        }
        return array_values(array_unique($out));
    }
}
