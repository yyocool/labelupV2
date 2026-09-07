<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\SiteIntroRepository;
use RuntimeException;

final class SiteIntroService
{
    private SiteIntroRepository $repo;

    public function __construct()
    {
        $this->repo = new SiteIntroRepository();
    }

    /** @return array<string, mixed> */
    public function get(): array
    {
        return $this->present($this->repo->get());
    }

    /** Public payload for first-visit player (null when disabled / empty). */
    /** @return array<string, mixed>|null */
    public function activeForSite(): ?array
    {
        $row = $this->present($this->repo->get());
        if (!(int) ($row['is_enabled'] ?? 0)) {
            return null;
        }
        $type = (string) ($row['source_type'] ?? 'youtube');
        if ($type === 'youtube') {
            if (($row['youtube_id'] ?? '') === '') {
                return null;
            }
        } elseif ($type === 'upload') {
            if (($row['video_url'] ?? '') === '') {
                return null;
            }
        } elseif ($type !== 'animation') {
            return null;
        }
        return [
            'source_type' => $type,
            'youtube_id' => (string) ($row['youtube_id'] ?? ''),
            'video_url' => (string) ($row['video_url'] ?? ''),
            'skip_label' => (string) ($row['skip_label'] ?? '건너뛰기'),
        ];
    }

    public function save(array $data): void
    {
        $type = trim((string) ($data['source_type'] ?? 'youtube'));
        if (!in_array($type, ['youtube', 'upload', 'animation'], true)) {
            throw new RuntimeException('영상 유형이 올바르지 않습니다.');
        }
        $youtube = trim((string) ($data['youtube_url'] ?? ''));
        $path = trim((string) ($data['video_path'] ?? ''));
        $enabled = !empty($data['is_enabled']) ? 1 : 0;
        $skip = trim((string) ($data['skip_label'] ?? '건너뛰기'));
        if ($skip === '') {
            $skip = '건너뛰기';
        }
        if ($enabled) {
            if ($type === 'youtube') {
                if ($youtube === '' || self::extractYoutubeId($youtube) === null) {
                    throw new RuntimeException('유효한 유튜브 URL을 입력해 주세요.');
                }
            } elseif ($type === 'upload' && $path === '') {
                throw new RuntimeException('업로드된 동영상 파일이 필요합니다.');
            }
        }
        $this->repo->save([
            'is_enabled' => $enabled,
            'source_type' => $type,
            'youtube_url' => $youtube,
            'video_path' => $path,
            'skip_label' => mb_substr($skip, 0, 80),
        ]);
    }

    /** @return array{path:string,url:string} */
    public function storeUploadedVideo(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('동영상 업로드에 실패했습니다.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('업로드 파일이 유효하지 않습니다.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 120 * 1024 * 1024) {
            throw new RuntimeException('동영상은 120MB 이하만 업로드할 수 있습니다.');
        }
        $name = (string) ($file['name'] ?? 'video.mp4');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp4', 'webm', 'mov', 'm4v'], true)) {
            throw new RuntimeException('mp4, webm, mov, m4v 파일만 업로드할 수 있습니다.');
        }
        $dir = public_path('assets/intro');
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('업로드 폴더를 만들 수 없습니다.');
        }
        $filename = 'intro_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('동영상 저장에 실패했습니다.');
        }
        @chmod($dest, 0664);
        $path = '/assets/intro/' . $filename;
        return ['path' => $path, 'url' => self::resolveVideoUrl($path)];
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    public function present(array $row): array
    {
        $youtube = (string) ($row['youtube_url'] ?? '');
        $path = (string) ($row['video_path'] ?? '');
        return [
            'id' => (int) ($row['id'] ?? 1),
            'is_enabled' => (int) ($row['is_enabled'] ?? 0),
            'source_type' => (string) ($row['source_type'] ?? 'youtube'),
            'youtube_url' => $youtube,
            'youtube_id' => self::extractYoutubeId($youtube) ?? '',
            'video_path' => $path,
            'video_url' => self::resolveVideoUrl($path),
            'skip_label' => (string) ($row['skip_label'] ?? '건너뛰기'),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    public static function resolveVideoUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '/assets/')) {
            return asset(ltrim(substr($path, strlen('/assets/')), '/'));
        }
        if (str_starts_with($path, '/')) {
            return url(ltrim($path, '/'));
        }
        return asset(ltrim($path, '/'));
    }

    public static function extractYoutubeId(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i', $url, $m)) {
            return $m[1];
        }
        if (preg_match('~^[A-Za-z0-9_-]{6,}$~', $url)) {
            return $url;
        }
        return null;
    }
}
