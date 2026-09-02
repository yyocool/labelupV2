<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BaseModel;

final class LabelTemplateRepository extends BaseModel
{
    public function count(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS c FROM label_templates');
        return (int) ($row['c'] ?? 0);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM label_templates WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->fetchOne('SELECT * FROM label_templates WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    }

    /**
     * @param array{q?:string, category?:string, active_only?:bool, page?:int, per_page?:int, with_document?:bool} $filters
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, per_page:int}
     */
    public function list(array $filters = []): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 24)));
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['active_only'])) {
            $where[] = 'is_active = 1';
        }
        if (!empty($filters['category'])) {
            $where[] = 'category = :category';
            $params['category'] = (string) $filters['category'];
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(name LIKE :q_name OR slug LIKE :q_slug OR tags LIKE :q_tags OR description LIKE :q_desc)';
            $like = '%' . $q . '%';
            $params['q_name'] = $like;
            $params['q_slug'] = $like;
            $params['q_tags'] = $like;
            $params['q_desc'] = $like;
        }

        $sqlWhere = implode(' AND ', $where);
        $countRow = $this->fetchOne("SELECT COUNT(*) AS c FROM label_templates WHERE {$sqlWhere}", $params);
        $total = (int) ($countRow['c'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $cols = !empty($filters['with_document'])
            ? '*'
            : 'id, slug, name, category, tags, description, tone, paper_no, paper_w_mm, paper_h_mm, paper_shape, is_active, sort_order, created_at, updated_at';

        $items = $this->fetchAll(
            "SELECT {$cols} FROM label_templates WHERE {$sqlWhere}
             ORDER BY sort_order ASC, id ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function listActiveAll(): array
    {
        return $this->fetchAll(
            'SELECT id, slug, name, category, tags, description, tone, paper_no, paper_w_mm, paper_h_mm, paper_shape, document_json, sort_order
             FROM label_templates
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = (int) ($data['id'] ?? 0);
        $params = [
            'slug' => (string) ($data['slug'] ?? ''),
            'name' => (string) ($data['name'] ?? ''),
            'category' => (string) ($data['category'] ?? ''),
            'tags' => $data['tags'] ?? null,
            'description' => $data['description'] ?? null,
            'tone' => (string) ($data['tone'] ?? '#7B2840'),
            'paper_no' => $data['paper_no'] ?? null,
            'paper_w_mm' => (float) ($data['paper_w_mm'] ?? 70),
            'paper_h_mm' => (float) ($data['paper_h_mm'] ?? 36),
            'paper_shape' => (string) ($data['paper_shape'] ?? 'rect'),
            'document_json' => (string) ($data['document_json'] ?? '{}'),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $params['id'] = $id;
            $this->execute(
                'UPDATE label_templates SET
                    slug=:slug, name=:name, category=:category, tags=:tags, description=:description,
                    tone=:tone, paper_no=:paper_no, paper_w_mm=:paper_w_mm, paper_h_mm=:paper_h_mm,
                    paper_shape=:paper_shape, document_json=:document_json, is_active=:is_active,
                    sort_order=:sort_order, updated_at=:updated_at
                 WHERE id=:id',
                $params
            );
            return $id;
        }

        $params['created_at'] = $now;
        $this->execute(
            'INSERT INTO label_templates
                (slug, name, category, tags, description, tone, paper_no, paper_w_mm, paper_h_mm,
                 paper_shape, document_json, is_active, sort_order, created_at, updated_at)
             VALUES
                (:slug, :name, :category, :tags, :description, :tone, :paper_no, :paper_w_mm, :paper_h_mm,
                 :paper_shape, :document_json, :is_active, :sort_order, :created_at, :updated_at)',
            $params
        );
        return (int) $this->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM label_templates WHERE id = :id', ['id' => $id]);
    }
}
