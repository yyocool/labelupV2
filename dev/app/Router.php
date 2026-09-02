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
use App\Controllers\HeroAdminController;
use App\Controllers\Api\HeroAdminApiController;
use App\Controllers\ShopAdminController;
use App\Controllers\ShopController;
use App\Controllers\Api\AdminApiController;
use App\Controllers\Api\AiChatApiController;
use App\Controllers\Api\AuthApiController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\SeedController;
use App\Controllers\Api\SystemController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;

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
        $aiChatApi = new AiChatApiController();
        $adminApi = new AdminApiController();
        $legalApi = new LegalApiController();
        $shop = new ShopAdminController();
        $shopApi = new ShopAdminApiController();
        $creditAdmin = new CreditAdminController();
        $creditAdminApi = new CreditAdminApiController();
        $heroAdmin = new HeroAdminController();
        $heroAdminApi = new HeroAdminApiController();
        $eventPopupAdmin = new EventPopupAdminController();
        $eventPopupAdminApi = new EventPopupAdminApiController();
        $contentAdmin = new ContentAdminController();
        $contentAdminApi = new ContentAdminApiController();
        $shopPublic = new ShopController();
        $shopPublicApi = new ShopApiController();

        $router->get('/', [$home, 'index']);

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

        $router->get('/admin/ops/credit-rewards', [$creditAdmin, 'rewardRules']);
        $router->get('/admin/ops/purchase-credits', [$creditAdmin, 'purchaseCredits']);
        $router->get('/admin/ops/hero-slides', [$heroAdmin, 'index']);
        $router->get('/admin/ops/event-popups', [$eventPopupAdmin, 'index']);
        $router->get('/admin/content/cliparts', [$contentAdmin, 'cliparts']);

        $router->get('/admin/shop/categories', [$shop, 'categories']);
        $router->get('/admin/shop/specs', [$shop, 'specs']);
        $router->get('/admin/shop/products', [$shop, 'products']);
        $router->get('/admin/shop/orders', [$shop, 'orders']);
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

        $router->post('/api/ai/chat', [$aiChatApi, 'chat']);

        $router->post('/api/admin/login', [$adminApi, 'login']);
        $router->post('/api/admin/users/update', [$adminApi, 'updateUser']);
        $router->post('/api/admin/legal/update', [$adminApi, 'updateLegal']);

        $router->post('/api/admin/credit/reward/save', [$creditAdminApi, 'saveRewardRule']);
        $router->post('/api/admin/credit/reward/delete', [$creditAdminApi, 'deleteRewardRule']);
        $router->post('/api/admin/credit/purchase-product/save', [$creditAdminApi, 'savePurchaseProduct']);
        $router->post('/api/admin/credit/purchase-product/delete', [$creditAdminApi, 'deletePurchaseProduct']);
        $router->post('/api/admin/credit/codes/generate', [$creditAdminApi, 'generateCodes']);
        $router->post('/api/admin/credit/adjust', [$creditAdminApi, 'adjustCredit']);
        $router->post('/api/admin/credit/cs/save', [$creditAdminApi, 'saveCsLog']);

        $router->post('/api/admin/hero/slide/save', [$heroAdminApi, 'save']);
        $router->post('/api/admin/hero/slide/delete', [$heroAdminApi, 'delete']);

        $router->post('/api/admin/event-popup/save', [$eventPopupAdminApi, 'save']);
        $router->post('/api/admin/event-popup/delete', [$eventPopupAdminApi, 'delete']);

        $router->post('/api/admin/content/clipart/save', [$contentAdminApi, 'saveClipart']);
        $router->post('/api/admin/content/clipart/delete', [$contentAdminApi, 'deleteClipart']);
        $router->post('/api/admin/content/clipart/upload', [$contentAdminApi, 'uploadClipart']);
        $router->post('/api/admin/content/clipart/seed', [$contentAdminApi, 'seedCliparts']);
        $router->post('/api/admin/content/category/save', [$contentAdminApi, 'saveCategory']);

        $router->post('/api/admin/shop/category/save', [$shopApi, 'saveCategory']);
        $router->post('/api/admin/shop/category/upload-images', [$shopApi, 'uploadCategoryImages']);
        $router->post('/api/admin/shop/category/delete', [$shopApi, 'deleteCategory']);
        $router->post('/api/admin/shop/spec/save', [$shopApi, 'saveSpec']);
        $router->post('/api/admin/shop/spec/upload-images', [$shopApi, 'uploadSpecImages']);
        $router->post('/api/admin/shop/spec/delete', [$shopApi, 'deleteSpec']);
        $router->post('/api/admin/shop/product/save', [$shopApi, 'saveProduct']);
        $router->post('/api/admin/shop/product/upload-images', [$shopApi, 'uploadProductImages']);
        $router->post('/api/admin/shop/product/delete', [$shopApi, 'deleteProduct']);
        $router->post('/api/admin/shop/order/update', [$shopApi, 'updateOrder']);
        $router->post('/api/admin/shop/coupon/save', [$shopApi, 'saveCoupon']);
        $router->post('/api/admin/shop/coupon/delete', [$shopApi, 'deleteCoupon']);
        $router->post('/api/admin/shop/banner/save', [$shopApi, 'saveBanner']);
        $router->post('/api/admin/shop/banner/delete', [$shopApi, 'deleteBanner']);

        $router->get('/api/shop/cart', [$shopPublicApi, 'cart']);
        $router->post('/api/shop/cart/add', [$shopPublicApi, 'addCart']);
        $router->post('/api/shop/cart/update', [$shopPublicApi, 'updateCart']);
        $router->post('/api/shop/cart/remove', [$shopPublicApi, 'removeCart']);

        return $router;
    }
}
