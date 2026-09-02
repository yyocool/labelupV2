<?php

/**
 * SQL 파일을 실행 (Windows CRLF / 주석 지원)
 */
function execute_sql_file(PDO $pdo, $filePath)
{
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException('SQL 파일을 읽을 수 없습니다: ' . $filePath);
    }

    // BOM 제거
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    // 줄 단위 주석 제거
    $sql = preg_replace('/--[^\r\n]*/', '', $sql);
    // 줄바꿈 통일
    $sql = str_replace(array("\r\n", "\r"), "\n", $sql);

    try {
        $pdo->exec("SET sql_mode = 'NO_ENGINE_SUBSTITUTION'");
    } catch (Exception $e) {
        // ignore
    }

    $statements = explode(';', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        $pdo->exec($statement);
    }
}

/**
 * 설치 가능 여부 확인
 */
function schema_tables_exist(PDO $pdo)
{
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'users'");
        return $result && $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}
