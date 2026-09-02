<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class RememberTokenRepository extends BaseModel
{
    public function create(int $userId, string $tokenHash, string $expiresAt, string $context = 'user'): void
    {
        $this->execute(
            'INSERT INTO user_remember_tokens (user_id, context, token_hash, expires_at, created_at)
             VALUES (:user_id, :context, :token_hash, :expires_at, :created_at)',
            [
                'user_id' => $userId,
                'context' => $context,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public function findValid(string $tokenHash, string $context = 'user'): ?array
    {
        return $this->fetchOne(
            'SELECT t.*, u.email, u.role, u.status, p.name
             FROM user_remember_tokens t
             INNER JOIN users u ON u.id = t.user_id AND u.deleted_at IS NULL
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE t.token_hash = :hash AND t.context = :context AND t.expires_at > :now AND u.status = :status
             LIMIT 1',
            [
                'hash' => $tokenHash,
                'context' => $context,
                'now' => date('Y-m-d H:i:s'),
                'status' => 'active',
            ]
        );
    }

    public function deleteByUser(int $userId, ?string $context = null): void
    {
        if ($context === null) {
            $this->execute('DELETE FROM user_remember_tokens WHERE user_id = :user_id', ['user_id' => $userId]);
            return;
        }

        $this->execute(
            'DELETE FROM user_remember_tokens WHERE user_id = :user_id AND context = :context',
            ['user_id' => $userId, 'context' => $context]
        );
    }

    public function deleteByHash(string $tokenHash): void
    {
        $this->execute('DELETE FROM user_remember_tokens WHERE token_hash = :hash', ['hash' => $tokenHash]);
    }
}
