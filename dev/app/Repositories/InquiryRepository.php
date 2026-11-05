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

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        return $this->fetchAll(
            "SELECT id, subject, content, status, answer, answered_at, created_at
             FROM user_inquiries WHERE user_id = :uid ORDER BY id DESC LIMIT {$limit}",
            ['uid' => $userId]
        );
    }

    public function saveAnswer(int $id, string $answer): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            "UPDATE user_inquiries SET answer = :answer, answered_at = :answered_at, status = 'answered', updated_at = :now WHERE id = :id",
            ['answer' => $answer, 'answered_at' => $now, 'now' => $now, 'id' => $id]
        );
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
            'UPDATE user_inquiries SET status = :status, admin_memo = COALESCE(:memo, admin_memo), updated_at = :now WHERE id = :id',
            [
                'status' => $status,
                'memo' => $memo,
                'now' => date('Y-m-d H:i:s'),
                'id' => $id,
            ]
        );
    }

    public function addLog(array $row): void
    {
        $this->execute(
            'INSERT INTO user_inquiry_logs (inquiry_id, admin_id, admin_name, action, status_from, status_to, note, created_at)
             VALUES (:inquiry_id, :admin_id, :admin_name, :action, :status_from, :status_to, :note, :created_at)',
            [
                'inquiry_id' => (int) $row['inquiry_id'],
                'admin_id' => !empty($row['admin_id']) ? (int) $row['admin_id'] : null,
                'admin_name' => mb_substr((string) ($row['admin_name'] ?? ''), 0, 100),
                'action' => (string) $row['action'],
                'status_from' => $row['status_from'] ?? null,
                'status_to' => $row['status_to'] ?? null,
                'note' => $row['note'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * @param array<int, int> $inquiryIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function logsFor(array $inquiryIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $inquiryIds))));
        if ($ids === []) {
            return [];
        }
        $rows = $this->fetchAll(
            'SELECT * FROM user_inquiry_logs WHERE inquiry_id IN (' . implode(',', $ids) . ') ORDER BY id ASC'
        );
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['inquiry_id']][] = $row;
        }
        return $grouped;
    }
}
