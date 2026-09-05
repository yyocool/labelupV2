<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ClipartService;
use App\Services\LabelTemplateService;
use App\Services\ProductDetailPageService;
use App\Services\ShopAdminService;
use App\Services\UserAiClipartService;

final class ContentAdminController extends BaseController
{
    private AuthService $auth;
    private ClipartService $cliparts;
    private LabelTemplateService $templates;
    private UserAiClipartService $userDesigns;
    private ProductDetailPageService $detailPages;
    private ShopAdminService $shop;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->cliparts = new ClipartService();
        $this->templates = new LabelTemplateService();
        $this->userDesigns = new UserAiClipartService();
        $this->detailPages = new ProductDetailPageService();
        $this->shop = new ShopAdminService();
    }

    public function cliparts(): void
    {
        $this->requireAdmin();
        $this->cliparts->ensureDefaultCategories();

        $q = trim((string) ($_GET['q'] ?? ''));
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $tag = trim((string) ($_GET['tag'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $list = $this->cliparts->list([
            'q' => $q,
            'category_id' => $categoryId ?: null,
            'tag' => $tag,
            'page' => $page,
            'per_page' => 24,
        ]);

        view('admin/layout', [
            'contentTemplate' => 'admin/content/cliparts',
            'pageTitle' => '컨텐츠관리 › 클립아트관리 — 라벨업 관리자',
            'activeMenu' => 'content-cliparts',
            'menuGroup' => 'content',
            'crumbTitle' => '컨텐츠관리 › 클립아트관리',
            'user' => $this->auth->admin(),
            'list' => $list,
            'categories' => $this->cliparts->categories(),
            'filters' => [
                'q' => $q,
                'category_id' => $categoryId,
                'tag' => $tag,
            ],
        ]);
    }

    public function userDesigns(): void
    {
        $this->requireAdmin();

        $q = trim((string) ($_GET['q'] ?? ''));
        $userId = (int) ($_GET['user_id'] ?? 0);
        $status = array_key_exists('status', $_GET)
            ? trim((string) $_GET['status'])
            : ($userId > 0 ? '' : 'pending');
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        if (!in_array($status, ['', 'pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $filters = [
            'q' => $q,
            'user_id' => $userId,
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'page' => $page,
            'per_page' => 20,
        ];

        view('admin/layout', [
            'contentTemplate' => 'admin/content/user-designs',
            'pageTitle' => '컨텐츠관리 › 사용자디자인 — 라벨업 관리자',
            'activeMenu' => 'content-user-designs',
            'menuGroup' => 'content',
            'crumbTitle' => '컨텐츠관리 › 사용자디자인',
            'user' => $this->auth->admin(),
            'list' => $this->userDesigns->adminList($filters),
            'stats' => $this->userDesigns->adminStats(),
            'rejectReasons' => UserAiClipartService::rejectReasons(),
            'filters' => $filters,
        ]);
    }

    public function templates(): void
    {
        $this->requireAdmin();
        $this->templates->ensureSeeded();

        $q = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $list = $this->templates->list([
            'q' => $q,
            'category' => $category,
            'page' => $page,
            'per_page' => 24,
            'with_document' => true,
        ]);

        view('admin/layout', [
            'contentTemplate' => 'admin/content/templates',
            'pageTitle' => '컨텐츠관리 › 템플릿관리 — 라벨업 관리자',
            'activeMenu' => 'content-templates',
            'menuGroup' => 'content',
            'crumbTitle' => '컨텐츠관리 › 템플릿관리',
            'user' => $this->auth->admin(),
            'list' => $list,
            'categories' => $this->templates->categories(),
            'filters' => [
                'q' => $q,
                'category' => $category,
            ],
        ]);
    }

    public function productDetailPages(): void
    {
        $this->requireAdmin();

        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'registered' => trim((string) ($_GET['registered'] ?? '')),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'product_status' => trim((string) ($_GET['product_status'] ?? '')),
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'per_page' => 20,
        ];

        view('admin/layout', [
            'contentTemplate' => 'admin/content/product-detail-pages',
            'pageTitle' => '컨텐츠관리 › 상세페이지관리 — 라벨업 관리자',
            'activeMenu' => 'content-product-detail-pages',
            'menuGroup' => 'content',
            'crumbTitle' => '컨텐츠관리 › 상세페이지관리',
            'user' => $this->auth->admin(),
            'list' => $this->detailPages->adminList($filters),
            'categories' => $this->shop->categories(),
        ]);
    }

    private function requireAdmin(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
