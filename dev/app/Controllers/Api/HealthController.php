<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Helpers\Database;

final class HealthController extends BaseController
{
    public function index(): never
    {
        $dbStatus = 'disconnected';
        $dbMessage = '';

        try {
            Database::connection()->query('SELECT 1');
            $dbStatus = 'connected';
        } catch (\Throwable $e) {
            $dbMessage = APP_DEBUG ? $e->getMessage() : 'connection failed';
        }

        $this->jsonSuccess([
            'service' => app_config('name'),
            'version' => app_config('version'),
            'environment' => app_config('environment'),
            'database' => $dbStatus,
            'database_message' => $dbMessage,
            'timestamp' => date('c'),
        ], 'ok');
    }
}
