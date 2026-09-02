<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ShopAdminService;
use RuntimeException;

final class ShopAdminApiController extends BaseController
{
    private AuthService $auth;
    private ShopAdminService $shop;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->shop = new ShopAdminService();
    }

    public function saveCategory(): never
    {
        $this->guard();
        try {
            $id = $this->shop->saveCategory(request_json());
            $this->jsonSuccess(['id' => $id], '카테고리가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function uploadCategoryImages(): never
    {
        $this->guard();
        try {
            if (empty($_FILES['images'])) {
                throw new RuntimeException('업로드할 이미지를 선택해주세요.');
            }
            $urls = $this->shop->uploadCategoryImages($_FILES['images']);
            $this->jsonSuccess(['urls' => $urls], '이미지가 업로드되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function deleteCategory(): never
    {
        $this->guard();
        $id = (int) (request_json()['id'] ?? 0);
        try {
            $this->shop->deleteCategory($id);
            $this->jsonSuccess(null, '카테고리가 삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function saveSpec(): never
    {
        $this->guard();
        try {
            $id = $this->shop->saveSpec(request_json());
            $this->jsonSuccess(['id' => $id], '라벨 규격이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function uploadSpecImages(): never
    {
        $this->guard();
        try {
            if (empty($_FILES['images'])) {
                throw new RuntimeException('업로드할 이미지를 선택해주세요.');
            }
            $urls = $this->shop->uploadSpecImages($_FILES['images']);
            $this->jsonSuccess(['urls' => $urls], '이미지가 업로드되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function deleteSpec(): never
    {
        $this->guard();
        $id = (int) (request_json()['id'] ?? 0);
        try {
            $this->shop->deleteSpec($id);
            $this->jsonSuccess(null, '라벨 규격이 삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function saveProduct(): never
    {
        $this->guard();
        try {
            $id = $this->shop->saveProduct(request_json());
            $this->jsonSuccess(['id' => $id], '상품이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function uploadProductImages(): never
    {
        $this->guard();
        try {
            if (empty($_FILES['images'])) {
                throw new RuntimeException('업로드할 이미지를 선택해주세요.');
            }
            $urls = $this->shop->uploadProductImages($_FILES['images']);
            $this->jsonSuccess(['urls' => $urls], '이미지가 업로드되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function deleteProduct(): never
    {
        $this->guard();
        $id = (int) (request_json()['id'] ?? 0);
        try {
            $this->shop->deleteProduct($id);
            $this->jsonSuccess(null, '상품이 삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function updateOrder(): never
    {
        $this->guard();
        $payload = request_json();
        $id = (int) ($payload['id'] ?? 0);
        try {
            $this->shop->updateOrder($id, $payload);
            $this->jsonSuccess(null, '주문 정보가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function orderDetail(): never
    {
        $this->guard();
        $id = (int) (request_json()['id'] ?? $_GET['id'] ?? 0);
        try {
            $this->jsonSuccess($this->shop->orderDetail($id));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function bulkUpdateOrders(): never
    {
        $this->guard();
        $payload = request_json();
        try {
            $count = $this->shop->bulkUpdateOrders(
                is_array($payload['ids'] ?? null) ? $payload['ids'] : [],
                $payload
            );
            $this->jsonSuccess(['count' => $count], $count . '건이 처리되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function saveProductCompat(): never
    {
        $this->guard();
        $payload = request_json();
        $id = (int) ($payload['id'] ?? 0);
        try {
            $this->shop->saveProductCompat($id, $payload);
            $this->jsonSuccess(null, '호환코드가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function saveCoupon(): never
    {
        $this->guard();
        try {
            $id = $this->shop->saveCoupon(request_json());
            $this->jsonSuccess(['id' => $id], '쿠폰이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function deleteCoupon(): never
    {
        $this->guard();
        $id = (int) (request_json()['id'] ?? 0);
        try {
            $this->shop->deleteCoupon($id);
            $this->jsonSuccess(null, '쿠폰이 삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function saveBanner(): never
    {
        $this->guard();
        try {
            $id = $this->shop->saveBanner(request_json());
            $this->jsonSuccess(['id' => $id], '배너가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function deleteBanner(): never
    {
        $this->guard();
        $id = (int) (request_json()['id'] ?? 0);
        try {
            $this->shop->deleteBanner($id);
            $this->jsonSuccess(null, '배너가 삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}

