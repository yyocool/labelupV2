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
        $aiCredit = (new AiCreditService())->memberSummary($userId, 1, 15);
        $cliparts = new UserAiClipartService();
        $myCliparts = $cliparts->listForUser($userId, 48);
        $clipartCount = $cliparts->countForUser($userId);

        $usageLimit = (int) ($aiCredit['limit'] ?? 0);
        $usageUsed = (int) ($aiCredit['used'] ?? 0);

        $wishlistService = new ShopWishlistService();
        $wishlist = $wishlistService->items($userId, 12);
        $wishlistCount = $wishlistService->count($userId);
        $coupons = [];
        try {
            $coupons = $this->usableCoupons();
        } catch (\Throwable) {
            $coupons = [];
        }
        $couponCount = count($coupons);

        return [
            'user' => $user,
            'grade' => (new MemberGradeService())->forUser($userId),
            'plan' => $this->planInfo($user),
            'stats' => [
                'points' => $creditBalance,
                'coupons' => $couponCount,
                'orders' => $orderCount,
                'shipping' => $shippingCount,
            ],
            'credit' => [
                'balance' => $creditBalance,
                'transactions' => array_map(static function (array $row): array {
                    $row['description'] = CreditService::repairUtf8((string) ($row['description'] ?? ''));
                    return $row;
                }, $creditTx['items'] ?? []),
            ],
            'ai_usage' => $aiCredit,
            'usage' => [
                'used' => $usageUsed,
                'limit' => $usageLimit,
                'label' => (string) ($aiCredit['label'] ?? '이번 달 AI 사용 잉크'),
            ],
            'cliparts' => $myCliparts,
            'clipartCount' => $clipartCount,
            'wishlist' => $wishlist,
            'wishlistCount' => $wishlistCount,
            'quickLinks' => [
                ['label' => '내 클립아트', 'ic' => '✦', 'href' => '#cliparts', 'badge' => $clipartCount],
                ['label' => '찜한 상품', 'ic' => '♥', 'href' => '#wishlist', 'badge' => $wishlistCount],
                ['label' => '배송지 관리', 'ic' => '📍', 'href' => '#address'],
                ['label' => '쿠폰함', 'ic' => '🎫', 'href' => url('account/coupons'), 'badge' => $couponCount],
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
            'recentDesigns' => $this->recentDesigns($userId),
            'recentOrders' => $this->formatOrders($orders),
            'templates' => $this->sampleTemplates(),
            'brands' => $this->sampleBrands($user),
            'addresses' => (new UserAddressService())->list($userId),
            'address' => $this->defaultAddress($user, $userId),
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
    private function recentDesigns(int $userId): array
    {
        $items = [];
        foreach ((new EditorWorkspaceService())->recentForUser($userId, 6) as $work) {
            $items[] = [
                'id' => (int) ($work['id'] ?? 0),
                'name' => (string) ($work['title'] ?? '새 라벨 디자인'),
                'status' => 'editing',
                'thumb' => (string) ($work['preview_url'] ?? ''),
                'preview_svg' => (string) ($work['preview_svg'] ?? ''),
                'updated_label' => (string) ($work['updated_label'] ?? ''),
                'href' => (string) ($work['editor_url'] ?? url('editor/')),
            ];
        }
        return $items;
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
    private function defaultAddress(array $user, int $userId = 0): array
    {
        $saved = $userId > 0 ? (new UserAddressService())->defaultAddress($userId) : null;
        if ($saved) {
            return [
                'label' => (string) ($saved['label'] ?? '기본 배송지'),
                'name' => (string) ($saved['recipient_name'] ?? ''),
                'phone' => (string) ($saved['recipient_phone'] ?? ''),
                'address' => (string) ($saved['address_line'] ?? ''),
            ];
        }
        return [
            'label' => '기본 배송지',
            'name' => (string) ($user['name'] ?? ''),
            'phone' => (string) ($user['phone'] ?? ''),
            'address' => '',
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, page: int, pages: int, total: int}
     */
    public function orders(int $userId, int $page = 1, int $perPage = 10): array
    {
        $perPage = max(5, min(30, $perPage));
        $total = $this->shop->countOrdersByUser($userId);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($pages, $page));
        $rows = $this->shop->ordersByUser($userId, $perPage, ($page - 1) * $perPage);
        return [
            'items' => $this->formatOrders($rows),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function usableCoupons(): array
    {
        $items = [];
        foreach ($this->shop->activeCoupons() as $row) {
            $items[] = $this->formatCoupon($row);
        }
        return $items;
    }

    /** @param array<string, mixed> $row */
    private function formatCoupon(array $row): array
    {
        $type = (string) ($row['discount_type'] ?? 'fixed');
        $value = (int) ($row['discount_value'] ?? 0);
        $discount = $type === 'percent'
            ? $value . '% 할인'
            : number_format($value) . '원 할인';
        $min = (int) ($row['min_order_amount'] ?? 0);
        $start = substr((string) ($row['starts_at'] ?? ''), 0, 10);
        $end = substr((string) ($row['ends_at'] ?? ''), 0, 10);
        $period = ($start !== '' || $end !== '')
            ? trim(($start !== '' ? str_replace('-', '.', $start) : '시작일 없음') . ' ~ ' . ($end !== '' ? str_replace('-', '.', $end) : '기한 없음'))
            : '기간 제한 없음';

        return [
            'id' => (int) ($row['id'] ?? 0),
            'code' => (string) ($row['code'] ?? ''),
            'name' => (string) ($row['name'] ?? '할인 쿠폰'),
            'discount_label' => $discount,
            'min_label' => $min > 0 ? number_format($min) . '원 이상 주문 시' : '최소 주문금액 없음',
            'period_label' => $period,
        ];
    }

    /** @param array<int, array<string, mixed>> $orders */
    /** @return array<int, array<string, mixed>> */
    private function formatOrders(array $orders): array
    {
        $items = [];
        foreach ($orders as $order) {
            $orderNo = (string) ($order['order_no'] ?? '');
            $status = (string) ($order['status'] ?? 'pending');
            $tracking = trim((string) ($order['tracking_no'] ?? ''));
            $items[] = [
                'id' => (int) ($order['id'] ?? 0),
                'order_no' => $orderNo,
                'href' => url('account/orders/' . $orderNo),
                'name' => self::orderItemsLabel($order['items'] ?? [], (string) ($order['customer_name'] ?? '주문')),
                'date' => date('Y.m.d', strtotime((string) ($order['created_at'] ?? 'now'))),
                'status' => $status,
                'status_label' => ShopAdminService::orderStatusLabel($status),
                'status_class' => $this->orderStatusClass($status),
                'total' => (int) ($order['total_amount'] ?? 0),
                'thumb' => $this->orderThumb($order['items'] ?? []),
                'tracking_no' => $tracking,
                'tracking_url' => ShopAdminService::trackingUrl((string) ($order['carrier'] ?? ''), $tracking),
                'can_track' => $tracking !== '' && in_array($status, ['shipping', 'delivered'], true),
            ];
        }
        return $items;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function orderThumb(array $items): string
    {
        $first = $items[0] ?? null;
        if (!is_array($first)) {
            return asset('hero-tall-1.webp');
        }
        $pid = (int) ($first['product_id'] ?? 0);
        if ($pid > 0) {
            $product = $this->shop->findProductForPreview($pid);
            $path = trim((string) ($product['thumbnail'] ?? ''));
            if ($path !== '') {
                return ShopProductImageService::resolveUrl($path);
            }
        }
        return asset('hero-tall-1.webp');
    }

    /**
     * 주문내역에 "상품명 (옵션) 외 N건" 형태로 보여준다.
     *
     * @param array<int, array<string, mixed>> $items
     */
    private static function orderItemsLabel(array $items, string $fallback): string
    {
        $first = $items[0] ?? null;
        if (!is_array($first)) {
            return $fallback;
        }
        $label = trim((string) ($first['product_name'] ?? ''));
        if ($label === '') {
            return $fallback;
        }
        $option = trim((string) ($first['option_name'] ?? ''));
        if ($option !== '') {
            $label .= ' (' . $option . ')';
        }
        $extra = count($items) - 1;
        if ($extra > 0) {
            $label .= ' 외 ' . $extra . '건';
        }
        return $label;
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
