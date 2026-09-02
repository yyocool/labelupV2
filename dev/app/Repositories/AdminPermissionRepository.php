<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class AdminPermissionRepository extends BaseModel
{
    /** @return array<int, string> */
    public function keysFor(int $adminUserId): array
    {
        $rows = $this->fetchAll(
            'SELECT menu_key FROM admin_menu_permissions WHERE admin_user_id = :uid ORDER BY menu_key ASC',
            ['uid' => $adminUserId]
        );
        return array_values(array_map(static fn (array $row): string => (string) $row['menu_key'], $rows));
    }

    /** @param array<int, string> $keys */
    public function replaceAll(int $adminUserId, array $keys): void
    {
        $this->execute(
            'DELETE FROM admin_menu_permissions WHERE admin_user_id = :uid',
            ['uid' => $adminUserId]
        );
        $now = date('Y-m-d H:i:s');
        foreach (array_unique($keys) as $key) {
            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }
            $this->execute(
                'INSERT INTO admin_menu_permissions (admin_user_id, menu_key, created_at)
                 VALUES (:uid, :menu_key, :created_at)',
                ['uid' => $adminUserId, 'menu_key' => $key, 'created_at' => $now]
            );
        }
    }

    public function deleteFor(int $adminUserId): void
    {
        $this->execute(
            'DELETE FROM admin_menu_permissions WHERE admin_user_id = :uid',
            ['uid' => $adminUserId]
        );
    }
}
