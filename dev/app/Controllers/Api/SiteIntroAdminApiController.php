<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\SiteIntroService;
use RuntimeException;

final class SiteIntroAdminApiController extends BaseController
{
    private AuthService $auth;
    private SiteIntroService $intro;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->intro = new SiteIntroService();
    }

    public function save(): never
    {
        $this->guard();
        try {
            $this->intro->save(request_json());
            $this->jsonSuccess(['intro' => $this->intro->get()], '인트로 설정이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function upload(): never
    {
        $this->guard();
        try {
            if (empty($_FILES['video'])) {
                throw new RuntimeException('업로드할 동영상이 없습니다.');
            }
            $stored = $this->intro->storeUploadedVideo($_FILES['video']);
            $this->jsonSuccess($stored, '동영상이 업로드되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
