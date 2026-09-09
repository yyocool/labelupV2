<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class AiCreditRepository extends BaseModel
{
    /** @return array{is_enabled:int,monthly_budget:int,low_balance_threshold:int,updated_at:?string} */
    public function getConfig(): array
    {
        $this->ensureTables();
        $row = $this->fetchOne('SELECT * FROM ai_credit_config WHERE id = 1');
        if (!$row) {
            $this->execute(
                'INSERT INTO ai_credit_config (id, is_enabled, monthly_budget, low_balance_threshold, updated_at)
                 VALUES (1, 1, 0, 10, :now)',
                ['now' => date('Y-m-d H:i:s')]
            );
            $row = $this->fetchOne('SELECT * FROM ai_credit_config WHERE id = 1') ?: [];
        }
        return [
            'is_enabled' => (int) ($row['is_enabled'] ?? 1),
            'monthly_budget' => (int) ($row['monthly_budget'] ?? 0),
            'low_balance_threshold' => (int) ($row['low_balance_threshold'] ?? 10),
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    public function saveConfig(array $data): void
    {
        $this->ensureTables();
        $this->execute(
            'INSERT INTO ai_credit_config (id, is_enabled, monthly_budget, low_balance_threshold, updated_at)
             VALUES (1, :is_enabled, :monthly_budget, :low_balance_threshold, :now)
             ON DUPLICATE KEY UPDATE
               is_enabled = VALUES(is_enabled),
               monthly_budget = VALUES(monthly_budget),
               low_balance_threshold = VALUES(low_balance_threshold),
               updated_at = VALUES(updated_at)',
            [
                'is_enabled' => (int) !empty($data['is_enabled']),
                'monthly_budget' => max(0, (int) ($data['monthly_budget'] ?? 0)),
                'low_balance_threshold' => max(0, (int) ($data['low_balance_threshold'] ?? 10)),
                'now' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function allCosts(): array
    {
        $this->ensureTables();
        $this->seedDefaultsIfEmpty();
        return $this->fetchAll(
            'SELECT * FROM ai_credit_costs ORDER BY sort_order ASC, intent ASC'
        );
    }

    public function saveCost(array $data): void
    {
        $this->ensureTables();
        $intent = trim((string) ($data['intent'] ?? ''));
        if ($intent === '') {
            return;
        }
        $this->execute(
            'INSERT INTO ai_credit_costs (intent, label, credit_cost, description, is_active, sort_order, updated_at)
             VALUES (:intent, :label, :credit_cost, :description, :is_active, :sort_order, :now)
             ON DUPLICATE KEY UPDATE
               label = VALUES(label),
               credit_cost = VALUES(credit_cost),
               description = VALUES(description),
               is_active = VALUES(is_active),
               sort_order = VALUES(sort_order),
               updated_at = VALUES(updated_at)',
            [
                'intent' => $intent,
                'label' => trim((string) ($data['label'] ?? $intent)),
                'credit_cost' => max(0, (int) ($data['credit_cost'] ?? 0)),
                'description' => trim((string) ($data['description'] ?? '')),
                'is_active' => (int) !empty($data['is_active']),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'now' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function saveCosts(array $rows): void
    {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $this->saveCost($row);
        }
    }

    public function costForIntent(string $intent): ?array
    {
        $this->ensureTables();
        $this->seedDefaultsIfEmpty();
        $row = $this->fetchOne(
            'SELECT * FROM ai_credit_costs WHERE intent = :intent LIMIT 1',
            ['intent' => $intent]
        );
        return $row ?: null;
    }

    private function seedDefaultsIfEmpty(): void
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS c FROM ai_credit_costs');
        if ((int) ($row['c'] ?? 0) > 0) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $defaults = [
            ['chat', '일반 대화', 1, '라비 AI 일반 채팅/질문', 10],
            ['recommend_product', '상품 추천', 2, '라벨지·상품 추천', 20],
            ['ask_image_mode', '이미지 모드 안내', 1, '이미지 첨부 후 모드 선택 안내', 30],
            ['generate_clipart', '클립아트 생성', 15, 'AI 클립아트/일러스트 생성', 40],
            ['generate_template', '템플릿 생성', 10, '라벨 템플릿 초안 생성', 50],
            ['generate_data_template', '데이터 라벨 생성', 20, '엑셀·문서 기반 데이터 라벨 생성', 60],
        ];
        foreach ($defaults as [$intent, $label, $cost, $desc, $sort]) {
            $this->execute(
                'INSERT IGNORE INTO ai_credit_costs
                 (intent, label, credit_cost, description, is_active, sort_order, updated_at)
                 VALUES (:intent, :label, :credit_cost, :description, 1, :sort_order, :now)',
                [
                    'intent' => $intent,
                    'label' => $label,
                    'credit_cost' => $cost,
                    'description' => $desc,
                    'sort_order' => $sort,
                    'now' => $now,
                ]
            );
        }
    }

    private function ensureTables(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }
        $this->execute(
            "CREATE TABLE IF NOT EXISTS ai_credit_config (
                id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
                is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                monthly_budget INT UNSIGNED NOT NULL DEFAULT 0,
                low_balance_threshold INT UNSIGNED NOT NULL DEFAULT 10,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->execute(
            "CREATE TABLE IF NOT EXISTS ai_credit_costs (
                intent VARCHAR(64) NOT NULL PRIMARY KEY,
                label VARCHAR(100) NOT NULL,
                credit_cost INT UNSIGNED NOT NULL DEFAULT 1,
                description VARCHAR(255) NOT NULL DEFAULT '',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        try {
            $this->execute(
                "ALTER TABLE credit_transactions
                 MODIFY COLUMN source ENUM('reward','purchase_code','admin','order','system','ai') NOT NULL DEFAULT 'system'"
            );
        } catch (\Throwable) {
            // already migrated
        }
        $ready = true;
    }
}
