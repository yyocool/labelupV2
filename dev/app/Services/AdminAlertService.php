<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminAlertRepository;

final class AdminAlertService
{
    private AdminAlertRepository $repo;

    public function __construct()
    {
        $this->repo = new AdminAlertRepository();
    }

    /** @return array<string, mixed> */
    public function snapshot(int $adminUserId): array
    {
        $cursor = $this->repo->cursor($adminUserId);
        $orders = $this->repo->latestOrdersAfter($cursor['last_seen_order_id']);
        $inquiries = $this->repo->latestInquiriesAfter($cursor['last_seen_inquiry_id']);
        $orderUnread = $this->repo->countOrdersAfter($cursor['last_seen_order_id']);
        $inquiryUnread = $this->repo->countInquiriesAfter($cursor['last_seen_inquiry_id']);
        $canOrder = admin_can_menu('shop-orders');
        $canInquiry = admin_can_menu('ops-inquiries');
        if (!$canOrder) {
            $orders = [];
            $orderUnread = 0;
        }
        if (!$canInquiry) {
            $inquiries = [];
            $inquiryUnread = 0;
        }

        return [
            'orders' => [
                'unread' => $orderUnread,
                'latest_id' => $this->repo->maxOrderId(),
                'latest' => $orders,
            ],
            'inquiries' => [
                'unread' => $inquiryUnread,
                'latest_id' => $this->repo->maxInquiryId(),
                'latest' => $inquiries,
            ],
            'unread_total' => $orderUnread + $inquiryUnread,
        ];
    }

    public function ack(int $adminUserId, int $orderId, int $inquiryId): void
    {
        $this->repo->saveCursor(
            $adminUserId,
            max(0, $orderId),
            max(0, $inquiryId)
        );
    }
}
