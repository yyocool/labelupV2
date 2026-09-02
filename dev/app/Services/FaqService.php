<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FaqRepository;
use RuntimeException;

final class FaqService
{
    private FaqRepository $repo;

    public function __construct()
    {
        $this->repo = new FaqRepository();
    }

    /** @return array<int, array<string, mixed>> */
    public function categoriesForAdmin(): array
    {
        return array_map([$this, 'presentCategory'], $this->repo->allCategories());
    }

    /** @return array<int, array<string, mixed>> */
    public function faqsForAdmin(): array
    {
        return array_map([$this, 'presentFaq'], $this->repo->allFaqs());
    }

    /**
     * @return array<int, array{id:int,name:string,slug:string,items:array<int, array<string, mixed>>}>
     */
    public function groupedForSite(): array
    {
        $grouped = [];
        foreach ($this->repo->activeCategories() as $cat) {
            $id = (int) $cat['id'];
            $grouped[$id] = [
                'id' => $id,
                'name' => (string) ($cat['name'] ?? ''),
                'slug' => (string) ($cat['slug'] ?? ''),
                'items' => [],
            ];
        }
        foreach ($this->repo->activeFaqs() as $row) {
            $cid = (int) ($row['category_id'] ?? 0);
            if (!isset($grouped[$cid])) {
                continue;
            }
            $grouped[$cid]['items'][] = $this->presentFaq($row);
        }
        return array_values(array_filter($grouped, static fn (array $g): bool => $g['items'] !== []));
    }

    public function saveCategory(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('카테고리 이름을 입력해 주세요.');
        }
        $id = (int) ($data['id'] ?? 0);
        $slug = $this->normalizeSlug((string) ($data['slug'] ?? ''), $name);
        $slug = $this->uniqueSlug($slug, $id);
        $data['name'] = $name;
        $data['slug'] = $slug;
        $data['is_active'] = !empty($data['is_active']);
        return $this->repo->saveCategory($data);
    }

    public function deleteCategory(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('삭제할 카테고리가 없습니다.');
        }
        if ($this->repo->findCategory($id) === null) {
            throw new RuntimeException('카테고리를 찾을 수 없습니다.');
        }
        if ($this->repo->countByCategory($id) > 0) {
            throw new RuntimeException('이 카테고리에 FAQ가 있어 삭제할 수 없습니다. FAQ를 먼저 옮기거나 삭제해 주세요.');
        }
        $this->repo->deleteCategory($id);
    }

    public function saveFaq(array $data): int
    {
        $question = trim((string) ($data['question'] ?? ''));
        $answer = trim((string) ($data['answer'] ?? ''));
        $categoryId = (int) ($data['category_id'] ?? 0);
        if ($question === '') {
            throw new RuntimeException('질문을 입력해 주세요.');
        }
        if ($answer === '' || $answer === '<p><br></p>' || $answer === '<p></p>') {
            throw new RuntimeException('답변을 입력해 주세요.');
        }
        if ($categoryId <= 0 || $this->repo->findCategory($categoryId) === null) {
            throw new RuntimeException('카테고리를 선택해 주세요.');
        }
        $data['question'] = $question;
        $data['answer'] = $answer;
        $data['is_active'] = !empty($data['is_active']);
        return $this->repo->saveFaq($data);
    }

    public function deleteFaq(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('삭제할 FAQ가 없습니다.');
        }
        $this->repo->deleteFaq($id);
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    public function presentCategory(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        $row['is_active'] = (int) ($row['is_active'] ?? 0);
        $row['faq_count'] = $this->repo->countByCategory((int) $row['id']);
        return $row;
    }

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    public function presentFaq(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['category_id'] = (int) ($row['category_id'] ?? 0);
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        $row['is_active'] = (int) ($row['is_active'] ?? 0);
        $row['category_name'] = (string) ($row['category_name'] ?? '');
        $row['category_slug'] = (string) ($row['category_slug'] ?? '');
        return $row;
    }

    private function normalizeSlug(string $slug, string $name): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        if ($slug !== '') {
            return $slug;
        }
        $fromName = strtolower(trim($name));
        $fromName = preg_replace('/[^a-z0-9\-]+/', '-', $fromName) ?? '';
        $fromName = trim($fromName, '-');
        return $fromName !== '' ? $fromName : 'faq';
    }

    private function uniqueSlug(string $slug, int $exceptId): string
    {
        $base = $slug !== '' ? $slug : 'faq';
        $try = $base;
        $n = 2;
        while ($this->repo->slugExists($try, $exceptId)) {
            $try = $base . '-' . $n;
            $n++;
        }
        return $try;
    }
}
