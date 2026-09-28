#!/usr/bin/env python3
"""051 마이그레이션 기록 정리.

label_specs 컬럼이 이미 서버에 추가되어 있어 051 ALTER 가 Duplicate column 으로
실패하고, 그 때문에 이후 마이그레이션이 전부 막힌다. 컬럼 존재를 확인한 뒤
migrations 테이블에 051 을 적용 완료로 기록한다.
"""
from __future__ import annotations

import os
import sys

import paramiko

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelupdev'
REMOTE_HOSTNAME = 'labelupdev.gagamkorea.kr'
MIGRATION = '051_label_spec_geometry.sql'


def run(ssh: paramiko.SSHClient, command: str, quiet: bool = False) -> str:
    _, stdout, stderr = ssh.exec_command(command, timeout=120)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if not quiet:
        if out.strip():
            print(out.strip())
        if err.strip() and 'Using a password' not in err:
            print('[stderr]', err.strip(), file=sys.stderr)
    return out


def db_credentials(ssh: paramiko.SSHClient) -> dict[str, str]:
    raw = run(ssh, f'cat {REMOTE_ROOT}/.env', quiet=True)
    creds: dict[str, str] = {}
    for line in raw.splitlines():
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        creds[key.strip()] = value.strip().strip('"').strip("'")
    return creds


def main() -> int:
    if not PASSWORD:
        print('LABELUP_SSH_PASSWORD 환경변수를 설정하세요.', file=sys.stderr)
        return 1

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    try:
        env = db_credentials(ssh)
        print('=== .env DB 키 목록:', ', '.join(k for k in env if k.startswith('DB_')))
        name = env.get('DB_NAME') or env.get('DB_DATABASE') or ''
        user = env.get('DB_USER') or env.get('DB_USERNAME') or ''
        password = env.get('DB_PASSWORD') or env.get('DB_PASS') or ''
        host = env.get('DB_HOST') or '127.0.0.1'
        if not (name and user):
            print('[FAIL] DB 접속 정보를 읽지 못했습니다.', file=sys.stderr)
            return 2

        mysql = f"mysql --default-character-set=utf8 -h{host} -u{user} -p'{password}' {name}"

        print('=== label_specs 신규 컬럼 확인')
        run(ssh, f'''{mysql} -e "SHOW COLUMNS FROM label_specs WHERE Field IN ('paper_size','top_margin_mm','left_margin_mm','columns_count','rows_count','h_gap_mm','v_gap_mm','corner_radius_x_mm','corner_radius_y_mm','label_color','custom_path_svg')"''')

        print('=== 배치값이 채워진 규격 수')
        run(ssh, f'''{mysql} -e "SELECT COUNT(*) AS total, SUM(columns_count IS NOT NULL AND rows_count IS NOT NULL) AS with_grid FROM label_specs"''')

        print(f'=== migrations 에 {MIGRATION} 적용 기록')
        run(ssh, f'''{mysql} -e "INSERT IGNORE INTO migrations (migration, batch, created_at) VALUES ('{MIGRATION}', 1, NOW())"''')
        run(ssh, f'''{mysql} -e "SELECT migration, created_at FROM migrations ORDER BY id DESC LIMIT 3"''')

        print('=== 마이그레이션 재실행(막힘 해소 확인)')
        run(ssh, f"curl -s -X POST http://127.0.0.1/api/system/migrate -H 'Host: {REMOTE_HOSTNAME}'")
    finally:
        transport.close()
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
