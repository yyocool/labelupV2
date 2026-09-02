<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class AiExamplePromptRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT * FROM ai_example_prompts ORDER BY sort_order ASC, id ASC'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function activeForSurface(string $surface): array
    {
        return $this->fetchAll(
            'SELECT * FROM ai_example_prompts
             WHERE is_active = 1
               AND (surface = :surface OR surface = \'both\')
             ORDER BY sort_order ASC, id ASC',
            ['surface' => $surface]
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM ai_example_prompts WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function save(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'label' => trim((string) ($data['label'] ?? '')),
            'prompt_text' => trim((string) ($data['prompt_text'] ?? '')),
            'surface' => trim((string) ($data['surface'] ?? 'both')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (int) !empty($data['is_active']),
            'now' => $now,
        ];

        if ($id > 0) {
            $this->execute(
                'UPDATE ai_example_prompts SET label=:label, prompt_text=:prompt_text, surface=:surface,
                 sort_order=:sort_order, is_active=:is_active, updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }

        $this->execute(
            'INSERT INTO ai_example_prompts (label, prompt_text, surface, sort_order, is_active, created_at, updated_at)
             VALUES (:label, :prompt_text, :surface, :sort_order, :is_active, :now, :now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM ai_example_prompts WHERE id = :id', ['id' => $id]);
    }
}
