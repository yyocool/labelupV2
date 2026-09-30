<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\LaunchChecklistService;
use RuntimeException;

final class LaunchChecklistApiController extends BaseController
{
    private LaunchChecklistService $service;

    public function __construct()
    {
        $this->service = new LaunchChecklistService();
    }

    public function show(): never
    {
        $this->jsonSuccess($this->service->snapshot());
    }

    public function save(): never
    {
        try {
            $saved = $this->service->saveItem(request_json());
            $snap = $this->service->snapshot();
            $this->jsonSuccess([
                'item' => $saved,
                'progress' => $snap['progress'],
                'updated_at' => $snap['updated_at'],
            ], '저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function reset(): never
    {
        $body = request_json();
        $confirm = trim((string) ($body['confirm'] ?? ''));
        if ($confirm !== 'RESET') {
            $this->jsonError('확인 문구가 필요합니다. confirm 에 RESET 을 보내세요.', null, 422);
        }
        try {
            $this->service->resetAll();
            $this->jsonSuccess($this->service->snapshot(), '체크리스트를 초기화했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
