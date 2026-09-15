<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\EventPopupService;
use App\Services\ShopService;
use App\Services\TossPaymentsService;
use RuntimeException;
use Throwable;

final class ShopPaymentController extends BaseController
{
    private AuthService $auth;
    private ShopService $shop;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->shop = new ShopService();
    }

    public function success(): void
    {
        $paymentKey = trim((string) ($_GET['paymentKey'] ?? ''));
        $orderId = trim((string) ($_GET['orderId'] ?? ''));
        $amount = (int) ($_GET['amount'] ?? 0);
        $source = trim((string) ($_GET['source'] ?? 'shop'));

        try {
            if ($paymentKey === '' || $orderId === '' || $amount < 1) {
                throw new RuntimeException('결제 승인 정보가 올바르지 않습니다.');
            }
            $result = $this->shop->confirmTossPayment($paymentKey, $orderId, $amount, $source);
            redirect('shop/complete?order=' . rawurlencode((string) $result['order_no']));
        } catch (Throwable $e) {
            if ($orderId !== '') {
                $this->shop->markPaymentFailed($orderId, $e->getMessage());
            }
            $qs = http_build_query([
                'orderId' => $orderId,
                'message' => $e->getMessage(),
                'code' => 'CONFIRM_FAILED',
                'source' => $source,
            ]);
            redirect('shop/pay/fail?' . $qs);
        }
    }

    public function fail(): void
    {
        $orderId = trim((string) ($_GET['orderId'] ?? ''));
        $message = trim((string) ($_GET['message'] ?? '결제가 취소되었거나 실패했습니다.'));
        $code = trim((string) ($_GET['code'] ?? ''));
        $source = trim((string) ($_GET['source'] ?? 'shop'));
        if ($orderId !== '') {
            $this->shop->markPaymentFailed($orderId, $message);
        }

        view('shop/layout', [
            'contentTemplate' => 'shop/pay-fail',
            'pageTitle' => '결제 실패 — 라벨업',
            'seoPage' => 'shop-pay-fail',
            'hideShopAside' => true,
            'authUser' => $this->auth->user(),
            'cartCount' => $this->shop->cartCount(),
            'activeNav' => 'shop',
            'shopService' => $this->shop,
            'shopCategories' => $this->shop->homeData()['categories'],
            'shopSubNav' => 'cart',
            'eventPopups' => (new EventPopupService())->activeForSite(),
            'orderNo' => $orderId,
            'failMessage' => $message,
            'failCode' => $code,
            'source' => $source,
            'canRetry' => $orderId !== '' && (new TossPaymentsService())->isEnabled(),
        ]);
    }
}
