<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class LegalDocumentRepository extends BaseModel
{
    public function findByKey(string $docKey): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM legal_documents WHERE doc_key = :key LIMIT 1',
            ['key' => $docKey]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT * FROM legal_documents ORDER BY FIELD(doc_key, \'terms\', \'privacy\', \'marketing\'), id ASC'
        );
    }

    public function update(string $docKey, string $title, string $content): void
    {
        $now = date('Y-m-d H:i:s');
        $this->execute(
            'UPDATE legal_documents
             SET title = :title, content = :content, version = version + 1, updated_at = :updated
             WHERE doc_key = :key',
            [
                'title' => $title,
                'content' => $content,
                'updated' => $now,
                'key' => $docKey,
            ]
        );
    }
}
