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
        $document = $this->externalizeDocumentMedia($userId, $document);
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

    /**
     * 큰 data-URL 이미지를 파일로 빼고 URL로 바꿔 max_allowed_packet 초과를 막는다.
     *
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    private function externalizeDocumentMedia(int $userId, array $document): array
    {
        if ($userId <= 0) {
            return $document;
        }
        $this->walkDocumentMedia($document, $userId);
        return $document;
    }

    /**
     * @param array<string, mixed>|list<mixed> $node
     */
    private function walkDocumentMedia(array &$node, int $userId): void
    {
        foreach ($node as $key => &$value) {
            if (is_string($value)
                && (strcasecmp((string) $key, 'imageData') === 0 || strcasecmp((string) $key, 'image_data') === 0)
            ) {
                $replaced = $this->storeMediaDataUrl($userId, $value);
                if ($replaced !== null) {
                    $value = $replaced;
                }
                continue;
            }
            if (is_array($value)) {
                $this->walkDocumentMedia($value, $userId);
            }
        }
        unset($value);
    }

    private function storeMediaDataUrl(int $userId, string $dataUrl): ?string
    {
        $dataUrl = trim($dataUrl);
        // 이미 URL/경로면 그대로 둔다.
        if ($dataUrl === '' || !str_starts_with(strtolower($dataUrl), 'data:image/')) {
            return null;
        }
        // 작은 인라인(아이콘 등)은 유지. 임계값 초과만 파일화.
        if (strlen($dataUrl) < 24_000) {
            return null;
        }
        if (!preg_match('#^data:image/(png|jpeg|jpg|webp|gif);base64,([A-Za-z0-9+/=\s]+)$#i', $dataUrl, $m)) {
            return null;
        }
        $ext = strtolower($m[1]);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        $bin = base64_decode(preg_replace('/\s+/', '', $m[2]) ?? '', true);
        if ($bin === false || strlen($bin) < 32 || strlen($bin) > 12_000_000) {
            return null;
        }

        $dir = public_path('assets/editor-media/' . $userId);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return null;
        }
        @chmod($dir, 0777);

        $hash = sha1($bin);
        $rel = 'editor-media/' . $userId . '/' . $hash . '.' . $ext;
        $full = public_path('assets/' . $rel);
        if (!is_file($full)) {
            if (@file_put_contents($full, $bin) === false) {
                return null;
            }
            @chmod($full, 0666);
        }

        return '/assets/' . $rel;
    }
}
