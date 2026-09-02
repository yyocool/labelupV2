<?php
/**
 * 환경별 DB 설정 로더
 */
$appConfig = require __DIR__ . '/app.php';
$env = isset($appConfig['environment']) ? $appConfig['environment'] : 'local';

$configFile = __DIR__ . "/database.{$env}.php";
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/database.local.php';
}
if (!file_exists($configFile)) {
    throw new RuntimeException("DB 설정 파일을 찾을 수 없습니다: database.{$env}.php");
}

$config = require $configFile;

// 원격 설정이 비어 있으면 로컬 설정으로 폴백 (동일 서버 MySQL)
$password = isset($config['password']) ? $config['password'] : '';
if ($env === 'remote' && ($password === '' || $password === 'CHANGE_ME')) {
    $localFile = __DIR__ . '/database.local.php';
    if (file_exists($localFile)) {
        $local = require $localFile;
        if (!empty($local['password']) && $local['password'] !== 'CHANGE_ME') {
            $config = $local;
        }
    }
}

return $config;
