<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class UserAddressRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function listByUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT * FROM user_shipping_addresses
             WHERE user_id = :uid
             ORDER BY is_default DESC, id DESC',
            ['uid' => $userId]
        );
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_shipping_addresses WHERE id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => $userId]
        );
    }

    public function countByUser(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM user_shipping_addresses WHERE user_id = :uid',
            ['uid' => $userId]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function defaultForUser(int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_shipping_addresses
             WHERE user_id = :uid
             ORDER BY is_default DESC, id DESC
             LIMIT 1',
            ['uid' => $userId]
        );
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO user_shipping_addresses (
                user_id, label, recipient_name, recipient_phone,
                zip, address_base, address_detail, is_default, created_at, updated_at
            ) VALUES (
                :user_id, :label, :recipient_name, :recipient_phone,
                :zip, :address_base, :address_detail, :is_default, :created_at, :updated_at
            )',
            [
                'user_id' => (int) $data['user_id'],
                'label' => (string) $data['label'],
                'recipient_name' => (string) $data['recipient_name'],
                'recipient_phone' => (string) $data['recipient_phone'],
                'zip' => (string) $data['zip'],
                'address_base' => (string) $data['address_base'],
                'address_detail' => (string) ($data['address_detail'] ?? ''),
                'is_default' => !empty($data['is_default']) ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        return (int) $this->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function updateForUser(int $id, int $userId, array $data): void
    {
        $this->execute(
            'UPDATE user_shipping_addresses SET
                label = :label,
                recipient_name = :recipient_name,
                recipient_phone = :recipient_phone,
                zip = :zip,
                address_base = :address_base,
                address_detail = :address_detail,
                is_default = :is_default,
                updated_at = :updated_at
             WHERE id = :id AND user_id = :uid',
            [
                'id' => $id,
                'uid' => $userId,
                'label' => (string) $data['label'],
                'recipient_name' => (string) $data['recipient_name'],
                'recipient_phone' => (string) $data['recipient_phone'],
                'zip' => (string) $data['zip'],
                'address_base' => (string) $data['address_base'],
                'address_detail' => (string) ($data['address_detail'] ?? ''),
                'is_default' => !empty($data['is_default']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public function deleteForUser(int $id, int $userId): void
    {
        $this->execute(
            'DELETE FROM user_shipping_addresses WHERE id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => $userId]
        );
    }

    public function clearDefault(int $userId): void
    {
        $this->execute(
            'UPDATE user_shipping_addresses SET is_default = 0 WHERE user_id = :uid',
            ['uid' => $userId]
        );
    }

    public function setDefault(int $id, int $userId): void
    {
        $this->clearDefault($userId);
        $this->execute(
            'UPDATE user_shipping_addresses SET is_default = 1, updated_at = :updated_at
             WHERE id = :id AND user_id = :uid',
            ['id' => $id, 'uid' => $userId, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }
}
