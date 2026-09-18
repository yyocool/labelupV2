<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\QaReviewRepository;
use RuntimeException;

final class QaReviewService
{
    public const STATUSES = [
        '' => '미설정',
        'done' => '완료',
        'error' => '오류',
        'fix' => '보완필요',
        'enhance' => '고도화필요',
    ];

    private QaReviewRepository $repo;

    public function __construct()
    {
        $this->repo = new QaReviewRepository();
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return self::STATUSES;
    }

    public static function normalizeStatus(mixed $raw): string
    {
        $value = trim((string) $raw);
        if ($value === 'ok' || $value === '1' || $value === 'true') {
            $value = 'done';
        }
        return array_key_exists($value, self::STATUSES) ? $value : '';
    }

    public static function isDone(string $status): bool
    {
        return $status === 'done';
    }

    public static function hasRequestHtml(string $html): bool
    {
        $plain = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return $plain !== '' || str_contains($html, '<img');
    }

    /** @return array{items:list<array<string,mixed>>,summary:array<string,int>,statuses:array<string,string>} */
    public function sheet(): array
    {
        $checks = $this->repo->allChecks();
        $items = [];
        $summary = [
            'total' => 0,
            'dev_done' => 0,
            'client_done' => 0,
            'both_done' => 0,
            'issues' => 0,
            'requests' => 0,
            'user' => 0,
            'admin' => 0,
        ];

        foreach (QaReviewCatalog::items() as $item) {
            $state = $checks[$item['key']] ?? [
                'dev_status' => '',
                'client_status' => '',
                'note' => '',
                'request_html' => '',
                'updated_at' => null,
            ];
            $dev = self::normalizeStatus($state['dev_status'] ?? '');
            $client = self::normalizeStatus($state['client_status'] ?? '');
            $requestHtml = (string) ($state['request_html'] ?? '');
            $row = array_merge($item, $state, [
                'dev_status' => $dev,
                'client_status' => $client,
                'request_html' => $requestHtml,
                'has_request' => self::hasRequestHtml($requestHtml),
            ]);
            $items[] = $row;
            $summary['total']++;
            if ($item['area'] === 'user') {
                $summary['user']++;
            } else {
                $summary['admin']++;
            }
            if (self::isDone($dev)) {
                $summary['dev_done']++;
            }
            if (self::isDone($client)) {
                $summary['client_done']++;
            }
            if (self::isDone($dev) && self::isDone($client)) {
                $summary['both_done']++;
            }
            if (in_array($dev, ['error', 'fix', 'enhance'], true)
                || in_array($client, ['error', 'fix', 'enhance'], true)) {
                $summary['issues']++;
            }
            if (!empty($row['has_request'])) {
                $summary['requests']++;
            }
        }

        return [
            'items' => $items,
            'summary' => $summary,
            'statuses' => self::STATUSES,
        ];
    }

    /** @param array<string, mixed> $payload */
    public function saveItem(array $payload, ?int $adminId): array
    {
        $key = $this->assertValidKey((string) ($payload['key'] ?? ''));
        $mode = trim((string) ($payload['mode'] ?? 'status'));

        if ($mode === 'request') {
            $html = (string) ($payload['request_html'] ?? '');
            $this->repo->upsertRequest($key, $html, $adminId);
            return [
                'key' => $key,
                'mode' => 'request',
                'request_html' => $html,
                'has_request' => self::hasRequestHtml($html),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        $devStatus = self::normalizeStatus($payload['dev_status'] ?? '');
        $clientStatus = self::normalizeStatus($payload['client_status'] ?? '');
        $note = trim((string) ($payload['note'] ?? ''));
        $this->repo->upsertStatuses($key, $devStatus, $clientStatus, $note, $adminId);

        return [
            'key' => $key,
            'mode' => 'status',
            'dev_status' => $devStatus,
            'client_status' => $clientStatus,
            'note' => $note,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array<string, mixed> $file $_FILES['image']
     * @return array{url:string,path:string}
     */
    public function uploadImage(array $file): array
    {
        $tmp = (string) ($file['tmp_name'] ?? '');
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('이미지 업로드에 실패했습니다.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 8 * 1024 * 1024) {
            throw new RuntimeException('이미지는 8MB 이하만 업로드할 수 있습니다.');
        }

        $info = @getimagesize($tmp);
        if ($info === false) {
            throw new RuntimeException('이미지 파일이 아닙니다.');
        }
        $mime = strtolower((string) ($info['mime'] ?? ''));
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($extMap[$mime])) {
            throw new RuntimeException('jpg, png, gif, webp만 업로드할 수 있습니다.');
        }

        $subdir = date('Ym');
        $dir = public_path('assets/qa-review/' . $subdir);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('업로드 폴더를 만들 수 없습니다.');
        }
        $filename = 'qa_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extMap[$mime];
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('이미지 저장에 실패했습니다.');
        }
        @chmod($dest, 0664);

        $path = '/assets/qa-review/' . $subdir . '/' . $filename;
        return [
            'path' => $path,
            'url' => url(ltrim($path, '/')),
        ];
    }

    private function assertValidKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            throw new RuntimeException('검수 항목 키가 필요합니다.');
        }
        foreach (QaReviewCatalog::items() as $item) {
            if ($item['key'] === $key) {
                return $key;
            }
        }
        throw new RuntimeException('알 수 없는 검수 항목입니다.');
    }
}
