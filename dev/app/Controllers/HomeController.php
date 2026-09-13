<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AiExamplePromptService;
use App\Services\AuthService;
use App\Services\ClipartService;
use App\Services\EditorWorkspaceService;
use App\Services\EventPopupService;
use App\Services\HomeHeroService;
use App\Services\LabelTemplateService;

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
        $popularTemplates = [];
        try {
            $popularTemplates = (new LabelTemplateService())->popularForHome(12);
        } catch (\Throwable) {
            $popularTemplates = [];
        }
        $popularCliparts = [];
        try {
            $popularCliparts = (new ClipartService())->popularForHome(12);
        } catch (\Throwable) {
            $popularCliparts = [];
        }
        $this->render('home/index', [
            'pageTitle' => '라벨업 LABEL UP',
            'year' => (int) date('Y'),
            'authUser' => $user,
            'activeNav' => 'home',
            'heroSlides' => (new HomeHeroService())->slidesForHome(),
            'eventPopups' => (new EventPopupService())->activeForSite(),
            'recentWorks' => $recentWorks,
            'examplePrompts' => $examplePrompts,
            'popularTemplates' => $popularTemplates,
            'popularCliparts' => $popularCliparts,
        ]);
    }
}
