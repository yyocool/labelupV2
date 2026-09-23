<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\EditorWorkspaceService;
use App\Services\UserAiClipartService;
use RuntimeException;

final class LibraryApiController extends BaseController
{
    private AuthService $auth;
    private EditorWorkspaceService $workspaces;
    private UserAiClipartService $cliparts;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->workspaces = new EditorWorkspaceService();
        $this->cliparts = new UserAiClipartService();
    }

    public function trash(): never
    {
        $this->run('trash');
    }

    public function restore(): never
    {
        $this->run('restore');
    }

    public function purge(): never
    {
        $this->run('purge');
    }

    private function run(string $action): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $data = request_json();
        $type = trim((string) ($data['type'] ?? request_input('type', '')));
        $id = (int) ($data['id'] ?? request_input('id', 0));
        if ($id <= 0 || !in_array($type, ['workspace', 'clipart'], true)) {
            $this->jsonError('삭제할 항목이 올바르지 않습니다.', null, 422);
        }

        $userId = (int) $this->auth->id();
        try {
            if ($type === 'workspace') {
                match ($action) {
                    'trash' => $this->workspaces->trashForUser($userId, $id),
                    'restore' => $this->workspaces->restoreForUser($userId, $id),
                    default => $this->workspaces->purgeForUser($userId, $id),
                };
            } else {
                match ($action) {
                    'trash' => $this->cliparts->trashForUser($userId, $id),
                    'restore' => $this->cliparts->restoreForUser($userId, $id),
                    default => $this->cliparts->purgeForUser($userId, $id),
                };
            }
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 404);
        }

        $messages = [
            'trash' => '휴지통으로 옮겼습니다.',
            'restore' => '원래 위치로 복원했습니다.',
            'purge' => '완전히 삭제했습니다.',
        ];
        $this->jsonSuccess(['id' => $id, 'type' => $type, 'action' => $action], $messages[$action]);
    }
}
