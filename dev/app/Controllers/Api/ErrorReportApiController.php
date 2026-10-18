<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Middleware\AuthMiddleware;
use App\Services\AuthService;
use App\Services\ErrorReportService;
use RuntimeException;

final class ErrorReportApiController extends BaseController
{
    public function submit(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle();
        try {
            $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
            if (str_contains($contentType, 'multipart/form-data')) {
                if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                    $this->jsonError('첨부 이미지가 너무 큽니다. 한 장당 4MB, 최대 4장까지 첨부할 수 있습니다.', null, 422);
                }
                $payload = $_POST;
                $files = self::normalizeUploads($_FILES['images'] ?? null);
            } else {
                $payload = request_json();
                $files = [];
            }
            $id = (new ErrorReportService())->submit((int) $auth->id(), $payload, $files);
            $this->jsonSuccess(['id' => $id], '접수되었습니다. 확인 후 결과를 안내합니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function detail(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);
        try {
            $detail = (new ErrorReportService())->adminDetail((int) ($_GET['id'] ?? 0));
            $this->jsonSuccess($detail);
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    public function process(): never
    {
        $auth = new AuthService();
        (new AuthMiddleware($auth))->handle(true);
        $payload = request_json();
        try {
            $detail = (new ErrorReportService())->process(
                (int) ($payload['id'] ?? 0),
                $payload,
                (int) $auth->adminId()
            );
            $this->jsonSuccess($detail, '처리 결과를 저장했습니다.');
        } catch (RuntimeException $e) {
            $this->jsonError($e->getMessage(), null, 422);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private static function normalizeUploads(mixed $files): array
    {
        if (!is_array($files) || !isset($files['name'])) {
            return [];
        }
        if (!is_array($files['name'])) {
            return [$files];
        }
        $out = [];
        foreach ($files['name'] as $i => $name) {
            $out[] = [
                'name' => $name,
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];
        }
        return $out;
    }
}
