<?php

declare(strict_types=1);

function load_env(string $path): array
{
    $env = [];
    if (!is_readable($path)) {
        return $env;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\"'");
        $env[$key] = $value;
        if (getenv($key) === false && function_exists('putenv')) {
            @putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }

    return $env;
}

$GLOBALS['ENV'] = load_env(dirname(__DIR__, 2) . '/.env');

function env(string $key, mixed $default = null): mixed
{
    $env = $GLOBALS['ENV'] ?? [];
    return $env[$key] ?? getenv($key) ?: $default;
}
