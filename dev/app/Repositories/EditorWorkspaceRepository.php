<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class EditorWorkspaceRepository extends BaseModel
{
    public function findByUserId(int $userId): ?array
    {
        return $this->findLatestByUserId($userId);
    }

    public function findLatestByUserId(int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_editor_workspaces WHERE user_id = :uid ORDER BY updated_at DESC, id DESC LIMIT 1',
            ['uid' => $userId]
        );
    }

    public function findByIdForUser(int $userId, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return $this->fetchOne(
            'SELECT * FROM user_editor_workspaces WHERE id = :id AND user_id = :uid LIMIT 1',
            ['id' => $id, 'uid' => $userId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function listByUserId(int $userId, int $limit = 12): array
    {
        $limit = max(1, min(48, $limit));
        return $this->fetchAll(
            "SELECT id, user_id, title, preview_path, created_at, updated_at
             FROM user_editor_workspaces
             WHERE user_id = :uid
             ORDER BY updated_at DESC, id DESC
             LIMIT {$limit}",
            ['uid' => $userId]
        );
    }

    public function upsert(int $userId, int $id, string $title, string $documentJson, ?string $uiJson): int
    {
        $now = date('Y-m-d H:i:s');
        $existing = $id > 0 ? $this->findByIdForUser($userId, $id) : null;
        if ($existing) {
            $this->execute(
                'UPDATE user_editor_workspaces
                 SET title = :title, document_json = :doc, ui_json = :ui, updated_at = :now
                 WHERE id = :id AND user_id = :uid',
                [
                    'title' => $title,
                    'doc' => $documentJson,
                    'ui' => $uiJson,
                    'now' => $now,
                    'id' => (int) $existing['id'],
                    'uid' => $userId,
                ]
            );
            return (int) $existing['id'];
        }

        $this->execute(
            'INSERT INTO user_editor_workspaces (user_id, title, document_json, ui_json, created_at, updated_at)
             VALUES (:uid, :title, :doc, :ui, :created, :updated)',
            [
                'uid' => $userId,
                'title' => $title,
                'doc' => $documentJson,
                'ui' => $uiJson,
                'created' => $now,
                'updated' => $now,
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function updatePreviewPath(int $id, int $userId, string $previewPath): void
    {
        $this->execute(
            'UPDATE user_editor_workspaces SET preview_path = :preview, updated_at = :now WHERE id = :id AND user_id = :uid',
            [
                'preview' => $previewPath,
                'now' => date('Y-m-d H:i:s'),
                'id' => $id,
                'uid' => $userId,
            ]
        );
    }
}
