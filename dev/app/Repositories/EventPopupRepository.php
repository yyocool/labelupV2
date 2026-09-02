<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class EventPopupRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT * FROM event_popups ORDER BY sort_order ASC, id DESC'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function activeForNow(?string $now = null): array
    {
        $now = $now ?: date('Y-m-d H:i:s');
        return $this->fetchAll(
            'SELECT * FROM event_popups
             WHERE is_active = 1
               AND (start_at IS NULL OR start_at <= :now1)
               AND (end_at IS NULL OR end_at >= :now2)
             ORDER BY sort_order ASC, id DESC',
            ['now1' => $now, 'now2' => $now]
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM event_popups WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function save(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'title' => trim((string) ($data['title'] ?? '')),
            'image_url' => trim((string) ($data['image_url'] ?? '')),
            'link_url' => trim((string) ($data['link_url'] ?? '')) ?: null,
            'content' => trim((string) ($data['content'] ?? '')) ?: null,
            'start_at' => $this->nullableDatetime($data['start_at'] ?? null),
            'end_at' => $this->nullableDatetime($data['end_at'] ?? null),
            'hide_days' => max(0, (int) ($data['hide_days'] ?? 1)),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (int) !empty($data['is_active']),
            'now' => $now,
        ];

        if ($id > 0) {
            $this->execute(
                'UPDATE event_popups SET title=:title, image_url=:image_url, link_url=:link_url, content=:content,
                 start_at=:start_at, end_at=:end_at, hide_days=:hide_days, sort_order=:sort_order,
                 is_active=:is_active, updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }

        $this->execute(
            'INSERT INTO event_popups
             (title, image_url, link_url, content, start_at, end_at, hide_days, sort_order, is_active, created_at, updated_at)
             VALUES
             (:title, :image_url, :link_url, :content, :start_at, :end_at, :hide_days, :sort_order, :is_active, :now, :now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM event_popups WHERE id = :id', ['id' => $id]);
    }

    private function nullableDatetime(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }
}
