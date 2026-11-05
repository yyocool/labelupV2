<?php

declare(strict_types=1);

/*
 * 서비스 DB 일일 백업.
 *
 * 크론은 한국시간 03:20. 서버 시계가 CST(UTC+8)이면 02:20 에 등록한다.
 *   php {앱루트}/bin/backup_db.php >> {앱루트}/storage/backups/db/cron.log
 *
 * 접속 정보는 앱 루트의 .env (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
 * 보관 일수는 DB_BACKUP_KEEP_DAYS (기본 14, 1~90).
 * 결과: storage/backups/db/{db}_YYYYMMDD_HHMMSS.sql.gz
 *       storage/backups/db/latest.json
 *
 * 복구 예:
 *   gzip -dc storage/backups/db/파일.sql.gz | mysql -h DB호스트 -u DB사용자 -p DB이름
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const BACKUP_KEEP_DEFAULT = 14;
const BACKUP_KEEP_MAX = 90;

if (($argv[1] ?? '') === '--notify-fail') {
    backup_notify_fail((string) ($argv[2] ?? 'DB 백업 실패'));
    exit(0);
}

date_default_timezone_set('Asia/Seoul');

$root = dirname(__DIR__);
$backupDir = $root . '/storage/backups/db';
$started = date('Y-m-d H:i:s');
$startedTs = microtime(true);

try {
    if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
        throw new RuntimeException('백업 폴더를 만들지 못했습니다.');
    }
    chmod($backupDir, 0700);
    $htaccess = $backupDir . '/.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents($htaccess, "Require all denied\nDeny from all\n");
    }

    $lockFp = fopen($backupDir . '/.lock', 'c');
    if ($lockFp === false || !flock($lockFp, LOCK_EX | LOCK_NB)) {
        echo "SKIP already running\n";
        exit(0);
    }

    $env = backup_read_env($root . '/.env');
    $host = $env['DB_HOST'] ?? '';
    $port = (int) ($env['DB_PORT'] ?? 3306);
    $database = $env['DB_DATABASE'] ?? '';
    $username = $env['DB_USERNAME'] ?? '';
    $password = $env['DB_PASSWORD'] ?? '';
    $keepDays = (int) ($env['DB_BACKUP_KEEP_DAYS'] ?? BACKUP_KEEP_DEFAULT);
    if ($keepDays < 1) {
        $keepDays = BACKUP_KEEP_DEFAULT;
    }
    if ($keepDays > BACKUP_KEEP_MAX) {
        $keepDays = BACKUP_KEEP_MAX;
    }
    if ($host === '' || $database === '' || $username === '' || $password === '') {
        throw new RuntimeException('.env 의 DB 접속 정보가 비어 있습니다.');
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
        throw new RuntimeException('DB 이름이 백업에 적합하지 않습니다.');
    }

    $stamp = date('Ymd_His');
    $filename = $database . '_' . $stamp . '.sql.gz';
    $finalPath = $backupDir . '/' . $filename;
    $partialPath = $backupDir . '/.' . $filename . '.partial';
    $engine = 'php';

    $mysqldump = backup_function_allowed('proc_open') ? backup_find_mysqldump() : null;
    $dumped = false;
    $dumpError = '';
    if ($mysqldump !== null) {
        $dump = backup_mysqldump($mysqldump, $host, $port, $username, $password, $database, $partialPath, $backupDir);
        if ($dump['ok']) {
            $dumped = true;
            $engine = 'mysqldump';
        } else {
            $dumpError = $dump['error'];
            @unlink($partialPath);
            if (empty($dump['fallback'])) {
                throw new RuntimeException($dumpError !== '' ? $dumpError : 'mysqldump 실패');
            }
        }
    }

    if (!$dumped) {
        backup_php_dump($host, $port, $username, $password, $database, $partialPath);
        $engine = 'php';
    }

    if (!backup_gzip_valid($partialPath)) {
        throw new RuntimeException('백업 파일이 손상되었거나 테이블이 없습니다.' . ($dumpError !== '' ? ' ' . $dumpError : ''));
    }

    chmod($partialPath, 0600);
    if (!rename($partialPath, $finalPath)) {
        throw new RuntimeException('백업 파일을 확정하지 못했습니다.');
    }
    chmod($finalPath, 0600);

    $removed = backup_rotate($backupDir, $database, $keepDays, $finalPath);
    $bytes = (int) filesize($finalPath);
    $finished = date('Y-m-d H:i:s');
    $elapsed = round(microtime(true) - $startedTs, 1);
    backup_write_status($backupDir, [
        'ok' => true,
        'engine' => $engine,
        'file' => $filename,
        'bytes' => $bytes,
        'started_at' => $started,
        'finished_at' => $finished,
        'elapsed_sec' => $elapsed,
        'keep_days' => $keepDays,
        'removed' => $removed,
        'error' => '',
    ]);
    backup_log($backupDir, sprintf(
        '%s OK %s %d bytes %.1fs %s removed=%d',
        $finished,
        $filename,
        $bytes,
        $elapsed,
        $engine,
        $removed
    ));
    echo "OK {$filename} {$bytes} {$engine}\n";
    exit(0);
} catch (Throwable $e) {
    $message = backup_public_error($e->getMessage());
    @unlink($partialPath ?? '');
    if (isset($backupDir) && is_dir($backupDir)) {
        backup_write_status($backupDir, [
            'ok' => false,
            'engine' => '',
            'file' => '',
            'bytes' => 0,
            'started_at' => $started,
            'finished_at' => date('Y-m-d H:i:s'),
            'elapsed_sec' => round(microtime(true) - $startedTs, 1),
            'keep_days' => $keepDays ?? BACKUP_KEEP_DEFAULT,
            'removed' => 0,
            'error' => $message,
        ]);
        backup_log($backupDir, date('Y-m-d H:i:s') . ' FAIL ' . $message);
        backup_spawn_notify($message);
    }
    fwrite(STDERR, "FAIL {$message}\n");
    exit(1);
}

function backup_notify_fail(string $message): void
{
    $root = dirname(__DIR__);
    $logDir = $root . '/storage/logs';
    if (is_dir($logDir)) {
        ini_set('session.save_path', $logDir);
    }
    try {
        require $root . '/bootstrap.php';
        App\Services\OpsSmsNotifier::notify('db_backup_failed', 'DB 일일 백업 실패: ' . backup_public_error($message), date('Y-m-d'));
    } catch (Throwable $e) {
        fwrite(STDERR, 'sms skip: ' . backup_public_error($e->getMessage()) . PHP_EOL);
    }
}

function backup_function_allowed(string $name): bool
{
    if (!function_exists($name)) {
        return false;
    }
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

    return !in_array($name, $disabled, true);
}

function backup_spawn_notify(string $message): void
{
    if (!backup_function_allowed('proc_open')) {
        try {
            backup_notify_fail($message);
        } catch (Throwable $e) {
            fwrite(STDERR, 'sms skip: ' . backup_public_error($e->getMessage()) . PHP_EOL);
        }

        return;
    }
    $php = PHP_BINARY;
    if ($php === '' || !is_file($php)) {
        return;
    }
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', 'php://temp', 'w'],
        2 => ['file', 'php://temp', 'w'],
    ];
    $proc = @proc_open([$php, __FILE__, '--notify-fail', $message], $descriptors, $pipes);
    if (!is_resource($proc)) {
        return;
    }
    fclose($pipes[0]);
    proc_close($proc);
}

/** @return array<string, string> */
function backup_read_env(string $path): array
{
    if (!is_readable($path)) {
        throw new RuntimeException('.env 파일을 읽지 못했습니다.');
    }
    $env = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('.env 파일을 읽지 못했습니다.');
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\"'");
    }

    return $env;
}

function backup_find_mysqldump(): ?string
{
    foreach (['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/usr/bin/mariadb-dump'] as $path) {
        if (is_executable($path)) {
            return $path;
        }
    }
    $out = [];
    $code = 1;
    @exec('command -v mysqldump 2>/dev/null', $out, $code);
    if ($code === 0 && isset($out[0]) && is_string($out[0]) && is_executable($out[0])) {
        return $out[0];
    }
    $out = [];
    $code = 1;
    @exec('command -v mariadb-dump 2>/dev/null', $out, $code);
    if ($code === 0 && isset($out[0]) && is_string($out[0]) && is_executable($out[0])) {
        return $out[0];
    }

    return null;
}

/**
 * @return array{ok: bool, error: string, fallback: bool}
 */
function backup_mysqldump(string $bin, string $host, int $port, string $username, string $password, string $database, string $partialPath, string $backupDir): array
{
    $cnfPath = $backupDir . '/.client-' . getmypid() . '.cnf';
    $cnf = "[client]\n"
        . 'host=' . backup_ini_quote($host) . "\n"
        . 'port=' . $port . "\n"
        . 'user=' . backup_ini_quote($username) . "\n"
        . 'password=' . backup_ini_quote($password) . "\n";
    if (file_put_contents($cnfPath, $cnf) === false) {
        return ['ok' => false, 'error' => 'DB 접속 파일을 쓰지 못했습니다.', 'fallback' => true];
    }
    chmod($cnfPath, 0600);

    $optionSets = [
        ['--single-transaction', '--quick', '--default-character-set=utf8mb4', '--hex-blob', '--no-tablespaces', '--skip-lock-tables', '--column-statistics=0'],
        ['--single-transaction', '--quick', '--default-character-set=utf8mb4', '--hex-blob', '--no-tablespaces', '--skip-lock-tables'],
        ['--single-transaction', '--quick', '--default-character-set=utf8mb4', '--skip-lock-tables'],
    ];
    $lastError = 'mysqldump 실패';
    try {
        foreach ($optionSets as $opts) {
            @unlink($partialPath);
            $gz = gzopen($partialPath, 'wb9');
            if ($gz === false) {
                return ['ok' => false, 'error' => '백업 파일을 열지 못했습니다.', 'fallback' => false];
            }
            gzwrite($gz, backup_header($database));
            $errFile = $backupDir . '/.dump-' . getmypid() . '.err';
            $cmd = array_merge([$bin, '--defaults-extra-file=' . $cnfPath], $opts, [$database]);
            $proc = proc_open($cmd, [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['file', $errFile, 'w'],
            ], $pipes);
            if (!is_resource($proc)) {
                gzclose($gz);
                @unlink($partialPath);
                @unlink($errFile);
                return ['ok' => false, 'error' => 'mysqldump를 시작하지 못했습니다.', 'fallback' => true];
            }
            fclose($pipes[0]);
            $bytes = 0;
            while (true) {
                $chunk = fread($pipes[1], 262144);
                if ($chunk === false) {
                    break;
                }
                if ($chunk === '') {
                    if (feof($pipes[1])) {
                        break;
                    }
                    continue;
                }
                $bytes += strlen($chunk);
                if (gzwrite($gz, $chunk) === false) {
                    fclose($pipes[1]);
                    proc_close($proc);
                    gzclose($gz);
                    @unlink($partialPath);
                    @unlink($errFile);
                    return ['ok' => false, 'error' => '백업 파일을 쓰지 못했습니다. 디스크 용량을 확인해 주세요.', 'fallback' => false];
                }
            }
            fclose($pipes[1]);
            $code = proc_close($proc);
            $err = trim((string) @file_get_contents($errFile));
            @unlink($errFile);
            if ($code === 0 && $bytes > 0) {
                gzwrite($gz, "\n-- Backup completed\n");
                gzclose($gz);

                return ['ok' => true, 'error' => '', 'fallback' => false];
            }
            gzclose($gz);
            @unlink($partialPath);
            $lastError = $err !== '' ? $err : ('mysqldump exit ' . $code);
            $optionError = stripos($lastError, 'unknown option') !== false
                || stripos($lastError, 'unknown variable') !== false
                || stripos($lastError, 'ambiguous option') !== false;
            if (!$optionError) {
                return ['ok' => false, 'error' => backup_public_error($lastError), 'fallback' => false];
            }
        }
    } finally {
        @unlink($cnfPath);
    }

    return ['ok' => false, 'error' => backup_public_error($lastError), 'fallback' => true];
}

function backup_php_dump(string $host, int $port, string $username, string $password, string $database, string $partialPath): void
{
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('SET NAMES utf8mb4');

    $gz = gzopen($partialPath, 'wb9');
    if ($gz === false) {
        throw new RuntimeException('백업 파일을 열지 못했습니다.');
    }
    $write = static function (string $sql) use ($gz): void {
        if (gzwrite($gz, $sql) === false) {
            throw new RuntimeException('백업 파일을 쓰지 못했습니다. 디스크 용량을 확인해 주세요.');
        }
    };
    try {
        $write(backup_header($database));
        $listed = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
        $bases = [];
        $views = [];
        foreach ($listed as $row) {
            $name = (string) ($row[0] ?? '');
            if ($name === '') {
                continue;
            }
            $type = strtoupper((string) ($row[1] ?? 'BASE TABLE'));
            if ($type === 'VIEW') {
                $views[] = $name;
            } else {
                $bases[] = $name;
            }
        }
        foreach ($bases as $name) {
            $ident = backup_ident($name);
            $create = $pdo->query('SHOW CREATE TABLE ' . $ident)->fetch(PDO::FETCH_ASSOC);
            $ddl = backup_strip_definer((string) ($create['Create Table'] ?? ''));
            if ($ddl === '') {
                throw new RuntimeException('테이블 구조를 읽지 못했습니다: ' . $name);
            }
            $write("\nDROP TABLE IF EXISTS {$ident};\n{$ddl};\n");
            backup_dump_rows($pdo, $ident, $write);
        }
        foreach ($views as $name) {
            $ident = backup_ident($name);
            $create = $pdo->query('SHOW CREATE VIEW ' . $ident)->fetch(PDO::FETCH_ASSOC);
            $ddl = backup_strip_definer((string) ($create['Create View'] ?? ''));
            if ($ddl === '') {
                continue;
            }
            $write("\nDROP VIEW IF EXISTS {$ident};\n{$ddl};\n");
        }
        $write("\nSET FOREIGN_KEY_CHECKS=1;\n-- Backup completed\n");
    } finally {
        gzclose($gz);
    }
}

function backup_dump_rows(PDO $pdo, string $ident, callable $write): void
{
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    try {
        $stmt = $pdo->query('SELECT * FROM ' . $ident);
        if ($stmt === false) {
            return;
        }
        $cols = [];
        $batch = [];
        $batchBytes = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === []) {
                $cols = array_keys($row);
            }
            $vals = [];
            foreach ($row as $value) {
                $vals[] = backup_literal($pdo, $value);
            }
            $tuple = '(' . implode(',', $vals) . ')';
            $batch[] = $tuple;
            $batchBytes += strlen($tuple);
            if (count($batch) >= 80 || $batchBytes >= 524288) {
                backup_flush_insert($write, $ident, $cols, $batch);
                $batch = [];
                $batchBytes = 0;
            }
        }
        if ($batch !== []) {
            backup_flush_insert($write, $ident, $cols, $batch);
        }
        $stmt->closeCursor();
    } finally {
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }
}

/** @param array<int, string> $cols @param array<int, string> $batch */
function backup_flush_insert(callable $write, string $ident, array $cols, array $batch): void
{
    if ($cols === [] || $batch === []) {
        return;
    }
    $colSql = implode(',', array_map('backup_ident', $cols));
    $write('INSERT INTO ' . $ident . ' (' . $colSql . ") VALUES\n" . implode(",\n", $batch) . ";\n");
}

function backup_literal(PDO $pdo, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }
    $text = (string) $value;
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $text) === 1) {
        return '0x' . bin2hex($text);
    }
    $quoted = $pdo->quote($text);

    return $quoted === false ? '0x' . bin2hex($text) : $quoted;
}

function backup_header(string $database): string
{
    $ident = backup_ident($database);

    return "-- LabelUp DB backup\n"
        . '-- created_at: ' . date('Y-m-d H:i:s') . " Asia/Seoul\n"
        . '-- database: ' . $database . "\n"
        . "SET NAMES utf8mb4;\n"
        . "SET FOREIGN_KEY_CHECKS=0;\n"
        . "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n"
        . "USE {$ident};\n";
}

function backup_ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function backup_strip_definer(string $ddl): string
{
    $stripped = preg_replace('/DEFINER=`[^`]*`@`[^`]*`\s*/', '', $ddl);

    return $stripped === null ? $ddl : $stripped;
}

function backup_ini_quote(string $value): string
{
    return '"' . str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', '', ''], $value) . '"';
}

function backup_gzip_valid(string $path): bool
{
    if (!is_file($path) || filesize($path) < 64) {
        return false;
    }
    $gz = gzopen($path, 'rb');
    if ($gz === false) {
        return false;
    }
    $tail = '';
    $carry = '';
    $sawTable = false;
    while (!gzeof($gz)) {
        $chunk = gzread($gz, 65536);
        if (!is_string($chunk) || $chunk === '') {
            break;
        }
        $chunk = $carry . $chunk;
        if (!$sawTable && str_contains($chunk, 'CREATE TABLE')) {
            $sawTable = true;
        }
        $carry = substr($chunk, -32);
        $tail = substr($chunk, -400);
    }
    $err = gzerror($gz);
    gzclose($gz);
    $errNo = is_array($err) ? (int) ($err[0] ?? 0) : 0;

    return $sawTable && str_contains($tail, '-- Backup completed') && $errNo === 0;
}

function backup_rotate(string $dir, string $database, int $keepDays, string $keepPath): int
{
    $cutoff = time() - ($keepDays * 86400);
    $removed = 0;
    $pattern = $dir . '/' . $database . '_*.sql.gz';
    $files = glob($pattern);
    if ($files === false) {
        return 0;
    }
    foreach ($files as $file) {
        if ($file === $keepPath || !is_file($file)) {
            continue;
        }
        $mtime = filemtime($file);
        if ($mtime !== false && $mtime < $cutoff) {
            if (@unlink($file)) {
                $removed++;
            }
        }
    }

    return $removed;
}

/** @param array<string, mixed> $status */
function backup_write_status(string $dir, array $status): void
{
    $json = json_encode($status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        return;
    }
    $path = $dir . '/latest.json';
    file_put_contents($path, $json . "\n");
    @chmod($path, 0600);
}

function backup_log(string $dir, string $line): void
{
    $path = $dir . '/backup.log';
    file_put_contents($path, $line . "\n", FILE_APPEND);
    @chmod($path, 0600);
    if (is_file($path) && filesize($path) > 524288) {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (is_array($lines)) {
            file_put_contents($path, implode("\n", array_slice($lines, -200)) . "\n");
        }
    }
}

function backup_public_error(string $message): string
{
    $message = preg_replace('/password[=:][^\s]+/i', 'password=***', $message) ?? $message;
    $message = str_replace(["\r", "\n"], ' ', $message);
    if (strlen($message) > 400) {
        return substr($message, 0, 400) . '…';
    }

    return $message;
}
