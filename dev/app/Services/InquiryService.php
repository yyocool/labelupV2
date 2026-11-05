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
        $items = $this->repo->allForAdmin($status);
        $logs = $this->repo->logsFor(array_column($items, 'id'));
        foreach ($items as &$item) {
            $item['logs'] = $logs[(int) $item['id']] ?? [];
        }
        unset($item);
        return $items;
    }

    public function addNote(int $id, string $note, string $status, array $admin): void
    {
        $note = trim($note);
        $row = $id > 0 ? $this->repo->find($id) : null;
        if ($row === null) {
            throw new RuntimeException('문의를 찾을 수 없습니다.');
        }
        $from = (string) ($row['status'] ?? '');
        $status = $status !== '' ? $status : $from;
        $this->assertStatus($status);
        if ($note === '' && $status === $from) {
            throw new RuntimeException('처리 내용을 입력하거나 상태를 바꿔 주세요.');
        }
        if ($status !== $from) {
            $this->repo->updateStatus($id, $status);
        }
        $this->log($id, $admin, 'note', $from, $status, $note);
    }

    public static function logActionLabel(string $action): string
    {
        return match ($action) {
            'answer' => '회원 답변',
            'status' => '상태 변경',
            default => '처리 메모',
        };
    }

    private function log(int $id, array $admin, string $action, string $from, string $to, string $note = ''): void
    {
        $this->repo->addLog([
            'inquiry_id' => $id,
            'admin_id' => $admin['id'] ?? null,
            'admin_name' => trim((string) ($admin['name'] ?? '')) ?: (string) ($admin['email'] ?? ''),
            'action' => $action,
            'status_from' => $from !== $to ? $from : null,
            'status_to' => $from !== $to ? $to : null,
            'note' => $note !== '' ? $note : null,
        ]);
    }

    private function assertStatus(string $status): void
    {
        if (!in_array($status, ['open', 'in_progress', 'answered', 'closed'], true)) {
            throw new RuntimeException('잘못된 상태입니다.');
        }
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

    public function updateStatus(int $id, string $status, ?string $memo = null, array $admin = []): void
    {
        if ($id <= 0) {
            throw new RuntimeException('문의가 없습니다.');
        }
        $row = $this->repo->find($id);
        if ($row === null) {
            throw new RuntimeException('문의를 찾을 수 없습니다.');
        }
        $this->assertStatus($status);
        $this->repo->updateStatus($id, $status, $memo);
        $from = (string) ($row['status'] ?? '');
        if ($from !== $status) {
            $this->log($id, $admin, 'status', $from, $status);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function listForUser(int $userId): array
    {
        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'subject' => (string) ($row['subject'] ?? ''),
            'content' => (string) ($row['content'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'status_label' => self::statusLabel((string) ($row['status'] ?? '')),
            'answer' => (string) ($row['answer'] ?? ''),
            'answered_at' => substr((string) ($row['answered_at'] ?? ''), 0, 16),
            'created_at' => substr((string) ($row['created_at'] ?? ''), 0, 16),
        ], $this->repo->listForUser($userId));
    }

    public function answer(int $id, string $answer, array $admin = []): void
    {
        $answer = trim($answer);
        if ($answer === '') {
            throw new RuntimeException('답변 내용을 입력해 주세요.');
        }
        $row = $id > 0 ? $this->repo->find($id) : null;
        if ($row === null) {
            throw new RuntimeException('문의를 찾을 수 없습니다.');
        }
        $this->repo->saveAnswer($id, $answer);
        $this->log($id, $admin, 'answer', (string) ($row['status'] ?? ''), 'answered', $answer);
        if (!empty($row['user_id'])) {
            (new NotificationService())->notifyInquiryAnswered((int) $row['user_id'], $id, (string) ($row['subject'] ?? ''));
        }
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
