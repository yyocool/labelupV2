<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NotificationRepository;

final class NotificationService
{
    public const TYPE_WELCOME = 'welcome';
    public const TYPE_CREDIT_LOW = 'credit_low';
    public const TYPE_CREDIT_CHANGE = 'credit_change';
    public const TYPE_ORDER_PLACED = 'order_placed';
    public const TYPE_ORDER_STATUS = 'order_status';
    public const TYPE_SYSTEM = 'system';

    private NotificationRepository $repo;

    public function __construct()
    {
        $this->repo = new NotificationRepository();
    }

    /** @return array<string, array{key:string,label:string,hint:string}> */
    public static function preferenceMeta(): array
    {
        return [
            'pref_welcome' => [
                'key' => 'pref_welcome',
                'label' => '회원가입 안내',
                'hint' => '가입 환영 메시지와 시작 안내',
            ],
            'pref_credit_low' => [
                'key' => 'pref_credit_low',
                'label' => '크레딧 부족 경고',
                'hint' => '잔액이 설정 기준 이하로 내려갈 때',
            ],
            'pref_credit_change' => [
                'key' => 'pref_credit_change',
                'label' => '크레딧 변동',
                'hint' => '적립·사용·조정 시 알림',
            ],
            'pref_order_placed' => [
                'key' => 'pref_order_placed',
                'label' => '주문 접수',
                'hint' => '주문이 생성되었을 때',
            ],
            'pref_order_status' => [
                'key' => 'pref_order_status',
                'label' => '주문·배송 상태 변경',
                'hint' => '결제·준비·배송·완료·취소 등',
            ],
            'pref_system' => [
                'key' => 'pref_system',
                'label' => '시스템 공지',
                'hint' => '중요 서비스 안내',
            ],
        ];
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_WELCOME => '환영',
            self::TYPE_CREDIT_LOW => '크레딧 경고',
            self::TYPE_CREDIT_CHANGE => '크레딧',
            self::TYPE_ORDER_PLACED => '주문',
            self::TYPE_ORDER_STATUS => '배송',
            self::TYPE_SYSTEM => '공지',
            default => '알림',
        };
    }

    private function prefColumnForType(string $type): ?string
    {
        return match ($type) {
            self::TYPE_WELCOME => 'pref_welcome',
            self::TYPE_CREDIT_LOW => 'pref_credit_low',
            self::TYPE_CREDIT_CHANGE => 'pref_credit_change',
            self::TYPE_ORDER_PLACED => 'pref_order_placed',
            self::TYPE_ORDER_STATUS => 'pref_order_status',
            self::TYPE_SYSTEM => 'pref_system',
            default => null,
        };
    }

    public function prefs(int $userId): array
    {
        return $this->presentPrefs($this->repo->ensurePrefs($userId));
    }

    public function savePrefs(int $userId, array $data): array
    {
        return $this->presentPrefs($this->repo->savePrefs($userId, $data));
    }

    /** @param array<string, mixed> $row */
    private function presentPrefs(array $row): array
    {
        return [
            'pref_welcome' => !empty($row['pref_welcome']),
            'pref_credit_low' => !empty($row['pref_credit_low']),
            'pref_credit_change' => !empty($row['pref_credit_change']),
            'pref_order_placed' => !empty($row['pref_order_placed']),
            'pref_order_status' => !empty($row['pref_order_status']),
            'pref_system' => !empty($row['pref_system']),
            'low_credit_threshold' => (int) ($row['low_credit_threshold'] ?? 100),
            'meta' => self::preferenceMeta(),
        ];
    }

    public function notify(
        int $userId,
        string $type,
        string $title,
        string $body = '',
        string $linkUrl = '',
        string $refType = '',
        string $refId = '',
        bool $force = false
    ): ?int {
        if ($userId <= 0) {
            return null;
        }
        try {
            if (!$force) {
                $prefs = $this->repo->ensurePrefs($userId);
                $col = $this->prefColumnForType($type);
                if ($col !== null && empty($prefs[$col])) {
                    return null;
                }
            }
            return $this->repo->create([
                'user_id' => $userId,
                'type' => $type,
                'title' => mb_substr(trim($title), 0, 180),
                'body' => mb_substr(trim($body), 0, 500),
                'link_url' => mb_substr(trim($linkUrl), 0, 400),
                'ref_type' => mb_substr(trim($refType), 0, 40),
                'ref_id' => mb_substr(trim($refId), 0, 64),
            ]);
        } catch (\Throwable $e) {
            // 알림 실패가 본 흐름을 막지 않도록
            return null;
        }
    }

    public function notifyWelcome(int $userId, string $name = ''): void
    {
        $who = trim($name) !== '' ? trim($name) . '님, ' : '';
        $this->notify(
            $userId,
            self::TYPE_WELCOME,
            '라벨업에 오신 것을 환영합니다',
            $who . '마이페이지에서 알림·크레딧·주문을 확인할 수 있어요. 지금 바로 라벨을 디자인해 보세요.',
            url('account'),
            'user',
            (string) $userId
        );
    }

    public function notifyCreditChange(int $userId, int $amount, int $balanceAfter, string $description): void
    {
        $sign = $amount >= 0 ? '+' : '';
        $this->notify(
            $userId,
            self::TYPE_CREDIT_CHANGE,
            '크레딧이 ' . ($amount >= 0 ? '적립' : '사용') . '되었습니다',
            trim($description) !== ''
                ? $description . " ({$sign}" . number_format($amount) . ' C) · 잔액 ' . number_format($balanceAfter) . ' C'
                : "{$sign}" . number_format($amount) . ' C · 잔액 ' . number_format($balanceAfter) . ' C',
            url('account') . '#credits',
            'credit',
            (string) $userId
        );
    }

    public function maybeNotifyCreditLow(int $userId, int $previous, int $next): void
    {
        if ($userId <= 0 || $next >= $previous) {
            return;
        }
        $prefs = $this->repo->ensurePrefs($userId);
        if (empty($prefs['pref_credit_low'])) {
            return;
        }
        $threshold = max(0, (int) ($prefs['low_credit_threshold'] ?? 100));
        if ($next > $threshold || $previous <= $threshold) {
            return;
        }
        if ($this->repo->hasRecentOfType($userId, self::TYPE_CREDIT_LOW, 24)) {
            return;
        }
        $this->notify(
            $userId,
            self::TYPE_CREDIT_LOW,
            '크레딧이 얼마 남지 않았습니다',
            '현재 잔액 ' . number_format($next) . ' C (기준 ' . number_format($threshold) . ' C 이하). 마이페이지에서 내역을 확인해 주세요.',
            url('account') . '#credits',
            'credit',
            (string) $userId,
            true
        );
    }

    public function notifyOrderPlaced(int $userId, string $orderNo, int $total): void
    {
        $this->notify(
            $userId,
            self::TYPE_ORDER_PLACED,
            '주문이 접수되었습니다',
            '주문번호 ' . $orderNo . ' · ' . number_format($total) . '원. 진행 상황은 마이페이지에서 확인할 수 있습니다.',
            url('account') . '#orders',
            'order',
            $orderNo
        );
    }

    public function notifyOrderStatus(int $userId, string $orderNo, string $status, string $trackingNo = ''): void
    {
        $label = ShopAdminService::orderStatusLabel($status);
        $body = '주문번호 ' . $orderNo . ' 상태가 「' . $label . '」으로 변경되었습니다.';
        if ($status === 'shipping' && $trackingNo !== '') {
            $body .= ' 송장번호: ' . $trackingNo;
        }
        $this->notify(
            $userId,
            self::TYPE_ORDER_STATUS,
            '주문 상태가 변경되었습니다',
            $body,
            url('account') . '#orders',
            'order',
            $orderNo
        );
    }

    /** @return array{items: array<int, array<string, mixed>>, unread: int, total: int} */
    public function list(int $userId, int $limit = 30): array
    {
        $raw = $this->repo->listForUser($userId, $limit, 0);
        $items = [];
        foreach ($raw['items'] as $row) {
            $items[] = [
                'id' => (int) ($row['id'] ?? 0),
                'type' => (string) ($row['type'] ?? ''),
                'type_label' => self::typeLabel((string) ($row['type'] ?? '')),
                'title' => (string) ($row['title'] ?? ''),
                'body' => (string) ($row['body'] ?? ''),
                'link_url' => (string) ($row['link_url'] ?? ''),
                'is_read' => !empty($row['is_read']),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'created_label' => $this->relativeTime((string) ($row['created_at'] ?? '')),
            ];
        }
        return [
            'items' => $items,
            'unread' => (int) ($raw['unread'] ?? 0),
            'total' => (int) ($raw['total'] ?? 0),
        ];
    }

    public function unreadCount(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        try {
            return $this->repo->unreadCount($userId);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function markRead(int $userId, ?int $id = null): int
    {
        return $this->repo->markRead($userId, $id);
    }

    private function relativeTime(string $at): string
    {
        $ts = strtotime($at);
        if (!$ts) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return '방금';
        }
        if ($diff < 3600) {
            return (int) floor($diff / 60) . '분 전';
        }
        if ($diff < 86400) {
            return (int) floor($diff / 3600) . '시간 전';
        }
        if ($diff < 86400 * 7) {
            return (int) floor($diff / 86400) . '일 전';
        }
        return date('Y.m.d H:i', $ts);
    }
}
