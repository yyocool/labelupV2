<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\NotificationService;
use RuntimeException;

final class NotificationApiController extends BaseController
{
    private AuthService $auth;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->notifications = new NotificationService();
    }

    public function index(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $limit = max(1, min(50, (int) ($_GET['limit'] ?? 30)));
        $this->jsonSuccess($this->notifications->list((int) $this->auth->id(), $limit));
    }

    public function unread(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $this->jsonSuccess([
            'unread' => $this->notifications->unreadCount((int) $this->auth->id()),
        ]);
    }

    public function markRead(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $data = request_json();
        $id = isset($data['id']) ? (int) $data['id'] : null;
        if ($id !== null && $id <= 0) {
            $id = null;
        }
        $changed = $this->notifications->markRead((int) $this->auth->id(), $id);
        $this->jsonSuccess([
            'changed' => $changed,
            'unread' => $this->notifications->unreadCount((int) $this->auth->id()),
        ], $id ? '알림을 읽음 처리했습니다.' : '모든 알림을 읽음 처리했습니다.');
    }

    public function prefs(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        $this->jsonSuccess($this->notifications->prefs((int) $this->auth->id()));
    }

    public function savePrefs(): never
    {
        (new AuthMiddleware($this->auth))->handle();
        try {
            $prefs = $this->notifications->savePrefs((int) $this->auth->id(), request_json());
            $this->jsonSuccess($prefs, '알림 설정을 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
