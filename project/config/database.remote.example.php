<?php
/**
 * 원격(스테이징/운영) DB 설정 예시
 * 이 파일을 복사:  cp database.remote.example.php database.remote.php
 * database.remote.php 는 Git에 올리지 않습니다.
 */
return array(
    'host'     => 'localhost',
    'port'     => 3306,
    'dbname'   => 'labelup',
    'username' => 'labelup',
    'password' => 'YOUR_PASSWORD_HERE',
    'charset'  => 'utf8mb4',
);
