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
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

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
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
    $host = request_host();
    if (!$https && is_labelup_public_host($host)) {
        // 리버스 프록시가 X-Forwarded-Proto를 안 넘기는 호스팅 대비.
        // labelup.co.kr / www 만 Secure 쿠키를 강제한다.
        $https = true;
    }
    $cookieDomain = site_cookie_domain();
    // 예전 host-only 쿠키(labelup_session)와 이름이 같으면 www/apex 중 하나만 읽힌다.
    session_name($cookieDomain !== '' ? 'labelup_site' : ($appConfig['session_key'] ?? 'labelupdev_session'));
    $cookieParams = [
        'lifetime' => (int) ($appConfig['session_lifetime'] ?? 7200),
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if ($cookieDomain !== '') {
        $cookieParams['domain'] = $cookieDomain;
    }
    session_set_cookie_params($cookieParams);
    session_start();
}

(new App\Services\AuthService())->attemptRememberLogin();

foreach (['uploads', 'designs', 'pdf', 'logs'] as $dirKey) {
    $dir = $appConfig['paths'][$dirKey] ?? null;
    if ($dir && !is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}
