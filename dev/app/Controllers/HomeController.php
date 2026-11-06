<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AiExamplePromptService;
use App\Services\AuthService;
use App\Services\EditorWorkspaceService;
use App\Services\EventPopupService;
use App\Services\HomeHeroService;
use App\Services\HomeMidBannerService;
use App\Services\ShopService;
use App\Services\SiteModeService;

final class HomeController extends BaseController
{
    /** 편집기(정적 Blazor)가 모바일 ☰ 에서 메인과 똑같은 사이드바를 띄우도록 조각만 내려준다. */
    public function editorSiteNav(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, private');
        $this->render('home/partials/editor-site-nav', [
            'authUser' => (new AuthService())->user(),
        ]);
    }

    public function index(): void
    {
        $auth = new AuthService();
        $user = $auth->user();
        $recentWorks = [];
        if ($user && !empty($user['id'])) {
            $recentWorks = (new EditorWorkspaceService())->recentForUser((int) $user['id'], 6);
        }
        $examplePrompts = [];
        try {
            $examplePrompts = (new AiExamplePromptService())->activeForSurface('home');
        } catch (\Throwable) {
            $examplePrompts = [];
        }
        $popularPapers = [];
        try {
            $popularPapers = (new ShopService())->popularPapersForHome(12);
        } catch (\Throwable) {
            $popularPapers = [];
        }
        $midBanners = [];
        try {
            $midBanners = (new HomeMidBannerService())->slidesForHome();
        } catch (\Throwable) {
            $midBanners = [];
        }
        $siteRuntime = (new SiteModeService())->publicConfig();
        $this->render('home/index', [
            'pageTitle' => 'labelup — 라벨업',
            'year' => (int) date('Y'),
            'authUser' => $user,
            'activeNav' => 'home',
            'heroSlides' => (new HomeHeroService())->slidesForHome(),
            'eventPopups' => (new EventPopupService())->activeForSite(),
            'recentWorks' => $recentWorks,
            'examplePrompts' => $examplePrompts,
            'popularPapers' => $popularPapers,
            'midBanners' => $midBanners,
            'siteRuntime' => $siteRuntime,
        ]);
    }
}
