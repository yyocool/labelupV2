<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\InquiryService;
use App\Services\OpsSmsNotifier;
use RuntimeException;

final class InquiryApiController extends BaseController
{
    public function submit(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle();
        $user = $auth->user() ?? [];
        $payload = request_json();
        try {
            $id = (new InquiryService())->submit($payload + [
                'name' => $payload['name'] ?? ($user['name'] ?? ''),
                'email' => $payload['email'] ?? ($user['email'] ?? ''),
            ], $auth->id());
            OpsSmsNotifier::notify('new_inquiry', sprintf(
                '1:1 문의 #%d %s: %s',
                $id,
                mb_substr(trim((string) ($payload['name'] ?? ($user['name'] ?? ''))), 0, 10),
                mb_substr(trim((string) ($payload['subject'] ?? '')), 0, 40)
            ));
            $this->jsonSuccess(['id' => $id], '문의가 접수되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function mine(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle();
        $this->jsonSuccess(['items' => (new InquiryService())->listForUser((int) $auth->id())]);
    }

    public function answer(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);
        $payload = request_json();
        try {
            (new InquiryService())->answer((int) ($payload['id'] ?? 0), (string) ($payload['answer'] ?? ''), $auth->admin() ?? []);
            $this->jsonSuccess(null, '답변이 등록되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function note(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);
        $payload = request_json();
        try {
            (new InquiryService())->addNote(
                (int) ($payload['id'] ?? 0),
                (string) ($payload['note'] ?? ''),
                (string) ($payload['status'] ?? ''),
                $auth->admin() ?? []
            );
            $this->jsonSuccess(null, '처리 이력이 등록되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function update(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);
        $payload = request_json();
        try {
            (new InquiryService())->updateStatus(
                (int) ($payload['id'] ?? 0),
                (string) ($payload['status'] ?? ''),
                isset($payload['admin_memo']) ? (string) $payload['admin_memo'] : null,
                $auth->admin() ?? []
            );
            $this->jsonSuccess(null, '문의 상태가 변경되었습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }
}
