<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ErrorReportService;

final class ErrorReportAdminController extends BaseController
{
    public function index(): void
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);
        $status = trim((string) ($_GET['status'] ?? 'all'));
        $kind = trim((string) ($_GET['kind'] ?? 'all'));
        $service = new ErrorReportService();
        view('admin/layout', [
            'contentTemplate' => 'admin/error-reports',
            'pageTitle' => '운영관리 › 오류&제안관리 — 라벨업 관리자',
            'activeMenu' => 'ops-error-reports',
            'menuGroup' => 'ops',
            'crumbTitle' => '운영관리 › 오류&제안관리',
            'user' => $auth->admin(),
            'items' => $service->adminList($status, $kind),
            'filterStatus' => $status,
            'filterKind' => $kind,
            'statuses' => ErrorReportService::statuses(),
            'kinds' => ErrorReportService::kinds(),
        ]);
    }
}
