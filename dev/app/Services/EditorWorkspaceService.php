<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EditorWorkspaceRepository;
use RuntimeException;

final class EditorWorkspaceService
{
    private EditorWorkspaceRepository $repo;

    public function __construct()
    {
        $this->repo = new EditorWorkspaceRepository();
    }

    public function findForUser(int $userId, int $id = 0): ?array
    {
        $row = $id > 0
            ? $this->repo->findByIdForUser($userId, $id)
            : $this->repo->findLatestByUserId($userId);
        return $row ? $this->present($row, true) : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function recentForUser(int $userId, int $limit = 6): array
    {
        $items = [];
        foreach ($this->repo->listByUserId($userId, $limit) as $row) {
            $items[] = $this->present($row, false);
        }
        return $items;
    }

    /**
     * @param array<string, mixed> $document
     * @param array<string, mixed>|null $ui
     * @return array<string, mixed>
     */
    public function save(int $userId, int $id, string $title, array $document, ?array $ui, string $previewDataUrl = ''): array
    {
        $docJson = json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($docJson === false) {
            throw new RuntimeException('문서 JSON 직렬화에 실패했습니다.');
        }
        $uiJson = null;
        if (is_array($ui)) {
            $encoded = json_encode($ui, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $uiJson = $encoded === false ? null : $encoded;
        }

        $savedId = $this->repo->upsert($userId, $id, $title, $docJson, $uiJson);
        $previewPath = $this->storePreview($userId, $savedId, $previewDataUrl);
        if ($previewPath !== null) {
            $this->repo->updatePreviewPath($savedId, $userId, $previewPath);
        }

        $row = $this->repo->findByIdForUser($userId, $savedId);
        return $row ? $this->present($row, false) : [
            'id' => $savedId,
            'title' => $title,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /** @param array<string, mixed> $row */
    private function present(array $row, bool $withDocument): array
    {
        $id = (int) ($row['id'] ?? 0);
        $preview = $this->previewUrl((string) ($row['preview_path'] ?? ''));
        $out = [
            'id' => $id,
            'title' => (string) ($row['title'] ?? '새 라벨 디자인'),
            'preview_url' => $preview,
            'updated_at' => $row['updated_at'] ?? null,
            'updated_label' => $this->formatUpdated((string) ($row['updated_at'] ?? '')),
            'editor_url' => url('editor/') . ($id > 0 ? '?project=' . $id : ''),
        ];
        if ($withDocument) {
            $doc = json_decode((string) ($row['document_json'] ?? ''), true);
            $ui = json_decode((string) ($row['ui_json'] ?? ''), true);
            $out['document'] = is_array($doc) ? $doc : null;
            $out['ui'] = is_array($ui) ? $ui : null;
        }
        return $out;
    }

    private function previewUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $rel = ltrim($path, '/');
        if (str_starts_with($rel, 'assets/')) {
            return asset(substr($rel, strlen('assets/')));
        }
        return asset($rel);
    }

    private function formatUpdated(string $at): string
    {
        $ts = strtotime($at);
        if ($ts === false) {
            return '';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return '방금';
        }
        if ($diff < 3600) {
            return (int) floor($diff / 60) . '분 전';
        }
        if ($diff < 86400) {
            return (int) floor($diff / 3600) . '시간 전';
        }
        if ($diff < 86400 * 7) {
            return (int) floor($diff / 86400) . '일 전';
        }
        return date('Y.m.d', $ts);
    }

    private function storePreview(int $userId, int $id, string $dataUrl): ?string
    {
        $dataUrl = trim($dataUrl);
        if ($dataUrl === '' || $userId <= 0 || $id <= 0) {
            return null;
        }
        if (!preg_match('#^data:image/(png|jpeg|jpg|webp);base64,([A-Za-z0-9+/=\s]+)$#i', $dataUrl, $m)) {
            return null;
        }
        $bin = base64_decode(preg_replace('/\s+/', '', $m[2]) ?? '', true);
        if ($bin === false || strlen($bin) < 32 || strlen($bin) > 2_000_000) {
            return null;
        }

        $dir = public_path('assets/editor-previews/' . $userId);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return null;
        }
        @chmod($dir, 0777);
        $rel = 'editor-previews/' . $userId . '/' . $id . '.png';
        $full = public_path('assets/' . $rel);
        if (@file_put_contents($full, $bin) === false) {
            return null;
        }
        @chmod($full, 0666);
        return $rel;
    }
}
