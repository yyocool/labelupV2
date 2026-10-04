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

    public static function pageSettingsDir(): string
    {
        return public_path('assets/shop-page');
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
        if (str_contains($dir, 'shop-page')) {
            return '/assets/shop-page/';
        }
        if (str_contains($dir, DIRECTORY_SEPARATOR . 'hero') || str_ends_with($dir, '/hero') || str_ends_with($dir, '\\hero')) {
            return '/assets/hero/';
        }

        return '/assets/products/';
    }

    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /** PHP 업로드 오류 코드별 안내. 그냥 건너뛰면 왜 실패했는지 알 수 없다. */
    private const UPLOAD_ERRORS = [
        UPLOAD_ERR_INI_SIZE => '서버 허용 용량(upload_max_filesize)을 넘었습니다.',
        UPLOAD_ERR_FORM_SIZE => '폼이 허용한 용량을 넘었습니다.',
        UPLOAD_ERR_PARTIAL => '전송이 중간에 끊겼습니다. 다시 시도해주세요.',
        UPLOAD_ERR_NO_FILE => '파일이 전송되지 않았습니다.',
        UPLOAD_ERR_NO_TMP_DIR => '서버에 업로드 임시 폴더가 없습니다.',
        UPLOAD_ERR_CANT_WRITE => '서버가 임시 파일을 디스크에 쓰지 못했습니다.',
        UPLOAD_ERR_EXTENSION => 'PHP 확장이 업로드를 중단시켰습니다.',
    ];

    /** 단일 업로드도 복수 업로드와 같은 모양으로 맞춘다. */
    private static function normalizeFilesShape(array $files): array
    {
        if (isset($files['name']) && !is_array($files['name'])) {
            return [
                'name' => [$files['name']],
                'type' => [$files['type'] ?? ''],
                'tmp_name' => [$files['tmp_name'] ?? ''],
                'error' => [$files['error'] ?? UPLOAD_ERR_NO_FILE],
                'size' => [$files['size'] ?? 0],
            ];
        }
        return $files;
    }

    /**
     * 가로를 $targetWidth 로 맞추고 세로는 비율을 유지한다.
     * 이미 그 폭이면 파일을 건드리지 않는다.
     */
    public static function scaleFileToWidth(string $path, int $targetWidth): bool
    {
        if ($targetWidth < 1 || !is_file($path) || !function_exists('imagecreatetruecolor')) {
            return false;
        }
        $info = @getimagesize($path);
        if ($info === false) {
            return false;
        }
        $srcW = (int) ($info[0] ?? 0);
        $srcH = (int) ($info[1] ?? 0);
        $type = (int) ($info[2] ?? 0);
        if ($srcW < 1 || $srcH < 1 || $srcW === $targetWidth) {
            return $srcW === $targetWidth;
        }

        $src = self::loadGdImage($path, $type);
        if ($src === null) {
            return false;
        }
        if (!imageistruecolor($src)) {
            $true = imagecreatetruecolor($srcW, $srcH);
            if ($true === false) {
                imagedestroy($src);
                return false;
            }
            imagealphablending($true, false);
            imagesavealpha($true, true);
            $clear = imagecolorallocatealpha($true, 0, 0, 0, 127);
            imagefilledrectangle($true, 0, 0, $srcW, $srcH, $clear);
            imagecopy($true, $src, 0, 0, 0, 0, $srcW, $srcH);
            imagedestroy($src);
            $src = $true;
        }

        $destH = max(1, (int) round($srcH * ($targetWidth / $srcW)));
        $dst = imagecreatetruecolor($targetWidth, $destH);
        if ($dst === false) {
            imagedestroy($src);
            return false;
        }
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $clear = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $targetWidth, $destH, $clear);
        imagealphablending($dst, true);
        if (function_exists('imagesetinterpolation')) {
            imagesetinterpolation($dst, IMG_BICUBIC);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $destH, $srcW, $srcH);
        imagedestroy($src);

        $tmp = $path . '.scaled';
        $saved = self::saveGdImage($dst, $tmp, $type);
        imagedestroy($dst);
        if (!$saved) {
            @unlink($tmp);
            return false;
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            return false;
        }
        @chmod($path, 0664);
        return true;
    }

    /** @return \GdImage|null */
    private static function loadGdImage(string $path, int $type)
    {
        $im = match ($type) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : false,
            IMAGETYPE_PNG => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : false,
            IMAGETYPE_GIF => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : false,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
        return $im === false ? null : $im;
    }

    /** @param \GdImage $im */
    private static function saveGdImage($im, string $path, int $type): bool
    {
        return match ($type) {
            IMAGETYPE_JPEG => function_exists('imagejpeg') && @imagejpeg($im, $path, 90),
            IMAGETYPE_GIF => function_exists('imagegif') && @imagegif($im, $path),
            IMAGETYPE_WEBP => function_exists('imagewebp') && @imagewebp($im, $path, 90),
            default => function_exists('imagepng') && @imagepng($im, $path, 6),
        };
    }

    /**
     * 파일별 저장 결과를 보낸 순서 그대로 돌려준다.
     * 실패한 칸도 건너뛰지 않고 채워야 호출 측에서 파일과 경로가 어긋나지 않는다.
     *
     * @return array<int, array{name: string, size: int, path: string, error: string}>
     */
    public static function storeUploadedFilesDetailed(array $files, ?string $dir = null, string $prefix = 'prod_', int $fitWidth = 0): array
    {
        $dir = $dir ?? self::productsDir();
        $publicPrefix = self::publicPrefixForDir($dir);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('이미지 저장 경로를 만들 수 없습니다.');
        }

        $files = self::normalizeFilesShape($files);

        $results = [];
        $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            $name = (string) ($files['name'][$i] ?? 'image');
            $size = (int) ($files['size'][$i] ?? 0);
            $fail = static function (string $error) use ($name, $size): array {
                return ['name' => $name, 'size' => $size, 'path' => '', 'error' => $error];
            };

            $code = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($code !== UPLOAD_ERR_OK) {
                $results[] = $fail(self::UPLOAD_ERRORS[$code] ?? "업로드 오류 (코드 {$code})");
                continue;
            }
            $tmp = (string) ($files['tmp_name'][$i] ?? '');
            if (!is_uploaded_file($tmp)) {
                $results[] = $fail('업로드된 파일이 아닙니다.');
                continue;
            }
            if ($size <= 0 || filesize($tmp) === 0) {
                $results[] = $fail('내용이 비어 있는 파일입니다.');
                continue;
            }
            if (@getimagesize($tmp) === false) {
                $results[] = $fail('이미지로 읽을 수 없는 파일입니다. 다른 형식으로 저장해 보세요.');
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, self::ALLOWED_EXT, true)) {
                $ext = 'webp';
            }
            $filename = $prefix . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $dir . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($tmp, $dest)) {
                $results[] = $fail('서버에 파일을 저장하지 못했습니다.');
                continue;
            }
            if ($fitWidth > 0) {
                self::scaleFileToWidth($dest, $fitWidth);
            }
            @chmod($dest, 0664);
            $savedSize = (int) (@filesize($dest) ?: $size);
            $results[] = ['name' => $name, 'size' => $savedSize, 'path' => $publicPrefix . $filename, 'error' => ''];
        }

        return $results;
    }

    /** @return array<int, string> */
    public static function storeUploadedFiles(array $files, ?string $dir = null, string $prefix = 'prod_'): array
    {
        $saved = [];
        $errors = [];
        foreach (self::storeUploadedFilesDetailed($files, $dir, $prefix) as $result) {
            if ($result['path'] !== '') {
                $saved[] = $result['path'];
                continue;
            }
            // 빈 input 칸은 오류로 보지 않는다.
            if ($result['error'] !== self::UPLOAD_ERRORS[UPLOAD_ERR_NO_FILE]) {
                $errors[] = $result['name'] . ' — ' . $result['error'];
            }
        }

        if ($errors !== []) {
            throw new RuntimeException('이미지 업로드에 실패했습니다. ' . implode(' / ', $errors));
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

    /**
     * 상세페이지 에디터는 한 장이 실패해도 나머지는 살려야 하므로
     * 예외 대신 파일별 결과를 그대로 넘긴다.
     *
     * @return array<int, array{name: string, size: int, path: string, error: string}>
     */
    public static function storePageSettingUploads(array $files, int $fitWidth = 0): array
    {
        return self::storeUploadedFilesDetailed($files, self::pageSettingsDir(), 'page_', $fitWidth);
    }
}
