<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\MigrationService;
use App\Services\UserService;

final class SystemController extends BaseController
{
    public function migrate(): never
    {
        if (app_config('environment') === 'remote' && !APP_DEBUG) {
            $this->jsonError('Migration endpoint is disabled in production.', null, 403);
        }

        try {
            $service = new MigrationService();
            $executed = $service->runPending();
            if (count($executed) > 0) {
                (new UserService())->ensureAdminExists();
            }
            $this->jsonSuccess(
                ['executed' => $executed],
                count($executed) ? 'migrations applied' : 'no pending migrations'
            );
        } catch (\Throwable $e) {
            $this->jsonError(APP_DEBUG ? $e->getMessage() : 'Migration failed', null, 500);
        }
    }
}
