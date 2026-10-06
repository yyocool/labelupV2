<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ProductDetailPageService;
use App\Services\ShopAdminService;
use RuntimeException;

final class ShopAdminApiController extends BaseController
{
    private AuthService $auth;
    private ShopAdminService $shop;
    private ProductDetailPageService $detailPages;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->shop = new ShopAdminService();
        $this->detailPages = new ProductDetailPageService();
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

    public function productPageSettings(): never
    {
        $this->guard();
        $this->jsonSuccess($this->shop->productPageSettings());
    }

    public function saveProductPageSettings(): never
    {
        $this->guard();
        try {
            $saved = $this->shop->saveProductPageSettings(request_json());
            $this->jsonSuccess($saved, '공통 헤더/푸터 설정이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function productPageCategorySettingsList(): never
    {
        $this->guard();
        $this->jsonSuccess($this->shop->productPageCategorySettingsList());
    }

    public function productPageCategorySettings(): never
    {
        $this->guard();
        try {
            $categoryId = (int) ($_GET['category_id'] ?? request_json()['category_id'] ?? 0);
            $this->jsonSuccess($this->shop->productPageCategorySettings($categoryId));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function saveProductPageCategorySettings(): never
    {
        $this->guard();
        try {
            $saved = $this->shop->saveProductPageCategorySettings(request_json());
            $this->jsonSuccess($saved, '카테고리 헤더/푸터 설정이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function uploadProductPageImages(): never
    {
        $this->guard();
        try {
            if (empty($_FILES['images'])) {
                throw new RuntimeException('업로드할 이미지를 선택해주세요. (요청 용량이 서버 한도를 넘으면 내용이 비워져 도착합니다)');
            }
            $fitWidth = (int) ($_POST['fit_width'] ?? 0);
            if ($fitWidth < 0 || $fitWidth > 4096) {
                $fitWidth = 0;
            }
            $results = $this->shop->uploadProductPageImages($_FILES['images'], $fitWidth);
            $urls = [];
            $failed = 0;
            foreach ($results as $result) {
                if ($result['path'] !== '') {
                    $urls[] = $result['path'];
                } else {
                    $failed++;
                }
            }
            if ($results === []) {
                throw new RuntimeException('업로드할 이미지가 없습니다.');
            }
            // 전부 실패해도 예외로 던지지 않는다. 호출 측이 results 로 어느 파일이
            // 왜 실패했는지 짚어야 하는데, 예외로 바꾸면 그 정보가 한 줄로 뭉개진다.
            $this->jsonSuccess(
                ['urls' => $urls, 'results' => $results, 'failed_count' => $failed],
                $failed === 0
                    ? '이미지가 업로드되었습니다.'
                    : count($urls) . '장 업로드, ' . $failed . '장 실패했습니다.'
            );
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage());
        }
    }

    public function productDetailPage(): never
    {
        $this->guard();
        try {
            $productId = (int) ($_GET['product_id'] ?? 0);
            if ($productId <= 0) {
                $productId = (int) (request_json()['product_id'] ?? 0);
            }
            $this->jsonSuccess($this->detailPages->getForEdit($productId));
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function saveProductDetailPage(): never
    {
        $this->guard();
        try {
            $payload = request_json();
            $saved = $this->detailPages->save(
                (int) ($payload['product_id'] ?? 0),
                (string) ($payload['html_content'] ?? '')
            );
            $this->jsonSuccess($saved, $saved['has_detail_page'] ? '상품 상세 내용이 저장되었습니다.' : '상품 상세 내용을 비워 등록을 해제했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}

