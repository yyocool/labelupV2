<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class PasswordResetTokenRepository extends BaseModel
{
    public function create(int $userId, string $token, string $expiresAt): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at)
             VALUES (:user_id, :token, :expires_at, :created_at)',
            [
                'user_id' => $userId,
                'token' => $token,
                'expires_at' => $expiresAt,
                'created_at' => $now,
            ]
        );

        return (int) $this->lastInsertId();
    }

    public function invalidateByUser(int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'UPDATE password_reset_tokens SET used_at = :now
             WHERE user_id = :user_id AND used_at IS NULL',
            ['now' => $now, 'user_id' => $userId]
        );
    }

    public function findValid(string $token): ?array
    {
        return $this->fetchOne(
            'SELECT t.*, u.email, u.status, p.name
             FROM password_reset_tokens t
             INNER JOIN users u ON u.id = t.user_id AND u.deleted_at IS NULL
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE t.token = :token
               AND t.used_at IS NULL
               AND t.expires_at > :now
             LIMIT 1',
            ['token' => $token, 'now' => date('Y-m-d H:i:s')]
        );
    }

    public function markUsed(int $id): void
    {
        $this->execute(
            'UPDATE password_reset_tokens SET used_at = :now WHERE id = :id',
            ['now' => date('Y-m-d H:i:s'), 'id' => $id]
        );
    }
}
