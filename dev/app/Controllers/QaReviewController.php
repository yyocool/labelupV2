<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\QaReviewService;

final class QaReviewController extends BaseController
{
    private AuthService $auth;
    private QaReviewService $qa;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->qa = new QaReviewService();
    }

    public function index(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        $sheet = $this->qa->sheet();
        $this->render('admin/qa-review', [
            'pageTitle' => '기능 검수 시트 — 라벨업',
            'user' => $this->auth->admin(),
            'items' => $sheet['items'],
            'summary' => $sheet['summary'],
            'statuses' => $sheet['statuses'],
            'saveUrl' => url('api/admin/qa-review/save'),
            'uploadUrl' => url('api/admin/qa-review/upload-image'),
        ]);
    }
}
