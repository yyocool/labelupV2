<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\AuthService;
use App\Services\ShopReviewService;
use RuntimeException;

final class ShopReviewApiController extends BaseController
{
    private AuthService $auth;
    private ShopReviewService $reviews;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->reviews = new ShopReviewService();
    }

    public function index(): never
    {
        $productId = (int) ($_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            $this->jsonError('상품을 찾을 수 없습니다.', null, 422);
        }
        try {
            $list = $this->reviews->listForProduct(
                $productId,
                (string) ($_GET['type'] ?? 'all'),
                (int) ($_GET['page'] ?? 1),
                $this->auth->id()
            );
        } catch (\Throwable) {
            $list = ['items' => [], 'total' => 0, 'page' => 1, 'has_more' => false];
        }
        $this->jsonSuccess($list + ['summary' => $this->reviews->summary($productId)]);
    }

    public function save(): never
    {
        $user = $this->requireUser();
        try {
            $result = $this->reviews->submitByUser($user, request_json());
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
        $message = $result['status'] === 'pending'
            ? '리뷰가 등록되었습니다. 관리자 확인 후 노출됩니다.'
            : '리뷰가 등록되었습니다.';
        $this->jsonSuccess($result, $message);
    }

    public function delete(): never
    {
        $user = $this->requireUser();
        try {
            $this->reviews->deleteByUser((int) $user['id'], (int) (request_json()['id'] ?? 0));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
        $this->jsonSuccess(null, '리뷰가 삭제되었습니다.');
    }

    public function upload(): never
    {
        if (!$this->auth->checkUser() && !$this->auth->checkAdmin()) {
            $this->jsonError('로그인 후 이용해 주세요.', null, 401);
        }
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

    /** @return array<string, mixed> */
    private function requireUser(): array
    {
        $user = $this->auth->user();
        if ($user === null) {
            $this->jsonError('로그인 후 리뷰를 작성할 수 있습니다.', null, 401);
        }
        return $user;
    }
}
