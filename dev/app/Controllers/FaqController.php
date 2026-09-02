<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\EventPopupService;
use App\Services\FaqService;
use App\Services\ShopService;

final class FaqController extends BaseController
{
    public function index(): void
    {
        $auth = new AuthService();
        $user = $auth->user();
        $this->render('faq/index', [
            'pageTitle' => '자주 묻는 질문 — 라벨업',
            'year' => (int) date('Y'),
            'authUser' => $user,
            'activeNav' => 'faq',
            'groups' => (new FaqService())->groupedForSite(),
            'cartCount' => (new ShopService())->cartCount(),
            'eventPopups' => (new EventPopupService())->activeForSite(),
        ]);
    }
}
