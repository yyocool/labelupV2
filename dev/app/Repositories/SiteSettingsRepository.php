<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;
use Throwable;

final class SiteSettingsRepository extends BaseModel
{
    /** @return array<string, string> */
    public function all(): array
    {
        try {
            $rows = $this->fetchAll('SELECT setting_key, setting_value FROM site_settings');
        } catch (Throwable) {
            return [];
        }
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        return $map;
    }

    public function get(string $key, string $default = ''): string
    {
        try {
            $row = $this->fetchOne(
                'SELECT setting_value FROM site_settings WHERE setting_key = :k LIMIT 1',
                ['k' => $key]
            );
        } catch (Throwable) {
            return $default;
        }
        if ($row === null) {
            return $default;
        }
        return (string) ($row['setting_value'] ?? $default);
    }

    public function set(string $key, string $value): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO site_settings (setting_key, setting_value, updated_at)
             VALUES (:k, :v, :now)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)',
            ['k' => $key, 'v' => $value, 'now' => $now]
        );
    }
}
