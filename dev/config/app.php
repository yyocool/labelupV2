<?php

declare(strict_types=1);

$envFile = dirname(__DIR__) . '/.env';
$env = [];

if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\"'");
    }
}

$detectedEnv = env('APP_ENV') ?? (getenv('LABELUP_ENV') ?: null);
if (!$detectedEnv && isset($_SERVER['HTTP_HOST'])) {
    $host = strtolower((string) $_SERVER['HTTP_HOST']);
    if (str_contains($host, 'labelupdev.gagamkorea.kr')) {
        $detectedEnv = 'remote';
    } elseif (!str_contains($host, 'localhost') && !str_contains($host, '127.0.0.1')) {
        $detectedEnv = 'remote';
    } else {
        $detectedEnv = 'local';
    }
}

return [
    'name' => $env['APP_NAME'] ?? 'LabelUp',
    'subtitle' => 'with AI',
    'version' => '0.1.0',
    'environment' => $detectedEnv ?: 'local',
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN)
        || (isset($_GET['debug']) && $_GET['debug'] === '1'),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'timezone' => $env['TIMEZONE'] ?? 'Asia/Seoul',
    'session_key' => $env['SESSION_KEY'] ?? 'labelupdev_session',
    'session_lifetime' => (int) ($env['SESSION_LIFETIME'] ?? 7200),
    'locale' => 'ko',
    'supported_locales' => ['ko', 'en'],
    'paths' => [
        'root' => dirname(__DIR__),
        'storage' => dirname(__DIR__) . '/storage',
        'uploads' => dirname(__DIR__) . '/storage/uploads',
        'designs' => dirname(__DIR__) . '/storage/designs',
        'pdf' => dirname(__DIR__) . '/storage/pdf',
        'logs' => dirname(__DIR__) . '/storage/logs',
    ],
    'mail' => [
        'from' => $env['MAIL_FROM'] ?? 'noreply@labelup.kr',
    ],
    'openai' => [
        'model' => $env['OPENAI_MODEL'] ?? 'gpt-4o-mini',
        'max_tokens' => (int) ($env['OPENAI_MAX_TOKENS'] ?? 1800),
    ],
];
