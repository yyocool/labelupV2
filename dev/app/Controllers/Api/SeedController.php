<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\UserService;

final class SeedController extends BaseController
{
    public function admin(): never
    {
        if (app_config('environment') === 'remote' && !APP_DEBUG) {
            $this->jsonError('Seed endpoint is disabled.', null, 403);
        }
        (new UserService())->ensureAdminExists();
        $this->jsonSuccess(null, 'admin user ensured');
    }
}
