<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ClipartService;
use App\Services\LabelTemplateService;
use App\Services\UserAiClipartService;
use RuntimeException;

final class ContentAdminApiController extends BaseController
{
    private AuthService $auth;
    private ClipartService $cliparts;
    private LabelTemplateService $templates;
    private UserAiClipartService $userDesigns;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->cliparts = new ClipartService();
        $this->templates = new LabelTemplateService();
        $this->userDesigns = new UserAiClipartService();
    }

    public function saveClipart(): never
    {
        $this->guard();
        try {
            $data = request_json();
            $id = $this->cliparts->save($data);
            $this->jsonSuccess(['id' => $id, 'item' => $this->cliparts->find($id)], '클립아트가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function deleteClipart(): never
    {
        $this->guard();
        try {
            $this->cliparts->delete((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(null, '삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function uploadClipart(): never
    {
        $this->guard();
        try {
            if (empty($_FILES['images'])) {
                throw new RuntimeException('업로드할 이미지가 없습니다.');
            }
            $paths = $this->cliparts->storeUploadedImages($_FILES['images']);
            $urls = array_map(
                static fn (string $p): string => ClipartService::resolveUrl($p),
                $paths
            );
            $this->jsonSuccess(['paths' => $paths, 'urls' => $urls], '이미지가 업로드되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function saveCategory(): never
    {
        $this->guard();
        try {
            $id = $this->cliparts->saveCategory(request_json());
            $this->jsonSuccess(['id' => $id], '카테고리가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function seedCliparts(): never
    {
        $this->guard();
        try {
            $this->cliparts->ensureDefaultCategories();
            $manifestPath = storage_path('imports/clipart_seed_manifest.json');
            if (!is_file($manifestPath)) {
                throw new RuntimeException('시드 매니페스트가 없습니다. 먼저 생성 스크립트를 실행해 주세요.');
            }
            $raw = file_get_contents($manifestPath);
            $data = json_decode((string) $raw, true);
            if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
                throw new RuntimeException('시드 매니페스트 형식이 올바르지 않습니다.');
            }
            $result = $this->cliparts->importSeedItems($data['items']);
            $this->jsonSuccess($result + ['total' => $this->cliparts->count()], '클립아트 시드가 반영되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function saveTemplate(): never
    {
        $this->guard();
        try {
            $id = $this->templates->save(request_json());
            $this->jsonSuccess(['id' => $id, 'item' => $this->templates->find($id)], '템플릿이 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function deleteTemplate(): never
    {
        $this->guard();
        try {
            $this->templates->delete((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(null, '삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function reviewUserDesign(): never
    {
        $this->guard();
        try {
            $data = request_json();
            $item = $this->userDesigns->review(
                (int) ($data['id'] ?? 0),
                (string) ($data['action'] ?? ''),
                isset($data['note']) ? (string) $data['note'] : null,
                $this->auth->adminId()
            );
            $label = ($data['action'] ?? '') === 'reject' ? '반려했습니다.' : '승인했습니다.';
            $this->jsonSuccess(['item' => $item], '사용자 디자인을 ' . $label);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function approveUserDesigns(): never
    {
        $this->guard();
        try {
            $ids = request_json()['ids'] ?? [];
            if (!is_array($ids)) {
                $ids = [];
            }
            $count = $this->userDesigns->approveMany($ids, $this->auth->adminId());
            if ($count <= 0) {
                throw new RuntimeException('승인할 디자인을 선택해 주세요.');
            }
            $this->jsonSuccess(['count' => $count], $count . '건을 승인했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function deleteUserDesign(): never
    {
        $this->guard();
        try {
            $this->userDesigns->delete((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(null, '사용자 디자인을 삭제했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function seedTemplates(): never
    {
        $this->guard();
        try {
            $force = !empty(request_json()['force']);
            $result = $this->templates->seed($force);
            $this->jsonSuccess($result, '템플릿 시드가 반영되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
