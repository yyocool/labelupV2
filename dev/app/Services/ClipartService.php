<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClipartRepository;
use RuntimeException;

final class ClipartService
{
    private ClipartRepository $repo;

    public function __construct()
    {
        $this->repo = new ClipartRepository();
    }

    public function categories(bool $activeOnly = false): array
    {
        return $this->repo->categories($activeOnly);
    }

    /** @param array<string, mixed> $filters */
    public function list(array $filters = []): array
    {
        $result = $this->repo->listCliparts($filters);
        foreach ($result['items'] as &$item) {
            $item['image_url'] = self::resolveUrl((string) ($item['image_path'] ?? ''));
            $item['tags'] = $this->repo->tagsForClipart((int) $item['id']);
            $item['hashtag_list'] = self::parseHashtags((string) ($item['hashtags'] ?? ''));
        }
        unset($item);
        return $result;
    }

    public function find(int $id): ?array
    {
        $item = $this->repo->findClipart($id);
        if (!$item) {
            return null;
        }
        $item['image_url'] = self::resolveUrl((string) ($item['image_path'] ?? ''));
        $item['tags'] = $this->repo->tagsForClipart($id);
        $item['hashtag_list'] = self::parseHashtags((string) ($item['hashtags'] ?? ''));
        return $item;
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException('클립아트 제목을 입력해 주세요.');
        }

        $imagePath = trim((string) ($data['image_path'] ?? ''));
        if ($imagePath === '' && $id > 0) {
            $existing = $this->repo->findClipart($id);
            $imagePath = (string) ($existing['image_path'] ?? '');
        }
        if ($imagePath === '') {
            throw new RuntimeException('이미지를 등록해 주세요.');
        }

        $hashtags = self::normalizeHashtags((string) ($data['hashtags'] ?? ''));
        $tags = self::parseHashtags($hashtags);

        $payload = [
            'id' => $id,
            'category_id' => (int) ($data['category_id'] ?? 0) ?: null,
            'title' => $title,
            'image_path' => ShopProductImageService::normalizePublicPath($imagePath),
            'hashtags' => $hashtags,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'source' => (string) ($data['source'] ?? ($id > 0 ? 'upload' : 'upload')),
        ];

        $savedId = $this->repo->saveClipart($payload);
        $this->repo->syncTags($savedId, $tags);
        return $savedId;
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('삭제할 항목이 없습니다.');
        }
        $row = $this->repo->findClipart($id);
        if (!$row) {
            return;
        }
        $this->repo->deleteClipart($id);
        $path = (string) ($row['image_path'] ?? '');
        if ($path !== '' && str_starts_with($path, '/assets/cliparts/')) {
            $full = public_path(ltrim($path, '/'));
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    /** @param array<string, mixed> $data */
    public function saveCategory(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('카테고리명을 입력해 주세요.');
        }
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->repo->slugify($name);
        }
        return $this->repo->saveCategory([
            'id' => (int) ($data['id'] ?? 0),
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    /** @return array<int, string> */
    public function storeUploadedImages(array $files): array
    {
        return ShopProductImageService::storeUploadedFiles(
            $files,
            public_path('assets/cliparts'),
            'clip_'
        );
    }

    public static function resolveUrl(string $path): string
    {
        return ShopProductImageService::resolveUrl($path);
    }

    public static function normalizeHashtags(string $raw): string
    {
        $parts = self::parseHashtags($raw);
        if ($parts === []) {
            return '';
        }
        return implode(' ', array_map(static fn ($t) => '#' . $t, $parts));
    }

    /** @return list<string> */
    public static function parseHashtags(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $chunks = preg_split('/[\s,，、]+/u', $raw) ?: [];
        $out = [];
        foreach ($chunks as $chunk) {
            $tag = trim($chunk);
            $tag = ltrim($tag, "#＃");
            $tag = trim($tag);
            if ($tag === '') {
                continue;
            }
            $key = mb_strtolower($tag);
            $out[$key] = $tag;
        }
        return array_values($out);
    }

    /**
     * Import seed manifest items. Skips existing image_path.
     * @param array<int, array<string, mixed>> $items
     * @return array{inserted:int, skipped:int}
     */
    public function importSeedItems(array $items): array
    {
        $inserted = 0;
        $skipped = 0;
        foreach ($items as $item) {
            $path = ShopProductImageService::normalizePublicPath((string) ($item['image_path'] ?? ''));
            if ($path === '') {
                $skipped++;
                continue;
            }
            if ($this->repo->findByImagePath($path)) {
                $existing = $this->repo->findByImagePath($path);
                $categoryId = null;
                $slug = (string) ($item['category_slug'] ?? '');
                if ($slug !== '') {
                    $cat = $this->repo->findCategoryBySlug($slug);
                    if ($cat) {
                        $categoryId = (int) $cat['id'];
                    }
                }
                $hashtags = self::normalizeHashtags((string) ($item['hashtags'] ?? ''));
                $id = $this->repo->saveClipart([
                    'id' => (int) $existing['id'],
                    'category_id' => $categoryId ?: ($existing['category_id'] ?? null),
                    'title' => (string) ($item['title'] ?? $existing['title']),
                    'image_path' => $path,
                    'hashtags' => $hashtags !== '' ? $hashtags : ($existing['hashtags'] ?? null),
                    'description' => $item['description'] ?? ($existing['description'] ?? null),
                    'sort_order' => (int) ($item['sort_order'] ?? $existing['sort_order'] ?? 0),
                    'is_active' => 1,
                    'source' => 'seed',
                ]);
                $this->repo->syncTags($id, self::parseHashtags($hashtags !== '' ? $hashtags : (string) ($existing['hashtags'] ?? '')));
                $skipped++;
                continue;
            }

            $categoryId = null;
            $slug = (string) ($item['category_slug'] ?? '');
            if ($slug !== '') {
                $cat = $this->repo->findCategoryBySlug($slug);
                if ($cat) {
                    $categoryId = (int) $cat['id'];
                }
            }

            $hashtags = self::normalizeHashtags((string) ($item['hashtags'] ?? ''));
            $id = $this->repo->saveClipart([
                'category_id' => $categoryId,
                'title' => (string) ($item['title'] ?? '클립아트'),
                'image_path' => $path,
                'hashtags' => $hashtags,
                'description' => $item['description'] ?? null,
                'sort_order' => (int) ($item['sort_order'] ?? 0),
                'is_active' => 1,
                'source' => 'seed',
            ]);
            $this->repo->syncTags($id, self::parseHashtags($hashtags));
            $inserted++;
        }

        return compact('inserted', 'skipped');
    }

    /** Ensure default categories exist. @return int created count */
    public function ensureDefaultCategories(): int
    {
        $defaults = [
            ['name' => '식품·카페', 'slug' => 'food', 'description' => '원두, 베이커리, 음식 라벨용'],
            ['name' => '뷰티·화장품', 'slug' => 'beauty', 'description' => '화장품·향수·스킨케어'],
            ['name' => '배송·물류', 'slug' => 'shipping', 'description' => '택배·주소·주의 표시'],
            ['name' => '네임·키즈', 'slug' => 'kids', 'description' => '이름표·어린이·학용품'],
            ['name' => '하트·선물', 'slug' => 'gift', 'description' => '감사·축하·리본'],
            ['name' => '비즈니스', 'slug' => 'business', 'description' => '바코드·가격·오피스'],
            ['name' => '자연·식물', 'slug' => 'nature', 'description' => '잎·꽃·친환경'],
            ['name' => '동물', 'slug' => 'animal', 'description' => '귀여운 동물 모티브'],
            ['name' => '시즌·기념일', 'slug' => 'season', 'description' => '계절·기념일 장식'],
            ['name' => '기본 도형', 'slug' => 'shape', 'description' => '원·별·체크 등 기본형'],
        ];
        $created = 0;
        foreach ($defaults as $i => $row) {
            if ($this->repo->findCategoryBySlug($row['slug'])) {
                continue;
            }
            $this->repo->saveCategory([
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'sort_order' => ($i + 1) * 10,
                'is_active' => 1,
            ]);
            $created++;
        }
        return $created;
    }

    public function count(): int
    {
        return $this->repo->countCliparts();
    }
}
