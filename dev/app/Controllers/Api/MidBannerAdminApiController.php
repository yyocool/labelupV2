<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\HomeMidBannerService;
use RuntimeException;

final class MidBannerAdminApiController extends BaseController
{
    private AuthService $auth;
    private HomeMidBannerService $banners;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->banners = new HomeMidBannerService();
    }

    public function save(): never
    {
        $this->guard();
        try {
            $id = $this->banners->save(request_json());
            $this->jsonSuccess(['id' => $id], '중간배너가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function upload(): never
    {
        $this->guard();
        try {
            if (empty($_FILES['image'])) {
                throw new RuntimeException('업로드할 이미지를 선택해주세요.');
            }
            $stored = $this->banners->storeUploadedImage($_FILES['image']);
            $this->jsonSuccess($stored, '이미지가 업로드되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function delete(): never
    {
        $this->guard();
        $this->banners->delete((int) (request_json()['id'] ?? 0));
        $this->jsonSuccess(null, '삭제되었습니다.');
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
