<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\EditorWorkspaceService;
use RuntimeException;

final class EditorWorkspaceApiController extends BaseController
{
    private AuthService $auth;
    private EditorWorkspaceService $workspaces;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->workspaces = new EditorWorkspaceService();
    }

    public function index(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $limit = max(1, min(48, (int) ($_GET['limit'] ?? 24)));
        $this->jsonSuccess([
            'items' => $this->workspaces->recentForUser((int) $this->auth->id(), $limit),
        ]);
    }

    public function show(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $id = (int) ($_GET['id'] ?? 0);
        $row = $this->workspaces->findForUser((int) $this->auth->id(), $id);
        if (!$row) {
            $this->jsonSuccess(null, '저장된 작업공간이 없습니다.');
        }
        $this->jsonSuccess($row);
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
        $ui = isset($data['ui']) && is_array($data['ui']) ? $data['ui'] : null;
        $preview = (string) ($data['preview'] ?? $data['preview_data_url'] ?? '');
        $id = (int) ($data['id'] ?? 0);

        try {
            $saved = $this->workspaces->save((int) $this->auth->id(), $id, $title, $document, $ui, $preview);
            $this->jsonSuccess($saved, '작업 내역이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 500);
        }
    }
}
