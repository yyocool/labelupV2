<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class UserOauthAccountRepository extends BaseModel
{
    public function findByProvider(string $provider, string $providerUserId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_oauth_accounts
             WHERE provider = :provider AND provider_user_id = :pid AND deleted_at IS NULL
             LIMIT 1',
            ['provider' => $provider, 'pid' => $providerUserId]
        );
    }

    public function upsert(
        int $userId,
        string $provider,
        string $providerUserId,
        ?string $providerEmail,
        ?string $accessToken,
        ?string $refreshToken,
        ?string $tokenExpiresAt
    ): void {
        $existing = $this->findByProvider($provider, $providerUserId);
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $this->execute(
                'UPDATE user_oauth_accounts
                 SET user_id = :user_id,
                     provider_email = :email,
                     access_token = :access_token,
                     refresh_token = :refresh_token,
                     token_expires_at = :expires_at,
                     updated_at = :now
                 WHERE id = :id',
                [
                    'user_id' => $userId,
                    'email' => $providerEmail,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_at' => $tokenExpiresAt,
                    'now' => $now,
                    'id' => (int) $existing['id'],
                ]
            );
            return;
        }

        $this->execute(
            'INSERT INTO user_oauth_accounts
             (user_id, provider, provider_user_id, provider_email, access_token, refresh_token, token_expires_at, created_at, updated_at)
             VALUES
             (:user_id, :provider, :pid, :email, :access_token, :refresh_token, :expires_at, :now, :now)',
            [
                'user_id' => $userId,
                'provider' => $provider,
                'pid' => $providerUserId,
                'email' => $providerEmail,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at' => $tokenExpiresAt,
                'now' => $now,
            ]
        );
    }
}
