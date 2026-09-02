<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class FaqRepository extends BaseModel
{
    /** @return array<int, array<string, mixed>> */
    public function allCategories(): array
    {
        return $this->fetchAll(
            'SELECT * FROM faq_categories ORDER BY sort_order ASC, id ASC'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function activeCategories(): array
    {
        return $this->fetchAll(
            'SELECT * FROM faq_categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
    }

    public function findCategory(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM faq_categories WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function slugExists(string $slug, int $exceptId = 0): bool
    {
        $row = $this->fetchOne(
            'SELECT id FROM faq_categories WHERE slug = :slug AND id <> :id LIMIT 1',
            ['slug' => $slug, 'id' => $exceptId]
        );
        return $row !== null;
    }

    public function saveCategory(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'name' => trim((string) ($data['name'] ?? '')),
            'slug' => trim((string) ($data['slug'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (int) !empty($data['is_active']),
            'now' => $now,
        ];

        if ($id > 0) {
            $this->execute(
                'UPDATE faq_categories SET name=:name, slug=:slug, sort_order=:sort_order,
                 is_active=:is_active, updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }

        $this->execute(
            'INSERT INTO faq_categories (name, slug, sort_order, is_active, created_at, updated_at)
             VALUES (:name, :slug, :sort_order, :is_active, :now, :now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function deleteCategory(int $id): void
    {
        $this->execute('DELETE FROM faq_categories WHERE id = :id', ['id' => $id]);
    }

    public function countByCategory(int $categoryId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS cnt FROM faqs WHERE category_id = :id',
            ['id' => $categoryId]
        );
        return (int) ($row['cnt'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function allFaqs(): array
    {
        return $this->fetchAll(
            'SELECT f.*, c.name AS category_name, c.slug AS category_slug
             FROM faqs f
             LEFT JOIN faq_categories c ON c.id = f.category_id
             ORDER BY c.sort_order ASC, f.sort_order ASC, f.id DESC'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function activeFaqs(): array
    {
        return $this->fetchAll(
            'SELECT f.*, c.name AS category_name, c.slug AS category_slug
             FROM faqs f
             INNER JOIN faq_categories c ON c.id = f.category_id
             WHERE f.is_active = 1 AND c.is_active = 1
             ORDER BY c.sort_order ASC, f.sort_order ASC, f.id ASC'
        );
    }

    public function findFaq(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM faqs WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function saveFaq(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'category_id' => (int) ($data['category_id'] ?? 0),
            'question' => trim((string) ($data['question'] ?? '')),
            'answer' => (string) ($data['answer'] ?? ''),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (int) !empty($data['is_active']),
            'now' => $now,
        ];

        if ($id > 0) {
            $this->execute(
                'UPDATE faqs SET category_id=:category_id, question=:question, answer=:answer,
                 sort_order=:sort_order, is_active=:is_active, updated_at=:now WHERE id=:id',
                $params + ['id' => $id]
            );
            return $id;
        }

        $this->execute(
            'INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
             VALUES (:category_id, :question, :answer, :sort_order, :is_active, :now, :now)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function deleteFaq(int $id): void
    {
        $this->execute('DELETE FROM faqs WHERE id = :id', ['id' => $id]);
    }
}
