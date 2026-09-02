<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class UserProfileRepository extends BaseModel
{
    public function create(int $userId, string $name, string $locale = 'ko'): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO user_profiles (user_id, name, locale, created_at, updated_at)
             VALUES (:user_id, :name, :locale, :created_at, :updated_at)',
            [
                'user_id' => $userId,
                'name' => $name,
                'locale' => $locale,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function update(int $userId, array $data): void
    {
        $fields = [];
        $params = ['user_id' => $userId, 'now' => date('Y-m-d H:i:s')];
        foreach (['name', 'phone', 'company', 'locale'] as $key) {
            if (array_key_exists($key, $data)) {
                $fields[] = "$key = :$key";
                $params[$key] = $data[$key];
            }
        }
        if (!$fields) {
            return;
        }
        $fields[] = 'updated_at = :now';
        $this->execute(
            'UPDATE user_profiles SET ' . implode(', ', $fields) . ' WHERE user_id = :user_id AND deleted_at IS NULL',
            $params
        );
    }
}
