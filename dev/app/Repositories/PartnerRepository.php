<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class PartnerRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function list(string $q = ''): array
    {
        $sql = 'SELECT id, company_name, login_id, contact_name, phone, email, memo, status, last_login_at, created_at, updated_at
                FROM partners
                WHERE deleted_at IS NULL';
        $params = [];
        if ($q !== '') {
            $sql .= ' AND (company_name LIKE :q OR login_id LIKE :q OR contact_name LIKE :q OR phone LIKE :q OR email LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY id DESC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM partners WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $id]
        );
    }

    public function findByLoginId(string $loginId): ?array
    {
        $loginId = trim($loginId);
        if ($loginId === '') {
            return null;
        }

        return $this->fetchOne(
            'SELECT * FROM partners WHERE login_id = :login_id AND deleted_at IS NULL LIMIT 1',
            ['login_id' => $loginId]
        );
    }

    public function touchLogin(int $id): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'UPDATE partners SET last_login_at = :last_login_at, updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL',
            [
                'last_login_at' => $now,
                'updated_at' => $now,
                'id' => $id,
            ]
        );
    }

    public function loginIdExists(string $loginId, int $exceptId = 0): bool
    {
        $row = $this->fetchOne(
            'SELECT id FROM partners WHERE login_id = :login_id AND id <> :id LIMIT 1',
            ['login_id' => $loginId, 'id' => $exceptId]
        );

        return $row !== null;
    }

    /** @param array<string, mixed> $row */
    public function create(array $row): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO partners
                (company_name, login_id, password_hash, contact_name, phone, email, memo, status, created_at, updated_at)
             VALUES
                (:company_name, :login_id, :password_hash, :contact_name, :phone, :email, :memo, :status, :created_at, :updated_at)',
            [
                'company_name' => $row['company_name'],
                'login_id' => $row['login_id'],
                'password_hash' => $row['password_hash'],
                'contact_name' => $row['contact_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'memo' => $row['memo'],
                'status' => $row['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) $this->lastInsertId();
    }

    /** @param array<string, mixed> $row */
    public function update(int $id, array $row): void
    {
        $this->execute(
            'UPDATE partners SET
                company_name = :company_name,
                login_id = :login_id,
                contact_name = :contact_name,
                phone = :phone,
                email = :email,
                memo = :memo,
                status = :status,
                updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL',
            [
                'company_name' => $row['company_name'],
                'login_id' => $row['login_id'],
                'contact_name' => $row['contact_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'memo' => $row['memo'],
                'status' => $row['status'],
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $id,
            ]
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->execute(
            'UPDATE partners SET password_hash = :password_hash, updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL',
            [
                'password_hash' => $passwordHash,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $id,
            ]
        );
    }

    public function softDelete(int $id): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'UPDATE partners SET
                login_id = :login_id,
                status = :status,
                deleted_at = :deleted_at,
                updated_at = :updated_at
             WHERE id = :id AND deleted_at IS NULL',
            [
                'login_id' => 'deleted-' . $id,
                'status' => 'inactive',
                'deleted_at' => $now,
                'updated_at' => $now,
                'id' => $id,
            ]
        );
    }
}
