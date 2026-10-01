<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\SiteModeService;

final class SiteRuntimeApiController extends BaseController
{
    public function runtime(): never
    {
        $cfg = (new SiteModeService())->publicConfig();
        // 유지보수 중에도 관리자 UI/클라이언트가 모드를 알 수 있게 최소 정보만 공개
        $this->jsonSuccess([
            'mode' => $cfg['mode'],
            'show_ai_usage' => $cfg['show_ai_usage'],
            'show_ai_debug' => $cfg['show_ai_debug'],
        ]);
    }
}
