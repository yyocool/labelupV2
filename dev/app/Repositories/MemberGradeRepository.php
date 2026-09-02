<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class MemberGradeRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function all(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->fetchAll(
            "SELECT * FROM member_grades {$where} ORDER BY sort_order ASC, id ASC"
        );
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return $this->fetchOne('SELECT * FROM member_grades WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findBySlug(string $slug, ?int $exceptId = null): ?array
    {
        $sql = 'SELECT * FROM member_grades WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId) {
            $sql .= ' AND id != :except_id';
            $params['except_id'] = $exceptId;
        }
        return $this->fetchOne($sql . ' LIMIT 1', $params);
    }

    public function defaultGrade(): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM member_grades WHERE is_default = 1 AND is_active = 1 ORDER BY id ASC LIMIT 1'
        ) ?? $this->fetchOne('SELECT * FROM member_grades WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 1');
    }

    public function save(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'name' => trim((string) ($data['name'] ?? '')),
            'slug' => trim((string) ($data['slug'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'color' => trim((string) ($data['color'] ?? '#7B2D3E')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_default' => !empty($data['is_default']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'now' => $now,
        ];
        if ($id > 0) {
            $this->execute(
                'UPDATE member_grades
                 SET name=:name, slug=:slug, description=:description, color=:color,
                     sort_order=:sort_order, is_default=:is_default, is_active=:is_active, updated_at=:now
                 WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }
        $this->execute(
            'INSERT INTO member_grades (name, slug, description, color, sort_order, is_default, is_active, created_at, updated_at)
             VALUES (:name, :slug, :description, :color, :sort_order, :is_default, :is_active, :now, :now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM member_grades WHERE id = :id', ['id' => $id]);
    }

    public function clearDefault(?int $exceptId = null): void
    {
        if ($exceptId) {
            $this->execute(
                'UPDATE member_grades SET is_default = 0, updated_at = :now WHERE id != :id',
                ['now' => date('Y-m-d H:i:s'), 'id' => $exceptId]
            );
            return;
        }
        $this->execute(
            'UPDATE member_grades SET is_default = 0, updated_at = :now',
            ['now' => date('Y-m-d H:i:s')]
        );
    }

    public function countUsers(int $gradeId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM users WHERE grade_id = :id AND deleted_at IS NULL',
            ['id' => $gradeId]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function reassignUsers(int $fromGradeId, int $toGradeId): void
    {
        $this->execute(
            'UPDATE users SET grade_id = :to_id, updated_at = :now WHERE grade_id = :from_id AND deleted_at IS NULL',
            ['to_id' => $toGradeId, 'from_id' => $fromGradeId, 'now' => date('Y-m-d H:i:s')]
        );
    }

    public function assignUser(int $userId, int $gradeId): void
    {
        $this->execute(
            'UPDATE users SET grade_id = :grade_id, updated_at = :now WHERE id = :id AND deleted_at IS NULL',
            ['grade_id' => $gradeId, 'now' => date('Y-m-d H:i:s'), 'id' => $userId]
        );
    }

    public function forUser(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        return $this->fetchOne(
            'SELECT g.id, g.name, g.slug, g.description, g.color, g.is_default, g.is_active
             FROM users u
             INNER JOIN member_grades g ON g.id = u.grade_id
             WHERE u.id = :id AND u.deleted_at IS NULL
             LIMIT 1',
            ['id' => $userId]
        );
    }
}
