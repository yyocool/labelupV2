<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductDetailPageRepository;
use App\Repositories\ShopRepository;
use RuntimeException;
use ZipArchive;

final class ProductDetailPageService
{
    private ProductDetailPageRepository $repo;
    private ShopRepository $shopRepo;

    public function __construct()
    {
        $this->repo = new ProductDetailPageRepository();
        $this->shopRepo = new ShopRepository();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{
     *   items: array<int, array<string, mixed>>,
     *   total: int,
     *   page: int,
     *   pages: int,
     *   summary: array{total:int,registered:int,unregistered:int},
     *   filters: array<string, mixed>
     * }
     */
    public function adminList(array $filters): array
    {
        $normalized = [
            'q' => trim((string) ($filters['q'] ?? '')),
            'registered' => $this->normalizeRegistered((string) ($filters['registered'] ?? '')),
            'category_id' => (int) ($filters['category_id'] ?? 0),
            'product_status' => $this->normalizeProductStatus((string) ($filters['product_status'] ?? '')),
        ];
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));

        $list = $this->repo->adminList($normalized, $page, $perPage);
        $list['summary'] = $this->repo->summary($normalized);
        $list['filters'] = $normalized + ['page' => $list['page'], 'per_page' => $perPage];

        return $list;
    }

    /** @return array<string, mixed> */
    public function getForEdit(int $productId): array
    {
        $product = $this->requireProduct($productId);
        $row = $this->repo->findByProductId($productId);
        $stored = self::imagesOnlyHtml((string) ($row['html_content'] ?? ''));
        $html = $stored !== '' ? $stored : $this->composedImageHtml($product);
        return [
            'product_id' => $productId,
            'product_name' => (string) ($product['name'] ?? ''),
            'sku' => (string) ($product['sku'] ?? ''),
            'category_name' => (string) ($product['category_name'] ?? ''),
            'html_content' => $html,
            'status' => (string) ($row['status'] ?? ''),
            'has_detail_page' => $row !== null && $stored !== '',
        ];
    }

    /**
     * 상세페이지 이미지 버튼과 같은 구성.
     * 공통헤더, 카테고리헤더, 헤더이미지, 상품규격, 사용예, 카테고리푸터, 공통푸터.
     *
     * @return array<int, array{path:string,name:string}>
     */
    public function imageFiles(int $productId): array
    {
        return $this->collectImageFiles($this->requireProduct($productId));
    }

    public function streamImageZip(int $productId): void
    {
        $product = $this->requireProduct($productId);
        $files = $this->collectImageFiles($product);
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('압축 다운로드를 사용할 수 없습니다.');
        }
        if ($files === []) {
            throw new RuntimeException('받을 수 있는 이미지가 없습니다.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pdpimg');
        if ($tmp === false) {
            throw new RuntimeException('다운로드 파일을 만들지 못했습니다.');
        }
        $zipPath = $tmp . '.zip';
        @unlink($tmp);
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('다운로드 파일을 만들지 못했습니다.');
        }
        $flags = defined('ZipArchive::FL_ENC_UTF_8') ? ZipArchive::FL_ENC_UTF_8 : 0;
        foreach ($files as $file) {
            $zip->addFile($file['path'], $file['name'], 0, 0, $flags);
        }
        $zip->close();

        $sku = $this->safeZipLabel((string) ($product['sku'] ?? ''));
        if ($sku === 'image') {
            $sku = 'product-' . $productId;
        }
        $downloadName = $sku . '-상세이미지.zip';
        $fallbackName = $sku . '-images.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName));
        header('Content-Length: ' . (string) filesize($zipPath));
        header('Cache-Control: no-store');
        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }

    /**
     * @param array<string, mixed> $product
     * @return array<int, array{path:string,name:string}>
     */
    private function collectImageFiles(array $product): array
    {
        $layout = (new ShopService())->productPageLayout((int) ($product['category_id'] ?? 0));
        $files = [];
        $seq = 1;
        $add = function (string $publicPath, string $label) use (&$files, &$seq): void {
            $path = $this->safeAsset($publicPath);
            if ($path === null) {
                return;
            }
            $files[] = [
                'path' => $path,
                'name' => sprintf('%02d_%s.%s', $seq, $this->safeZipLabel($label), $this->imageExt($path)),
            ];
            $seq++;
        };

        foreach ($layout['header_blocks'] ?? [] as $block) {
            if (!is_array($block)) {
                continue;
            }
            $add((string) ($block['image'] ?? ''), $this->layoutImageLabel($block, true));
        }
        $add((string) ($product['header_image'] ?? ''), '헤더이미지');
        $add((string) ($product['spec_sheet_image'] ?? ''), '상품규격');
        $add((string) ($product['shoot_image'] ?? ''), '사용예');
        foreach ($layout['footer_blocks'] ?? [] as $block) {
            if (!is_array($block)) {
                continue;
            }
            $add((string) ($block['image'] ?? ''), $this->layoutImageLabel($block, false));
        }

        return $files;
    }

    /** @param array<string, mixed> $block */
    private function layoutImageLabel(array $block, bool $header): string
    {
        if (($block['source'] ?? '') === 'global') {
            return $header ? '공통헤더' : '공통푸터';
        }
        $name = trim((string) ($block['category_name'] ?? ''));
        $prefix = $header ? '카테고리헤더' : '카테고리푸터';
        return $name !== '' ? ($prefix . '_' . $name) : $prefix;
    }

    private function safeAsset(string $publicPath): ?string
    {
        $src = trim($publicPath);
        if ($src === '') {
            return null;
        }
        $src = preg_replace('/[?#].*$/', '', $src) ?? $src;
        if (preg_match('#^https?://[^/]+(/assets/.+)$#i', $src, $match) === 1) {
            $src = $match[1];
        }
        $src = ShopProductImageService::normalizePublicPath($src);
        if (!str_starts_with($src, '/assets/') || str_contains($src, '..')) {
            return null;
        }
        $allowed = false;
        foreach (['/assets/shop-page/', '/assets/product-headers/', '/assets/spec-sheets/', '/assets/product-shoots/'] as $prefix) {
            if (str_starts_with($src, $prefix)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            return null;
        }
        $full = public_path(ltrim($src, '/'));
        if (!is_file($full)) {
            return null;
        }
        $real = realpath($full);
        $root = realpath(public_path('assets'));
        if ($real === false || $root === false) {
            return null;
        }
        $realNorm = str_replace('\\', '/', $real);
        $rootNorm = rtrim(str_replace('\\', '/', $root), '/') . '/';
        if (!str_starts_with($realNorm, $rootNorm)) {
            return null;
        }
        return $real;
    }

    private function safeZipLabel(string $label): string
    {
        $label = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|', "\0"], ' ', $label);
        $label = trim(preg_replace('/\s+/u', ' ', $label) ?? $label);
        if ($label === '') {
            return 'image';
        }
        return function_exists('mb_substr') ? mb_substr($label, 0, 80) : substr($label, 0, 80);
    }

    private function imageExt(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $ext : 'png';
    }

    /** @return array<string, mixed> */
    public function save(int $productId, string $html): array
    {
        $this->requireProduct($productId);
        $this->repo->saveForProduct($productId, self::imagesOnlyHtml($html), 'published');
        return $this->getForEdit($productId);
    }

    /**
     * 헤더, 상품규격, 촬영 이미지만 위에서부터 붙인다.
     *
     * @param array<string, mixed> $product
     */
    private function composedImageHtml(array $product): string
    {
        $items = [
            [(string) ($product['header_image'] ?? ''), '헤더 이미지'],
            [(string) ($product['spec_sheet_image'] ?? ''), '상품규격'],
            [(string) ($product['shoot_image'] ?? ''), '촬영'],
        ];
        $tags = [];
        foreach ($items as [$path, $alt]) {
            $src = self::cleanImageSrc(ShopProductImageService::normalizePublicPath($path));
            if ($src === '' || !is_file(public_path(ltrim($src, '/')))) {
                continue;
            }
            $tags[] = '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '">';
        }
        return implode('', $tags);
    }

    /** 이미지 태그만 남긴다. p, div, style 같은 다른 태그는 저장하지 않는다. */
    public static function imagesOnlyHtml(string $html): string
    {
        if (trim($html) === '' || !preg_match_all('/<img\b[^>]*>/iu', $html, $matches)) {
            return '';
        }
        $tags = [];
        foreach ($matches[0] as $tag) {
            if (!preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/iu', $tag, $srcMatch)) {
                continue;
            }
            $src = self::cleanImageSrc(html_entity_decode($srcMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($src === '') {
                continue;
            }
            $alt = '';
            if (preg_match('/\balt\s*=\s*(["\'])(.*?)\1/iu', $tag, $altMatch)) {
                $alt = trim(html_entity_decode($altMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            $tags[] = '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '">';
        }
        return implode('', $tags);
    }

    private static function cleanImageSrc(string $src): string
    {
        $src = trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($src === '') {
            return '';
        }
        $src = preg_replace('/[?#].*$/', '', $src) ?? $src;
        if (preg_match('#^https?://[^/]+(/assets/.+)$#i', $src, $match) === 1) {
            $src = $match[1];
        }
        if (!str_starts_with($src, '/assets/') || str_contains($src, '..')) {
            return '';
        }
        if (preg_match('#^/assets/[A-Za-z0-9_./-]+$#', $src) !== 1) {
            return '';
        }
        return $src;
    }

    /** @return array<string, mixed> */
    private function requireProduct(int $productId): array
    {
        if ($productId <= 0) {
            throw new RuntimeException('상품을 선택해주세요.');
        }
        $product = $this->shopRepo->findProductForPreview($productId);
        if (!$product) {
            throw new RuntimeException('상품을 찾을 수 없습니다.');
        }
        return $product;
    }

    private function normalizeRegistered(string $value): string
    {
        return in_array($value, ['yes', 'no'], true) ? $value : '';
    }

    private function normalizeProductStatus(string $value): string
    {
        return in_array($value, ['draft', 'active', 'soldout', 'hidden'], true) ? $value : '';
    }
}
