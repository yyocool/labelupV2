<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\MemberGradeService;

final class MemberGradeAdminController extends BaseController
{
    private AuthService $auth;
    private MemberGradeService $grades;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->grades = new MemberGradeService();
    }

    public function index(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
        view('admin/layout', [
            'contentTemplate' => 'admin/member-grades',
            'pageTitle' => '설정 › 회원등급 설정 — 라벨업 관리자',
            'activeMenu' => 'settings-member-grades',
            'menuGroup' => 'settings',
            'crumbTitle' => '설정 › 회원등급 설정',
            'user' => $this->auth->admin(),
            'grades' => $this->grades->listAll(),
        ]);
    }
}
