<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AdminService;
use App\Services\AuthService;
use App\Services\CreditAdminService;
use App\Services\MemberGradeService;
use App\Services\LegalDocumentService;
use App\Services\ShopAdminService;

final class AdminController extends BaseController
{
    private AuthService $auth;
    private AdminService $admin;
    private LegalDocumentService $legal;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->admin = new AdminService();
        $this->legal = new LegalDocumentService();
    }

    public function dashboard(): void
    {
        $this->requireAdmin();
        $user = $this->auth->admin();

        $this->renderAdmin('admin/dashboard', [
            'pageTitle' => '관리자 대시보드 — 라벨업',
            'activeMenu' => 'dashboard',
            'user' => $user,
            'stats' => array_merge($this->admin->dashboardStats(), (new ShopAdminService())->dashboardStats()),
            'recentLogins' => $this->admin->recentLoginLogs(),
        ]);
    }

    public function loginForm(): void
    {
        if ($this->auth->checkAdmin()) {
            redirect('/admin');
        }

        $this->render('admin/login', [
            'pageTitle' => '관리자 로그인 — 라벨업',
            'memberLoggedIn' => $this->auth->checkUser(),
        ]);
    }

    public function logout(): never
    {
        $this->auth->logoutAdmin();
        redirect('/admin/login');
    }

    public function users(): void
    {
        $this->requireAdmin();
        $user = $this->auth->admin();
        $search = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $credits = new CreditAdminService();
        $this->renderAdmin('admin/users', [
            'pageTitle' => '회원 관리 — 라벨업',
            'activeMenu' => 'users',
            'user' => $user,
            'search' => $search,
            'list' => $credits->listUsers($search, $page),
            'grants' => $credits->grantHistory(null, 1, 20),
            'grades' => (new MemberGradeService())->listActive(),
        ]);
    }

    public function userDetail(string $id): void
    {
        $this->requireAdmin();
        $user = $this->auth->admin();
        try {
            $detail = (new CreditAdminService())->userDetail((int) $id);
        } catch (\RuntimeException $e) {
            http_response_code(404);
            view('errors/404');
            return;
        }

        $this->renderAdmin('admin/user-detail', [
            'pageTitle' => '회원 상세 — 라벨업',
            'activeMenu' => 'users',
            'crumbTitle' => '운영관리 › 회원 관리 › 상세',
            'user' => $user,
            'detail' => $detail,
        ]);
    }

    public function settings(): void
    {
        $this->requireAdmin();
        $user = $this->auth->admin();

        $this->renderAdmin('admin/settings', [
            'pageTitle' => '운영설정 — 라벨업',
            'activeMenu' => 'settings',
            'menuGroup' => 'ops',
            'crumbTitle' => '운영관리 › 운영설정',
            'user' => $user,
            'documents' => $this->legal->all(),
            'useSummernote' => true,
        ]);
    }

    private function requireAdmin(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }

    private function renderAdmin(string $template, array $data = []): void
    {
        $data['contentTemplate'] = $template;
        view('admin/layout', $data);
    }
}
