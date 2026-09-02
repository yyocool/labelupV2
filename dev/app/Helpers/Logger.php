<?php

declare(strict_types=1);

namespace App\Helpers;

final class Logger
{
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $logDir = app_config('paths.logs', APP_ROOT . '/storage/logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $line = sprintf(
            "[%s] %s %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );

        @file_put_contents($logDir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
