<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AiExamplePromptRepository;
use RuntimeException;

final class AiExamplePromptService
{
    private AiExamplePromptRepository $repo;

    public function __construct()
    {
        $this->repo = new AiExamplePromptRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function allForAdmin(): array
    {
        return array_map([$this, 'present'], $this->repo->all());
    }

    /** @return array<int, array<string, mixed>> */
    public function activeForSurface(string $surface): array
    {
        $surface = $this->normalizeSurface($surface, true);
        return array_map([$this, 'present'], $this->repo->activeForSurface($surface));
    }

    public function save(array $data): int
    {
        $label = trim((string) ($data['label'] ?? ''));
        $prompt = trim((string) ($data['prompt_text'] ?? ''));
        if ($label === '') {
            throw new RuntimeException('버튼 이름을 입력해 주세요.');
        }
        if ($prompt === '') {
            throw new RuntimeException('예시 프롬프트를 입력해 주세요.');
        }
        if (mb_strlen($label) > 40) {
            throw new RuntimeException('버튼 이름은 40자 이내로 입력해 주세요.');
        }
        if (mb_strlen($prompt) > 500) {
            throw new RuntimeException('프롬프트는 500자 이내로 입력해 주세요.');
        }
        $data['label'] = $label;
        $data['prompt_text'] = $prompt;
        $data['surface'] = $this->normalizeSurface((string) ($data['surface'] ?? 'both'), false);
        $data['is_active'] = !empty($data['is_active']);
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
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        $row['is_active'] = (int) ($row['is_active'] ?? 0);
        $row['surface'] = (string) ($row['surface'] ?? 'both');
        $row['surface_label'] = match ($row['surface']) {
            'home' => '홈만',
            'editor' => '편집기만',
            default => '홈+편집기',
        };
        return $row;
    }

    public function normalizeSurface(string $surface, bool $forQuery): string
    {
        $surface = strtolower(trim($surface));
        if (in_array($surface, ['home', 'editor', 'both'], true)) {
            return $surface;
        }
        return $forQuery ? 'home' : 'both';
    }
}
