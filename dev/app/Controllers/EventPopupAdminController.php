<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\EventPopupService;

final class EventPopupAdminController extends BaseController
{
    private AuthService $auth;
    private EventPopupService $popups;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->popups = new EventPopupService();
    }

    public function index(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        view('admin/layout', [
            'contentTemplate' => 'admin/event-popups',
            'pageTitle' => '운영관리 › 이벤트 팝업관리 — 라벨업 관리자',
            'activeMenu' => 'ops-event-popups',
            'menuGroup' => 'ops',
            'crumbTitle' => '운영관리 › 이벤트 팝업관리',
            'user' => $this->auth->admin(),
            'items' => $this->popups->allForAdmin(),
            'useSummernote' => true,
        ]);
    }
}
