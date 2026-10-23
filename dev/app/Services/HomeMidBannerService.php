<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HomeMidBannerRepository;
use RuntimeException;

final class HomeMidBannerService
{
    /** @var array<string, array{label:string,family:string,weight:int}> */
    public const FONT_PRESETS = [
        'pretendard-extrabold' => ['label' => 'Pretendard ExtraBold', 'family' => 'Pretendard', 'weight' => 800],
        'pretendard-bold' => ['label' => 'Pretendard Bold', 'family' => 'Pretendard', 'weight' => 700],
        'pretendard-semibold' => ['label' => 'Pretendard SemiBold', 'family' => 'Pretendard', 'weight' => 600],
        'pretendard-medium' => ['label' => 'Pretendard Medium', 'family' => 'Pretendard', 'weight' => 500],
        'pretendard-regular' => ['label' => 'Pretendard Regular', 'family' => 'Pretendard', 'weight' => 400],
        'pretendard-light' => ['label' => 'Pretendard Light', 'family' => 'Pretendard', 'weight' => 300],
    ];

    public const DEFAULT_MAIN_FONT = 'pretendard-extrabold';
    public const DEFAULT_MAIN_SIZE = 50;
    public const DEFAULT_MAIN_COLOR = '#FFFFFF';
    public const DEFAULT_SUB_FONT = 'pretendard-regular';
    public const DEFAULT_SUB_SIZE = 25;
    public const DEFAULT_SUB_COLOR = '#FFFFFF';
    public const DEFAULT_ALIGN_H = 'left';
    public const DEFAULT_ALIGN_V = 'middle';
    public const ALIGN_H = ['left', 'center', 'right'];
    public const ALIGN_V = ['top', 'middle', 'bottom'];

    private HomeMidBannerRepository $repo;

    public function __construct()
    {
        $this->repo = new HomeMidBannerRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function slidesForHome(): array
    {
        return array_map([$this, 'formatSlide'], $this->repo->active());
    }

    /** @return array<int, array<string, mixed>> */
    public function allForAdmin(): array
    {
        return array_map([$this, 'formatSlide'], $this->repo->all());
    }

    public function save(array $data): int
    {
        if (trim((string) ($data['image_url'] ?? '')) === '') {
            throw new RuntimeException('이미지를 업로드해주세요.');
        }
        $data['image_url'] = self::normalizeStoredPath((string) $data['image_url']);
        $data = self::normalizeTextFields($data);
        return $this->repo->save($data);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    /** @param array<string, mixed> $file $_FILES['image'] shape */
    public function storeUploadedImage(array $file): array
    {
        $paths = ShopProductImageService::storeUploadedFiles(
            $file,
            public_path('assets/mid-banners'),
            'mid_'
        );
        $path = $paths[0] ?? '';
        if ($path === '') {
            throw new RuntimeException('이미지 업로드에 실패했습니다.');
        }

        return [
            'path' => $path,
            'url' => self::resolveImageUrl($path),
        ];
    }

    /** @param array<string, mixed> $slide */
    public function formatSlide(array $slide): array
    {
        $slide = self::normalizeTextFields($slide);
        $slide['image_src'] = self::resolveImageUrl((string) ($slide['image_url'] ?? ''));
        $slide['main_style'] = self::inlineStyle((string) $slide['main_font'], (int) $slide['main_size'], (string) $slide['main_color']);
        $slide['sub_style'] = self::inlineStyle((string) $slide['sub_font'], (int) $slide['sub_size'], (string) $slide['sub_color']);
        return $slide;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalizeTextFields(array $data): array
    {
        $data['main_text'] = trim((string) ($data['main_text'] ?? ''));
        $data['sub_text'] = trim((string) ($data['sub_text'] ?? ''));
        $data['main_font'] = self::normalizeFont((string) ($data['main_font'] ?? ''), self::DEFAULT_MAIN_FONT);
        $data['sub_font'] = self::normalizeFont((string) ($data['sub_font'] ?? ''), self::DEFAULT_SUB_FONT);
        $data['main_size'] = self::normalizeSize($data['main_size'] ?? null, self::DEFAULT_MAIN_SIZE);
        $data['sub_size'] = self::normalizeSize($data['sub_size'] ?? null, self::DEFAULT_SUB_SIZE);
        $data['main_color'] = self::normalizeColor((string) ($data['main_color'] ?? ''), self::DEFAULT_MAIN_COLOR);
        $data['sub_color'] = self::normalizeColor((string) ($data['sub_color'] ?? ''), self::DEFAULT_SUB_COLOR);
        $data['text_align_h'] = self::normalizeAlign((string) ($data['text_align_h'] ?? ''), self::ALIGN_H, self::DEFAULT_ALIGN_H);
        $data['text_align_v'] = self::normalizeAlign((string) ($data['text_align_v'] ?? ''), self::ALIGN_V, self::DEFAULT_ALIGN_V);
        return $data;
    }

    /** @param list<string> $allowed */
    public static function normalizeAlign(string $value, array $allowed, string $fallback): string
    {
        $value = strtolower(trim($value));
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    public static function normalizeFont(string $font, string $fallback): string
    {
        $font = strtolower(trim($font));
        return isset(self::FONT_PRESETS[$font]) ? $font : $fallback;
    }

    public static function normalizeSize(mixed $size, int $fallback): int
    {
        $n = (int) $size;
        if ($n < 12 || $n > 120) {
            return $fallback;
        }
        return $n;
    }

    public static function normalizeColor(string $color, string $fallback): string
    {
        $color = strtoupper(trim($color));
        if (preg_match('/^#[0-9A-F]{6}$/', $color) === 1) {
            return $color;
        }
        if (preg_match('/^[0-9A-F]{6}$/', $color) === 1) {
            return '#' . $color;
        }
        return $fallback;
    }

    public static function inlineStyle(string $fontKey, int $size, string $color): string
    {
        $preset = self::FONT_PRESETS[$fontKey] ?? self::FONT_PRESETS[self::DEFAULT_MAIN_FONT];
        $family = $preset['family'];
        $weight = (int) $preset['weight'];
        return '--mid-size:' . $size . 'px;font-family:' . $family . ',sans-serif;font-weight:' . $weight
            . ';font-size:var(--mid-size);color:' . $color;
    }

    public static function normalizeStoredPath(string $path): string
    {
        $path = ShopProductImageService::normalizePublicPath($path);
        if (preg_match('#^/assets/banners/(mid_.+)$#', $path, $m) === 1) {
            return '/assets/mid-banners/' . $m[1];
        }
        return $path;
    }

    public static function resolveImageUrl(string $url): string
    {
        $url = self::normalizeStoredPath($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if (str_starts_with($url, '/assets/')) {
            return asset(ltrim(substr($url, strlen('/assets/')), '/'));
        }
        return asset(ltrim($url, '/'));
    }
}
