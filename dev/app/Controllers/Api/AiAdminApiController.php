<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AiExamplePromptService;
use App\Services\AuthService;
use RuntimeException;

final class AiAdminApiController extends BaseController
{
    private AuthService $auth;
    private AiExamplePromptService $prompts;

    public function __construct()
    {
        $this->auth = new AuthService();
        $this->prompts = new AiExamplePromptService();
    }

    public function savePrompt(): never
    {
        $this->guard();
        try {
            $id = $this->prompts->save(request_json());
            $this->jsonSuccess(['id' => $id], '예시 프롬프트가 저장되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function deletePrompt(): never
    {
        $this->guard();
        try {
            $this->prompts->delete((int) (request_json()['id'] ?? 0));
            $this->jsonSuccess(null, '삭제되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    private function guard(): void
    {
        (new AuthMiddleware($this->auth))->handle(true);
    }
}
