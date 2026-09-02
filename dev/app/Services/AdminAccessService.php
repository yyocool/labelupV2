<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminPermissionRepository;
use App\Repositories\UserRepository;
use Throwable;

final class AdminAccessService
{
    private UserRepository $users;
    private AdminPermissionRepository $perms;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->perms = new AdminPermissionRepository();
    }

    public function isSuper(int $adminUserId): bool
    {
        if ($adminUserId <= 0) {
            return false;
        }
        try {
            $user = $this->users->findById($adminUserId);
        } catch (Throwable) {
            return true;
        }
        if (!$user || ($user['role'] ?? '') !== 'admin') {
            return false;
        }
        return !empty($user['is_super_admin']);
    }

    /** @return array<int, string> */
    public function allowedKeys(int $adminUserId): array
    {
        if ($this->isSuper($adminUserId)) {
            return array_column(admin_menu_catalog(), 'key');
        }
        try {
            $keys = $this->perms->keysFor($adminUserId);
        } catch (Throwable) {
            return array_column(admin_menu_catalog(), 'key');
        }
        if (!in_array('dashboard', $keys, true)) {
            $keys[] = 'dashboard';
        }
        return array_values(array_unique($keys));
    }

    public function can(int $adminUserId, string $menuKey): bool
    {
        if ($menuKey === '' || $menuKey === 'dashboard') {
            return true;
        }
        if ($this->isSuper($adminUserId)) {
            return true;
        }
        return in_array($menuKey, $this->allowedKeys($adminUserId), true);
    }

    /** @return array<int, array{key:string,label:string,href:string,group:string,ic:string}> */
    public function allowedCatalog(int $adminUserId): array
    {
        $keys = array_flip($this->allowedKeys($adminUserId));
        return array_values(array_filter(
            admin_menu_catalog(),
            static fn (array $item): bool => isset($keys[$item['key']])
        ));
    }

    public static function menuKeyForPath(string $path): ?string
    {
        $path = rtrim($path, '/') ?: '/';
        $skip = [
            '/admin/login',
            '/admin/logout',
            '/api/admin/login',
            '/api/admin/favorites',
            '/api/admin/alerts',
            '/api/admin/alerts/ack',
            '/api/admin/password',
        ];
        if (in_array($path, $skip, true)) {
            return null;
        }
        if ($path === '/admin') {
            return 'dashboard';
        }

        $map = [
            '/admin/settings/admins' => 'settings-admins',
            '/api/admin/admins' => 'settings-admins',
            '/admin/settings/member-grades' => 'settings-member-grades',
            '/api/admin/member-grades' => 'settings-member-grades',
            '/admin/settings/seo' => 'settings-seo',
            '/api/admin/seo' => 'settings-seo',
            '/admin/settings/tracking' => 'settings-tracking',
            '/api/admin/marketing' => 'settings-tracking',
            '/admin/users' => 'users',
            '/api/admin/users' => 'users',
            '/api/admin/credit/adjust' => 'users',
            '/api/admin/credit/grant' => 'users',
            '/api/admin/credit/grants' => 'users',
            '/api/admin/credit/cs' => 'users',
            '/admin/settings' => 'settings',
            '/api/admin/legal' => 'settings',
            '/admin/ops/credit-rewards' => 'ops-credit-rewards',
            '/api/admin/credit/reward' => 'ops-credit-rewards',
            '/admin/ops/purchase-credits' => 'ops-purchase-credits',
            '/api/admin/credit/purchase' => 'ops-purchase-credits',
            '/api/admin/credit/codes' => 'ops-purchase-credits',
            '/admin/ops/hero-slides' => 'ops-hero-slides',
            '/api/admin/hero' => 'ops-hero-slides',
            '/admin/ops/event-popups' => 'ops-event-popups',
            '/api/admin/event-popup' => 'ops-event-popups',
            '/admin/ops/faq' => 'ops-faq',
            '/api/admin/faq' => 'ops-faq',
            '/admin/ops/inquiries' => 'ops-inquiries',
            '/api/admin/inquiry' => 'ops-inquiries',
            '/admin/ai/example-prompts' => 'ai-example-prompts',
            '/api/admin/ai/example-prompt' => 'ai-example-prompts',
            '/admin/ai/usage' => 'ai-usage',
            '/admin/content/cliparts' => 'content-cliparts',
            '/api/admin/content/clipart' => 'content-cliparts',
            '/admin/content/user-designs' => 'content-user-designs',
            '/api/admin/content/user-design' => 'content-user-designs',
            '/admin/content/templates' => 'content-templates',
            '/api/admin/content/template' => 'content-templates',
            '/admin/shop/categories' => 'shop-categories',
            '/api/admin/shop/category' => 'shop-categories',
            '/admin/shop/specs' => 'shop-specs',
            '/api/admin/shop/spec' => 'shop-specs',
            '/admin/shop/products' => 'shop-products',
            '/api/admin/shop/product' => 'shop-products',
            '/admin/shop/orders' => 'shop-orders',
            '/api/admin/shop/order' => 'shop-orders',
            '/admin/shop/shipping' => 'shop-shipping',
            '/admin/shop/coupons' => 'shop-coupons',
            '/api/admin/shop/coupon' => 'shop-coupons',
            '/admin/shop/banners' => 'shop-banners',
            '/api/admin/shop/banner' => 'shop-banners',
        ];
        uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($map as $prefix => $key) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $key;
            }
        }
        return null;
    }
}
