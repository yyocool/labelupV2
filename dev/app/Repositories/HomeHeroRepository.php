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
            'main_text' => trim((string) ($data['main_text'] ?? '')),
            'main_font' => (string) ($data['main_font'] ?? 'pretendard-extrabold'),
            'main_size' => (int) ($data['main_size'] ?? 44),
            'main_size_mobile' => (int) ($data['main_size_mobile'] ?? 26),
            'main_color' => (string) ($data['main_color'] ?? '#FFFFFF'),
            'sub_text' => trim((string) ($data['sub_text'] ?? '')),
            'sub_font' => (string) ($data['sub_font'] ?? 'pretendard-medium'),
            'sub_size' => (int) ($data['sub_size'] ?? 20),
            'sub_size_mobile' => (int) ($data['sub_size_mobile'] ?? 14),
            'sub_color' => (string) ($data['sub_color'] ?? '#FFFFFF'),
            'text_align_h' => (string) ($data['text_align_h'] ?? 'left'),
            'text_align_v' => (string) ($data['text_align_v'] ?? 'middle'),
            'image_url' => trim((string) ($data['image_url'] ?? '')),
            'mobile_image_url' => trim((string) ($data['mobile_image_url'] ?? '')) ?: null,
            'link_url' => trim((string) ($data['link_url'] ?? '')) ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (int) !empty($data['is_active']),
            'now' => $now,
        ];
        $columns = array_keys(array_diff_key($params, ['now' => true]));
        if ($id > 0) {
            $set = implode(',', array_map(static fn (string $c): string => $c . '=:' . $c, $columns));
            $this->execute(
                'UPDATE home_hero_slides SET ' . $set . ',updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }
        $this->execute(
            'INSERT INTO home_hero_slides (' . implode(',', $columns) . ',created_at,updated_at)
             VALUES (:' . implode(',:', $columns) . ',:now,:now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM home_hero_slides WHERE id = :id', ['id' => $id]);
    }
}
