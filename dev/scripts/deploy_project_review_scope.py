#!/usr/bin/env python3
"""Deploy project review-scope files to /home/labelup/project"""
import io
import os
import sys
from pathlib import Path

import paramiko

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelup/project'
LOCAL_ROOT = Path(__file__).resolve().parents[2] / 'project'

FILES = [
    'review-scope.php',
    'views/review-scope.php',
    'views/review-scope-print.php',
    'includes/DevScopeService.php',
    'includes/migrate.php',
]


def ensure_dir(sftp, remote_dir):
    parts = remote_dir.strip('/').split('/')
    cur = ''
    for part in parts:
        cur += '/' + part
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)


def run(ssh, cmd):
    print('$', cmd[:160])
    _, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out.strip():
        print(out.strip())
    if err.strip():
        print('ERR:', err.strip()[:800])
    return out


def main():
    if not PASSWORD:
        print('LABELUP_SSH_PASSWORD missing', file=sys.stderr)
        sys.exit(1)

    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(t)
    ssh = paramiko.SSHClient()
    ssh._transport = t

    uploaded = []
    for rel in FILES:
        local = LOCAL_ROOT / rel.replace('/', os.sep)
        if not local.is_file():
            print('MISSING', rel)
            continue
        remote = REMOTE_ROOT + '/' + rel
        ensure_dir(sftp, os.path.dirname(remote).replace('\\', '/'))
        sftp.put(str(local), remote)
        uploaded.append(rel)
        print('PUT', rel, local.stat().st_size)

    run(ssh, f'chown -R www:www {REMOTE_ROOT}/review-scope.php {REMOTE_ROOT}/views/review-scope.php {REMOTE_ROOT}/views/review-scope-print.php 2>/dev/null || true')
    run(ssh, f'php -l {REMOTE_ROOT}/review-scope.php; php -l {REMOTE_ROOT}/includes/DevScopeService.php; php -l {REMOTE_ROOT}/includes/migrate.php; php -l {REMOTE_ROOT}/includes/auth.php')

    migrate_php = """<?php
require '/home/labelup/project/includes/bootstrap.php';
$db = Database::getConnection();
$review = $db->query(\"SHOW COLUMNS FROM dev_scope_items LIKE 'review_confirmed'\")->fetch();
$client = $db->query(\"SHOW COLUMNS FROM dev_scope_items LIKE 'client_confirmed'\")->fetch();
echo json_encode(array(
  'ok' => true,
  'review_confirmed' => (bool) $review,
  'client_confirmed' => (bool) $client,
), JSON_UNESCAPED_UNICODE);
"""
    sftp.putfo(io.BytesIO(migrate_php.encode('utf-8')), '/tmp/check_review_cols.php')
    run(ssh, 'php /tmp/check_review_cols.php')
    run(ssh, 'rm -f /tmp/check_review_cols.php')

    print('UPLOADED', len(uploaded), 'files')
    t.close()


if __name__ == '__main__':
    main()
