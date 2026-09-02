<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class UserRepository extends BaseModel
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT u.*, p.name, p.phone, p.company, p.avatar, p.locale,
                    g.name AS grade_name, g.slug AS grade_slug, g.color AS grade_color, g.description AS grade_description
             FROM users u
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             LEFT JOIN member_grades g ON g.id = u.grade_id
             WHERE u.id = :id AND u.deleted_at IS NULL LIMIT 1',
            ['id' => $id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1',
            ['email' => $email]
        );
    }

    public function findByNameAndPhone(string $name, string $phoneDigits): ?array
    {
        return $this->fetchOne(
            'SELECT u.id, u.email, p.name, p.phone
             FROM users u
             INNER JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE u.deleted_at IS NULL
               AND u.status = :status
               AND p.name = :name
               AND REPLACE(REPLACE(REPLACE(REPLACE(p.phone, "-", ""), " ", ""), ".", ""), "+", "") = :phone
             LIMIT 1',
            ['status' => 'active', 'name' => $name, 'phone' => $phoneDigits]
        );
    }

    public function findByEmailAndName(string $email, string $name): ?array
    {
        return $this->fetchOne(
            'SELECT u.*
             FROM users u
             INNER JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE u.deleted_at IS NULL
               AND u.email = :email
               AND p.name = :name
             LIMIT 1',
            ['email' => $email, 'name' => $name]
        );
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email AND deleted_at IS NULL';
        $params = ['email' => $email];
        if ($excludeUserId) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeUserId;
        }
        return (bool) $this->fetchOne($sql . ' LIMIT 1', $params);
    }

    public function create(string $email, string $passwordHash, string $role = 'member'): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO users (email, password_hash, role, status, created_at, updated_at)
             VALUES (:email, :password_hash, :role, :status, :created_at, :updated_at)',
            [
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->execute(
            'UPDATE users SET password_hash = :hash, updated_at = :now WHERE id = :id AND deleted_at IS NULL',
            ['hash' => $passwordHash, 'now' => date('Y-m-d H:i:s'), 'id' => $userId]
        );
    }

    public function updateLastLogin(int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'UPDATE users SET last_login_at = :last_login, updated_at = :updated WHERE id = :id',
            ['last_login' => $now, 'updated' => $now, 'id' => $userId]
        );
    }

    public function softDelete(int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'UPDATE users SET status = :status, deleted_at = :deleted, updated_at = :updated WHERE id = :id',
            ['status' => 'withdrawn', 'deleted' => $now, 'updated' => $now, 'id' => $userId]
        );
    }

    public function countActive(): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM users WHERE deleted_at IS NULL AND status = :status',
            ['status' => 'active']
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function countByRole(string $role): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM users WHERE deleted_at IS NULL AND role = :role',
            ['role' => $role]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function countRegisteredSince(string $since): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM users WHERE deleted_at IS NULL AND created_at >= :since',
            ['since' => $since]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function listForAdmin(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $params = [];
        $where = 'u.deleted_at IS NULL';

        if ($search !== '') {
            $where .= ' AND (u.email LIKE :search OR p.name LIKE :search OR p.company LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql = "SELECT u.id, u.email, u.role, u.status, u.last_login_at, u.created_at,
                       p.name, p.phone, p.company
                FROM users u
                LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
                WHERE {$where}
                ORDER BY u.id DESC
                LIMIT {$perPage} OFFSET {$offset}";

        return $this->fetchAll($sql, $params);
    }

    public function countForAdmin(string $search = ''): int
    {
        $params = [];
        $where = 'u.deleted_at IS NULL';

        if ($search !== '') {
            $where .= ' AND (u.email LIKE :search OR p.name LIKE :search OR p.company LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM users u
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE {$where}",
            $params
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function updateRole(int $userId, string $role): void
    {
        $this->execute(
            'UPDATE users SET role = :role, updated_at = :now WHERE id = :id AND deleted_at IS NULL',
            ['role' => $role, 'now' => date('Y-m-d H:i:s'), 'id' => $userId]
        );
    }

    public function updateStatus(int $userId, string $status): void
    {
        $this->execute(
            'UPDATE users SET status = :status, updated_at = :now WHERE id = :id AND deleted_at IS NULL',
            ['status' => $status, 'now' => date('Y-m-d H:i:s'), 'id' => $userId]
        );
    }

    public function setSuperAdmin(int $userId, bool $isSuper): void
    {
        $this->execute(
            'UPDATE users SET is_super_admin = :flag, updated_at = :now WHERE id = :id AND deleted_at IS NULL',
            ['flag' => $isSuper ? 1 : 0, 'now' => date('Y-m-d H:i:s'), 'id' => $userId]
        );
    }

    public function countSuperAdmins(?int $exceptUserId = null): int
    {
        $sql = "SELECT COUNT(*) AS cnt FROM users
                WHERE deleted_at IS NULL AND role = 'admin' AND is_super_admin = 1 AND status = 'active'";
        $params = [];
        if ($exceptUserId) {
            $sql .= ' AND id != :except_id';
            $params['except_id'] = $exceptUserId;
        }
        $row = $this->fetchOne($sql, $params);
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function listAdmins(): array
    {
        return $this->fetchAll(
            "SELECT u.id, u.email, u.role, u.status, u.is_super_admin, u.last_login_at, u.created_at,
                    p.name, p.phone
             FROM users u
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE u.deleted_at IS NULL AND u.role = 'admin'
             ORDER BY u.is_super_admin DESC, u.id ASC"
        );
    }
}
