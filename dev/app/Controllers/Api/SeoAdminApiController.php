<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\SeoService;
use RuntimeException;

final class SeoAdminApiController extends BaseController
{
    private AuthService $auth;
    private SeoService $seo;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->seo = new SeoService();
    }

    public function saveSeo(): never
    {
        $this->guard();
        try {
            $this->seo->saveSeo(request_json());
            $this->jsonSuccess($this->seo->adminSeoPayload(), 'SEO 설정을 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function savePage(): never
    {
        $this->guard();
        try {
            $this->seo->savePage(request_json());
            $this->jsonSuccess($this->seo->adminSeoPayload(), '페이지 SEO를 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function saveMarketing(): never
    {
        $this->guard();
        try {
            $this->seo->saveMarketing(request_json());
            $this->jsonSuccess($this->seo->adminMarketingPayload(), '광고 스크립트를 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function saveFile(): never
    {
        $this->guard();
        $data = request_json();
        try {
            $id = $this->seo->saveFile((string) ($data['filename'] ?? ''), (string) ($data['content'] ?? ''));
            $this->jsonSuccess(['id' => $id, 'files' => $this->seo->files()], '인증 파일을 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function deleteFile(): never
    {
        $this->guard();
        try {
            $this->seo->deleteFile((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(['files' => $this->seo->files()], '파일을 삭제했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
