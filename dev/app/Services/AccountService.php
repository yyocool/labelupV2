<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CreditRepository;
use App\Repositories\ShopRepository;
use App\Repositories\UserRepository;

final class AccountService
{
    private UserRepository $users;
    private ShopRepository $shop;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->shop = new ShopRepository();
    }

    /** @return array<string, mixed> */
    public function dashboard(int $userId): array
    {
        $user = $this->users->findById($userId) ?? [];
        unset($user['password_hash']);

        $orders = $this->shop->ordersByUser($userId, 5);
        $orderCount = $this->shop->countOrdersByUser($userId);
        $shippingCount = $this->shop->countOrdersByUser($userId, 'shipping');
        $creditRepo = new CreditRepository();
        $creditRepo->ensureBalanceRow($userId);
        $creditBalance = $creditRepo->getBalance($userId);
        $creditTx = $creditRepo->transactionsForUser($userId, 1, 10);
        $cliparts = new UserAiClipartService();
        $myCliparts = $cliparts->listForUser($userId, 48);
        $clipartCount = $cliparts->countForUser($userId);

        return [
            'user' => $user,
            'grade' => (new MemberGradeService())->forUser($userId),
            'plan' => $this->planInfo($user),
            'stats' => [
                'points' => $creditBalance,
                'coupons' => 3,
                'orders' => $orderCount,
                'shipping' => $shippingCount,
            ],
            'credit' => [
                'balance' => $creditBalance,
                'transactions' => $creditTx['items'] ?? [],
            ],
            'usage' => [
                'used' => 68,
                'limit' => 200,
                'label' => '이번 달 디자인/출력 사용량',
            ],
            'cliparts' => $myCliparts,
            'clipartCount' => $clipartCount,
            'quickLinks' => [
                ['label' => '내 클립아트', 'ic' => '✦', 'href' => '#cliparts', 'badge' => $clipartCount],
                ['label' => '배송지 관리', 'ic' => '📍', 'href' => '#address'],
                ['label' => '결제수단 관리', 'ic' => '💳', 'href' => '#', 'disabled' => true],
                ['label' => '쿠폰함', 'ic' => '🎫', 'href' => url('shop/cart'), 'badge' => 3],
            ],
            'shortcuts' => [
                ['label' => '라벨 편집', 'ic' => '✎', 'href' => url('editor/')],
                ['label' => '새 라벨 만들기', 'ic' => '＋', 'href' => url('editor/')],
                ['label' => 'AI 라벨 디자인', 'ic' => '✦', 'href' => url('/')],
                ['label' => '내 클립아트', 'ic' => '★', 'href' => '#cliparts'],
                ['label' => '엑셀로 만들기', 'ic' => '⌘', 'href' => '#', 'disabled' => true],
                ['label' => '주문하기', 'ic' => '🛒', 'href' => url('shop/cart')],
                ['label' => '샘플 요청', 'ic' => '📦', 'href' => url('shop/products') . '?category=label-paper'],
            ],
            'recentDesigns' => $this->sampleDesigns(),
            'recentOrders' => $this->formatOrders($orders),
            'templates' => $this->sampleTemplates(),
            'brands' => $this->sampleBrands($user),
            'address' => $this->defaultAddress($user),
            'tools' => [
                ['label' => '라벨 편집', 'ic' => '✎', 'href' => url('editor/')],
                ['label' => '엑셀 데이터 연동', 'ic' => '⌘', 'href' => '#', 'disabled' => true],
                ['label' => 'AI 라벨 디자인', 'ic' => '✦', 'href' => url('/')],
                ['label' => '내 클립아트', 'ic' => '★', 'href' => '#cliparts'],
                ['label' => '템플릿 둘러보기', 'ic' => '▦', 'href' => url('/')],
            ],
        ];
    }

    /** @param array<string, mixed> $user */
    private function planInfo(array $user): array
    {
        $created = strtotime((string) ($user['created_at'] ?? 'now'));
        $start = date('Y.m.d', $created ?: time());
        $end = date('Y.m.d', strtotime('+1 year', $created ?: time()));
        $remain = max(0, (int) floor((strtotime('+1 year', $created ?: time()) - time()) / 86400));

        return [
            'name' => 'Pro Plan',
            'period' => "{$start} ~ {$end}",
            'remain_days' => $remain,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sampleDesigns(): array
    {
        return [
            ['name' => '올리브 오일 라벨', 'status' => 'editing', 'thumb' => asset('tpl-olive.webp')],
            ['name' => '꿀 스티커', 'status' => 'complete', 'thumb' => asset('tpl-handmade.webp')],
            ['name' => '배송 라벨', 'status' => 'complete', 'thumb' => asset('tpl-shipping.webp')],
            ['name' => '커피 원두 라벨', 'status' => 'editing', 'thumb' => asset('tpl-coffee.webp')],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sampleTemplates(): array
    {
        return [
            ['name' => '핸드메이드 라벨', 'count' => 12, 'thumb' => asset('tpl-handmade.webp')],
            ['name' => '감사 스티커', 'count' => 8, 'thumb' => asset('tpl-thanks.webp')],
            ['name' => '가격표 라벨', 'count' => 5, 'thumb' => asset('tpl-price.webp')],
        ];
    }

    /** @param array<string, mixed> $user */
    /** @return array<int, array<string, string>> */
    private function sampleBrands(array $user): array
    {
        $company = trim((string) ($user['company'] ?? ''));
        $brands = [
            ['name' => '그린팜', 'initial' => 'G'],
            ['name' => '내추럴데이즈', 'initial' => 'N'],
        ];
        if ($company !== '') {
            array_unshift($brands, ['name' => $company, 'initial' => mb_substr($company, 0, 1)]);
        }
        return $brands;
    }

    /** @param array<string, mixed> $user */
    private function defaultAddress(array $user): array
    {
        return [
            'label' => '기본 배송지',
            'name' => (string) ($user['name'] ?? '수령인'),
            'phone' => (string) ($user['phone'] ?? '010-0000-0000'),
            'address' => '서울특별시 강남구 테헤란로 123, 4층 (샘플)',
        ];
    }

    /** @param array<int, array<string, mixed>> $orders */
    /** @return array<int, array<string, mixed>> */
    private function formatOrders(array $orders): array
    {
        $shop = new ShopAdminService();
        $items = [];
        foreach ($orders as $order) {
            $items[] = [
                'order_no' => (string) $order['order_no'],
                'name' => (string) ($order['customer_name'] ?? '주문'),
                'date' => date('Y.m.d', strtotime((string) ($order['created_at'] ?? 'now'))),
                'status' => (string) ($order['status'] ?? 'pending'),
                'status_label' => ShopAdminService::orderStatusLabel((string) ($order['status'] ?? 'pending')),
                'total' => (int) ($order['total_amount'] ?? 0),
                'thumb' => asset('hero-tall-1.webp'),
            ];
        }
        if (!$items) {
            foreach ($this->fallbackOrders() as $row) {
                $items[] = $row;
            }
        }
        return $items;
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackOrders(): array
    {
        return [
            [
                'order_no' => 'LU-SAMPLE-001',
                'name' => '50x30 유포지 500매',
                'date' => date('Y.m.d', strtotime('-3 days')),
                'status' => 'shipping',
                'status_label' => '배송중',
                'total' => 33700,
                'thumb' => asset('hero-tall-1.webp'),
            ],
            [
                'order_no' => 'LU-SAMPLE-002',
                'name' => '100x50 아트지 250매',
                'date' => date('Y.m.d', strtotime('-8 days')),
                'status' => 'delivered',
                'status_label' => '배송완료',
                'total' => 25000,
                'thumb' => asset('hero-tall-2.webp'),
            ],
        ];
    }

    public function orderStatusClass(string $status): string
    {
        return match ($status) {
            'delivered' => 'is-success',
            'shipping', 'preparing', 'paid' => 'is-accent',
            'cancelled', 'refunded' => 'is-muted',
            default => 'is-warn',
        };
    }
}
