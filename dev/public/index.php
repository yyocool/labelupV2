<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$startedAt = microtime(true);
try {
    App\Services\AccessLogService::ensureVisitorCookie();
} catch (Throwable) {
}
$accessLogId = 0;
try {
    $accessLogId = (new App\Services\AccessLogService())->beginRequest();
} catch (Throwable) {
}
register_shutdown_function(static function () use ($accessLogId, $startedAt): void {
    try {
        (new App\Services\AccessLogService())->finishRequest($accessLogId, $startedAt);
    } catch (Throwable) {
    }
});

$router = App\Router::register();
$router->dispatch(request_method(), $_SERVER['REQUEST_URI'] ?? '/');
