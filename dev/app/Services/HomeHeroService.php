<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HomeHeroRepository;
use RuntimeException;

final class HomeHeroService
{
    public const DEFAULT_MAIN_FONT = 'pretendard-extrabold';
    public const DEFAULT_MAIN_SIZE = 44;
    public const DEFAULT_MAIN_SIZE_MOBILE = 26;
    public const DEFAULT_SUB_FONT = 'pretendard-medium';
    public const DEFAULT_SUB_SIZE = 20;
    public const DEFAULT_SUB_SIZE_MOBILE = 14;
    public const DEFAULT_COLOR = '#FFFFFF';

    private HomeHeroRepository $repo;

    public function __construct()
    {
        $this->repo = new HomeHeroRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function slidesForHome(): array
    {
        $slides = $this->repo->active();
        if ($slides) {
            return array_map([$this, 'formatSlide'], $slides);
        }
        return $this->defaultSlides();
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
        $data['image_url'] = ShopProductImageService::normalizePublicPath((string) $data['image_url']);
        $mobile = trim((string) ($data['mobile_image_url'] ?? ''));
        $data['mobile_image_url'] = $mobile !== '' ? ShopProductImageService::normalizePublicPath($mobile) : '';
        return $this->repo->save(self::normalizeTextFields($data));
    }

    /**
     * 폰트 목록·색상·위치 규칙은 홈 「용도별 라벨」 중간배너와 같다.
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalizeTextFields(array $data): array
    {
        $data['main_text'] = trim((string) ($data['main_text'] ?? ''));
        $data['sub_text'] = trim((string) ($data['sub_text'] ?? ''));
        $data['main_font'] = HomeMidBannerService::normalizeFont((string) ($data['main_font'] ?? ''), self::DEFAULT_MAIN_FONT);
        $data['sub_font'] = HomeMidBannerService::normalizeFont((string) ($data['sub_font'] ?? ''), self::DEFAULT_SUB_FONT);
        $data['main_size'] = HomeMidBannerService::normalizeSize($data['main_size'] ?? null, self::DEFAULT_MAIN_SIZE);
        $data['main_size_mobile'] = HomeMidBannerService::normalizeSize($data['main_size_mobile'] ?? null, self::DEFAULT_MAIN_SIZE_MOBILE);
        $data['sub_size'] = HomeMidBannerService::normalizeSize($data['sub_size'] ?? null, self::DEFAULT_SUB_SIZE);
        $data['sub_size_mobile'] = HomeMidBannerService::normalizeSize($data['sub_size_mobile'] ?? null, self::DEFAULT_SUB_SIZE_MOBILE);
        $data['main_color'] = HomeMidBannerService::normalizeColor((string) ($data['main_color'] ?? ''), self::DEFAULT_COLOR);
        $data['sub_color'] = HomeMidBannerService::normalizeColor((string) ($data['sub_color'] ?? ''), self::DEFAULT_COLOR);
        $data['text_align_h'] = HomeMidBannerService::normalizeAlign((string) ($data['text_align_h'] ?? ''), HomeMidBannerService::ALIGN_H, HomeMidBannerService::DEFAULT_ALIGN_H);
        $data['text_align_v'] = HomeMidBannerService::normalizeAlign((string) ($data['text_align_v'] ?? ''), HomeMidBannerService::ALIGN_V, HomeMidBannerService::DEFAULT_ALIGN_V);
        return $data;
    }

    public static function textStyle(string $fontKey, int $size, int $sizeMobile, string $color): string
    {
        $preset = HomeMidBannerService::FONT_PRESETS[$fontKey] ?? HomeMidBannerService::FONT_PRESETS[self::DEFAULT_MAIN_FONT];
        return '--hero-size:' . $size . 'px;--hero-size-m:' . $sizeMobile . 'px;font-family:' . $preset['family']
            . ',sans-serif;font-weight:' . (int) $preset['weight'] . ';color:' . $color;
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
            public_path('assets/hero'),
            'hero_'
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
        $mobile = trim((string) ($slide['mobile_image_url'] ?? ''));
        $slide['mobile_image_src'] = $mobile !== '' ? self::resolveImageUrl($mobile) : '';
        $slide['main_style'] = self::textStyle((string) $slide['main_font'], (int) $slide['main_size'], (int) $slide['main_size_mobile'], (string) $slide['main_color']);
        $slide['sub_style'] = self::textStyle((string) $slide['sub_font'], (int) $slide['sub_size'], (int) $slide['sub_size_mobile'], (string) $slide['sub_color']);
        return $slide;
    }

    public static function resolveImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return asset('hero-tall-1.webp');
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if (str_starts_with($url, '/assets/')) {
            return asset(ltrim(substr($url, strlen('/assets/')), '/'));
        }
        return asset(ltrim($url, '/'));
    }

    /** @return array<int, array<string, mixed>> */
    private function defaultSlides(): array
    {
        return [
            [
                'id' => 0,
                'title' => '라벨 디자인 · 템플릿 · 바코드',
                'alt_text' => '라벨 디자인, 템플릿, 바코드 QR 기능 소개',
                'image_url' => '/assets/hero-tall-1.webp',
                'image_src' => asset('hero-tall-1.webp'),
                'link_url' => '/',
                'sort_order' => 1,
                'is_active' => 1,
            ],
            [
                'id' => 0,
                'title' => '인기 템플릿 모음',
                'alt_text' => '라벨업 템플릿 소개',
                'image_url' => '/assets/hero-tall-2.webp',
                'image_src' => asset('hero-tall-2.webp'),
                'link_url' => '/',
                'sort_order' => 2,
                'is_active' => 1,
            ],
            [
                'id' => 0,
                'title' => '바코드 · QR 생성',
                'alt_text' => '바코드 QR 생성 소개',
                'image_url' => '/assets/hero-tall-3.webp',
                'image_src' => asset('hero-tall-3.webp'),
                'link_url' => '/',
                'sort_order' => 3,
                'is_active' => 1,
            ],
        ];
    }
}
