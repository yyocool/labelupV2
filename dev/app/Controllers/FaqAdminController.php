<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\FaqService;

final class FaqAdminController extends BaseController
{
    private AuthService $auth;
    private FaqService $faqs;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->faqs = new FaqService();
    }

    public function index(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        view('admin/layout', [
            'contentTemplate' => 'admin/faq',
            'pageTitle' => '운영관리 › FAQ 관리 — 라벨업 관리자',
            'activeMenu' => 'ops-faq',
            'menuGroup' => 'ops',
            'crumbTitle' => '운영관리 › FAQ 관리',
            'user' => $this->auth->admin(),
            'categories' => $this->faqs->categoriesForAdmin(),
            'items' => $this->faqs->faqsForAdmin(),
            'useSummernote' => true,
        ]);
    }
}
