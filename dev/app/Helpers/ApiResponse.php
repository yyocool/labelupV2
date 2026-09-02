<?php

declare(strict_types=1);

namespace App\Helpers;

final class ApiResponse
{
    public static function success(mixed $data = null, string $message = '', int $status = 200): never
    {
        self::send([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    public static function error(string $message, mixed $data = null, int $status = 400): never
    {
        self::send([
            'success' => false,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    private static function send(array $payload, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
