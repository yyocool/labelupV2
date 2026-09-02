<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class UserAiClipartRepository extends BaseModel
{
    /**
     * @param array{user_id:int,title:string,prompt:?string,image_url:string,file_name:?string} $data
     */
    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO user_ai_cliparts (user_id, title, prompt, image_url, file_name, created_at, updated_at)
             VALUES (:user_id, :title, :prompt, :image_url, :file_name, :created_at, :updated_at)',
            [
                'user_id' => (int) $data['user_id'],
                'title' => (string) $data['title'],
                'prompt' => $data['prompt'] ?? null,
                'image_url' => (string) $data['image_url'],
                'file_name' => $data['file_name'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) $this->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function listByUser(int $userId, int $limit = 48): array
    {
        return $this->fetchAll(
            'SELECT * FROM user_ai_cliparts
             WHERE user_id = :uid AND review_status <> :rej
             ORDER BY id DESC LIMIT ' . max(1, min(120, $limit)),
            ['uid' => $userId, 'rej' => 'rejected']
        );
    }

    public function countByUser(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM user_ai_cliparts WHERE user_id = :uid AND review_status <> :rej',
            ['uid' => $userId, 'rej' => 'rejected']
        );
        return (int) ($row['cnt'] ?? 0);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT c.*, u.email, p.name AS user_name
             FROM user_ai_cliparts c
             INNER JOIN users u ON u.id = c.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE c.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @param array{q?:string,user_id?:int,status?:string,date_from?:string,date_to?:string,page?:int,per_page?:int} $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int}
     */
    public function adminList(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(48, (int) ($filters['per_page'] ?? 24)));
        [$where, $params] = $this->adminWhere($filters);

        $count = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM user_ai_cliparts c
             INNER JOIN users u ON u.id = c.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE {$where}",
            $params
        );
        $total = (int) ($count['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT c.*, u.email, p.name AS user_name
             FROM user_ai_cliparts c
             INNER JOIN users u ON u.id = c.user_id
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE {$where}
             ORDER BY c.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    /**
     * @return array{total:int,pending:int,approved:int,rejected:int,approved_month:int,users:int}
     */
    public function adminStats(): array
    {
        $row = $this->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN review_status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN review_status = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN review_status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                SUM(CASE WHEN review_status = 'approved'
                    AND reviewed_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 ELSE 0 END) AS approved_month,
                COUNT(DISTINCT user_id) AS users
             FROM user_ai_cliparts"
        ) ?? [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
            'approved_month' => (int) ($row['approved_month'] ?? 0),
            'users' => (int) ($row['users'] ?? 0),
        ];
    }

    public function updateReview(int $id, string $status, ?string $note, ?int $adminId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'UPDATE user_ai_cliparts
             SET review_status = :status, review_note = :note, reviewed_at = :at, reviewed_by = :by, updated_at = :now
             WHERE id = :id',
            [
                'status' => $status,
                'note' => $note,
                'at' => $now,
                'by' => $adminId,
                'now' => $now,
                'id' => $id,
            ]
        );
    }

    /** @param array<int, int> $ids */
    public function updateReviewMany(array $ids, string $status, ?int $adminId): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $in = implode(',', $ids);
        $this->execute(
            "UPDATE user_ai_cliparts
             SET review_status = :status, reviewed_at = :at, reviewed_by = :by, updated_at = :now
             WHERE id IN ({$in})",
            [
                'status' => $status,
                'at' => $now,
                'by' => $adminId,
                'now' => $now,
            ]
        );
        return count($ids);
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM user_ai_cliparts WHERE id = :id', ['id' => $id]);
    }

    /**
     * @param array{q?:string,user_id?:int,status?:string,date_from?:string,date_to?:string} $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function adminWhere(array $filters): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $userId = (int) ($filters['user_id'] ?? 0);
        $status = trim((string) ($filters['status'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        $where = '1=1';
        $params = [];
        if ($userId > 0) {
            $where .= ' AND c.user_id = :uid';
            $params['uid'] = $userId;
        }
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $where .= ' AND c.review_status = :st';
            $params['st'] = $status;
        }
        if ($q !== '') {
            $where .= ' AND (c.title LIKE :q1 OR c.prompt LIKE :q2 OR u.email LIKE :q3 OR p.name LIKE :q4)';
            $like = '%' . $q . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where .= ' AND c.created_at >= :df';
            $params['df'] = $dateFrom . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where .= ' AND c.created_at <= :dt';
            $params['dt'] = $dateTo . ' 23:59:59';
        }

        return [$where, $params];
    }

    public function clearUsageClipart(int $id): void
    {
        $exists = $this->fetchOne("SHOW TABLES LIKE 'ai_usage_logs'");
        if (!$exists) {
            return;
        }
        $this->execute(
            'UPDATE ai_usage_logs SET clipart_id = NULL WHERE clipart_id = :id',
            ['id' => $id]
        );
    }
}
