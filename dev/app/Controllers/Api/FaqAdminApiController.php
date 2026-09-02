<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\FaqService;
use RuntimeException;

final class FaqAdminApiController extends BaseController
{
    private AuthService $auth;
    private FaqService $faqs;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->faqs = new FaqService();
    }

    public function save(): never
    {
        $this->guard();
        try {
            $id = $this->faqs->saveFaq(request_json());
            $this->jsonSuccess(['id' => $id], 'FAQ가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function delete(): never
    {
        $this->guard();
        try {
            $this->faqs->deleteFaq((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(null, '삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function saveCategory(): never
    {
        $this->guard();
        try {
            $id = $this->faqs->saveCategory(request_json());
            $this->jsonSuccess(['id' => $id], '카테고리가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function deleteCategory(): never
    {
        $this->guard();
        try {
            $this->faqs->deleteCategory((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(null, '카테고리가 삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
