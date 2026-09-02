<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\EventPopupService;
use App\Services\HomeHeroService;

final class HomeController extends BaseController
{
    public function index(): void
    {
        $auth = new AuthService();
        $this->render('home/index', [
            'pageTitle' => '라벨업 LABEL UP',
            'year' => (int) date('Y'),
            'authUser' => $auth->user(),
            'heroSlides' => (new HomeHeroService())->slidesForHome(),
            'eventPopups' => (new EventPopupService())->activeForSite(),
        ]);
    }
}
