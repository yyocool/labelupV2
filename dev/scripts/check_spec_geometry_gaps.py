#!/usr/bin/env python3
"""배치값(열·행)이 비어 있어 편집기가 추정으로 그리는 규격 목록."""
from __future__ import annotations

import os
import sys

import paramiko

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelupdev'


def main() -> int:
    if not PASSWORD:
        print('LABELUP_SSH_PASSWORD 환경변수를 설정하세요.', file=sys.stderr)
        return 1

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    try:
        _, out, _ = ssh.exec_command(f'cat {REMOTE_ROOT}/.env', timeout=60)
        env = {}
        for line in out.read().decode('utf-8', 'replace').splitlines():
            if '=' in line and not line.strip().startswith('#'):
                k, v = line.split('=', 1)
                env[k.strip()] = v.strip().strip('"').strip("'")

        mysql = (
            f"mysql --default-character-set=utf8 -h{env.get('DB_HOST', '127.0.0.1')} "
            f"-u{env['DB_USERNAME']} -p'{env['DB_PASSWORD']}' {env['DB_DATABASE']}"
        )
        query = (
            "SELECT s.id, s.name, s.labels_per_sheet, COUNT(p.id) AS products "
            "FROM label_specs s LEFT JOIN shop_products p ON p.spec_id = s.id "
            "WHERE s.columns_count IS NULL OR s.rows_count IS NULL "
            "GROUP BY s.id ORDER BY products DESC, s.id"
        )
        _, out, err = ssh.exec_command(f'{mysql} -e "{query}"', timeout=120)
        print(out.read().decode('utf-8', 'replace').strip())
        message = err.read().decode('utf-8', 'replace').strip()
        if message and 'Using a password' not in message:
            print('[stderr]', message, file=sys.stderr)
    finally:
        transport.close()
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
