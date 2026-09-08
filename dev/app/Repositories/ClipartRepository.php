<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class ClipartRepository extends BaseModel
{
    /** Format slugs (VECTORS/PNGs/SVGs) are file types, not categories. */
    public const FORMAT_SLUGS = ['vectors', 'pngs', 'svgs'];

    /** @return array<int, array<string, mixed>> */
    public function categories(bool $activeOnly = false): array
    {
        $where = ['slug NOT IN (\'vectors\', \'pngs\', \'svgs\')'];
        if ($activeOnly) {
            $where[] = 'is_active = 1';
        }
        $clause = implode(' AND ', $where);
        return $this->fetchAll(
            "SELECT * FROM clipart_categories WHERE {$clause} ORDER BY sort_order ASC, id ASC"
        );
    }

    public function deactivateByCategoryId(int $categoryId): void
    {
        if ($categoryId <= 0) {
            return;
        }
        $this->execute(
            'UPDATE cliparts SET is_active = 0, updated_at = :now WHERE category_id = :id',
            ['now' => date('Y-m-d H:i:s'), 'id' => $categoryId]
        );
    }

    public function deactivateByImagePathPrefix(string $prefix): void
    {
        $prefix = trim($prefix);
        if ($prefix === '') {
            return;
        }
        $this->execute(
            'UPDATE cliparts SET is_active = 0, updated_at = :now WHERE image_path LIKE :p',
            ['now' => date('Y-m-d H:i:s'), 'p' => $prefix . '%']
        );
    }

    public function findCategory(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM clipart_categories WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findCategoryBySlug(string $slug): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM clipart_categories WHERE slug = :slug LIMIT 1',
            ['slug' => $slug]
        );
    }

    /** @param array<string, mixed> $data */
    public function saveCategory(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $params['id'] = $id;
            $this->execute(
                'UPDATE clipart_categories SET name=:name, slug=:slug, description=:description,
                 sort_order=:sort_order, is_active=:is_active, updated_at=:updated_at WHERE id=:id',
                $params
            );
            return $id;
        }

        $params['created_at'] = $now;
        $this->execute(
            'INSERT INTO clipart_categories (name, slug, description, sort_order, is_active, created_at, updated_at)
             VALUES (:name, :slug, :description, :sort_order, :is_active, :created_at, :updated_at)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    /**
     * @param array{q?:string, category_id?:int, tag?:string, page?:int, per_page?:int} $filters
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, per_page:int}
     */
    public function listCliparts(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 24)));
        $where = ['1=1'];
        $params = [];
        $where[] = '(cat.slug IS NULL OR cat.slug NOT IN (\'vectors\', \'pngs\', \'svgs\'))';

        if (!empty($filters['category_id'])) {
            $where[] = 'c.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(c.title LIKE :q_title OR c.hashtags LIKE :q_tags OR c.description LIKE :q_desc OR cat.name LIKE :q_cat)';
            $like = '%' . $filters['q'] . '%';
            $params['q_title'] = $like;
            $params['q_tags'] = $like;
            $params['q_desc'] = $like;
            $params['q_cat'] = $like;
        }
        if (!empty($filters['tag'])) {
            $where[] = 'EXISTS (
                SELECT 1 FROM clipart_tag_map m
                INNER JOIN clipart_tags t ON t.id = m.tag_id
                WHERE m.clipart_id = c.id AND (t.name = :tag OR t.slug = :tag OR c.hashtags LIKE :tag_like)
            )';
            $params['tag'] = (string) $filters['tag'];
            $params['tag_like'] = '%#' . ltrim((string) $filters['tag'], '#') . '%';
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $where[] = 'c.is_active = :is_active';
            $params['is_active'] = (int) $filters['is_active'];
        }

        $clause = implode(' AND ', $where);
        $count = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM cliparts c
             LEFT JOIN clipart_categories cat ON cat.id = c.category_id
             WHERE {$clause}",
            $params
        );
        $total = (int) ($count['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT c.*, cat.name AS category_name, cat.slug AS category_slug
             FROM cliparts c
             LEFT JOIN clipart_categories cat ON cat.id = c.category_id
             WHERE {$clause}
             ORDER BY c.sort_order ASC, c.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public function findClipart(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT c.*, cat.name AS category_name, cat.slug AS category_slug
             FROM cliparts c
             LEFT JOIN clipart_categories cat ON cat.id = c.category_id
             WHERE c.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function findByImagePath(string $path): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM cliparts WHERE image_path = :p LIMIT 1',
            ['p' => $path]
        );
    }

    /**
     * 편집기용 활성 클립아트 페이지.
     *
     * @param array{page?:int,per_page?:int,q?:string,category_id?:int} $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public function listActiveForEditorPaged(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(60, (int) ($filters['per_page'] ?? 48)));
        $where = [
            'c.is_active = 1',
            '(cat.id IS NULL OR cat.is_active = 1)',
            '(cat.slug IS NULL OR cat.slug NOT IN (\'vectors\', \'pngs\', \'svgs\'))',
        ];
        $params = [];

        if (!empty($filters['category_id'])) {
            $where[] = 'c.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(c.title LIKE :q_title OR c.hashtags LIKE :q_tags OR c.description LIKE :q_desc OR cat.name LIKE :q_cat)';
            $like = '%' . $filters['q'] . '%';
            $params['q_title'] = $like;
            $params['q_tags'] = $like;
            $params['q_desc'] = $like;
            $params['q_cat'] = $like;
        }

        $clause = implode(' AND ', $where);
        $count = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM cliparts c
             LEFT JOIN clipart_categories cat ON cat.id = c.category_id
             WHERE {$clause}",
            $params
        );
        $total = (int) ($count['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $items = $this->fetchAll(
            "SELECT c.*, cat.name AS category_name, cat.slug AS category_slug
             FROM cliparts c
             LEFT JOIN clipart_categories cat ON cat.id = c.category_id
             WHERE {$clause}
             ORDER BY c.sort_order ASC, c.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /**
     * 편집기용 활성 클립아트 전체 (페이지네이션 없음, 상한 2000).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listActiveForEditor(int $limit = 2000): array
    {
        $limit = max(1, min(5000, $limit));
        return $this->fetchAll(
            "SELECT c.*, cat.name AS category_name, cat.slug AS category_slug
             FROM cliparts c
             LEFT JOIN clipart_categories cat ON cat.id = c.category_id
             WHERE c.is_active = 1
               AND (cat.id IS NULL OR cat.is_active = 1)
               AND (cat.slug IS NULL OR cat.slug NOT IN ('vectors', 'pngs', 'svgs'))
             ORDER BY c.sort_order ASC, c.id DESC
             LIMIT {$limit}"
        );
    }

    /** @param array<string, mixed> $data */
    public function saveClipart(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'title' => (string) ($data['title'] ?? ''),
            'image_path' => (string) ($data['image_path'] ?? ''),
            'hashtags' => $data['hashtags'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'source' => (string) ($data['source'] ?? 'upload'),
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $params['id'] = $id;
            $this->execute(
                'UPDATE cliparts SET category_id=:category_id, title=:title, image_path=:image_path,
                 hashtags=:hashtags, description=:description, sort_order=:sort_order,
                 is_active=:is_active, source=:source, updated_at=:updated_at WHERE id=:id',
                $params
            );
            return $id;
        }

        $params['created_at'] = $now;
        $this->execute(
            'INSERT INTO cliparts (category_id, title, image_path, hashtags, description, sort_order, is_active, source, created_at, updated_at)
             VALUES (:category_id, :title, :image_path, :hashtags, :description, :sort_order, :is_active, :source, :created_at, :updated_at)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function deleteClipart(int $id): void
    {
        $this->execute('DELETE FROM clipart_tag_map WHERE clipart_id = :id', ['id' => $id]);
        $this->execute('DELETE FROM cliparts WHERE id = :id', ['id' => $id]);
    }

    public function countCliparts(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS cnt FROM cliparts');
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return list<string> */
    public function tagsForClipart(int $clipartId): array
    {
        $rows = $this->fetchAll(
            'SELECT t.name FROM clipart_tags t
             INNER JOIN clipart_tag_map m ON m.tag_id = t.id
             WHERE m.clipart_id = :id ORDER BY t.name ASC',
            ['id' => $clipartId]
        );
        return array_values(array_map(static fn ($r) => (string) $r['name'], $rows));
    }

    /** @param list<string> $tags */
    public function syncTags(int $clipartId, array $tags): void
    {
        $this->execute('DELETE FROM clipart_tag_map WHERE clipart_id = :id', ['id' => $clipartId]);
        $now = date('Y-m-d H:i:s');
        foreach ($tags as $name) {
            $name = trim($name);
            $name = ltrim($name, "# \t");
            if ($name === '') {
                continue;
            }
            $slug = $this->slugify($name);
            $existing = $this->fetchOne('SELECT id FROM clipart_tags WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
            if ($existing) {
                $tagId = (int) $existing['id'];
            } else {
                $this->execute(
                    'INSERT INTO clipart_tags (name, slug, created_at) VALUES (:name, :slug, :created_at)',
                    ['name' => $name, 'slug' => $slug, 'created_at' => $now]
                );
                $tagId = (int) $this->lastInsertId();
            }
            $this->execute(
                'INSERT IGNORE INTO clipart_tag_map (clipart_id, tag_id) VALUES (:c, :t)',
                ['c' => $clipartId, 't' => $tagId]
            );
        }
    }

    public function slugify(string $text): string
    {
        $text = trim(mb_strtolower($text));
        $text = preg_replace('/\s+/u', '-', $text) ?? $text;
        $text = preg_replace('/[^a-z0-9가-힣\-_]/u', '', $text) ?? $text;
        return $text !== '' ? $text : 'tag-' . substr(md5($text . microtime()), 0, 8);
    }
}
