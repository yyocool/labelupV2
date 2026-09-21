<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ShopRepository;

/**
 * 편집기 "용지선택"용 카탈로그.
 * 썸네일을 절대 URL로 바꿔 다른 출처(로컬 빌드 편집기)에서도 이미지가 그대로 보이게 한다.
 */
final class EditorPaperCatalogService
{
    private ShopRepository $repo;

    public function __construct(?ShopRepository $repo = null)
    {
        $this->repo = $repo ?? new ShopRepository();
    }

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     categories: list<array{id:int,name:string}>,
     *     total: int,
     *     generatedAt: string
     * }
     */
    public function catalog(): array
    {
        $catalog = $this->repo->editorPapers();

        $items = [];
        foreach ($catalog['items'] ?? [] as $item) {
            $thumbnail = trim((string) ($item['thumbnailUrl'] ?? ''));
            $item['thumbnailUrl'] = $thumbnail === '' ? '' : absolute_url($thumbnail);
            $items[] = $item;
        }

        return [
            'items' => $items,
            'categories' => array_values($catalog['categories'] ?? []),
            'total' => count($items),
            'generatedAt' => date('c'),
        ];
    }
}
