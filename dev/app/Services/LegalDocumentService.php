<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LegalDocumentRepository;
use RuntimeException;

final class LegalDocumentService
{
    private const ALLOWED_KEYS = ['terms', 'privacy', 'marketing'];

    private LegalDocumentRepository $repo;

    public function __construct()
    {
        $this->repo = new LegalDocumentRepository();
    }

    public function get(string $docKey): array
    {
        $this->assertKey($docKey);
        $row = $this->repo->findByKey($docKey);
        if (!$row) {
            throw new RuntimeException('약관 문서를 찾을 수 없습니다.');
        }
        return $this->sanitize($row);
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return array_map(fn (array $row) => $this->sanitize($row), $this->repo->all());
    }

    public function update(string $docKey, string $title, string $content): array
    {
        $this->assertKey($docKey);
        $title = trim($title);
        $content = trim($content);
        if ($title === '') {
            throw new RuntimeException('제목을 입력해주세요.');
        }
        if ($content === '') {
            throw new RuntimeException('내용을 입력해주세요.');
        }
        if (!$this->repo->findByKey($docKey)) {
            throw new RuntimeException('약관 문서를 찾을 수 없습니다.');
        }
        $this->repo->update($docKey, $title, $content);
        return $this->get($docKey);
    }

    private function assertKey(string $docKey): void
    {
        if (!in_array($docKey, self::ALLOWED_KEYS, true)) {
            throw new RuntimeException('유효하지 않은 문서 키입니다.');
        }
    }

    private function sanitize(array $row): array
    {
        return [
            'doc_key' => $row['doc_key'],
            'title' => $row['title'],
            'content' => $row['content'],
            'version' => (int) ($row['version'] ?? 1),
            'is_required' => (bool) ($row['is_required'] ?? true),
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }
}
