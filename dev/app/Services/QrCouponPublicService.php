<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CreditRepository;
use App\Repositories\QrCouponRepository;

final class QrCouponPublicService
{
    private QrCouponRepository $repo;
    private QrCouponAdminService $admin;

    public function __construct(?QrCouponRepository $repo = null, ?QrCouponAdminService $admin = null)
    {
        $this->repo = $repo ?? new QrCouponRepository();
        $this->admin = $admin ?? new QrCouponAdminService();
    }

    /**
     * @return array{
     *   mode: string,
     *   code: ?array,
     *   group: ?array,
     *   products: list<array<string,mixed>>,
     *   coupon_page_url: string,
     *   error: ?string,
     *   should_close: bool
     * }
     */
    public function resolvePage(?string $code, ?int $groupNo, ?string $categorySlug, ?int $sheets): array
    {
        $base = [
            'mode' => 'generic',
            'code' => null,
            'group' => null,
            'products' => [],
            'coupon_page_url' => $this->admin->couponPageAbsoluteUrl(),
            'error' => null,
            'should_close' => false,
            'redeem' => null,
        ];

        if ($code !== null && $code !== '') {
            $row = $this->repo->findCode($code);
            if (!$row) {
                $base['mode'] = 'invalid';
                $base['error'] = '유효하지 않은 QR 쿠폰번호입니다. 상품에 인쇄된 QR코드로 다시 접속해 주세요.';
                $base['should_close'] = true;
                return $base;
            }
            if (($row['status'] ?? '') === 'disabled') {
                $base['mode'] = 'disabled';
                $base['error'] = '사용할 수 없는 QR 쿠폰입니다.';
                $base['code'] = $row;
                $base['should_close'] = true;
                return $base;
            }

            $group = [
                'group_no' => (int) $row['group_no'],
                'category_no' => (int) ($row['category_no'] ?? 0),
                'category_slug' => (string) $row['category_slug'],
                'category_name' => (string) ($row['category_name'] ?? $row['category_slug']),
                'sheets_per_pack' => (int) $row['sheets_per_pack'],
                'list_price' => (int) ($row['list_price'] ?? 0),
                'credit_amount' => isset($row['credit_amount']) ? (int) $row['credit_amount'] : null,
                'color_hex' => (string) ($row['color_hex'] ?? '#9b1c1c'),
            ];
            $base['mode'] = ($row['status'] ?? '') === 'used' ? 'used' : 'code';
            $base['code'] = $row;
            $base['group'] = $group;
            $base['products'] = $this->repo->productsForGroup($group['category_slug'], $group['sheets_per_pack']);
            $base['coupon_page_url'] = (string) ($row['coupon_page_url'] ?? $base['coupon_page_url']);
            return $base;
        }

        // 쿠폰번호(code)가 링크에 없으면 정상 진입으로 보지 않음
        $base['mode'] = 'missing_code';
        $base['error'] = '쿠폰번호가 링크에 포함되지 않았습니다. 상품에 인쇄된 고유 QR코드로 접속해 주세요.';
        $base['should_close'] = true;

        if ($groupNo && $groupNo > 0) {
            $group = $this->repo->findByGroupNo($groupNo);
            if ($group) {
                if ($categorySlug && $categorySlug !== (string) $group['category_slug']) {
                    $base['error'] = '쿠폰번호가 없고 카테고리 정보도 일치하지 않습니다.';
                } elseif ($sheets && $sheets !== (int) $group['sheets_per_pack']) {
                    $base['error'] = '쿠폰번호가 없고 매수 정보도 일치하지 않습니다.';
                }
                $base['group'] = $group;
            }
        }

        return $base;
    }

    /**
     * 로그인 회원이 유효한 QR을 열면 그룹 지급잉크를 즉시 적립한다.
     *
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    public function redeemForUser(array $page, int $userId, bool $isPreview = false): array
    {
        $page['redeem'] = null;
        if ($isPreview || $userId <= 0) {
            return $page;
        }
        $codeRow = $page['code'] ?? null;
        $code = is_array($codeRow) ? trim((string) ($codeRow['code'] ?? '')) : '';
        if ($code === '' || !empty($page['should_close'])) {
            return $page;
        }

        $result = $this->redeem($code, $userId);
        $page['redeem'] = $result;
        $fresh = $this->repo->findCode($code);
        if (is_array($fresh)) {
            $page['code'] = $fresh;
            $page['mode'] = ($fresh['status'] ?? '') === 'used' ? 'used' : ($page['mode'] ?? 'code');
            if (isset($page['group']) && is_array($page['group'])) {
                $page['group']['credit_amount'] = isset($fresh['credit_amount']) && $fresh['credit_amount'] !== null
                    ? (int) $fresh['credit_amount']
                    : null;
            }
        }
        return $page;
    }

    /**
     * @return array{status:string, amount:int, balance:int, message:string}
     */
    public function redeem(string $code, int $userId): array
    {
        $credits = new CreditService();
        $this->repo->begin();
        try {
            $row = $this->repo->lockCode($code);
            if (!$row) {
                $this->repo->rollback();
                return [
                    'status' => 'invalid',
                    'amount' => 0,
                    'balance' => $credits->balance($userId),
                    'message' => '유효하지 않은 QR 쿠폰번호입니다.',
                ];
            }

            $status = (string) ($row['status'] ?? '');
            $usedBy = (int) ($row['used_by'] ?? 0);
            $amount = isset($row['credit_amount']) && $row['credit_amount'] !== '' && $row['credit_amount'] !== null
                ? (int) $row['credit_amount']
                : 0;
            $refExists = (new CreditRepository())->hasSourceRef('qr_coupon', 'qr:' . $code);

            if ($status === 'disabled') {
                $this->repo->commit();
                return [
                    'status' => 'disabled',
                    'amount' => 0,
                    'balance' => $credits->balance($userId),
                    'message' => '사용할 수 없는 QR 쿠폰입니다.',
                ];
            }

            if ($status === 'used' || $refExists) {
                $this->repo->commit();
                $own = $usedBy === $userId || $refExists;
                return [
                    'status' => 'already',
                    'amount' => $amount,
                    'balance' => $credits->balance($userId),
                    'message' => $own
                        ? '이미 지급된 쿠폰입니다. 마이페이지 잉크 내역에서 확인해 주세요.'
                        : '이미 사용된 QR 쿠폰입니다.',
                ];
            }

            if ($amount < 1) {
                $this->repo->commit();
                return [
                    'status' => 'no_credit',
                    'amount' => 0,
                    'balance' => $credits->balance($userId),
                    'message' => '이 쿠폰의 지급 잉크가 아직 설정되지 않았습니다. 관리자에게 문의해 주세요.',
                ];
            }

            if (!$this->repo->markUsed((int) $row['id'], $userId)) {
                $this->repo->rollback();
                return [
                    'status' => 'already',
                    'amount' => $amount,
                    'balance' => $credits->balance($userId),
                    'message' => '이미 사용된 QR 쿠폰입니다.',
                ];
            }

            $balance = $credits->grantQrCoupon($userId, $amount, $code);
            $this->repo->commit();
            return [
                'status' => 'granted',
                'amount' => $amount,
                'balance' => $balance,
                'message' => '잉크 ' . number_format($amount) . ' C가 지급되었습니다.',
            ];
        } catch (\Throwable $e) {
            $this->repo->rollback();
            throw $e;
        }
    }
}
