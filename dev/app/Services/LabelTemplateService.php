<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LabelTemplateRepository;
use RuntimeException;

final class LabelTemplateService
{
    public const CATEGORIES = [
        'food' => '식품',
        'shipping' => '배송',
        'beauty' => '화장품',
        'price' => '가격표',
        'round' => '원형스티커',
        'gift' => '선물',
        'cafe' => '카페',
        'warning' => '주의표시',
        'warehouse' => '재고·물류',
        'event' => '행사',
    ];

    private LabelTemplateRepository $repo;

    public function __construct()
    {
        $this->repo = new LabelTemplateRepository();
    }

    /** @return array<string, string> */
    public function categories(): array
    {
        return self::CATEGORIES;
    }

    public function count(): int
    {
        return $this->repo->count();
    }

    public function ensureSeeded(): void
    {
        if ($this->repo->count() === 0) {
            $this->seed(false);
        }
    }

    /**
     * @return array{inserted:int, updated:int, skipped:int, total:int}
     */
    public function seed(bool $force = false): array
    {
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        foreach ((new LabelTemplateSeedService())->all() as $item) {
            $existing = $this->repo->findBySlug((string) $item['slug']);
            if ($existing && !$force) {
                $skipped++;
                continue;
            }
            if ($existing) {
                $item['id'] = (int) $existing['id'];
                $this->repo->save($item);
                $updated++;
            } else {
                $this->repo->save($item);
                $inserted++;
            }
        }
        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $this->repo->count(),
        ];
    }

    /** @param array<string, mixed> $filters */
    public function list(array $filters = []): array
    {
        $result = $this->repo->list($filters);
        foreach ($result['items'] as &$item) {
            $item = $this->present($item, !empty($filters['with_document']));
        }
        unset($item);
        return $result;
    }

    public function find(int $id): ?array
    {
        $row = $this->repo->find($id);
        return $row ? $this->present($row, true) : null;
    }

    public function findByKey(string $key): ?array
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }
        if (ctype_digit($key)) {
            return $this->find((int) $key);
        }
        $row = $this->repo->findBySlug($key);
        return $row ? $this->present($row, true) : null;
    }

    /** @return array{items: array<int, array<string, mixed>>, categories: array<int, array{key:string,name:string}>} */
    public function publicCatalog(): array
    {
        $this->ensureSeeded();
        $items = [];
        foreach ($this->repo->listActiveAll() as $row) {
            $items[] = $this->present($row, false);
        }
        $categories = [];
        foreach (self::CATEGORIES as $key => $name) {
            $categories[] = ['key' => $key, 'name' => $name];
        }
        return ['items' => $items, 'categories' => $categories];
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): int
    {
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('템플릿 이름을 입력해 주세요.');
        }

        $category = trim((string) ($data['category'] ?? ''));
        if ($category === '' || !isset(self::CATEGORIES[$category])) {
            throw new RuntimeException('카테고리를 선택해 주세요.');
        }

        $slug = $this->normalizeSlug((string) ($data['slug'] ?? ''), $name);
        $other = $this->repo->findBySlug($slug);
        if ($other && (int) $other['id'] !== $id) {
            throw new RuntimeException('이미 사용 중인 슬러그입니다.');
        }

        $w = max(10.0, (float) ($data['paper_w_mm'] ?? 70));
        $h = max(10.0, (float) ($data['paper_h_mm'] ?? 36));
        $shape = (string) ($data['paper_shape'] ?? 'rect');
        if (!in_array($shape, ['rect', 'roundrect', 'ellipse'], true)) {
            $shape = 'rect';
        }

        $documentJson = trim((string) ($data['document_json'] ?? ''));
        if ($documentJson === '' && $id > 0) {
            $existing = $this->repo->find($id);
            $documentJson = (string) ($existing['document_json'] ?? '');
        }
        if ($documentJson === '') {
            $documentJson = $this->blankDocument($name, $w, $h, $shape, (string) ($data['paper_no'] ?? 'CUSTOM'));
        }
        $this->assertDocumentJson($documentJson);

        $meta = $this->extractPaperMeta($documentJson, $w, $h, $shape, (string) ($data['paper_no'] ?? ''));

        return $this->repo->save([
            'id' => $id,
            'slug' => $slug,
            'name' => $name,
            'category' => $category,
            'tags' => $this->normalizeTags((string) ($data['tags'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'tone' => $this->normalizeTone((string) ($data['tone'] ?? '#7B2840')),
            'paper_no' => $meta['paper_no'],
            'paper_w_mm' => $meta['paper_w_mm'],
            'paper_h_mm' => $meta['paper_h_mm'],
            'paper_shape' => $meta['paper_shape'],
            'document_json' => $documentJson,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('삭제할 템플릿이 없습니다.');
        }
        if (!$this->repo->find($id)) {
            throw new RuntimeException('템플릿을 찾을 수 없습니다.');
        }
        $this->repo->delete($id);
    }

    /** @return array<string, mixed> */
    public function documentPayload(array $row): array
    {
        $decoded = json_decode((string) ($row['document_json'] ?? ''), true);
        if (!is_array($decoded)) {
            return [];
        }
        return isset($decoded['document']) && is_array($decoded['document']) ? $decoded['document'] : $decoded;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function present(array $row, bool $withDocument): array
    {
        $cat = (string) ($row['category'] ?? '');
        $out = [
            'id' => (int) $row['id'],
            'slug' => (string) $row['slug'],
            'name' => (string) $row['name'],
            'category' => $cat,
            'categoryKey' => $cat,
            'categoryName' => self::CATEGORIES[$cat] ?? $cat,
            'tags' => (string) ($row['tags'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'tone' => (string) ($row['tone'] ?? '#7B2840'),
            'paperNo' => (string) ($row['paper_no'] ?? ''),
            'paper_no' => (string) ($row['paper_no'] ?? ''),
            'widthMm' => (float) ($row['paper_w_mm'] ?? 0),
            'paper_w_mm' => (float) ($row['paper_w_mm'] ?? 0),
            'heightMm' => (float) ($row['paper_h_mm'] ?? 0),
            'paper_h_mm' => (float) ($row['paper_h_mm'] ?? 0),
            'shape' => (string) ($row['paper_shape'] ?? 'rect'),
            'paper_shape' => (string) ($row['paper_shape'] ?? 'rect'),
            'is_active' => (int) ($row['is_active'] ?? 1),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'previewText' => (string) $row['name'],
            'previewSvg' => LabelTemplatePreview::svgFromRow($row, $this->documentPayload($row)),
        ];
        if ($withDocument && isset($row['document_json'])) {
            $out['document_json'] = (string) $row['document_json'];
            $out['document'] = $this->documentPayload($row);
        }
        return $out;
    }

    private function normalizeSlug(string $slug, string $name): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'tpl-' . substr(md5($name . microtime()), 0, 8);
        }
        return substr($slug, 0, 80);
    }

    private function normalizeTags(string $raw): string
    {
        $parts = preg_split('/[\s,]+/u', $raw) ?: [];
        $tags = [];
        foreach ($parts as $part) {
            $t = trim((string) $part, "# \t\n\r");
            if ($t !== '') {
                $tags[$t] = $t;
            }
        }
        return implode(',', array_values($tags));
    }

    private function normalizeTone(string $tone): string
    {
        $tone = trim($tone);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $tone)) {
            return strtoupper($tone);
        }
        return '#7B2840';
    }

    private function assertDocumentJson(string $json): void
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('편집기 문서 JSON 형식이 올바르지 않습니다.');
        }
        $doc = isset($decoded['document']) && is_array($decoded['document']) ? $decoded['document'] : $decoded;
        if (!isset($doc['pages']) && !isset($doc['objects']) && !isset($doc['paper'])) {
            throw new RuntimeException('문서에 paper 또는 pages 정보가 없습니다.');
        }
    }

    /**
     * @return array{paper_no:string, paper_w_mm:float, paper_h_mm:float, paper_shape:string}
     */
    private function extractPaperMeta(string $json, float $w, float $h, string $shape, string $paperNo): array
    {
        $decoded = json_decode($json, true);
        $doc = is_array($decoded) && isset($decoded['document']) && is_array($decoded['document'])
            ? $decoded['document']
            : (is_array($decoded) ? $decoded : []);
        $paper = is_array($doc['paper'] ?? null) ? $doc['paper'] : [];
        return [
            'paper_no' => (string) ($paper['paperNo'] ?? $paperNo ?: 'CUSTOM'),
            'paper_w_mm' => (float) ($paper['labelWidthMm'] ?? $w),
            'paper_h_mm' => (float) ($paper['labelHeightMm'] ?? $h),
            'paper_shape' => (string) (($paper['shape']['kind'] ?? null) ?: $shape),
        ];
    }

    private function blankDocument(string $name, float $w, float $h, string $shape, string $paperNo): string
    {
        $envelope = [
            'format' => 'labelup',
            'version' => 2,
            'document' => [
                'version' => 2,
                'format' => 'labelup',
                'name' => $name,
                'background' => '#FFFFFF',
                'paper' => [
                    'version' => 1,
                    'paperNo' => $paperNo !== '' ? $paperNo : 'CUSTOM',
                    'name' => $name,
                    'category' => 'A4',
                    'brand' => 'LabelUp',
                    'paperWidthMm' => 210,
                    'paperHeightMm' => 297,
                    'labelWidthMm' => $w,
                    'labelHeightMm' => $h,
                    'columns' => 1,
                    'rows' => 1,
                    'leftMarginMm' => 10,
                    'topMarginMm' => 10,
                    'rightMarginMm' => 10,
                    'bottomMarginMm' => 10,
                    'hGapMm' => 2,
                    'vGapMm' => 2,
                    'labelColor' => '#FFFFFF',
                    'shape' => ['kind' => $shape, 'cornerRadiusMm' => $shape === 'roundrect' ? 2.0 : 0],
                ],
                'pages' => [[
                    'index' => 0,
                    'cells' => [[
                        'index' => 0,
                        'objects' => [[
                            'id' => 'tpl001',
                            'type' => 'text',
                            'zIndex' => 1,
                            'visible' => true,
                            'x' => $w * 0.1,
                            'y' => $h * 0.3,
                            'width' => $w * 0.8,
                            'height' => $h * 0.4,
                            'fill' => '#7B2840',
                            'strokeWidth' => 0,
                            'opacity' => 1,
                            'text' => $name,
                            'fontSize' => max(3.5, min(9, $h * 0.22)),
                            'fontFamily' => 'Pretendard',
                            'bold' => true,
                            'textAlign' => 'center',
                            'verticalAlign' => 'middle',
                            'backgroundTransparent' => true,
                            'textMode' => 'normal',
                        ]],
                    ]],
                ]],
                'printOffsetXMm' => 0,
                'printOffsetYMm' => 0,
            ],
        ];
        return (string) json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
