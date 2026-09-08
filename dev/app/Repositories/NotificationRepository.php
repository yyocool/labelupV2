<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class NotificationRepository extends BaseModel
{
    public function create(array $row): int
    {
        $this->execute(
            'INSERT INTO user_notifications
                (user_id, type, title, body, link_url, ref_type, ref_id, is_read, created_at)
             VALUES
                (:uid, :type, :title, :body, :link, :rtype, :rid, 0, :now)',
            [
                'uid' => (int) $row['user_id'],
                'type' => (string) $row['type'],
                'title' => (string) $row['title'],
                'body' => (string) ($row['body'] ?? ''),
                'link' => (string) ($row['link_url'] ?? ''),
                'rtype' => (string) ($row['ref_type'] ?? ''),
                'rid' => (string) ($row['ref_id'] ?? ''),
                'now' => date('Y-m-d H:i:s'),
            ]
        );
        return (int) $this->lastInsertId();
    }

    /** @return array{items: array<int, array<string, mixed>>, total: int, unread: int} */
    public function listForUser(int $userId, int $limit = 30, int $offset = 0): array
    {
        $items = $this->fetchAll(
            'SELECT id, type, title, body, link_url, ref_type, ref_id, is_read, created_at
             FROM user_notifications
             WHERE user_id = :uid
             ORDER BY id DESC
             LIMIT ' . max(1, min(50, $limit)) . ' OFFSET ' . max(0, $offset),
            ['uid' => $userId]
        );
        $totalRow = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM user_notifications WHERE user_id = :uid',
            ['uid' => $userId]
        );
        $unreadRow = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM user_notifications WHERE user_id = :uid AND is_read = 0',
            ['uid' => $userId]
        );
        return [
            'items' => $items ?: [],
            'total' => (int) ($totalRow['c'] ?? 0),
            'unread' => (int) ($unreadRow['c'] ?? 0),
        ];
    }

    public function unreadCount(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM user_notifications WHERE user_id = :uid AND is_read = 0',
            ['uid' => $userId]
        );
        return (int) ($row['c'] ?? 0);
    }

    public function markRead(int $userId, ?int $id = null): int
    {
        if ($id !== null && $id > 0) {
            return $this->execute(
                'UPDATE user_notifications SET is_read = 1
                 WHERE user_id = :uid AND id = :id AND is_read = 0',
                ['uid' => $userId, 'id' => $id]
            );
        }
        return $this->execute(
            'UPDATE user_notifications SET is_read = 1
             WHERE user_id = :uid AND is_read = 0',
            ['uid' => $userId]
        );
    }

    public function ensurePrefs(int $userId): array
    {
        $row = $this->fetchOne(
            'SELECT * FROM user_notification_prefs WHERE user_id = :uid LIMIT 1',
            ['uid' => $userId]
        );
        if ($row) {
            return $row;
        }
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO user_notification_prefs
                (user_id, pref_welcome, pref_credit_low, pref_credit_change,
                 pref_order_placed, pref_order_status, pref_system, low_credit_threshold, updated_at)
             VALUES
                (:uid, 1, 1, 1, 1, 1, 1, 100, :now)',
            ['uid' => $userId, 'now' => $now]
        );
        return $this->fetchOne(
            'SELECT * FROM user_notification_prefs WHERE user_id = :uid LIMIT 1',
            ['uid' => $userId]
        ) ?: [
            'user_id' => $userId,
            'pref_welcome' => 1,
            'pref_credit_low' => 1,
            'pref_credit_change' => 1,
            'pref_order_placed' => 1,
            'pref_order_status' => 1,
            'pref_system' => 1,
            'low_credit_threshold' => 100,
            'updated_at' => $now,
        ];
    }

    public function savePrefs(int $userId, array $data): array
    {
        $this->ensurePrefs($userId);
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'UPDATE user_notification_prefs SET
                pref_welcome = :w,
                pref_credit_low = :cl,
                pref_credit_change = :cc,
                pref_order_placed = :op,
                pref_order_status = :os,
                pref_system = :sys,
                low_credit_threshold = :th,
                updated_at = :now
             WHERE user_id = :uid',
            [
                'w' => !empty($data['pref_welcome']) ? 1 : 0,
                'cl' => !empty($data['pref_credit_low']) ? 1 : 0,
                'cc' => !empty($data['pref_credit_change']) ? 1 : 0,
                'op' => !empty($data['pref_order_placed']) ? 1 : 0,
                'os' => !empty($data['pref_order_status']) ? 1 : 0,
                'sys' => !empty($data['pref_system']) ? 1 : 0,
                'th' => max(0, min(100000, (int) ($data['low_credit_threshold'] ?? 100))),
                'now' => $now,
                'uid' => $userId,
            ]
        );
        return $this->ensurePrefs($userId);
    }

    public function hasRecentOfType(int $userId, string $type, int $withinHours = 24): bool
    {
        $hours = max(1, min(168, $withinHours));
        $row = $this->fetchOne(
            "SELECT id FROM user_notifications
             WHERE user_id = :uid AND type = :type
               AND created_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
             LIMIT 1",
            ['uid' => $userId, 'type' => $type]
        );
        return $row !== null;
    }
}
