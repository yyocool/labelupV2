<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InquiryRepository;
use RuntimeException;

final class InquiryService
{
    private InquiryRepository $repo;

    public function __construct()
    {
        $this->repo = new InquiryRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function allForAdmin(?string $status = null): array
    {
        $status = $status !== null && $status !== '' && $status !== 'all' ? $status : null;
        return $this->repo->allForAdmin($status);
    }

    public function submit(array $data, ?int $userId): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        if ($name === '' || $email === '' || $subject === '' || $content === '') {
            throw new RuntimeException('이름, 이메일, 제목, 내용을 모두 입력해 주세요.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('이메일 형식이 올바르지 않습니다.');
        }
        if (mb_strlen($subject) > 200) {
            throw new RuntimeException('제목은 200자 이내로 입력해 주세요.');
        }
        return $this->repo->create([
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'content' => $content,
        ]);
    }

    public function updateStatus(int $id, string $status, ?string $memo = null): void
    {
        if ($id <= 0) {
            throw new RuntimeException('문의가 없습니다.');
        }
        if ($this->repo->find($id) === null) {
            throw new RuntimeException('문의를 찾을 수 없습니다.');
        }
        $allowed = ['open', 'in_progress', 'answered', 'closed'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('잘못된 상태입니다.');
        }
        $this->repo->updateStatus($id, $status, $memo);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'open' => '접수',
            'in_progress' => '처리중',
            'answered' => '답변완료',
            'closed' => '종료',
            default => $status,
        };
    }
}
