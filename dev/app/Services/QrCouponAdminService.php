<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QrCouponRepository;
use RuntimeException;
use Throwable;

final class QrCouponAdminService
{
    private QrCouponRepository $repo;

    public function __construct(?QrCouponRepository $repo = null)
    {
        $this->repo = $repo ?? new QrCouponRepository();
    }

    public function couponPagePath(): string
    {
        return 'qr-coupon';
    }

    public function couponPageAbsoluteUrl(array $query = []): string
    {
        $url = absolute_url($this->couponPagePath());
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }
        return $url;
    }

    public function groupLandingUrl(array $group): string
    {
        return $this->couponPageAbsoluteUrl([
            'g' => (int) ($group['group_no'] ?? 0),
            'cat' => (string) ($group['category_slug'] ?? ''),
            'sheets' => (int) ($group['sheets_per_pack'] ?? 0),
        ]);
    }

    /**
     * @return array{
     *   rows: list<array<string, mixed>>,
     *   category_count: int,
     *   group_count: int,
     *   product_count: int,
     *   generated_qr_count: int,
     *   coupon_page_url: string
     * }
     */
    public function getGroupMatrix(): array
    {
        try {
            $groups = $this->repo->allGroups();
        } catch (Throwable $e) {
            throw new RuntimeException('QR 그룹 테이블이 없습니다. 마이그레이션을 실행해 주세요.', 0, $e);
        }

        $categoryCounts = [];
        foreach ($groups as $g) {
            $no = (int) ($g['category_no'] ?? 0);
            $categoryCounts[$no] = ($categoryCounts[$no] ?? 0) + 1;
        }

        $seenCategory = [];
        $rows = [];
        $productTotal = 0;
        $qrTotal = 0;
        foreach ($groups as $g) {
            $catNo = (int) ($g['category_no'] ?? 0);
            $productCount = (int) ($g['product_count'] ?? 0);
            $generated = (int) ($g['generated_qr_count'] ?? 0);
            $productTotal += $productCount;
            $qrTotal += $generated;
            $landingUrl = $this->groupLandingUrl($g);
            $row = [
                'group_no' => (int) ($g['group_no'] ?? 0),
                'category_no' => $catNo,
                'category_name' => (string) ($g['category_name'] ?? ''),
                'category_slug' => (string) ($g['category_slug'] ?? ''),
                'sheets_per_pack' => (int) ($g['sheets_per_pack'] ?? 0),
                'list_price' => (int) ($g['list_price'] ?? 0),
                'credit_amount' => isset($g['credit_amount']) && $g['credit_amount'] !== null
                    ? (int) $g['credit_amount']
                    : null,
                'color_hex' => (string) ($g['color_hex'] ?? '#9b1c1c'),
                'product_count' => $productCount,
                'generated_qr_count' => $generated,
                'coupon_page_url' => $landingUrl,
                'shop_category_id' => isset($g['shop_category_id']) && $g['shop_category_id'] !== null
                    ? (int) $g['shop_category_id']
                    : null,
                'show_category' => !isset($seenCategory[$catNo]),
                'category_rowspan' => $categoryCounts[$catNo] ?? 1,
            ];
            $seenCategory[$catNo] = true;
            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'category_count' => count($categoryCounts),
            'group_count' => count($rows),
            'product_count' => $productTotal,
            'generated_qr_count' => $qrTotal,
            'coupon_page_url' => $this->couponPageAbsoluteUrl(),
        ];
    }

    public function saveCreditAmount(int $groupNo, mixed $creditAmount): array
    {
        if ($groupNo < 1) {
            throw new RuntimeException('QR 그룹 번호가 올바르지 않습니다.');
        }

        $existing = $this->repo->findByGroupNo($groupNo);
        if (!$existing) {
            throw new RuntimeException('QR 그룹을 찾을 수 없습니다.');
        }

        $normalized = null;
        if ($creditAmount !== null && $creditAmount !== '') {
            if (!is_numeric($creditAmount)) {
                throw new RuntimeException('지급 크레딧은 숫자로 입력해 주세요.');
            }
            $normalized = (int) $creditAmount;
            if ($normalized < 0) {
                throw new RuntimeException('지급 크레딧은 0 이상이어야 합니다.');
            }
        }

        $this->repo->updateCreditAmount($groupNo, $normalized);

        return [
            'group_no' => $groupNo,
            'credit_amount' => $normalized,
        ];
    }

    public function generateCodes(int $groupNo, int $quantity, ?int $adminId): array
    {
        $group = $this->repo->findByGroupNo($groupNo);
        if (!$group) {
            throw new RuntimeException('QR 그룹을 찾을 수 없습니다.');
        }

        $landingUrl = $this->groupLandingUrl($group);
        // 배치 기준 URL = 그룹 랜딩(쿠폰페이지+카테고리+매수)
        $result = $this->repo->createBatch($group, $landingUrl, $quantity, $adminId);

        return [
            'batch_id' => $result['batch_id'],
            'group_no' => $groupNo,
            'quantity' => $quantity,
            'category_slug' => (string) $group['category_slug'],
            'category_name' => (string) $group['category_name'],
            'sheets_per_pack' => (int) $group['sheets_per_pack'],
            'coupon_page_url' => $landingUrl,
            'codes' => $result['codes'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function generationHistory(int $groupNo): array
    {
        if (!$this->repo->findByGroupNo($groupNo)) {
            throw new RuntimeException('QR 그룹을 찾을 수 없습니다.');
        }
        return $this->repo->batchesByGroup($groupNo);
    }

    /** @return list<array<string, mixed>> */
    public function usageHistory(int $groupNo): array
    {
        if (!$this->repo->findByGroupNo($groupNo)) {
            throw new RuntimeException('QR 그룹을 찾을 수 없습니다.');
        }
        return $this->repo->usageByGroup($groupNo);
    }
}
