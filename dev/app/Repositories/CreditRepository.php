<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class CreditRepository extends BaseModel
{
    public function getBalance(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT balance FROM user_credits WHERE user_id = :user_id',
            ['user_id' => $userId]
        );
        return (int) ($row['balance'] ?? 0);
    }

    public function ensureBalanceRow(int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT IGNORE INTO user_credits (user_id, balance, updated_at) VALUES (:user_id, 0, :now)',
            ['user_id' => $userId, 'now' => $now]
        );
    }

    public function setBalance(int $userId, int $balance): void
    {
        $this->ensureBalanceRow($userId);
        $this->execute(
            'UPDATE user_credits SET balance = :balance, updated_at = :now WHERE user_id = :user_id',
            ['balance' => $balance, 'now' => date('Y-m-d H:i:s'), 'user_id' => $userId]
        );
    }

    public function addTransaction(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO credit_transactions (user_id, amount, balance_after, tx_type, source, source_ref, description, admin_id, created_at)
             VALUES (:user_id, :amount, :balance_after, :tx_type, :source, :source_ref, :description, :admin_id, :created_at)',
            [
                'user_id' => (int) $data['user_id'],
                'amount' => (int) $data['amount'],
                'balance_after' => (int) $data['balance_after'],
                'tx_type' => $data['tx_type'] ?? 'earn',
                'source' => $data['source'] ?? 'system',
                'source_ref' => $data['source_ref'] ?? null,
                'description' => $data['description'] ?? '',
                'admin_id' => $data['admin_id'] ?? null,
                'created_at' => $now,
            ]
        );
        return (int) $this->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function allRewardRules(): array
    {
        return $this->fetchAll(
            'SELECT * FROM credit_reward_rules ORDER BY sort_order ASC, id ASC'
        );
    }

    public function saveRewardRule(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'code' => strtoupper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'credit_amount' => (int) $data['credit_amount'],
            'trigger_type' => (string) ($data['trigger_type'] ?? 'event'),
            'daily_limit' => ($data['daily_limit'] ?? '') !== '' ? (int) $data['daily_limit'] : null,
            'max_total_per_user' => ($data['max_total_per_user'] ?? '') !== '' ? (int) $data['max_total_per_user'] : null,
            'is_active' => (int) !empty($data['is_active']),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'now' => $now,
        ];
        if ($id > 0) {
            $this->execute(
                'UPDATE credit_reward_rules SET code=:code,name=:name,description=:description,credit_amount=:credit_amount,
                 trigger_type=:trigger_type,daily_limit=:daily_limit,max_total_per_user=:max_total_per_user,
                 is_active=:is_active,sort_order=:sort_order,updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }
        $this->execute(
            'INSERT INTO credit_reward_rules (code,name,description,credit_amount,trigger_type,daily_limit,max_total_per_user,is_active,sort_order,created_at,updated_at)
             VALUES (:code,:name,:description,:credit_amount,:trigger_type,:daily_limit,:max_total_per_user,:is_active,:sort_order,:now,:now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function deleteRewardRule(int $id): void
    {
        $this->execute('DELETE FROM credit_reward_rules WHERE id = :id', ['id' => $id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function allPurchaseProducts(): array
    {
        return $this->fetchAll('SELECT * FROM purchase_credit_products ORDER BY id DESC');
    }

    public function savePurchaseProduct(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'name' => trim((string) $data['name']),
            'sku' => trim((string) $data['sku']),
            'credit_amount' => (int) $data['credit_amount'],
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active' => (int) !empty($data['is_active']),
            'now' => $now,
        ];
        if ($id > 0) {
            $this->execute(
                'UPDATE purchase_credit_products SET name=:name,sku=:sku,credit_amount=:credit_amount,description=:description,is_active=:is_active,updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }
        $this->execute(
            'INSERT INTO purchase_credit_products (name,sku,credit_amount,description,is_active,created_at,updated_at)
             VALUES (:name,:sku,:credit_amount,:description,:is_active,:now,:now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function deletePurchaseProduct(int $id): void
    {
        $this->execute('DELETE FROM purchase_credit_products WHERE id = :id', ['id' => $id]);
    }

    /** @return array<int, array<string, mixed>> */
    public function redemptionHistory(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'c.is_redeemed = 1';
        if ($search !== '') {
            $where .= ' AND (c.code LIKE :search OR p.name LIKE :search OR p.sku LIKE :search OR u.email LIKE :search OR pr.name LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        $items = $this->fetchAll(
            "SELECT c.*, p.name AS product_name, p.sku, p.credit_amount,
                    u.email AS user_email, pr.name AS user_name
             FROM purchase_credit_codes c
             INNER JOIN purchase_credit_products p ON p.id = c.product_id
             LEFT JOIN users u ON u.id = c.redeemed_by_user_id
             LEFT JOIN user_profiles pr ON pr.user_id = u.id AND pr.deleted_at IS NULL
             WHERE {$where}
             ORDER BY c.redeemed_at DESC, c.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM purchase_credit_codes c
             INNER JOIN purchase_credit_products p ON p.id = c.product_id
             LEFT JOIN users u ON u.id = c.redeemed_by_user_id
             LEFT JOIN user_profiles pr ON pr.user_id = u.id AND pr.deleted_at IS NULL
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** @return array<int, array<string, mixed>> */
    public function allCodes(int $productId = 0): array
    {
        $params = [];
        $where = '1=1';
        if ($productId > 0) {
            $where .= ' AND c.product_id = :product_id';
            $params['product_id'] = $productId;
        }
        return $this->fetchAll(
            "SELECT c.*, p.name AS product_name, p.sku, p.credit_amount
             FROM purchase_credit_codes c
             INNER JOIN purchase_credit_products p ON p.id = c.product_id
             WHERE {$where}
             ORDER BY c.id DESC
             LIMIT 500",
            $params
        );
    }

    public function createCodes(int $productId, array $codes, string $batchNo): int
    {
        $now = date('Y-m-d H:i:s');
        $count = 0;
        foreach ($codes as $code) {
            $code = strtoupper(trim((string) $code));
            if ($code === '') {
                continue;
            }
            try {
                $this->execute(
                    'INSERT INTO purchase_credit_codes (product_id, code, batch_no, created_at) VALUES (:product_id, :code, :batch_no, :now)',
                    ['product_id' => $productId, 'code' => $code, 'batch_no' => $batchNo, 'now' => $now]
                );
                $count++;
            } catch (\Throwable) {
                // skip duplicate
            }
        }
        return $count;
    }

    /** @return array{items: array, total: int, page: int, pages: int} */
    public function transactionsForUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $items = $this->fetchAll(
            "SELECT * FROM credit_transactions WHERE user_id = :user_id ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
            ['user_id' => $userId]
        );
        $total = (int) ($this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM credit_transactions WHERE user_id = :user_id',
            ['user_id' => $userId]
        )['cnt'] ?? 0);
        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)), 'per_page' => $perPage];
    }

    /** @return array<int, array<string, mixed>> */
    public function csLogsForUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT cs.*, a.email AS admin_email
             FROM user_cs_logs cs
             LEFT JOIN users a ON a.id = cs.admin_id
             WHERE cs.user_id = :user_id
             ORDER BY cs.id DESC',
            ['user_id' => $userId]
        );
    }

    public function saveCsLog(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'user_id' => (int) $data['user_id'],
            'admin_id' => $data['admin_id'] ?? null,
            'category' => (string) ($data['category'] ?? 'inquiry'),
            'subject' => trim((string) $data['subject']),
            'content' => trim((string) ($data['content'] ?? '')),
            'status' => (string) ($data['status'] ?? 'open'),
            'now' => $now,
        ];
        if ($id > 0) {
            $this->execute(
                'UPDATE user_cs_logs SET category=:category,subject=:subject,content=:content,status=:status,updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }
        $this->execute(
            'INSERT INTO user_cs_logs (user_id,admin_id,category,subject,content,status,created_at,updated_at)
             VALUES (:user_id,:admin_id,:category,:subject,:content,:status,:now,:now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function listUsersWithCredit(string $search = '', int $page = 1, int $perPage = 20): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        $params = [];
        $where = 'u.deleted_at IS NULL';
        if ($search !== '') {
            $where .= ' AND (u.email LIKE :search OR p.name LIKE :search OR p.company LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        $items = $this->fetchAll(
            "SELECT u.id, u.email, u.role, u.status, u.created_at, u.last_login_at,
                    p.name, p.phone, p.company, COALESCE(c.balance, 0) AS credit_balance
             FROM users u
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             LEFT JOIN user_credits c ON c.user_id = u.id
             WHERE {$where}
             ORDER BY u.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM users u
             LEFT JOIN user_profiles p ON p.user_id = u.id AND p.deleted_at IS NULL
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);
        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)), 'per_page' => $perPage];
    }
}
