<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Helpers\Cors;
use App\Helpers\Logger;
use App\Services\EditorPaperCatalogService;
use Throwable;

/**
 * 편집기 용지선택 데이터 API.
 * 로컬 빌드 편집기(교차 출처)도 같은 DB를 보도록 CORS를 허용한 공개 읽기 전용 엔드포인트.
 */
final class EditorPaperApiController extends BaseController
{
    private EditorPaperCatalogService $papers;

    public function __construct()
    {
        $this->papers = new EditorPaperCatalogService();
    }

    public function index(): never
    {
        Cors::allowPublicRead();

        try {
            $catalog = $this->papers->catalog();
        } catch (Throwable $e) {
            Logger::error('편집기 용지 카탈로그 조회 실패', ['error' => $e->getMessage()]);
            $this->jsonError('용지 목록을 불러오지 못했습니다.', null, 500);
        }

        if (!headers_sent()) {
            header('Cache-Control: public, max-age=60');
        }

        $this->jsonSuccess($catalog);
    }

    public function preflight(): never
    {
        Cors::endPreflight();
    }
}
