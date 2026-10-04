<?php

declare(strict_types=1);

namespace App\Models;

final class MigrationModel extends BaseModel
{
    public function ensureMigrationsTable(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT UNSIGNED NOT NULL DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                deleted_at DATETIME NULL,
                UNIQUE KEY uk_migrations_name (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci'
        );
    }

    public function getApplied(): array
    {
        $this->ensureMigrationsTable();
        $rows = $this->fetchAll('SELECT migration FROM migrations WHERE deleted_at IS NULL ORDER BY id ASC');
        return array_column($rows, 'migration');
    }

    public function markApplied(string $name): void
    {
        $this->execute(
            'INSERT INTO migrations (migration, batch, created_at) VALUES (:migration, :batch, NOW())',
            ['migration' => $name, 'batch' => 1]
        );
    }

    public function runSqlFile(string $sql): void
    {
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql) ?: []));
        foreach ($statements as $statement) {
            // 앞쪽 SQL 주석(-- ...)만 제거. 주석만 있는 조각은 건너뜀.
            // \R 은 비UTF 모드에서 \x85(NEL)도 줄바꿈으로 보기 때문에
            // 한글 UTF-8 바이트(예: "션" = EC 85 98) 중간이 잘린다. 줄바꿈을 직접 나열한다.
            $lines = preg_split("/\r\n|\n|\r/", $statement) ?: [];
            $kept = [];
            foreach ($lines as $line) {
                $trim = ltrim($line);
                if ($trim === '' || str_starts_with($trim, '--')) {
                    continue;
                }
                $kept[] = $line;
            }
            $statement = trim(implode("\n", $kept));
            if ($statement === '') {
                continue;
            }
            $this->db->exec($statement);
        }
    }
}
