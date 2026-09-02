<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserLoginLogRepository;
use App\Repositories\UserRepository;

final class AdminService
{
    private UserRepository $users;
    private UserLoginLogRepository $loginLogs;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->loginLogs = new UserLoginLogRepository();
    }

    public function dashboardStats(): array
    {
        $today = date('Y-m-d 00:00:00');

        return [
            'total_users' => $this->users->countActive(),
            'admin_users' => $this->users->countByRole('admin'),
            'today_signups' => $this->users->countRegisteredSince($today),
            'today_logins' => $this->loginLogs->countTodaySuccess(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function recentLoginLogs(int $limit = 8): array
    {
        return $this->loginLogs->recent($limit);
    }

  /** @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, pages: int} */
    public function listUsers(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $total = $this->users->countForAdmin($search);
        $pages = max(1, (int) ceil($total / $perPage));

        return [
            'items' => $this->users->listForAdmin($search, $page, $perPage),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
        ];
    }

    public function updateUserRole(int $userId, string $role, int $actorId): void
    {
        if (!in_array($role, ['member', 'admin'], true)) {
            throw new \InvalidArgumentException('유효하지 않은 역할입니다.');
        }
        if ($userId === $actorId && $role !== 'admin') {
            throw new \InvalidArgumentException('본인의 관리자 권한은 해제할 수 없습니다.');
        }
        $this->users->updateRole($userId, $role);
    }

    public function updateUserStatus(int $userId, string $status, int $actorId): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new \InvalidArgumentException('유효하지 않은 상태입니다.');
        }
        if ($userId === $actorId) {
            throw new \InvalidArgumentException('본인 계정 상태는 변경할 수 없습니다.');
        }
        $this->users->updateStatus($userId, $status);
    }
}
