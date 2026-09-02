<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserAiClipartRepository;

final class UserAiClipartService
{
    private UserAiClipartRepository $repo;

    public function __construct()
    {
        $this->repo = new UserAiClipartRepository();
    }

    /**
     * @param array{url?:string,prompt?:string,title?:string} $clipart
     */
    public function saveForUser(int $userId, array $clipart): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $url = trim((string) ($clipart['url'] ?? ''));
        if ($url === '') {
            return 0;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $fileName = is_string($path) ? basename($path) : '';

        return $this->repo->create([
            'user_id' => $userId,
            'title' => trim((string) ($clipart['title'] ?? '')) ?: '라비가 그린 클립아트',
            'prompt' => trim((string) ($clipart['prompt'] ?? '')) ?: null,
            'image_url' => $url,
            'file_name' => $fileName !== '' ? $fileName : null,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId, int $limit = 48): array
    {
        $items = [];
        foreach ($this->repo->listByUser($userId, $limit) as $row) {
            $url = (string) ($row['image_url'] ?? '');
            $title = (string) ($row['title'] ?? '라비가 그린 클립아트');
            $qs = http_build_query(array_filter([
                'clipart' => $url,
                'name' => $title,
            ]));
            $items[] = $row + [
                'editor_url' => url('editor/') . ($qs !== '' ? '?' . $qs : ''),
            ];
        }
        return $items;
    }

    public function countForUser(int $userId): int
    {
        return $this->repo->countByUser($userId);
    }

    /**
     * @param array{q?:string,user_id?:int,status?:string,date_from?:string,date_to?:string,page?:int,per_page?:int} $filters
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, pages: int}
     */
    public function adminList(array $filters): array
    {
        $list = $this->repo->adminList($filters);
        foreach ($list['items'] as &$row) {
            $row = $this->decorateAdminRow($row);
        }
        unset($row);
        return $list;
    }

    /** @return array{total:int,pending:int,approved:int,rejected:int,approved_month:int,users:int} */
    public function adminStats(): array
    {
        return $this->repo->adminStats();
    }

    public function find(int $id): ?array
    {
        $row = $this->repo->find($id);
        return $row ? $this->decorateAdminRow($row) : null;
    }

    public function review(int $id, string $action, ?string $note, ?int $adminId): array
    {
        if ($id <= 0) {
            throw new \RuntimeException('디자인을 찾을 수 없습니다.');
        }
        if (!$this->repo->find($id)) {
            throw new \RuntimeException('디자인을 찾을 수 없습니다.');
        }
        $status = $action === 'reject' ? 'rejected' : ($action === 'approve' ? 'approved' : '');
        if ($status === '') {
            throw new \RuntimeException('검수 상태가 올바르지 않습니다.');
        }
        $note = trim((string) $note);
        if ($status === 'rejected' && $note === '') {
            throw new \RuntimeException('반려 사유를 입력해 주세요.');
        }
        $this->repo->updateReview($id, $status, $note !== '' ? $note : null, $adminId);
        $row = $this->find($id);
        if (!$row) {
            throw new \RuntimeException('디자인을 찾을 수 없습니다.');
        }
        return $row;
    }

    /** @param array<int, int> $ids */
    public function approveMany(array $ids, ?int $adminId): int
    {
        return $this->repo->updateReviewMany($ids, 'approved', $adminId);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'approved' => '승인',
            'rejected' => '반려',
            default => '대기',
        };
    }

    public static function rejectReasons(): array
    {
        return [
            '저작권 요소가 포함되어 있습니다.',
            '부적절한 콘텐츠입니다.',
            '품질이 기준에 미달합니다.',
            '프롬프트와 이미지가 일치하지 않습니다.',
        ];
    }

    public function delete(int $id): void
    {
        $row = $this->repo->find($id);
        if (!$row) {
            throw new \RuntimeException('디자인을 찾을 수 없습니다.');
        }
        $this->repo->clearUsageClipart($id);
        $this->repo->delete($id);
        $this->deleteLocalFile((string) ($row['image_url'] ?? ''), (string) ($row['file_name'] ?? ''));
    }

    private function deleteLocalFile(string $url, string $fileName): void
    {
        $name = $fileName !== '' ? $fileName : (string) basename((string) (parse_url($url, PHP_URL_PATH) ?: ''));
        if ($name === '' || !preg_match('/^clip_[\w\-]+\.(png|jpe?g|webp)$/i', $name)) {
            return;
        }
        foreach ([
            public_path('assets/ai-clipart/' . $name),
            storage_path('ai-clipart/' . $name),
        ] as $full) {
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    /** @param array<string, mixed> $row */
    private function decorateAdminRow(array $row): array
    {
        $url = (string) ($row['image_url'] ?? '');
        $title = (string) ($row['title'] ?? '라비가 그린 디자인');
        $qs = http_build_query(array_filter([
            'clipart' => $url,
            'name' => $title,
        ]));
        $status = (string) ($row['review_status'] ?? 'pending');
        $row['editor_url'] = url('editor/') . ($qs !== '' ? '?' . $qs : '');
        $row['user_url'] = url('admin/users/' . (int) ($row['user_id'] ?? 0));
        $row['review_status'] = $status !== '' ? $status : 'pending';
        $row['review_status_label'] = self::statusLabel($row['review_status']);
        $row['kind'] = 'ai';
        $row['kind_label'] = 'AI 생성';
        return $row;
    }
}
