<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$db = App\Models\BaseModel::class;
$pdo = (new class extends App\Models\BaseModel {
    public function pdo(): PDO
    {
        return $this->db;
    }
})->pdo();

$jobs = [
    ['user_editor_workspaces', 'trashed_at', 'ALTER TABLE user_editor_workspaces ADD COLUMN trashed_at DATETIME NULL AFTER updated_at'],
    ['user_editor_workspaces', 'idx_user_editor_workspaces_user_trash', 'ALTER TABLE user_editor_workspaces ADD KEY idx_user_editor_workspaces_user_trash (user_id, trashed_at)', 'index'],
    ['user_ai_cliparts', 'trashed_at', 'ALTER TABLE user_ai_cliparts ADD COLUMN trashed_at DATETIME NULL AFTER updated_at'],
    ['user_ai_cliparts', 'idx_user_ai_cliparts_user_trash', 'ALTER TABLE user_ai_cliparts ADD KEY idx_user_ai_cliparts_user_trash (user_id, trashed_at)', 'index'],
];

$done = [];
foreach ($jobs as $job) {
    [$table, $name, $sql] = $job;
    $kind = $job[3] ?? 'column';
    if ($kind === 'index') {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND INDEX_NAME = :n LIMIT 1'
        );
        $stmt->execute(['t' => $table, 'n' => $name]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :n LIMIT 1'
        );
        $stmt->execute(['t' => $table, 'n' => $name]);
    }
    if ($stmt->fetchColumn()) {
        $done[] = "skip {$table}.{$name}";
        continue;
    }
    $pdo->exec($sql);
    $done[] = "apply {$table}.{$name}";
}

echo json_encode(['ok' => true, 'done' => $done], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;
