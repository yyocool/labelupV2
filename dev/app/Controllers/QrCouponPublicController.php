<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\QrCouponRepository;
use App\Services\AuthService;
use App\Services\EventPopupService;
use App\Services\QrCouponAdminService;
use App\Services\QrCouponPublicService;
use App\Services\ShopService;

final class QrCouponPublicController extends BaseController
{
    public function index(): void
    {
        $this->renderPage(null);
    }

    public function show(string $code): void
    {
        $this->renderPage($code);
    }

    private function renderPage(?string $code): void
    {
        $auth = new AuthService();
        $user = $auth->user();

        $codeParam = $code !== null && $code !== ''
            ? rawurldecode($code)
            : (isset($_GET['code']) ? (string) $_GET['code'] : null);
        $groupNo = isset($_GET['g']) ? (int) $_GET['g'] : 0;
        $cat = isset($_GET['cat']) ? trim((string) $_GET['cat']) : null;
        $sheets = isset($_GET['sheets']) ? (int) $_GET['sheets'] : 0;
        $isPreview = isset($_GET['preview']) && (string) $_GET['preview'] === '1';

        $page = (new QrCouponPublicService())->resolvePage(
            $codeParam !== null && $codeParam !== '' ? $codeParam : null,
            $groupNo > 0 ? $groupNo : null,
            $cat !== null && $cat !== '' ? $cat : null,
            $sheets > 0 ? $sheets : null
        );

        // 관리자 미리보기: 쿠폰번호 없이 그룹 안내 표시 (g 없으면 첫 그룹으로 폴백)
        if ($isPreview && (empty($page['group']) || !empty($page['should_close']))) {
            $repo = new QrCouponRepository();
            $group = $groupNo > 0 ? $repo->findByGroupNo($groupNo) : null;
            if (!$group) {
                $groups = $repo->allGroups();
                $group = $groups[0] ?? null;
                if ($group) {
                    $groupNo = (int) ($group['group_no'] ?? 0);
                    $cat = (string) ($group['category_slug'] ?? '');
                    $sheets = (int) ($group['sheets_per_pack'] ?? 0);
                }
            }
            if ($group) {
                $page['mode'] = 'group';
                $page['error'] = null;
                $page['should_close'] = false;
                $page['group'] = $group;
                $page['products'] = $repo->productsForGroup(
                    (string) $group['category_slug'],
                    (int) $group['sheets_per_pack']
                );
                $page['coupon_page_url'] = (new QrCouponAdminService())->groupLandingUrl($group);
            }
        }

        $path = 'qr-coupon';
        $currentUrl = absolute_url($path);
        $qs = [];
        if ($groupNo > 0) {
            $qs['g'] = $groupNo;
        }
        if ($cat) {
            $qs['cat'] = $cat;
        }
        if ($sheets > 0) {
            $qs['sheets'] = $sheets;
        }
        if ($codeParam !== null && $codeParam !== '') {
            $qs['code'] = (string) $codeParam;
        }
        if ($qs !== []) {
            $currentUrl .= '?' . http_build_query($qs);
        }

        $this->render('qr-coupon/index', [
            'pageTitle' => '라벨 구매 크레딧 쿠폰 — 라벨업',
            'year' => (int) date('Y'),
            'authUser' => $user,
            'page' => $page,
            'loginUrl' => url('login') . '?redirect=' . rawurlencode($currentUrl),
            'registerUrl' => url('register') . '?redirect=' . rawurlencode($currentUrl),
            'cartCount' => (new ShopService())->cartCount(),
            'eventPopups' => (new EventPopupService())->activeForSite(),
            'isPreview' => $isPreview,
            'homeUrl' => url('/'),
        ]);
    }
}
