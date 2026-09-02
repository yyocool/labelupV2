<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MemberGradeRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class MemberGradeService
{
    private MemberGradeRepository $grades;
    private UserRepository $users;

    public function __construct()
    {
        $this->grades = new MemberGradeRepository();
        $this->users = new UserRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function listAll(): array
    {
        return $this->grades->all(false);
    }

    /** @return array<int, array<string, mixed>> */
    public function listActive(): array
    {
        return $this->grades->all(true);
    }

    /** @return array<string, mixed> */
    public function forUser(int $userId): array
    {
        $row = $this->grades->forUser($userId);
        if ($row) {
            return $this->present($row);
        }
        $fallback = $this->grades->defaultGrade();
        if ($fallback) {
            if ($userId > 0) {
                $this->grades->assignUser($userId, (int) $fallback['id']);
            }
            return $this->present($fallback);
        }
        return [
            'id' => 0,
            'name' => '일반',
            'slug' => 'general',
            'description' => '기본 회원등급입니다.',
            'color' => '#6B7280',
            'is_default' => 1,
            'is_active' => 1,
        ];
    }

    public function assign(int $userId, int $gradeId): void
    {
        if ($userId <= 0) {
            throw new RuntimeException('회원을 선택해주세요.');
        }
        if (!$this->users->findById($userId)) {
            throw new RuntimeException('회원을 찾을 수 없습니다.');
        }
        $grade = $this->grades->find($gradeId);
        if (!$grade || empty($grade['is_active'])) {
            throw new RuntimeException('유효한 회원등급이 아닙니다.');
        }
        $this->grades->assignUser($userId, $gradeId);
    }

    public function assignDefault(int $userId): void
    {
        $default = $this->grades->defaultGrade();
        if ($default) {
            $this->grades->assignUser($userId, (int) $default['id']);
        }
    }

    public function save(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('등급명을 입력해주세요.');
        }
        if (mb_strlen($name) > 80) {
            throw new RuntimeException('등급명은 80자 이내로 입력해주세요.');
        }
        $slug = $this->normalizeSlug((string) ($data['slug'] ?? ''), $name);
        if ($this->grades->findBySlug($slug, $id > 0 ? $id : null)) {
            throw new RuntimeException('이미 사용 중인 등급 코드입니다.');
        }
        $color = $this->normalizeColor((string) ($data['color'] ?? ''));
        $isDefault = !empty($data['is_default']);
        $isActive = !isset($data['is_active']) || !empty($data['is_active']);
        if ($id > 0) {
            $current = $this->grades->find($id);
            if (!$current) {
                throw new RuntimeException('회원등급을 찾을 수 없습니다.');
            }
            if (!empty($current['is_default']) && !$isDefault) {
                throw new RuntimeException('기본 등급은 해제할 수 없습니다. 다른 등급을 기본으로 지정하세요.');
            }
            if (!empty($current['is_default']) && !$isActive) {
                throw new RuntimeException('기본 등급은 비활성화할 수 없습니다.');
            }
        }
        if ($isDefault) {
            $isActive = true;
        }
        $savedId = $this->grades->save([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'description' => mb_substr(trim((string) ($data['description'] ?? '')), 0, 255),
            'color' => $color,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_default' => $isDefault ? 1 : 0,
            'is_active' => $isActive ? 1 : 0,
        ]);
        if ($isDefault) {
            $this->grades->clearDefault($savedId);
        }
        return $savedId;
    }

    public function delete(int $id): void
    {
        $grade = $this->grades->find($id);
        if (!$grade) {
            throw new RuntimeException('회원등급을 찾을 수 없습니다.');
        }
        if (!empty($grade['is_default'])) {
            throw new RuntimeException('기본 등급은 삭제할 수 없습니다.');
        }
        $fallback = $this->grades->defaultGrade();
        if ($fallback && (int) $fallback['id'] !== $id) {
            $this->grades->reassignUsers($id, (int) $fallback['id']);
        } elseif ($this->grades->countUsers($id) > 0) {
            throw new RuntimeException('이 등급을 사용 중인 회원이 있어 삭제할 수 없습니다.');
        }
        $this->grades->delete($id);
    }

    /** @param array<string, mixed> $row */
    private function present(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? '일반'),
            'slug' => (string) ($row['slug'] ?? 'general'),
            'description' => (string) ($row['description'] ?? ''),
            'color' => (string) ($row['color'] ?? '#6B7280'),
            'is_default' => !empty($row['is_default']) ? 1 : 0,
            'is_active' => !empty($row['is_active']) ? 1 : 0,
        ];
    }

    private function normalizeSlug(string $slug, string $name): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'grade-' . substr(sha1($name . microtime()), 0, 8);
        }
        return substr($slug, 0, 50);
    }

    private function normalizeColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#?[0-9a-fA-F]{6}$/', $color)) {
            return '#' . ltrim($color, '#');
        }
        return '#7B2D3E';
    }
}
