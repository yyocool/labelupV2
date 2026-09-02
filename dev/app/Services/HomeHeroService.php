<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\HomeHeroRepository;
use RuntimeException;

final class HomeHeroService
{
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
        return $this->repo->all();
    }

    public function save(array $data): int
    {
        if (trim((string) ($data['image_url'] ?? '')) === '') {
            throw new RuntimeException('이미지 URL을 입력해주세요.');
        }
        return $this->repo->save($data);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    /** @param array<string, mixed> $slide */
    public function formatSlide(array $slide): array
    {
        $slide['image_src'] = self::resolveImageUrl((string) ($slide['image_url'] ?? ''));
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
