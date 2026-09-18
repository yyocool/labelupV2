<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\QaReviewService;
use RuntimeException;

final class QaReviewApiController extends BaseController
{
    private AuthService $auth;
    private QaReviewService $qa;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->qa = new QaReviewService();
    }

    public function save(): never
    {
        (new AuthMiddleware($this->auth))->handle(true);
        try {
            $saved = $this->qa->saveItem(request_json(), $this->auth->adminId());
            $this->jsonSuccess($saved, '저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function uploadImage(): never
    {
        (new AuthMiddleware($this->auth))->handle(true);
        try {
            $file = $_FILES['image'] ?? null;
            if (!is_array($file)) {
                throw new RuntimeException('이미지 파일이 필요합니다.');
            }
            $uploaded = $this->qa->uploadImage($file);
            $this->jsonSuccess($uploaded, '업로드되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
