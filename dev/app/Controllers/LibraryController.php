<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\EditorWorkspaceService;
use App\Services\EventPopupService;
use App\Services\ShopService;
use App\Services\UserAiClipartService;

final class LibraryController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function projects(): void
    {
        $this->page('projects', '프로젝트 — 라벨업', 'library/projects');
    }

    public function locker(): void
    {
        $this->page('locker', '내 보관함 — 라벨업', 'library/locker');
    }

    public function trash(): void
    {
        $this->page('trash', '휴지통 — 라벨업', 'library/trash');
    }

    private function page(string $section, string $title, string $template): void
    {
        (new AuthMiddleware($this->auth))->handle();
        $userId = (int) $this->auth->id();
        $user = $this->auth->user();
        $workspaces = new EditorWorkspaceService();
        $cliparts = new UserAiClipartService();

        $data = [
            'pageTitle' => $title,
            'contentTemplate' => $template,
            'authUser' => $user,
            'cartCount' => (new ShopService())->cartCount(),
            'activeNav' => 'account',
            'librarySection' => $section,
            'eventPopups' => (new EventPopupService())->activeForSite(),
            'projects' => [],
            'cliparts' => [],
            'trashItems' => [],
        ];

        if ($section === 'projects') {
            $data['projects'] = $workspaces->recentForUser($userId, 120);
        } elseif ($section === 'locker') {
            $data['cliparts'] = $cliparts->listForUser($userId, 120);
        } else {
            $designs = $workspaces->recentForUser($userId, 120, true);
            $arts = $cliparts->listTrashedForUser($userId, 120);
            $data['trashItems'] = $this->mergeTrash($designs, $arts);
        }

        view('account/layout', $data);
    }

    /**
     * @param array<int, array<string, mixed>> $designs
     * @param array<int, array<string, mixed>> $arts
     * @return array<int, array<string, mixed>>
     */
    private function mergeTrash(array $designs, array $arts): array
    {
        $items = [];
        foreach ($designs as $row) {
            $items[] = $row + ['kind_label' => '프로젝트'];
        }
        foreach ($arts as $row) {
            $items[] = $row + [
                'title' => (string) ($row['title'] ?? '클립아트'),
                'kind_label' => '보관함',
            ];
        }
        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($b['trashed_at'] ?? ''), (string) ($a['trashed_at'] ?? ''));
        });
        return $items;
    }
}
