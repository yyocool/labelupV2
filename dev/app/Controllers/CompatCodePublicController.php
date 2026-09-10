<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CompatCodePublicService;

final class CompatCodePublicController extends BaseController
{
    public function index(): void
    {
        $page = (new CompatCodePublicService())->pageData();
        $this->render('compat/index', [
            'pageTitle' => '라벨업 호환코드표',
            'year' => (int) date('Y'),
            'page' => $page,
        ]);
    }

    public function qrSample(): void
    {
        $targetUrl = 'https://www.labelup.co.kr/compat-codes';
        $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&margin=12&data='
            . rawurlencode($targetUrl);

        $this->render('compat/qr-sample', [
            'pageTitle' => '호환코드표 QR — 라벨업',
            'year' => (int) date('Y'),
            'targetUrl' => $targetUrl,
            'qrImg' => $qrImg,
        ]);
    }
}
