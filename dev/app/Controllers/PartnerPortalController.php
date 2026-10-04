<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PartnerAuthService;
use App\Services\PartnerImageService;
use App\Services\ShopAdminService;
use RuntimeException;

final class PartnerPortalController extends BaseController
{
    private PartnerAuthService $auth;
    private PartnerImageService $images;

    public function __construct()
    {
        $this->auth = new PartnerAuthService();
        $this->images = new PartnerImageService();
    }

    public function loginForm(): void
    {
        if ($this->auth->fresh()) {
            redirect('/partner/images');
        }
        $error = (string) ($_SESSION['partner_login_error'] ?? '');
        unset($_SESSION['partner_login_error']);

        view('partner/login', [
            'pageTitle' => '협력사 로그인 — 라벨업',
            'error' => $error,
            'loginId' => (string) ($_SESSION['partner_login_id'] ?? ''),
        ]);
        unset($_SESSION['partner_login_id']);
    }

    public function login(): void
    {
        $loginId = trim((string) ($_POST['login_id'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        try {
            $this->auth->attempt($loginId, $password);
            unset($_SESSION['partner_login_id'], $_SESSION['partner_login_error']);
            redirect('/partner/images');
        } catch (RuntimeException $e) {
            $_SESSION['partner_login_id'] = $loginId;
            $_SESSION['partner_login_error'] = $e->getMessage();
            redirect('/partner/login');
        }
    }

    public function logout(): never
    {
        $this->auth->logout();
        redirect('/partner/login');
    }

    public function home(): void
    {
        $this->requirePartner();
        redirect('/partner/images');
    }

    public function images(): void
    {
        $partner = $this->requirePartner();
        $filters = $this->filtersFrom($_GET);
        $flash = (string) ($_SESSION['partner_flash'] ?? '');
        unset($_SESSION['partner_flash']);

        view('partner/layout', [
            'contentTemplate' => 'partner/images',
            'pageTitle' => '이미지DB — 라벨업 협력사',
            'activeMenu' => 'images',
            'partner' => $partner,
            'list' => $this->images->catalog($filters, (int) ($_GET['page'] ?? 1)),
            'categories' => (new ShopAdminService())->categories(),
            'flash' => $flash,
        ]);
    }

    public function download(): void
    {
        $this->requirePartner();
        $scope = (string) ($_POST['scope'] ?? 'selected');
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        try {
            $this->images->streamZip(
                array_map('intval', $ids),
                $scope === 'filtered',
                $this->filtersFrom($_POST)
            );
        } catch (RuntimeException $e) {
            $_SESSION['partner_flash'] = $e->getMessage();
            redirect($this->imagesUrl($this->filtersFrom($_POST)));
        }
    }

    public function downloadOne(string $id): void
    {
        $this->requirePartner();
        try {
            $this->images->streamZip([(int) $id], false, []);
        } catch (RuntimeException $e) {
            $_SESSION['partner_flash'] = $e->getMessage();
            redirect('/partner/images');
        }
    }

    /** @param array<string, mixed> $source */
    private function filtersFrom(array $source): array
    {
        $status = trim((string) ($source['product_status'] ?? ''));
        if (!in_array($status, ['active', 'soldout'], true)) {
            $status = '';
        }

        return [
            'q' => trim((string) ($source['q'] ?? '')),
            'category_id' => (int) ($source['category_id'] ?? 0),
            'product_status' => $status,
        ];
    }

    /** @param array{q?:string,category_id?:int,product_status?:string} $filters */
    private function imagesUrl(array $filters): string
    {
        $query = array_filter([
            'q' => (string) ($filters['q'] ?? ''),
            'category_id' => !empty($filters['category_id']) ? (string) (int) $filters['category_id'] : '',
            'product_status' => (string) ($filters['product_status'] ?? ''),
        ], static fn (string $value): bool => $value !== '');

        return '/partner/images' . ($query === [] ? '' : ('?' . http_build_query($query)));
    }

    private function requirePartner(): array
    {
        $partner = $this->auth->fresh();
        if (!$partner) {
            redirect('/partner/login');
        }

        return $partner;
    }
}
