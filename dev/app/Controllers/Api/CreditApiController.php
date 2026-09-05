<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\CreditService;

final class CreditApiController extends BaseController
{
    private AuthService $auth;
    private CreditService $credits;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->credits = new CreditService();
    }

    public function me(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($_GET['per_page'] ?? 20)));
        $this->jsonSuccess($this->credits->summaryForUser((int) $this->auth->id(), $page, $perPage));
    }
}
