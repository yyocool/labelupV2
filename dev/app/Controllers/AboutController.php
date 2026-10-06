<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\CompanyInfoService;
use App\Services\SiteModeService;

final class AboutController extends BaseController
{
    public function index(): void
    {
        $this->render('about/index', [
            'pageTitle' => '서비스 소개 — 라벨업',
            'seoPage' => 'about',
            'year' => (int) date('Y'),
            'authUser' => (new AuthService())->user(),
            'company' => (new CompanyInfoService())->all(),
            'companyRows' => (new CompanyInfoService())->publicRows(),
            'loginUrl' => url('login'),
            'registerUrl' => url('register'),
            'kakaoLoginUrl' => url('auth/kakao'),
            'snsHold' => (new SiteModeService())->isTempOpen(),
            'snsHoldMessage' => SiteModeService::SNS_HOLD_MESSAGE,
            'shopUrl' => url('shop'),
            'editorUrl' => url('editor/'),
            'faqUrl' => url('faq'),
            'termsUrl' => url('terms'),
            'privacyUrl' => url('privacy'),
        ]);
    }
}
