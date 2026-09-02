<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ShopProductImageService
{
    public static function productsDir(): string
    {
        return public_path('assets/products');
    }

    public static function normalizePublicPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (!str_starts_with($path, '/')) {
            $path = '/' . ltrim($path, '/');
        }
        return $path;
    }

    public static function resolveUrl(string $path): string
    {
        $path = self::normalizePublicPath($path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $url = url(ltrim($path, '/'));
        $full = public_path(ltrim($path, '/'));
        if (is_file($full)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($full);
        }
        return $url;
    }

    public static function categoriesDir(): string
    {
        return public_path('assets/categories');
    }

    public static function specsDir(): string
    {
        return public_path('assets/specs');
    }

    private static function publicPrefixForDir(string $dir): string
    {
        if (str_contains($dir, 'categories')) {
            return '/assets/categories/';
        }
        if (str_contains($dir, 'specs')) {
            return '/assets/specs/';
        }
        if (str_contains($dir, 'cliparts')) {
            return '/assets/cliparts/';
        }

        return '/assets/products/';
    }

    /** @return array<int, string> */
    public static function storeUploadedFiles(array $files, ?string $dir = null, string $prefix = 'prod_'): array
    {
        $dir = $dir ?? self::productsDir();
        $publicPrefix = self::publicPrefixForDir($dir);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('이미지 저장 경로를 만들 수 없습니다.');
        }

        // normalize single-file uploads to multi-file shape
        if (isset($files['name']) && !is_array($files['name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type'] ?? ''],
                'tmp_name' => [$files['tmp_name'] ?? ''],
                'error' => [$files['error'] ?? UPLOAD_ERR_NO_FILE],
                'size' => [$files['size'] ?? 0],
            ];
        }

        $saved = [];
        $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = $files['tmp_name'][$i] ?? '';
            if (!is_uploaded_file($tmp)) {
                continue;
            }
            $orig = (string) ($files['name'][$i] ?? 'image');
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $ext = 'webp';
            }
            $filename = $prefix . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $dir . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($tmp, $dest)) {
                throw new RuntimeException('이미지 업로드에 실패했습니다.');
            }
            @chmod($dest, 0664);
            $saved[] = $publicPrefix . $filename;
        }

        if ($saved === []) {
            throw new RuntimeException('업로드할 이미지가 없습니다.');
        }

        return $saved;
    }

    /** @return array<int, string> */
    public static function storeProductUploads(array $files): array
    {
        return self::storeUploadedFiles($files, self::productsDir(), 'prod_');
    }

    /** @return array<int, string> */
    public static function storeCategoryUploads(array $files): array
    {
        return self::storeUploadedFiles($files, self::categoriesDir(), 'cat_');
    }

    /** @return array<int, string> */
    public static function storeSpecUploads(array $files): array
    {
        return self::storeUploadedFiles($files, self::specsDir(), 'spec_');
    }
}
