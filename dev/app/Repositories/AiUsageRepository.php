<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class AiUsageRepository extends BaseModel
{
    public function insert(array $data): int
    {
        $this->execute(
            'INSERT INTO ai_usage_logs
             (user_id, surface, intent, model, prompt_tokens, completion_tokens, total_tokens,
              has_image, clipart_id, status, error_message, created_at)
             VALUES
             (:user_id, :surface, :intent, :model, :prompt_tokens, :completion_tokens, :total_tokens,
              :has_image, :clipart_id, :status, :error_message, :created_at)',
            [
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
            ]
        );
        return (int) $this->lastInsertId();
    }

    /** @return array<string, mixed> */
    public function summary(?string $from = null): array
    {
        $where = $from ? 'WHERE created_at >= :from' : '';
        $params = $from ? ['from' => $from] : [];
        $row = $this->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'ok' THEN 1 ELSE 0 END) AS ok_count,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) AS error_count,
                SUM(CASE WHEN intent = 'chat' THEN 1 ELSE 0 END) AS chat_count,
                SUM(CASE WHEN intent = 'recommend_product' THEN 1 ELSE 0 END) AS product_count,
                SUM(CASE WHEN intent = 'generate_clipart' THEN 1 ELSE 0 END) AS clipart_count,
                SUM(CASE WHEN surface = 'home' THEN 1 ELSE 0 END) AS home_count,
                SUM(CASE WHEN surface = 'editor' THEN 1 ELSE 0 END) AS editor_count,
                SUM(CASE WHEN has_image = 1 THEN 1 ELSE 0 END) AS image_count,
                SUM(IFNULL(total_tokens, 0)) AS tokens,
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
        return $this->fetchAll(
            "SELECT DATE(created_at) AS day,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'ok' THEN 1 ELSE 0 END) AS ok_count,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) AS error_count,
                    SUM(IFNULL(total_tokens, 0)) AS tokens
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
            "SELECT l.*, u.email
             FROM ai_usage_logs l
             LEFT JOIN users u ON u.id = l.user_id
             {$where}
             ORDER BY l.id DESC
             LIMIT {$limit}",
            $params
        );
    }
}
