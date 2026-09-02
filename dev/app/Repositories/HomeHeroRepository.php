<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class HomeHeroRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT * FROM home_hero_slides ORDER BY sort_order ASC, id ASC'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function active(): array
    {
        return $this->fetchAll(
            'SELECT * FROM home_hero_slides WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    public function save(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'title' => trim((string) ($data['title'] ?? '')),
            'alt_text' => trim((string) ($data['alt_text'] ?? '')),
            'image_url' => trim((string) ($data['image_url'] ?? '')),
            'link_url' => trim((string) ($data['link_url'] ?? '')) ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (int) !empty($data['is_active']),
            'now' => $now,
        ];
        if ($id > 0) {
            $this->execute(
                'UPDATE home_hero_slides SET title=:title,alt_text=:alt_text,image_url=:image_url,link_url=:link_url,sort_order=:sort_order,is_active=:is_active,updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }
        $this->execute(
            'INSERT INTO home_hero_slides (title,alt_text,image_url,link_url,sort_order,is_active,created_at,updated_at)
             VALUES (:title,:alt_text,:image_url,:link_url,:sort_order,:is_active,:now,:now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM home_hero_slides WHERE id = :id', ['id' => $id]);
    }
}
