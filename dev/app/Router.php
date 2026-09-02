<?php

declare(strict_types=1);

namespace App;

use App\Controllers\AdminController;
use App\Controllers\Api\CreditAdminApiController;
use App\Controllers\Api\LegalApiController;
use App\Controllers\Api\ShopAdminApiController;
use App\Controllers\Api\ShopApiController;
use App\Controllers\ContentAdminController;
use App\Controllers\Api\ContentAdminApiController;
use App\Controllers\CreditAdminController;
use App\Controllers\EventPopupAdminController;
use App\Controllers\Api\EventPopupAdminApiController;
use App\Controllers\FaqAdminController;
use App\Controllers\Api\FaqAdminApiController;
use App\Controllers\FaqController;
use App\Controllers\HeroAdminController;
use App\Controllers\Api\HeroAdminApiController;
use App\Controllers\ShopAdminController;
use App\Controllers\ShopController;
use App\Controllers\Api\AdminApiController;
use App\Controllers\Api\AdminWorkspaceApiController;
use App\Controllers\Api\InquiryApiController;
use App\Controllers\InquiryAdminController;
use App\Controllers\AdminAccountController;
use App\Controllers\Api\AdminAccountApiController;
use App\Controllers\AiAdminController;
use App\Controllers\Api\AiAdminApiController;
use App\Controllers\Api\AiChatApiController;
use App\Controllers\Api\AiExamplePromptApiController;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\EditorTemplateApiController;
use App\Controllers\Api\EditorWorkspaceApiController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\SeedController;
use App\Controllers\Api\SystemController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\SeoAdminController;
use App\Controllers\SeoPublicController;
use App\Controllers\Api\SeoAdminApiController;
use App\Controllers\MemberGradeAdminController;
use App\Controllers\Api\MemberGradeAdminApiController;

final class Router
{
    /** @var array<int, array{method:string,pattern:string,handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): self
    {
        return $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): self
    {
        return $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): self
    {
        $this->routes[] = compact('method', 'pattern', 'handler');
        return $this;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']) . '$#';
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }

            $params = array_filter($matches, static fn ($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
            ($route['handler'])(...array_values($params));
            return;
        }

        http_response_code(404);
        if (str_starts_with($path, '/api/')) {
            \App\Helpers\ApiResponse::error('Not Found', null, 404);
        }
        try {
            if ((new SeoPublicController())->tryServeFile($path)) {
                return;
            }
        } catch (\Throwable) {
        }
        view('errors/404');
    }

    public static function register(): self
    {
        $router = new self();
        $home = new HomeController();
        $auth = new AuthController();
        $admin = new AdminController();
        $health = new HealthController();
        $system = new SystemController();
        $seed = new SeedController();
        $authApi = new AuthApiController();
        $editorWorkspaceApi = new EditorWorkspaceApiController();
        $aiChatApi = new AiChatApiController();
        $aiPromptsPublic = new AiExamplePromptApiController();
        $aiAdmin = new AiAdminController();
        $aiAdminApi = new AiAdminApiController();
        $adminApi = new AdminApiController();
        $adminWorkspaceApi = new AdminWorkspaceApiController();
        $inquiryAdmin = new InquiryAdminController();
        $inquiryApi = new InquiryApiController();
        $adminAccount = new AdminAccountController();
        $adminAccountApi = new AdminAccountApiController();
        $legalApi = new LegalApiController();
        $shop = new ShopAdminController();
        $shopApi = new ShopAdminApiController();
        $creditAdmin = new CreditAdminController();
        $creditAdminApi = new CreditAdminApiController();
        $heroAdmin = new HeroAdminController();
        $heroAdminApi = new HeroAdminApiController();
        $eventPopupAdmin = new EventPopupAdminController();
        $eventPopupAdminApi = new EventPopupAdminApiController();
        $faqAdmin = new FaqAdminController();
        $faqAdminApi = new FaqAdminApiController();
        $faqPublic = new FaqController();
        $contentAdmin = new ContentAdminController();
        $contentAdminApi = new ContentAdminApiController();
        $editorTemplateApi = new EditorTemplateApiController();
        $shopPublic = new ShopController();
        $shopPublicApi = new ShopApiController();
        $seoAdmin = new SeoAdminController();
        $seoAdminApi = new SeoAdminApiController();
        $seoPublic = new SeoPublicController();
        $memberGradeAdmin = new MemberGradeAdminController();
        $memberGradeAdminApi = new MemberGradeAdminApiController();

        $router->get('/', [$home, 'index']);
        $router->get('/faq', [$faqPublic, 'index']);

        $router->get('/shop', [$shopPublic, 'index']);
        $router->get('/shop/products', [$shopPublic, 'products']);
        $router->get('/shop/products/{id}', [$shopPublic, 'product']);
        $router->get('/shop/cart', [$shopPublic, 'cart']);

        $router->get('/login', [$auth, 'loginForm']);
        $router->get('/register', [$auth, 'registerForm']);
        $router->get('/reset-password', [$auth, 'resetPasswordForm']);
        $router->get('/account', [$auth, 'account']);
        $router->get('/logout', [$auth, 'logout']);

        $router->get('/admin/login', [$admin, 'loginForm']);
        $router->get('/admin/logout', [$admin, 'logout']);
        $router->get('/admin', [$admin, 'dashboard']);
        $router->get('/admin/users', [$admin, 'users']);
        $router->get('/admin/users/{id}', [$admin, 'userDetail']);
        $router->get('/admin/settings', [$admin, 'settings']);
        $router->get('/admin/settings/admins', [$adminAccount, 'index']);
        $router->get('/admin/settings/member-grades', [$memberGradeAdmin, 'index']);
        $router->get('/admin/settings/seo', [$seoAdmin, 'seo']);
        $router->get('/admin/settings/tracking', [$seoAdmin, 'marketing']);

        $router->get('/robots.txt', [$seoPublic, 'robots']);
        $router->get('/sitemap.xml', [$seoPublic, 'sitemap']);
        $router->get('/ads.txt', [$seoPublic, 'adsTxt']);
        $router->get('/app-ads.txt', [$seoPublic, 'appAdsTxt']);

        $router->get('/admin/ops/credit-rewards', [$creditAdmin, 'rewardRules']);
        $router->get('/admin/ops/purchase-credits', [$creditAdmin, 'purchaseCredits']);
        $router->get('/admin/ops/hero-slides', [$heroAdmin, 'index']);
        $router->get('/admin/ops/event-popups', [$eventPopupAdmin, 'index']);
        $router->get('/admin/ops/faq', [$faqAdmin, 'index']);
        $router->get('/admin/ops/inquiries', [$inquiryAdmin, 'index']);
        $router->get('/admin/ai/example-prompts', [$aiAdmin, 'examplePrompts']);
        $router->get('/admin/ai/usage', [$aiAdmin, 'usage']);
        $router->get('/admin/content/cliparts', [$contentAdmin, 'cliparts']);
        $router->get('/admin/content/user-designs', [$contentAdmin, 'userDesigns']);
        $router->get('/admin/content/templates', [$contentAdmin, 'templates']);

        $router->get('/admin/shop/categories', [$shop, 'categories']);
        $router->get('/admin/shop/specs', [$shop, 'specs']);
        $router->get('/admin/shop/products', [$shop, 'products']);
        $router->get('/admin/shop/orders', [$shop, 'orders']);
        $router->get('/admin/shop/orders/export', [$shop, 'ordersExport']);
        $router->get('/admin/shop/shipping', [$shop, 'shipping']);
        $router->get('/admin/shop/coupons', [$shop, 'coupons']);
        $router->get('/admin/shop/banners', [$shop, 'banners']);

        $router->get('/api/health', [$health, 'index']);
        $router->post('/api/system/migrate', [$system, 'migrate']);
        $router->post('/api/system/seed-admin', [$seed, 'admin']);

        $router->get('/api/legal/{key}', [$legalApi, 'show']);

        $router->post('/api/auth/register', [$authApi, 'register']);
        $router->post('/api/auth/login', [$authApi, 'login']);
        $router->post('/api/auth/logout', [$authApi, 'logout']);
        $router->get('/api/auth/me', [$authApi, 'me']);
        $router->get('/api/auth/check-email', [$authApi, 'checkEmail']);
        $router->post('/api/auth/profile', [$authApi, 'updateProfile']);
        $router->post('/api/auth/password', [$authApi, 'changePassword']);
        $router->post('/api/auth/withdraw', [$authApi, 'withdraw']);
        $router->post('/api/auth/find-email', [$authApi, 'findEmail']);
        $router->post('/api/auth/password-reset/request', [$authApi, 'requestPasswordReset']);
        $router->post('/api/auth/password-reset/confirm', [$authApi, 'resetPassword']);

        $router->get('/api/editor/workspaces', [$editorWorkspaceApi, 'index']);
        $router->get('/api/editor/workspace', [$editorWorkspaceApi, 'show']);
        $router->post('/api/editor/workspace', [$editorWorkspaceApi, 'save']);
        $router->get('/api/editor/templates', [$editorTemplateApi, 'index']);
        $router->get('/api/editor/templates/{id}', [$editorTemplateApi, 'show']);

        $router->post('/api/ai/chat', [$aiChatApi, 'chat']);
        $router->get('/api/ai/example-prompts', [$aiPromptsPublic, 'index']);

        $router->post('/api/admin/ai/example-prompt/save', [$aiAdminApi, 'savePrompt']);
        $router->post('/api/admin/ai/example-prompt/delete', [$aiAdminApi, 'deletePrompt']);

        $router->get('/api/admin/favorites', [$adminWorkspaceApi, 'favorites']);
        $router->post('/api/admin/favorites', [$adminWorkspaceApi, 'saveFavorites']);
        $router->get('/api/admin/alerts', [$adminWorkspaceApi, 'alerts']);
        $router->post('/api/admin/alerts/ack', [$adminWorkspaceApi, 'ackAlerts']);
        $router->post('/api/inquiry', [$inquiryApi, 'submit']);
        $router->post('/api/admin/inquiry/update', [$inquiryApi, 'update']);

        $router->get('/api/admin/admins/lookup', [$adminAccountApi, 'lookup']);
        $router->post('/api/admin/admins/save', [$adminAccountApi, 'save']);
        $router->post('/api/admin/admins/revoke', [$adminAccountApi, 'revoke']);
        $router->post('/api/admin/member-grades/save', [$memberGradeAdminApi, 'save']);
        $router->post('/api/admin/member-grades/delete', [$memberGradeAdminApi, 'delete']);
        $router->post('/api/admin/seo/save', [$seoAdminApi, 'saveSeo']);
        $router->post('/api/admin/seo/page', [$seoAdminApi, 'savePage']);
        $router->post('/api/admin/marketing/save', [$seoAdminApi, 'saveMarketing']);
        $router->post('/api/admin/marketing/file', [$seoAdminApi, 'saveFile']);
        $router->post('/api/admin/marketing/file/delete', [$seoAdminApi, 'deleteFile']);

        $router->post('/api/admin/login', [$adminApi, 'login']);
        $router->post('/api/admin/password', [$adminApi, 'changePassword']);
        $router->post('/api/admin/users/update', [$adminApi, 'updateUser']);
        $router->post('/api/admin/legal/update', [$adminApi, 'updateLegal']);

        $router->post('/api/admin/credit/reward/save', [$creditAdminApi, 'saveRewardRule']);
        $router->post('/api/admin/credit/reward/delete', [$creditAdminApi, 'deleteRewardRule']);
        $router->post('/api/admin/credit/purchase-product/save', [$creditAdminApi, 'savePurchaseProduct']);
        $router->post('/api/admin/credit/purchase-product/delete', [$creditAdminApi, 'deletePurchaseProduct']);
        $router->post('/api/admin/credit/codes/generate', [$creditAdminApi, 'generateCodes']);
        $router->post('/api/admin/credit/adjust', [$creditAdminApi, 'adjustCredit']);
        $router->post('/api/admin/credit/grant', [$creditAdminApi, 'grantCredit']);
        $router->get('/api/admin/credit/grants', [$creditAdminApi, 'grantHistory']);
        $router->post('/api/admin/credit/cs/save', [$creditAdminApi, 'saveCsLog']);

        $router->post('/api/admin/hero/slide/save', [$heroAdminApi, 'save']);
        $router->post('/api/admin/hero/slide/delete', [$heroAdminApi, 'delete']);

        $router->post('/api/admin/event-popup/save', [$eventPopupAdminApi, 'save']);
        $router->post('/api/admin/event-popup/delete', [$eventPopupAdminApi, 'delete']);

        $router->post('/api/admin/faq/save', [$faqAdminApi, 'save']);
        $router->post('/api/admin/faq/delete', [$faqAdminApi, 'delete']);
        $router->post('/api/admin/faq/category/save', [$faqAdminApi, 'saveCategory']);
        $router->post('/api/admin/faq/category/delete', [$faqAdminApi, 'deleteCategory']);

        $router->post('/api/admin/content/clipart/save', [$contentAdminApi, 'saveClipart']);
        $router->post('/api/admin/content/clipart/delete', [$contentAdminApi, 'deleteClipart']);
        $router->post('/api/admin/content/clipart/upload', [$contentAdminApi, 'uploadClipart']);
        $router->post('/api/admin/content/clipart/seed', [$contentAdminApi, 'seedCliparts']);
        $router->post('/api/admin/content/category/save', [$contentAdminApi, 'saveCategory']);
        $router->post('/api/admin/content/template/save', [$contentAdminApi, 'saveTemplate']);
        $router->post('/api/admin/content/template/delete', [$contentAdminApi, 'deleteTemplate']);
        $router->post('/api/admin/content/template/seed', [$contentAdminApi, 'seedTemplates']);
        $router->post('/api/admin/content/user-design/review', [$contentAdminApi, 'reviewUserDesign']);
        $router->post('/api/admin/content/user-design/approve-batch', [$contentAdminApi, 'approveUserDesigns']);
        $router->post('/api/admin/content/user-design/delete', [$contentAdminApi, 'deleteUserDesign']);

        $router->post('/api/admin/shop/category/save', [$shopApi, 'saveCategory']);
        $router->post('/api/admin/shop/category/upload-images', [$shopApi, 'uploadCategoryImages']);
        $router->post('/api/admin/shop/category/delete', [$shopApi, 'deleteCategory']);
        $router->post('/api/admin/shop/spec/save', [$shopApi, 'saveSpec']);
        $router->post('/api/admin/shop/spec/upload-images', [$shopApi, 'uploadSpecImages']);
        $router->post('/api/admin/shop/spec/delete', [$shopApi, 'deleteSpec']);
        $router->post('/api/admin/shop/product/save', [$shopApi, 'saveProduct']);
        $router->post('/api/admin/shop/product/upload-images', [$shopApi, 'uploadProductImages']);
        $router->post('/api/admin/shop/product/delete', [$shopApi, 'deleteProduct']);
        $router->post('/api/admin/shop/product/compat-save', [$shopApi, 'saveProductCompat']);
        $router->post('/api/admin/shop/order/update', [$shopApi, 'updateOrder']);
        $router->post('/api/admin/shop/order/detail', [$shopApi, 'orderDetail']);
        $router->post('/api/admin/shop/order/bulk', [$shopApi, 'bulkUpdateOrders']);
        $router->post('/api/admin/shop/coupon/save', [$shopApi, 'saveCoupon']);
        $router->post('/api/admin/shop/coupon/delete', [$shopApi, 'deleteCoupon']);
        $router->post('/api/admin/shop/banner/save', [$shopApi, 'saveBanner']);
        $router->post('/api/admin/shop/banner/delete', [$shopApi, 'deleteBanner']);

        $router->get('/api/shop/editor-papers', [$shopPublicApi, 'editorPapers']);
        $router->get('/api/shop/catalog', [$shopPublicApi, 'catalog']);
        $router->get('/api/shop/lookup', [$shopPublicApi, 'lookup']);
        $router->get('/api/shop/products/{id}', [$shopPublicApi, 'product']);
        $router->get('/api/shop/cart', [$shopPublicApi, 'cart']);
        $router->post('/api/shop/cart/add', [$shopPublicApi, 'addCart']);
        $router->post('/api/shop/cart/update', [$shopPublicApi, 'updateCart']);
        $router->post('/api/shop/cart/remove', [$shopPublicApi, 'removeCart']);
        $router->post('/api/shop/checkout', [$shopPublicApi, 'checkout']);

        return $router;
    }
}
