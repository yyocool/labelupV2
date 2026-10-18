<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class ErrorReportRepository extends BaseModel
{
    public function ensureSchema(): void
    {
        $this->execute(
            "CREATE TABLE IF NOT EXISTS error_reports (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                category VARCHAR(30) NOT NULL DEFAULT 'site',
                title VARCHAR(200) NOT NULL,
                content TEXT NOT NULL,
                page_url VARCHAR(500) NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'open',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                KEY idx_error_reports_status (status),
                KEY idx_error_reports_user (user_id),
                KEY idx_error_reports_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
        $this->execute(
            "CREATE TABLE IF NOT EXISTS error_report_actions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                report_id BIGINT UNSIGNED NOT NULL,
                admin_id BIGINT UNSIGNED NULL,
                status VARCHAR(20) NOT NULL,
                result_note TEXT NULL,
                reward_credits INT NOT NULL DEFAULT 0,
                credit_tx_id BIGINT UNSIGNED NULL,
                created_at DATETIME NULL,
                KEY idx_error_report_actions_report (report_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
        $this->execute(
            "CREATE TABLE IF NOT EXISTS error_report_images (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                report_id BIGINT UNSIGNED NOT NULL,
                path VARCHAR(255) NOT NULL,
                created_at DATETIME NULL,
                KEY idx_error_report_images_report (report_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
        );
        $column = $this->fetchOne("SHOW COLUMNS FROM error_reports LIKE 'kind'");
        if (!$column) {
            $this->execute(
                "ALTER TABLE error_reports
                 ADD COLUMN kind VARCHAR(20) NOT NULL DEFAULT 'error' AFTER user_id"
            );
        }
    }

    public function countTodayByUser(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM error_reports
             WHERE user_id = :user_id AND created_at >= :start',
            ['user_id' => $userId, 'start' => date('Y-m-d 00:00:00')]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO error_reports (user_id, kind, category, title, content, page_url, status, created_at, updated_at)
             VALUES (:user_id, :kind, :category, :title, :content, :page_url, :status, :created_at, :updated_at)',
            [
                'user_id' => (int) $data['user_id'],
                'kind' => (string) ($data['kind'] ?? 'error'),
                'category' => (string) $data['category'],
                'title' => (string) $data['title'],
                'content' => (string) $data['content'],
                'page_url' => $data['page_url'] ?? null,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        return (int) $this->lastInsertId();
    }

    /** @param array<int, string> $paths */
    public function insertImages(int $reportId, array $paths): void
    {
        $now = date('Y-m-d H:i:s');
        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            $this->execute(
                'INSERT INTO error_report_images (report_id, path, created_at)
                 VALUES (:report_id, :path, :created_at)',
                ['report_id' => $reportId, 'path' => $path, 'created_at' => $now]
            );
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function images(int $reportId): array
    {
        if ($reportId <= 0) {
            return [];
        }
        return $this->fetchAll(
            'SELECT id, path FROM error_report_images WHERE report_id = :report_id ORDER BY id ASC',
            ['report_id' => $reportId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function listForAdmin(string $status, string $kind = 'all'): array
    {
        $where = [];
        $params = [];
        if ($status !== '' && $status !== 'all') {
            $where[] = 'r.status = :status';
            $params['status'] = $status;
        }
        if ($kind === 'error' || $kind === 'suggestion') {
            $where[] = 'r.kind = :kind';
            $params['kind'] = $kind;
        }
        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';
        return $this->fetchAll(
            "SELECT r.*, u.email AS user_email, COALESCE(p.name, '') AS user_name,
                    COALESCE(rew.reward_total, 0) AS reward_total,
                    (SELECT COUNT(*) FROM error_report_images i WHERE i.report_id = r.id) AS image_count
             FROM error_reports r
             INNER JOIN users u ON u.id = r.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             LEFT JOIN (
                SELECT report_id, SUM(reward_credits) AS reward_total
                FROM error_report_actions
                GROUP BY report_id
             ) rew ON rew.report_id = r.id
             {$whereSql}
             ORDER BY r.id DESC
             LIMIT 300",
            $params
        );
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return $this->fetchOne(
            "SELECT r.*, u.email AS user_email, COALESCE(p.name, '') AS user_name
             FROM error_reports r
             INNER JOIN users u ON u.id = r.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE r.id = :id
             LIMIT 1",
            ['id' => $id]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function actions(int $reportId): array
    {
        return $this->fetchAll(
            "SELECT a.*, ad.email AS admin_email, COALESCE(ap.name, '') AS admin_name
             FROM error_report_actions a
             LEFT JOIN users ad ON ad.id = a.admin_id
             LEFT JOIN user_profiles ap ON ap.user_id = ad.id AND ap.deleted_at IS NULL
             WHERE a.report_id = :report_id
             ORDER BY a.id DESC",
            ['report_id' => $reportId]
        );
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->execute(
            'UPDATE error_reports SET status = :status, updated_at = :updated_at WHERE id = :id',
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s'), 'id' => $id]
        );
    }

    /** @param array<string, mixed> $data */
    public function insertAction(array $data): int
    {
        $this->execute(
            'INSERT INTO error_report_actions
                (report_id, admin_id, status, result_note, reward_credits, credit_tx_id, created_at)
             VALUES
                (:report_id, :admin_id, :status, :result_note, :reward_credits, :credit_tx_id, :created_at)',
            [
                'report_id' => (int) $data['report_id'],
                'admin_id' => (int) $data['admin_id'],
                'status' => (string) $data['status'],
                'result_note' => (string) ($data['result_note'] ?? ''),
                'reward_credits' => (int) ($data['reward_credits'] ?? 0),
                'credit_tx_id' => $data['credit_tx_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function setActionCreditTx(int $actionId, int $txId): void
    {
        $this->execute(
            'UPDATE error_report_actions SET credit_tx_id = :tx WHERE id = :id',
            ['tx' => $txId, 'id' => $actionId]
        );
    }
}
