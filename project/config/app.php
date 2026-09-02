<?php
/**
 * 애플리케이션 공통 설정
 */
return array(
    'name'        => 'Label-UP',
    'subtitle'    => 'with AI',
    'version'     => '1.0.0',
    'timezone'    => 'Asia/Seoul',
    'session_key' => 'labelup_session',

    // local | remote | docker — LABELUP_ENV 환경변수 또는 도메인 자동 판별
    'environment' => getenv('LABELUP_ENV') ? getenv('LABELUP_ENV') : (
        (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false && strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false)
            ? 'remote' : 'local'
    ),

    // 기본 관리자 (install.php 실행 시 생성)
    'default_admin' => array(
        'username' => 'admin',
        'email'    => 'admin@labelup.local',
        'password' => 'admin1234',
        'name'     => '관리자',
    ),

    // 오류 진단 시 true 또는 URL에 ?debug=1
    'debug' => false,
);
