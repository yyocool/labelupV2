<?php

declare(strict_types=1);

namespace App\Helpers;

use Throwable;

final class ErrorHandler
{
    public static function handleException(Throwable $e): void
    {
        Logger::error($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if (self::isApiRequest()) {
            ApiResponse::error(
                APP_DEBUG ? $e->getMessage() : '서버 오류가 발생했습니다.',
                null,
                500
            );
        }

        http_response_code(500);
        if (APP_DEBUG) {
            echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
            return;
        }

        $view = APP_ROOT . '/views/errors/500.php';
        if (is_readable($view)) {
            require $view;
            return;
        }

        echo 'Internal Server Error';
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        Logger::error('[FATAL] ' . $error['message'], $error);
    }

    private static function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_starts_with($uri, '/api/') || str_contains($uri, '/api/');
    }
}
