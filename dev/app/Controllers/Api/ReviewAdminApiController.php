<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ShopReviewService;
use RuntimeException;

final class ReviewAdminApiController extends BaseController
{
    private AuthService $auth;
    private ShopReviewService $reviews;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->reviews = new ShopReviewService();
    }

    public function save(): never
    {
        $this->guard();
        try {
            $id = $this->reviews->saveByAdmin(request_json());
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
        $this->jsonSuccess(['id' => $id], '리뷰가 저장되었습니다.');
    }

    public function status(): never
    {
        $this->guard();
        $body = request_json();
        try {
            $n = $this->reviews->setStatus($this->ids($body), (string) ($body['status'] ?? ''));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
        $this->jsonSuccess(['count' => $n], $n . '건의 상태를 바꿨습니다.');
    }

    public function delete(): never
    {
        $this->guard();
        try {
            $n = $this->reviews->delete($this->ids(request_json()));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
        $this->jsonSuccess(['count' => $n], $n . '건을 삭제했습니다.');
    }

    public function settings(): never
    {
        $this->guard();
        try {
            $this->reviews->setApprovalMode((string) (request_json()['approval'] ?? ''));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
        $this->jsonSuccess(['approval' => $this->reviews->approvalMode()], '리뷰 노출 설정을 저장했습니다.');
    }

    public function upload(): never
    {
        $this->guard();
        $file = $_FILES['image'] ?? null;
        if (!is_array($file)) {
            $this->jsonError('이미지를 선택해 주세요.', null, 422);
        }
        try {
            $url = $this->reviews->uploadImage($file);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
        $this->jsonSuccess(['url' => $url]);
    }

    /** @param array<string, mixed> $body
     *  @return array<int, int>
     */
    private function ids(array $body): array
    {
        $ids = $body['ids'] ?? ($body['id'] ?? []);
        return array_map('intval', is_array($ids) ? $ids : [$ids]);
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
