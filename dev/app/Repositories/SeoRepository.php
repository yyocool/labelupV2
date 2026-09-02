<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class SeoRepository extends BaseModel
{
    /** @return array<string, string> */
    public function allSettings(): array
    {
        $rows = $this->fetchAll('SELECT setting_key, setting_value FROM seo_settings');
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        return $map;
    }

    public function setSetting(string $key, string $value): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'INSERT INTO seo_settings (setting_key, setting_value, updated_at)
             VALUES (:k, :v, :now)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)',
            ['k' => $key, 'v' => $value, 'now' => $now]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function pages(): array
    {
        return $this->fetchAll('SELECT * FROM seo_pages ORDER BY sort_order ASC, page_key ASC');
    }

    public function page(string $key): ?array
    {
        return $this->fetchOne('SELECT * FROM seo_pages WHERE page_key = :k LIMIT 1', ['k' => $key]);
    }

    /** @param array<string, mixed> $data */
    public function savePage(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'UPDATE seo_pages SET
                label = :label,
                path_pattern = :path_pattern,
                title = :title,
                description = :description,
                keywords = :keywords,
                og_title = :og_title,
                og_description = :og_description,
                og_image = :og_image,
                og_type = :og_type,
                robots = :robots,
                canonical_path = :canonical_path,
                noindex = :noindex,
                sitemap_include = :sitemap_include,
                sitemap_changefreq = :sitemap_changefreq,
                sitemap_priority = :sitemap_priority,
                extra_head = :extra_head,
                sort_order = :sort_order,
                updated_at = :updated_at
             WHERE page_key = :page_key',
            $data + ['updated_at' => $now]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function files(): array
    {
        return $this->fetchAll('SELECT * FROM marketing_files ORDER BY filename ASC');
    }

    public function fileByName(string $filename): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM marketing_files WHERE filename = :n AND is_active = 1 LIMIT 1',
            ['n' => $filename]
        );
    }

    public function saveFile(string $filename, string $content, string $kind = 'html'): int
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->fetchOne('SELECT id FROM marketing_files WHERE filename = :n LIMIT 1', ['n' => $filename]);
        if ($existing) {
            $this->execute(
                'UPDATE marketing_files SET content = :c, file_kind = :k, is_active = 1, updated_at = :u WHERE id = :id',
                ['c' => $content, 'k' => $kind, 'u' => $now, 'id' => (int) $existing['id']]
            );
            return (int) $existing['id'];
        }
        $this->execute(
            'INSERT INTO marketing_files (filename, content, file_kind, is_active, created_at, updated_at)
             VALUES (:n, :c, :k, 1, :now, :now)',
            ['n' => $filename, 'c' => $content, 'k' => $kind, 'now' => $now]
        );
        return (int) $this->lastInsertId();
    }

    public function deleteFile(int $id): void
    {
        $this->execute('DELETE FROM marketing_files WHERE id = :id', ['id' => $id]);
    }

    /** @return array<int, array{id:int,updated_at:?string}> */
    public function sitemapProducts(int $limit = 2000): array
    {
        $limit = max(1, min(5000, $limit));
        return $this->fetchAll(
            "SELECT p.id, p.updated_at
             FROM shop_products p
             INNER JOIN shop_categories c ON c.id = p.category_id AND c.is_active = 1
             WHERE p.status IN ('active','soldout')
             ORDER BY p.id DESC
             LIMIT {$limit}"
        );
    }

    /** @return array<int, array{slug:string,updated_at:?string}> */
    public function sitemapCategories(): array
    {
        return $this->fetchAll(
            'SELECT slug, updated_at FROM shop_categories WHERE is_active = 1 AND slug <> \'\' ORDER BY sort_order ASC, id ASC'
        );
    }
}
