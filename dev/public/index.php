<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$router = App\Router::register();
$router->dispatch(request_method(), $_SERVER['REQUEST_URI'] ?? '/');
