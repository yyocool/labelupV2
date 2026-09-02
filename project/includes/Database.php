<?php

class Database
{
    /** @var PDO|null */
    private static $instance = null;

    /** @var string|null */
    public static $lastError = null;

    public static function getConnection()
    {
        if (self::$instance === null) {
            try {
                $config = require __DIR__ . '/../config/database.php';
                $port = isset($config['port']) ? $config['port'] : 3306;
                $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $config['host'],
                    $port,
                    $config['dbname'],
                    $charset
                );
                self::$instance = new PDO($dsn, $config['username'], $config['password'], array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ));
            } catch (Exception $e) {
                self::$lastError = $e->getMessage();
                throw $e;
            }
        }
        return self::$instance;
    }
}
