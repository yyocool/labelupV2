<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class EditorWorkspaceRepository extends BaseModel
{
    public function findByUserId(int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_editor_workspaces WHERE user_id = :uid LIMIT 1',
            ['uid' => $userId]
        );
    }

    public function upsert(int $userId, string $title, string $documentJson, ?string $uiJson): void
    {
        $existing = $this->findByUserId($userId);
        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $this->execute(
                'UPDATE user_editor_workspaces
                 SET title = :title, document_json = :doc, ui_json = :ui, updated_at = :now
                 WHERE user_id = :uid',
                [
                    'title' => $title,
                    'doc' => $documentJson,
                    'ui' => $uiJson,
                    'now' => $now,
                    'uid' => $userId,
                ]
            );
            return;
        }

        $this->execute(
            'INSERT INTO user_editor_workspaces (user_id, title, document_json, ui_json, created_at, updated_at)
             VALUES (:uid, :title, :doc, :ui, :now, :now)',
            [
                'uid' => $userId,
                'title' => $title,
                'doc' => $documentJson,
                'ui' => $uiJson,
                'now' => $now,
            ]
        );
    }
}
