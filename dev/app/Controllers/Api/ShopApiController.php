<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\AuthService;
use App\Services\ShopService;
use RuntimeException;

final class ShopApiController extends BaseController
{
    private ShopService $shop;

    public function __construct()
    {
        $this->shop = new ShopService();
    }

    public function editorPapers(): never
    {
        $this->jsonSuccess($this->shop->editorPapers());
    }

    public function catalog(): never
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = array_filter([
            'q' => $q,
            'category_slug' => $category,
        ]);
        $this->jsonSuccess($this->shop->catalog($filters, $page, 12));
    }

    public function product(string $id): never
    {
        $product = $this->shop->productDetail((int) $id);
        if (!$product) {
            $this->jsonError('상품을 찾을 수 없습니다.', null, 404);
        }
        $this->jsonSuccess($this->shop->presentPublicProduct($product));
    }

    public function lookup(): never
    {
        $code = trim((string) ($_GET['code'] ?? ''));
        if ($code === '') {
            $this->jsonError('용지 번호를 입력해 주세요.', null, 422);
        }
        $product = $this->shop->lookupByCode($code);
        if (!$product) {
            $this->jsonError('현재 용지에 맞는 상품이 없습니다.', null, 404);
        }
        $this->jsonSuccess($product);
    }

    public function checkout(): never
    {
        $auth = new AuthService();
        $user = $auth->user();
        if (!$user) {
            $this->jsonError('로그인이 필요합니다.', null, 401);
        }
        try {
            $order = $this->shop->checkout(request_json(), $user);
            $this->jsonSuccess($order, '주문이 접수되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function cart(): never
    {
        $this->jsonSuccess($this->shop->cartSummary());
    }

    public function addCart(): never
    {
        $data = request_json();
        try {
            $this->shop->addToCart((int) ($data['product_id'] ?? 0), (int) ($data['qty'] ?? 1));
            $this->jsonSuccess($this->shop->cartSummary(), '장바구니에 담았습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function updateCart(): never
    {
        $data = request_json();
        try {
            $this->shop->updateCartItem((int) ($data['product_id'] ?? 0), (int) ($data['qty'] ?? 0));
            $this->jsonSuccess($this->shop->cartSummary(), '장바구니가 수정되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function removeCart(): never
    {
        $data = request_json();
        $this->shop->removeFromCart((int) ($data['product_id'] ?? 0));
        $this->jsonSuccess($this->shop->cartSummary(), '상품을 삭제했습니다.');
    }
}
