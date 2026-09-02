<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class AdminFavoriteRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function forAdmin(int $adminUserId): array
    {
        return $this->fetchAll(
            'SELECT * FROM admin_favorites WHERE admin_user_id = :uid ORDER BY slot ASC',
            ['uid' => $adminUserId]
        );
    }

    public function replaceAll(int $adminUserId, array $slots): void
    {
        $this->execute('DELETE FROM admin_favorites WHERE admin_user_id = :uid', ['uid' => $adminUserId]);
        $now = date('Y-m-d H:i:s');
        foreach ($slots as $row) {
            $slot = (int) ($row['slot'] ?? 0);
            $key = trim((string) ($row['menu_key'] ?? ''));
            if ($slot < 1 || $slot > 10 || $key === '') {
                continue;
            }
            $this->execute(
                'INSERT INTO admin_favorites (admin_user_id, slot, menu_key, created_at, updated_at)
                 VALUES (:uid, :slot, :menu_key, :created_at, :updated_at)',
                [
                    'uid' => $adminUserId,
                    'slot' => $slot,
                    'menu_key' => $key,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
