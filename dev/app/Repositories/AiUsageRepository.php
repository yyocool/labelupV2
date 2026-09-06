<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class AiUsageRepository extends BaseModel
{
    public function insert(array $data): int
    {
        $params = [
            'user_id' => (int) ($data['user_id'] ?? 0),
            'surface' => (string) ($data['surface'] ?? 'unknown'),
            'intent' => $data['intent'] ?? null,
            'model' => $data['model'] ?? null,
            'prompt_tokens' => $data['prompt_tokens'] ?? null,
            'completion_tokens' => $data['completion_tokens'] ?? null,
            'total_tokens' => $data['total_tokens'] ?? null,
            'has_image' => (int) !empty($data['has_image']),
            'clipart_id' => !empty($data['clipart_id']) ? (int) $data['clipart_id'] : null,
            'status' => (string) ($data['status'] ?? 'ok'),
            'error_message' => $data['error_message'] ?? null,
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
        ];

        if ($this->hasCostColumns()) {
            $this->execute(
                'INSERT INTO ai_usage_logs
                 (user_id, surface, intent, agent, difficulty, model, prompt_tokens, completion_tokens, total_tokens,
                  cost_usd, cost_krw, has_image, clipart_id, status, error_message, created_at)
                 VALUES
                 (:user_id, :surface, :intent, :agent, :difficulty, :model, :prompt_tokens, :completion_tokens, :total_tokens,
                  :cost_usd, :cost_krw, :has_image, :clipart_id, :status, :error_message, :created_at)',
                $params + [
                    'agent' => $data['agent'] ?? null,
                    'difficulty' => $data['difficulty'] ?? null,
                    'cost_usd' => $data['cost_usd'] ?? null,
                    'cost_krw' => $data['cost_krw'] ?? null,
                ]
            );
        } else {
            $this->execute(
                'INSERT INTO ai_usage_logs
                 (user_id, surface, intent, model, prompt_tokens, completion_tokens, total_tokens,
                  has_image, clipart_id, status, error_message, created_at)
                 VALUES
                 (:user_id, :surface, :intent, :model, :prompt_tokens, :completion_tokens, :total_tokens,
                  :has_image, :clipart_id, :status, :error_message, :created_at)',
                $params
            );
        }

        return (int) $this->lastInsertId();
    }

    /** @return array<string, mixed> */
    public function summary(?string $from = null): array
    {
        $where = $from ? 'WHERE created_at >= :from' : '';
        $params = $from ? ['from' => $from] : [];
        $costSql = $this->hasCostColumns()
            ? 'SUM(IFNULL(cost_krw, 0)) AS cost_krw, SUM(IFNULL(cost_usd, 0)) AS cost_usd'
            : '0 AS cost_krw, 0 AS cost_usd';
        $row = $this->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'ok' THEN 1 ELSE 0 END) AS ok_count,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) AS error_count,
                SUM(CASE WHEN intent = 'chat' THEN 1 ELSE 0 END) AS chat_count,
                SUM(CASE WHEN intent = 'recommend_product' THEN 1 ELSE 0 END) AS product_count,
                SUM(CASE WHEN intent = 'generate_clipart' THEN 1 ELSE 0 END) AS clipart_count,
                SUM(CASE WHEN intent = 'generate_template' THEN 1 ELSE 0 END) AS template_count,
                SUM(CASE WHEN intent = 'generate_data_template' THEN 1 ELSE 0 END) AS data_count,
                SUM(CASE WHEN surface = 'home' THEN 1 ELSE 0 END) AS home_count,
                SUM(CASE WHEN surface = 'editor' THEN 1 ELSE 0 END) AS editor_count,
                SUM(CASE WHEN has_image = 1 THEN 1 ELSE 0 END) AS image_count,
                SUM(IFNULL(total_tokens, 0)) AS tokens,
                {$costSql},
                COUNT(DISTINCT user_id) AS users
             FROM ai_usage_logs {$where}",
            $params
        );
        return $row ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function daily(?string $from = null): array
    {
        $where = $from ? 'WHERE created_at >= :from' : '';
        $params = $from ? ['from' => $from] : [];
        $costSql = $this->hasCostColumns() ? 'SUM(IFNULL(cost_krw, 0)) AS cost_krw' : '0 AS cost_krw';
        return $this->fetchAll(
            "SELECT DATE(created_at) AS day,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'ok' THEN 1 ELSE 0 END) AS ok_count,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) AS error_count,
                    SUM(IFNULL(total_tokens, 0)) AS tokens,
                    {$costSql}
             FROM ai_usage_logs {$where}
             GROUP BY DATE(created_at)
             ORDER BY day DESC
             LIMIT 31",
            $params
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function recent(int $limit = 40, ?string $from = null): array
    {
        $where = $from ? 'WHERE l.created_at >= :from' : '';
        $params = $from ? ['from' => $from] : [];
        $limit = max(1, min(100, $limit));
        return $this->fetchAll(
            "SELECT l.*, u.email, p.name
             FROM ai_usage_logs l
             LEFT JOIN users u ON u.id = l.user_id
             LEFT JOIN user_profiles p ON p.user_id = l.user_id AND p.deleted_at IS NULL
             {$where}
             ORDER BY l.id DESC
             LIMIT {$limit}",
            $params
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,per_page:int,pages:int,summary:array<string,mixed>}
     */
    public function search(array $filters, int $page = 1, int $perPage = 30): array
    {
        [$where, $params] = $this->filterClause($filters);
        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $costSql = $this->hasCostColumns()
            ? 'SUM(IFNULL(l.cost_krw, 0)) AS cost_krw, SUM(IFNULL(l.cost_usd, 0)) AS cost_usd'
            : '0 AS cost_krw, 0 AS cost_usd';

        $count = $this->fetchOne(
            "SELECT COUNT(*) AS cnt,
                    SUM(IFNULL(l.total_tokens, 0)) AS tokens,
                    {$costSql},
                    SUM(CASE WHEN l.status = 'ok' THEN 1 ELSE 0 END) AS ok_count,
                    SUM(CASE WHEN l.status = 'error' THEN 1 ELSE 0 END) AS error_count
             FROM ai_usage_logs l
             LEFT JOIN users u ON u.id = l.user_id
             LEFT JOIN user_profiles p ON p.user_id = l.user_id AND p.deleted_at IS NULL
             {$where}",
            $params
        );
        $total = (int) ($count['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
            $offset = ($page - 1) * $perPage;
        }

        $items = $this->fetchAll(
            "SELECT l.*, u.email, p.name
             FROM ai_usage_logs l
             LEFT JOIN users u ON u.id = l.user_id
             LEFT JOIN user_profiles p ON p.user_id = l.user_id AND p.deleted_at IS NULL
             {$where}
             ORDER BY l.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
            'summary' => [
                'total' => $total,
                'tokens' => (int) ($count['tokens'] ?? 0),
                'cost_krw' => round((float) ($count['cost_krw'] ?? 0), 4),
                'cost_usd' => (float) ($count['cost_usd'] ?? 0),
                'ok_count' => (int) ($count['ok_count'] ?? 0),
                'error_count' => (int) ($count['error_count'] ?? 0),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function memberStats(array $filters, int $limit = 100): array
    {
        [$where, $params] = $this->filterClause($filters);
        $limit = max(10, min(200, $limit));
        $costSql = $this->hasCostColumns() ? 'SUM(IFNULL(l.cost_krw, 0)) AS cost_krw' : '0 AS cost_krw';
        return $this->fetchAll(
            "SELECT l.user_id,
                    u.email,
                    p.name,
                    COUNT(*) AS requests,
                    SUM(CASE WHEN l.status = 'ok' THEN 1 ELSE 0 END) AS ok_count,
                    SUM(CASE WHEN l.status = 'error' THEN 1 ELSE 0 END) AS error_count,
                    SUM(IFNULL(l.total_tokens, 0)) AS tokens,
                    {$costSql},
                    MAX(l.created_at) AS last_at
             FROM ai_usage_logs l
             LEFT JOIN users u ON u.id = l.user_id
             LEFT JOIN user_profiles p ON p.user_id = l.user_id AND p.deleted_at IS NULL
             {$where}
             GROUP BY l.user_id, u.email, p.name
             ORDER BY tokens DESC, requests DESC
             LIMIT {$limit}",
            $params
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function filterClause(array $filters): array
    {
        $where = [];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(u.email LIKE :q OR p.name LIKE :q OR CAST(l.user_id AS CHAR) = :q_exact OR IFNULL(l.model, \'\') LIKE :q OR IFNULL(l.error_message, \'\') LIKE :q)';
            $params['q'] = '%' . $q . '%';
            $params['q_exact'] = $q;
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 'l.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $intent = trim((string) ($filters['intent'] ?? ''));
        if ($intent !== '') {
            $where[] = 'l.intent = :intent';
            $params['intent'] = $intent;
        }

        $surface = trim((string) ($filters['surface'] ?? ''));
        if ($surface !== '' && $surface !== 'all') {
            $where[] = 'l.surface = :surface';
            $params['surface'] = $surface;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && $status !== 'all') {
            $where[] = 'l.status = :status';
            $params['status'] = $status;
        }

        $from = trim((string) ($filters['from'] ?? ''));
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where[] = 'l.created_at >= :from';
            $params['from'] = $from . ' 00:00:00';
        }

        $to = trim((string) ($filters['to'] ?? ''));
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where[] = 'l.created_at <= :to';
            $params['to'] = $to . ' 23:59:59';
        }

        $sql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));
        return [$sql, $params];
    }

    /** @return array<int, array<string, mixed>> */
    public function rowsNeedingCost(int $limit = 2000): array
    {
        if (!$this->hasCostColumns()) {
            return [];
        }
        $limit = max(1, min(5000, $limit));
        return $this->fetchAll(
            "SELECT id, intent, model, prompt_tokens, completion_tokens, total_tokens, has_image, difficulty
             FROM ai_usage_logs
             WHERE IFNULL(total_tokens, 0) > 0
               AND (cost_krw IS NULL OR cost_krw = 0)
             ORDER BY id DESC
             LIMIT {$limit}"
        );
    }

    public function updateCost(int $id, float $usd, float $krw, ?string $agent = null): void
    {
        if ($id <= 0 || !$this->hasCostColumns()) {
            return;
        }
        $this->execute(
            'UPDATE ai_usage_logs
             SET cost_usd = :usd, cost_krw = :krw, agent = IFNULL(agent, :agent)
             WHERE id = :id',
            [
                'usd' => $usd,
                'krw' => $krw,
                'agent' => $agent,
                'id' => $id,
            ]
        );
    }

    private function hasCostColumns(): bool
    {
        static $ok = null;
        if ($ok !== null) {
            return $ok;
        }
        $row = $this->fetchOne("SHOW COLUMNS FROM ai_usage_logs LIKE 'cost_krw'");
        $ok = !empty($row);
        return $ok;
    }
}
