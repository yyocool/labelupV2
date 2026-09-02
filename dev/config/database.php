<?php

declare(strict_types=1);

$appConfig = require __DIR__ . '/app.php';
$env = $appConfig['environment'] ?? 'local';
$configFile = __DIR__ . '/database.' . $env . '.php';

if (!is_readable($configFile)) {
    $configFile = __DIR__ . '/database.local.php';
}

if (!is_readable($configFile)) {
    throw new RuntimeException('DB 설정 파일을 찾을 수 없습니다.');
}

return require $configFile;
