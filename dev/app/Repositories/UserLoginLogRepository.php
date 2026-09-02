<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class UserLoginLogRepository extends BaseModel
{
    public function log(?int $userId, string $email, bool $success, string $message = ''): void
    {
        $this->execute(
            'INSERT INTO user_login_logs (user_id, email, ip_address, user_agent, success, message, created_at)
             VALUES (:user_id, :email, :ip, :ua, :success, :message, :created_at)',
            [
                'user_id' => $userId,
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'success' => $success ? 1 : 0,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function recent(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        return $this->fetchAll(
            "SELECT l.id, l.user_id, l.email, l.ip_address, l.success, l.message, l.created_at,
                    p.name
             FROM user_login_logs l
             LEFT JOIN user_profiles p ON p.user_id = l.user_id AND p.deleted_at IS NULL
             ORDER BY l.id DESC
             LIMIT {$limit}"
        );
    }

    public function countTodaySuccess(): int
    {
        $today = date('Y-m-d 00:00:00');
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM user_login_logs WHERE success = 1 AND created_at >= :today',
            ['today' => $today]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function recentForUser(int $userId, int $limit = 15): array
    {
        $limit = max(1, min(50, $limit));
        return $this->fetchAll(
            "SELECT * FROM user_login_logs WHERE user_id = :user_id ORDER BY id DESC LIMIT {$limit}",
            ['user_id' => $userId]
        );
    }
}
