<?php
/**
 * Docker Compose 환경 DB 설정
 * docker-compose.yml 의 environment 변수를 사용합니다.
 */
return array(
    'host'     => getenv('DB_HOST') ? getenv('DB_HOST') : 'db',
    'port'     => (int) (getenv('DB_PORT') ? getenv('DB_PORT') : 3306),
    'dbname'   => getenv('DB_DATABASE') ? getenv('DB_DATABASE') : 'labelup',
    'username' => getenv('DB_USERNAME') ? getenv('DB_USERNAME') : 'labelup',
    'password' => getenv('DB_PASSWORD') ? getenv('DB_PASSWORD') : 'labelup',
    'charset'  => 'utf8mb4',
);
