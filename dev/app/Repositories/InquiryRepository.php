<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class InquiryRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function allForAdmin(?string $status = null): array
    {
        if ($status) {
            return $this->fetchAll(
                'SELECT * FROM user_inquiries WHERE status = :status ORDER BY id DESC',
                ['status' => $status]
            );
        }
        return $this->fetchAll('SELECT * FROM user_inquiries ORDER BY id DESC');
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM user_inquiries WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO user_inquiries (user_id, name, email, subject, content, status, created_at, updated_at)
             VALUES (:user_id, :name, :email, :subject, :content, :status, :created_at, :updated_at)',
            [
                'user_id' => !empty($data['user_id']) ? (int) $data['user_id'] : null,
                'name' => trim((string) ($data['name'] ?? '')),
                'email' => trim((string) ($data['email'] ?? '')),
                'subject' => trim((string) ($data['subject'] ?? '')),
                'content' => trim((string) ($data['content'] ?? '')),
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $memo = null): void
    {
        $this->execute(
            'UPDATE user_inquiries SET status = :status, admin_memo = :memo, updated_at = :now WHERE id = :id',
            [
                'status' => $status,
                'memo' => $memo,
                'now' => date('Y-m-d H:i:s'),
                'id' => $id,
            ]
        );
    }
}
