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

    public function findForUser(int $userId, int $id = 0, bool $includeTrashed = false): ?array
    {
        $row = $id > 0
            ? $this->repo->findByIdForUser($userId, $id, $includeTrashed)
            : $this->repo->findLatestByUserId($userId);
        return $row ? $this->present($row, true) : null;
    }

    /** @return array<int, array<string, mixed>> */
    public function recentForUser(int $userId, int $limit = 6, bool $trashed = false): array
    {
        $items = [];
        foreach ($this->repo->listByUserId($userId, $limit, $trashed) as $row) {
            $items[] = $this->present($row, false);
        }
        return $items;
    }

    public function trashForUser(int $userId, int $id): void
    {
        if ($id <= 0 || !$this->repo->trash($id, $userId)) {
            throw new RuntimeException('휴지통으로 보낼 디자인을 찾지 못했습니다.');
        }
    }

    public function restoreForUser(int $userId, int $id): void
    {
        if ($id <= 0 || !$this->repo->restore($id, $userId)) {
            throw new RuntimeException('복원할 디자인을 찾지 못했습니다.');
        }
    }

    public function purgeForUser(int $userId, int $id): void
    {
        $row = $this->repo->purge($id, $userId);
        if (!$row) {
            throw new RuntimeException('완전 삭제할 디자인을 찾지 못했습니다. 휴지통에서만 삭제할 수 있습니다.');
        }
        $this->deletePreviewFile((string) ($row['preview_path'] ?? ''));
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
        $doc = null;
        $docJson = (string) ($row['document_json'] ?? '');
        if ($docJson !== '') {
            $decoded = json_decode($docJson, true);
            $doc = is_array($decoded) ? $decoded : null;
        }
        $previewSvg = '';
        if ($doc !== null) {
            $previewDoc = $doc;
            $uid = (int) ($row['user_id'] ?? 0);
            if ($uid > 0) {
                $this->walkDocumentMedia($previewDoc, $uid, true);
            }
            $previewSvg = LabelTemplatePreview::svgFromRow($row, $previewDoc);
        }
        $previewUrl = $this->previewUrl((string) ($row['preview_path'] ?? ''));
        if ($previewUrl === '' && $previewSvg !== '') {
            $previewUrl = $this->storeSvgPreview(
                (int) ($row['user_id'] ?? 0),
                $id,
                $previewSvg
            );
        }
        $trashedAt = (string) ($row['trashed_at'] ?? '');
        $out = [
            'id' => $id,
            'type' => 'workspace',
            'title' => (string) ($row['title'] ?? '새 라벨 디자인'),
            'preview_url' => $previewUrl,
            'preview_svg' => $previewSvg,
            'updated_at' => $row['updated_at'] ?? null,
            'updated_label' => $this->formatUpdated((string) ($row['updated_at'] ?? '')),
            'trashed_at' => $trashedAt !== '' ? $trashedAt : null,
            'trashed_label' => $trashedAt !== '' ? $this->formatUpdated($trashedAt) : '',
            'editor_url' => url('editor/') . ($id > 0 ? '?project=' . $id : ''),
        ];
        if ($withDocument) {
            $ui = json_decode((string) ($row['ui_json'] ?? ''), true);
            $out['document'] = $doc;
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
            $rel = substr($rel, strlen('assets/'));
        }
        $full = public_path('assets/' . $rel);
        if (!is_file($full) || filesize($full) < 32) {
            return '';
        }
        return asset($rel);
    }

    private function storeSvgPreview(int $userId, int $id, string $svg): string
    {
        if ($userId <= 0 || $id <= 0 || trim($svg) === '') {
            return '';
        }
        $svg = LabelTemplatePreview::withInlinedImages($svg);
        $dir = public_path('assets/editor-previews/' . $userId);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return '';
        }
        @chmod($dir, 0777);
        $rel = 'editor-previews/' . $userId . '/' . $id . '.svg';
        $full = public_path('assets/' . $rel);
        if (@file_put_contents($full, $svg) === false) {
            // 파일이 안 써져도 data URL은 반환한다.
        } else {
            @chmod($full, 0666);
        }
        if (strlen($svg) > 1_200_000) {
            return asset($rel);
        }
        return 'data:image/svg+xml;charset=utf-8;base64,' . base64_encode($svg);
    }

    private function deletePreviewFile(string $path): void
    {
        $path = trim($path);
        if ($path === '' || str_contains($path, '..')) {
            return;
        }
        $rel = ltrim($path, '/');
        if (str_starts_with($rel, 'assets/')) {
            $rel = substr($rel, strlen('assets/'));
        }
        if (!preg_match('#^editor-previews/\d+/\d+\.(png|jpe?g|webp|svg)$#i', $rel)) {
            return;
        }
        $full = public_path('assets/' . $rel);
        if (is_file($full)) {
            @unlink($full);
        }
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
    private function walkDocumentMedia(array &$node, int $userId, bool $force = false): void
    {
        foreach ($node as $key => &$value) {
            if (is_string($value)
                && (strcasecmp((string) $key, 'imageData') === 0 || strcasecmp((string) $key, 'image_data') === 0)
            ) {
                $replaced = $this->storeMediaDataUrl($userId, $value, $force);
                if ($replaced !== null) {
                    $value = $replaced;
                }
                continue;
            }
            if (is_array($value)) {
                $this->walkDocumentMedia($value, $userId, $force);
            }
        }
        unset($value);
    }

    private function storeMediaDataUrl(int $userId, string $dataUrl, bool $force = false): ?string
    {
        $dataUrl = trim($dataUrl);
        // 이미 URL/경로면 그대로 둔다.
        if ($dataUrl === '' || !str_starts_with(strtolower($dataUrl), 'data:image/')) {
            return null;
        }
        // 작은 인라인(아이콘 등)은 유지. 미리보기 강제 시에는 파일로 뺀다.
        if (!$force && strlen($dataUrl) < 24_000) {
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
