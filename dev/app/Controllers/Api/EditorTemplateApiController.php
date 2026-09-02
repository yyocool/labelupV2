<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\LabelTemplateService;

final class EditorTemplateApiController extends BaseController
{
    private LabelTemplateService $templates;

    public function __construct()
    {
        $this->templates = new LabelTemplateService();
    }

    public function index(): never
    {
        $this->jsonSuccess($this->templates->publicCatalog());
    }

    public function show(string $id): never
    {
        $item = $this->templates->findByKey($id);
        if (!$item) {
            $this->jsonError('템플릿을 찾을 수 없습니다.', null, 404);
        }
        if ((int) ($item['is_active'] ?? 0) !== 1) {
            $this->jsonError('비공개 템플릿입니다.', null, 404);
        }

        $this->jsonSuccess([
            'id' => (int) $item['id'],
            'slug' => (string) $item['slug'],
            'name' => (string) $item['name'],
            'category' => (string) ($item['categoryName'] ?? $item['category']),
            'tone' => (string) $item['tone'],
            'document' => $item['document'] ?? null,
        ]);
    }
}
