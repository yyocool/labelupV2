<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class AdminAlertRepository extends BaseModel
{
    public function cursor(int $adminUserId): array
    {
        $row = $this->fetchOne(
            'SELECT * FROM admin_alert_cursors WHERE admin_user_id = :uid LIMIT 1',
            ['uid' => $adminUserId]
        );
        return [
            'last_seen_order_id' => (int) ($row['last_seen_order_id'] ?? 0),
            'last_seen_inquiry_id' => (int) ($row['last_seen_inquiry_id'] ?? 0),
        ];
    }

    public function saveCursor(int $adminUserId, int $orderId, int $inquiryId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO admin_alert_cursors (admin_user_id, last_seen_order_id, last_seen_inquiry_id, updated_at)
             VALUES (:uid, :oid, :iid, :now)
             ON DUPLICATE KEY UPDATE
                last_seen_order_id = GREATEST(last_seen_order_id, :oid2),
                last_seen_inquiry_id = GREATEST(last_seen_inquiry_id, :iid2),
                updated_at = :now2',
            [
                'uid' => $adminUserId,
                'oid' => $orderId,
                'iid' => $inquiryId,
                'now' => $now,
                'oid2' => $orderId,
                'iid2' => $inquiryId,
                'now2' => $now,
            ]
        );
    }

    public function countOrdersAfter(int $id): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM shop_orders WHERE id > :id',
            ['id' => $id]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function latestOrdersAfter(int $id, int $limit = 5): array
    {
        $limit = max(1, min(10, $limit));
        return $this->fetchAll(
            "SELECT id, order_no, customer_name, total_amount, status, created_at
             FROM shop_orders WHERE id > :id ORDER BY id DESC LIMIT {$limit}",
            ['id' => $id]
        );
    }

    public function maxOrderId(): int
    {
        $row = $this->fetchOne('SELECT MAX(id) AS mid FROM shop_orders');
        return (int) ($row['mid'] ?? 0);
    }

    public function countInquiriesAfter(int $id): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM user_inquiries WHERE id > :id',
            ['id' => $id]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function latestInquiriesAfter(int $id, int $limit = 5): array
    {
        $limit = max(1, min(10, $limit));
        return $this->fetchAll(
            "SELECT id, name, email, subject, status, created_at
             FROM user_inquiries WHERE id > :id ORDER BY id DESC LIMIT {$limit}",
            ['id' => $id]
        );
    }

    public function maxInquiryId(): int
    {
        $row = $this->fetchOne('SELECT MAX(id) AS mid FROM user_inquiries');
        return (int) ($row['mid'] ?? 0);
    }
}
