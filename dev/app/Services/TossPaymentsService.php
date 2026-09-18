<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * 토스페이먼츠 결제위젯 + 승인 API 공통 서비스
 * (쇼핑몰 / 편집기 주문서 공용)
 */
final class TossPaymentsService
{
    public function isConfigured(): bool
    {
        return $this->clientKey() !== '' && $this->secretKey() !== '';
    }

    public function isEnabled(): bool
    {
        $raw = strtolower(trim((string) env('TOSS_PAYMENTS_ENABLED', '1')));
        if (in_array($raw, ['0', 'false', 'off', 'no'], true)) {
            return false;
        }
        return $this->isConfigured();
    }

    public function clientKey(): string
    {
        return trim((string) env('TOSS_CLIENT_KEY', ''));
    }

    public function secretKey(): string
    {
        return trim((string) env('TOSS_SECRET_KEY', ''));
    }

    public function variantKey(): string
    {
        $key = trim((string) env('TOSS_VARIANT_KEY', 'DEFAULT'));
        return $key !== '' ? $key : 'DEFAULT';
    }

    public function agreementVariantKey(): string
    {
        $key = trim((string) env('TOSS_AGREEMENT_VARIANT_KEY', 'AGREEMENT'));
        return $key !== '' ? $key : 'AGREEMENT';
    }

    /**
     * 위젯에 노출되는 결제수단 안내(상점 대시보드 설정에 따라 실제 노출은 달라질 수 있음)
     * @return list<array{key:string,label:string,desc:string,group:string}>
     */
    public function availableMethods(): array
    {
        return [
            ['key' => 'card', 'label' => '신용·체크카드', 'desc' => '국내 주요 카드사', 'group' => '일반결제'],
            ['key' => 'transfer', 'label' => '계좌이체', 'desc' => '실시간 계좌이체', 'group' => '일반결제'],
            ['key' => 'virtualAccount', 'label' => '가상계좌', 'desc' => '입금 대기 후 자동 확인', 'group' => '일반결제'],
            ['key' => 'mobilePhone', 'label' => '휴대폰', 'desc' => '통신사 결제', 'group' => '일반결제'],
            ['key' => 'tosspay', 'label' => '토스페이', 'desc' => '토스 앱 간편결제', 'group' => '간편결제'],
            ['key' => 'kakaopay', 'label' => '카카오페이', 'desc' => '카카오톡 간편결제', 'group' => '간편결제'],
            ['key' => 'naverpay', 'label' => '네이버페이', 'desc' => '네이버 간편결제', 'group' => '간편결제'],
            ['key' => 'payco', 'label' => '페이코', 'desc' => 'PAYCO 간편결제', 'group' => '간편결제'],
            ['key' => 'samsungpay', 'label' => '삼성페이', 'desc' => '삼성 기기 간편결제', 'group' => '간편결제'],
            ['key' => 'applepay', 'label' => '애플페이', 'desc' => '지원 단말기·카드', 'group' => '간편결제'],
        ];
    }

    public function customerKey(?int $userId): string
    {
        if ($userId !== null && $userId > 0) {
            return 'user_' . $userId;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (empty($_SESSION['toss_guest_key'])) {
            $_SESSION['toss_guest_key'] = 'guest_' . bin2hex(random_bytes(12));
        }
        return (string) $_SESSION['toss_guest_key'];
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>
     */
    public function buildWidgetPayload(array $order, string $source = 'shop'): array
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('토스페이먼츠 설정이 필요합니다. TOSS_CLIENT_KEY / TOSS_SECRET_KEY 를 확인해 주세요.');
        }

        $orderNo = (string) ($order['order_no'] ?? '');
        $amount = (int) ($order['total_amount'] ?? $order['total'] ?? 0);
        if ($orderNo === '' || $amount < 1) {
            throw new RuntimeException('결제할 주문 정보가 올바르지 않습니다.');
        }

        $orderName = trim((string) ($order['order_name'] ?? ''));
        if ($orderName === '') {
            $orderName = '라벨업 주문 ' . $orderNo;
        }

        $successQuery = http_build_query([
            'orderId' => $orderNo,
            'source' => $source,
        ]);
        $failQuery = http_build_query([
            'orderId' => $orderNo,
            'source' => $source,
        ]);

        return [
            'enabled' => true,
            'provider' => 'toss',
            'client_key' => $this->clientKey(),
            'customer_key' => $this->customerKey(isset($order['user_id']) ? (int) $order['user_id'] : null),
            'variant_key' => $this->variantKey(),
            'agreement_variant_key' => $this->agreementVariantKey(),
            'order_id' => $orderNo,
            'order_name' => mb_substr($orderName, 0, 100),
            'amount' => $amount,
            'currency' => 'KRW',
            'customer_name' => (string) ($order['customer_name'] ?? ''),
            'customer_email' => (string) ($order['customer_email'] ?? ''),
            'customer_mobile_phone' => preg_replace('/\D+/', '', (string) ($order['customer_phone'] ?? '')) ?: null,
            'success_url' => absolute_url('shop/pay/success') . '?' . $successQuery,
            'fail_url' => absolute_url('shop/pay/fail') . '?' . $failQuery,
            'source' => $source,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmPayment(string $paymentKey, string $orderId, int $amount): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('토스페이먼츠 시크릿 키가 설정되지 않았습니다.');
        }
        $paymentKey = trim($paymentKey);
        $orderId = trim($orderId);
        if ($paymentKey === '' || $orderId === '' || $amount < 1) {
            throw new RuntimeException('결제 승인 파라미터가 올바르지 않습니다.');
        }

        return $this->request('POST', '/v1/payments/confirm', [
            'paymentKey' => $paymentKey,
            'orderId' => $orderId,
            'amount' => $amount,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentKey): array
    {
        $paymentKey = trim($paymentKey);
        if ($paymentKey === '') {
            throw new RuntimeException('paymentKey가 없습니다.');
        }
        return $this->request('GET', '/v1/payments/' . rawurlencode($paymentKey));
    }

    public static function methodLabel(array $payment): string
    {
        $method = (string) ($payment['method'] ?? '');
        $easy = $payment['easyPay']['provider'] ?? null;
        if (is_string($easy) && $easy !== '') {
            return match (strtoupper($easy)) {
                'TOSSPAY', '토스페이' => '토스페이',
                'KAKAOPAY', '카카오페이' => '카카오페이',
                'NAVERPAY', '네이버페이' => '네이버페이',
                'PAYCO', '페이코' => '페이코',
                'SAMSUNGPAY', '삼성페이' => '삼성페이',
                'APPLEPAY', '애플페이' => '애플페이',
                default => $easy,
            };
        }
        return match ($method) {
            '카드' => '신용·체크카드',
            '계좌이체' => '계좌이체',
            '가상계좌' => '가상계좌',
            '휴대폰' => '휴대폰',
            '간편결제' => '간편결제',
            default => $method !== '' ? $method : '토스페이먼츠',
        };
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $url = 'https://api.tosspayments.com' . $path;
        $headers = [
            'Authorization: Basic ' . base64_encode($this->secretKey() . ':'),
            'Content-Type: application/json',
        ];
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('결제 요청을 초기화하지 못했습니다.');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('토스페이먼츠 통신 오류: ' . $err);
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new RuntimeException('토스페이먼츠 응답을 해석하지 못했습니다.');
        }
        if ($status < 200 || $status >= 300) {
            $msg = (string) ($json['message'] ?? '결제 승인에 실패했습니다.');
            $code = (string) ($json['code'] ?? '');
            throw new RuntimeException($code !== '' ? "[{$code}] {$msg}" : $msg);
        }
        return $json;
    }
}
