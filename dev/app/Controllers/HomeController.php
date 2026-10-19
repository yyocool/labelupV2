<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AiExamplePromptService;
use App\Services\AuthService;
use App\Services\EditorWorkspaceService;
use App\Services\EventPopupService;
use App\Services\HomeHeroService;
use App\Services\ShopService;
use App\Services\SiteModeService;

final class HomeController extends BaseController
{
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
            'siteRuntime' => $siteRuntime,
        ]);
    }
}
