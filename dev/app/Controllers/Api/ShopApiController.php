<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\ShopService;
use RuntimeException;

final class ShopApiController extends BaseController
{
    private ShopService $shop;

    public function __construct()
    {
        $this->shop = new ShopService();
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
