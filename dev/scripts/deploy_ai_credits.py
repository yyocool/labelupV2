#!/usr/bin/env python3
"""Deploy AI credit feature files and apply migration on remote."""
from __future__ import annotations

import os
import sys
from pathlib import Path

import paramiko

HOST = "115.71.237.145"
USER = "root"
PASSWORD = os.environ.get("LABELUP_SSH_PASSWORD", "")
REMOTE = "/home/labelupdev"
LOCAL = Path(__file__).resolve().parents[1]

if not PASSWORD:
    print("LABELUP_SSH_PASSWORD required", file=sys.stderr)
    sys.exit(1)

FILES = [
    "database/migrations/038_ai_credit_settings.sql",
    "app/Repositories/AiCreditRepository.php",
    "app/Repositories/CreditRepository.php",
    "app/Repositories/AiUsageRepository.php",
    "app/Services/AiCreditService.php",
    "app/Services/CreditService.php",
    "app/Services/AccountService.php",
    "app/Services/AdminAccessService.php",
    "app/Controllers/AiAdminController.php",
    "app/Controllers/Api/AiAdminApiController.php",
    "app/Controllers/Api/AiChatApiController.php",
    "app/Helpers/functions.php",
    "app/Router.php",
    "views/admin/partials/ai-menu.php",
    "views/admin/ai-credit-settings.php",
    "views/account/index.php",
]


def ensure(sftp, remote_dir: str) -> None:
    parts = remote_dir.strip("/").split("/")
    cur = ""
    for part in parts:
        cur += "/" + part
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)


def main() -> None:
    files: list[tuple[Path, str]] = []
    for rel in FILES:
        local = LOCAL / rel
        if not local.is_file():
            print("missing", rel)
            continue
        files.append((local, f"{REMOTE}/{rel}"))

    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(t)
    assert sftp is not None
    for local, remote in files:
        ensure(sftp, str(Path(remote).as_posix().rsplit("/", 1)[0]))
        sftp.put(str(local), remote)
        print("put", local.name)
    sftp.close()
    t.close()

    migrate_php = r'''<?php
require '/home/labelupdev/vendor/autoload.php';
// Apply migration SQL via PDO from .env
$env = file('/home/labelupdev/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$cfg = [];
foreach ($env as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $cfg[trim($k)] = trim($v, " \t\"'");
}
$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['DB_HOST'] ?? '127.0.0.1', $cfg['DB_DATABASE']),
    $cfg['DB_USERNAME'],
    $cfg['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$sql = file_get_contents('/home/labelupdev/database/migrations/038_ai_credit_settings.sql');
foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '' || str_starts_with($stmt, '--')) continue;
    try {
        $pdo->exec($stmt);
        echo "OK: " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 80) . "\n";
    } catch (Throwable $e) {
        echo "WARN: " . $e->getMessage() . "\n";
    }
}
// record migration if table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) UNIQUE, executed_at DATETIME NULL)");
    $st = $pdo->prepare('INSERT IGNORE INTO migrations (migration, executed_at) VALUES (?, NOW())');
    $st->execute(['038_ai_credit_settings.sql']);
} catch (Throwable $e) {
    echo "miglog: ".$e->getMessage()."\n";
}
echo "done\n";
'''
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASSWORD)
    sftp = ssh.open_sftp()
    with sftp.file("/tmp/apply_038.php", "w") as f:
        f.write(migrate_php)
    sftp.close()
    _, o, e = ssh.exec_command("php /tmp/apply_038.php && php -l /home/labelupdev/app/Services/AiCreditService.php && php -l /home/labelupdev/app/Controllers/Api/AiChatApiController.php")
    print(o.read().decode())
    err = e.read().decode()
    if err:
        print(err)
    ssh.exec_command("rm -f /tmp/apply_038.php")
    ssh.close()


if __name__ == "__main__":
    main()
