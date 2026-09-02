<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Repositories\EditorWorkspaceRepository;
use App\Services\AuthService;

final class EditorWorkspaceApiController extends BaseController
{
    private AuthService $auth;
    private EditorWorkspaceRepository $repo;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->repo = new EditorWorkspaceRepository();
    }

    public function show(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $row = $this->repo->findByUserId($this->auth->id());
        if (!$row) {
            $this->jsonSuccess(null, '저장된 작업공간이 없습니다.');
        }

        $doc = json_decode((string) ($row['document_json'] ?? ''), true);
        $ui = json_decode((string) ($row['ui_json'] ?? ''), true);
        $this->jsonSuccess([
            'title' => (string) ($row['title'] ?? ''),
            'document' => is_array($doc) ? $doc : null,
            'ui' => is_array($ui) ? $ui : null,
            'updated_at' => $row['updated_at'] ?? null,
        ]);
    }

    public function save(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $data = request_json();

        $document = $data['document'] ?? null;
        if (!is_array($document)) {
            $this->jsonError('문서 데이터가 필요합니다.', null, 422);
        }

        $title = trim((string) ($data['title'] ?? ($document['name'] ?? '새 라벨 디자인')));
        if ($title === '') {
            $title = '새 라벨 디자인';
        }
        $title = mb_substr($title, 0, 200);

        $ui = $data['ui'] ?? null;
        $docJson = json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($docJson === false) {
            $this->jsonError('문서 JSON 직렬화에 실패했습니다.', null, 500);
        }
        $uiJson = null;
        if (is_array($ui)) {
            $encoded = json_encode($ui, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $uiJson = $encoded === false ? null : $encoded;
        }

        $this->repo->upsert($this->auth->id(), $title, $docJson, $uiJson);
        $this->jsonSuccess([
            'title' => $title,
            'updated_at' => date('Y-m-d H:i:s'),
        ], '작업 내역이 저장되었습니다.');
    }
}
