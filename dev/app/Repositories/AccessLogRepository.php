<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;
use PDOException;

final class AccessLogRepository extends BaseModel
{
    private static bool $ready = false;

    public function ensure(): void
    {
        if (self::$ready) {
            return;
        }
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS access_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                visited_at DATETIME NOT NULL,
                section VARCHAR(20) NOT NULL DEFAULT \'other\',
                method VARCHAR(8) NOT NULL DEFAULT \'GET\',
                path VARCHAR(255) NOT NULL,
                path_key VARCHAR(180) NOT NULL DEFAULT \'\',
                query_string VARCHAR(255) NULL,
                title VARCHAR(120) NOT NULL DEFAULT \'\',
                status_code SMALLINT UNSIGNED NULL,
                duration_ms INT UNSIGNED NULL,
                dwell_ms INT UNSIGNED NULL,
                ip VARCHAR(45) NOT NULL DEFAULT \'\',
                visitor_key CHAR(32) NOT NULL DEFAULT \'\',
                session_key CHAR(40) NOT NULL DEFAULT \'\',
                user_id BIGINT UNSIGNED NULL,
                actor_role VARCHAR(10) NOT NULL DEFAULT \'guest\',
                is_bot TINYINT(1) NOT NULL DEFAULT 0,
                device VARCHAR(12) NOT NULL DEFAULT \'desktop\',
                os_name VARCHAR(40) NOT NULL DEFAULT \'\',
                browser_name VARCHAR(40) NOT NULL DEFAULT \'\',
                referer_host VARCHAR(190) NOT NULL DEFAULT \'\',
                referer VARCHAR(255) NULL,
                utm_source VARCHAR(80) NOT NULL DEFAULT \'\',
                utm_medium VARCHAR(80) NOT NULL DEFAULT \'\',
                utm_campaign VARCHAR(120) NOT NULL DEFAULT \'\',
                lang VARCHAR(20) NOT NULL DEFAULT \'\',
                screen VARCHAR(20) NOT NULL DEFAULT \'\',
                viewport VARCHAR(20) NOT NULL DEFAULT \'\',
                timezone VARCHAR(40) NOT NULL DEFAULT \'\',
                user_agent VARCHAR(255) NULL,
                created_at DATETIME NULL,
                KEY idx_access_visited (visited_at),
                KEY idx_access_section_time (section, visited_at),
                KEY idx_access_visitor_time (visitor_key, visited_at),
                KEY idx_access_session_time (session_key, visited_at),
                KEY idx_access_user_time (user_id, visited_at),
                KEY idx_access_bot_time (is_bot, visited_at),
                KEY idx_access_actor_time (actor_role, visited_at),
                KEY idx_access_ip_time (ip, visited_at),
                KEY idx_access_path_time (path_key(60), visited_at),
                KEY idx_access_ref_time (referer_host(60), visited_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci'
        );
        self::$ready = true;
    }

    /** @param array<string, mixed> $row */
    public function insert(array $row): int
    {
        $this->ensure();
        $this->execute(
            'INSERT INTO access_logs (
                visited_at, section, method, path, path_key, query_string, title, status_code,
                duration_ms, dwell_ms, ip, visitor_key, session_key, user_id, actor_role, is_bot,
                device, os_name, browser_name, referer_host, referer, utm_source, utm_medium,
                utm_campaign, lang, screen, viewport, timezone, user_agent, created_at
            ) VALUES (
                :visited_at, :section, :method, :path, :path_key, :query_string, :title, :status_code,
                :duration_ms, :dwell_ms, :ip, :visitor_key, :session_key, :user_id, :actor_role, :is_bot,
                :device, :os_name, :browser_name, :referer_host, :referer, :utm_source, :utm_medium,
                :utm_campaign, :lang, :screen, :viewport, :timezone, :user_agent, :created_at
            )',
            $row
        );

        return (int) $this->lastInsertId();
    }

    /** @param array<string, mixed> $params */
    public function all(string $sql, array $params = []): array
    {
        $this->ensure();
        return $this->fetchAll($sql, $params);
    }

    /** @param array<string, mixed> $params */
    public function one(string $sql, array $params = []): ?array
    {
        $this->ensure();
        return $this->fetchOne($sql, $params);
    }

    /** @param array<string, mixed> $fields */
    public function updateById(int $id, array $fields): void
    {
        if ($id <= 0 || $fields === []) {
            return;
        }
        $sets = [];
        $params = ['id' => $id];
        foreach ($fields as $key => $value) {
            $sets[] = $key . ' = :' . $key;
            $params[$key] = $value;
        }
        $this->ensure();
        $this->execute('UPDATE access_logs SET ' . implode(', ', $sets) . ' WHERE id = :id', $params);
    }

    public function recentId(string $visitor, string $path, string $pathKey, string $since): int
    {
        $row = $this->one(
            'SELECT id FROM access_logs
             WHERE visitor_key = :visitor AND visited_at >= :since AND (path = :path OR path_key = :path_key)
             ORDER BY id DESC LIMIT 1',
            [
                'visitor' => $visitor,
                'since' => $since,
                'path' => $path,
                'path_key' => $pathKey,
            ]
        );

        return (int) ($row['id'] ?? 0);
    }

    public function countSinceIp(string $ip, string $since): int
    {
        $row = $this->one(
            'SELECT COUNT(*) AS c FROM access_logs WHERE ip = :ip AND visited_at >= :since',
            ['ip' => $ip, 'since' => $since]
        );

        return (int) ($row['c'] ?? 0);
    }

    public function tableExists(): bool
    {
        try {
            $this->one('SELECT id FROM access_logs LIMIT 1');
            self::$ready = true;
            return true;
        } catch (PDOException) {
            return false;
        }
    }
}
