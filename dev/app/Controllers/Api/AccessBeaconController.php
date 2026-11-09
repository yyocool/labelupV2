<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Services\AccessLogService;

final class AccessBeaconController
{
    public function store(): never
    {
        if (!$this->sameOrigin()) {
            ApiResponse::error('origin', null, 403);
        }
        (new AccessLogService())->beacon(request_json());
        ApiResponse::success(['ok' => true]);
    }

    private function sameOrigin(): bool
    {
        $host = request_host();
        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
        if ($origin !== '') {
            $name = parse_url($origin, PHP_URL_HOST);
            return is_string($name) && strtolower($name) === $host;
        }
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $name = parse_url($referer, PHP_URL_HOST);
            return is_string($name) && strtolower($name) === $host;
        }

        return true;
    }
}
