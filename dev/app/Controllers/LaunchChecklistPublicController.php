<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LaunchChecklistService;

final class LaunchChecklistPublicController extends BaseController
{
    public function index(): void
    {
        $snap = (new LaunchChecklistService())->snapshot();
        $this->render('launch-checklist/index', [
            'pageTitle' => '서비스 오픈 전 체크리스트 — 라벨업',
            'snapshot' => $snap,
            'apiUrl' => url('api/launch-checklist'),
            'resetUrl' => url('api/launch-checklist/reset'),
        ]);
    }
}
