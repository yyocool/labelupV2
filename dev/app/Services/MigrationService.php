<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MigrationModel;

final class MigrationService
{
    private MigrationModel $model;

    public function __construct()
    {
        $this->model = new MigrationModel();
    }

    public function runPending(): array
    {
        $this->model->ensureMigrationsTable();
        $applied = $this->model->getApplied();
        $dir = base_path('database/migrations');
        $files = glob($dir . '/*.sql') ?: [];
        sort($files);

        $executed = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }
            $sql = file_get_contents($file);
            if ($sql === false) {
                continue;
            }
            $this->model->runSqlFile($sql);
            $this->model->markApplied($name);
            $executed[] = $name;
        }

        return $executed;
    }
}
