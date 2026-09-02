<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EventPopupRepository;
use RuntimeException;

final class EventPopupService
{
    private EventPopupRepository $repo;

    public function __construct()
    {
        $this->repo = new EventPopupRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function allForAdmin(): array
    {
        return array_map([$this, 'present'], $this->repo->all());
    }

    /** Active popups for frontend (within schedule). */
    /** @return array<int, array<string, mixed>> */
    public function activeForSite(): array
    {
        return array_map([$this, 'present'], $this->repo->activeForNow());
    }

    public function save(array $data): int
    {
        $image = trim((string) ($data['image_url'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException('제목을 입력해 주세요.');
        }
        if ($image === '') {
            throw new RuntimeException('이미지 URL을 입력해 주세요.');
        }
        return $this->repo->save($data);
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('삭제할 항목이 없습니다.');
        }
        $this->repo->delete($id);
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    public function present(array $row): array
    {
        $row['image_src'] = self::resolveImageUrl((string) ($row['image_url'] ?? ''));
        $row['is_active'] = (int) ($row['is_active'] ?? 0);
        $row['hide_days'] = (int) ($row['hide_days'] ?? 1);
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        return $row;
    }

    public static function resolveImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        if (str_starts_with($url, '/assets/')) {
            return asset(ltrim(substr($url, strlen('/assets/')), '/'));
        }
        if (str_starts_with($url, '/')) {
            return url(ltrim($url, '/'));
        }
        return asset(ltrim($url, '/'));
    }
}
