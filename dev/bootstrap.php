<?php

declare(strict_types=1);

require_once __DIR__ . '/app/Helpers/Env.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = __DIR__ . '/app/' . $relative . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/app/Helpers/functions.php';

$appConfig = require __DIR__ . '/config/app.php';
$GLOBALS['APP_CONFIG'] = $appConfig;

define('APP_ROOT', $appConfig['paths']['root']);
define('APP_DEBUG', (bool) ($appConfig['debug'] ?? false));

date_default_timezone_set($appConfig['timezone'] ?? 'Asia/Seoul');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

set_exception_handler([App\Helpers\ErrorHandler::class, 'handleException']);
set_error_handler([App\Helpers\ErrorHandler::class, 'handleError']);
register_shutdown_function([App\Helpers\ErrorHandler::class, 'handleShutdown']);

if (!headers_sent()) {
    header('Permissions-Policy: local-fonts=*', false);
}

if (session_status() === PHP_SESSION_NONE) {
    session_name($appConfig['session_key'] ?? 'labelupdev_session');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if (!$https && (str_ends_with($host, 'labelup.co.kr') || str_contains($host, 'labelup.'))) {
        // 리버스 프록시가 X-Forwarded-Proto를 안 넘기는 호스팅 대비
        $https = true;
    }
    session_set_cookie_params([
        'lifetime' => (int) ($appConfig['session_lifetime'] ?? 7200),
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

(new App\Services\AuthService())->attemptRememberLogin();

foreach (['uploads', 'designs', 'pdf', 'logs'] as $dirKey) {
    $dir = $appConfig['paths'][$dirKey] ?? null;
    if ($dir && !is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}
