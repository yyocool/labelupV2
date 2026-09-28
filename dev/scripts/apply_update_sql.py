""".dbSchema 의 updateSQL 파일을 원격 DB에 적용한다.

사용: python scripts/apply_update_sql.py updateSQL_260928_1.sql
접속 정보는 원격 .env 에서만 읽는다(로컬에 자격증명을 두지 않는다).
"""
from __future__ import annotations

import os
import posixpath
import sys
from pathlib import Path

import paramiko

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelupdev'
SCHEMA_DIR = Path(__file__).resolve().parents[2] / '.dbSchema'


def run(ssh: paramiko.SSHClient, cmd: str, quiet: bool = False) -> str:
    _, stdout, stderr = ssh.exec_command(cmd, timeout=300)
    out = stdout.read().decode('utf-8', 'replace')
    err = stderr.read().decode('utf-8', 'replace').strip()
    if err and 'Warning' not in err:
        print(err, file=sys.stderr)
    if out and not quiet:
        print(out)
    return out


def db_creds(ssh: paramiko.SSHClient) -> dict[str, str]:
    raw = run(ssh, f'cat {REMOTE_ROOT}/.env', quiet=True)
    creds: dict[str, str] = {}
    for line in raw.splitlines():
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        creds[key.strip()] = value.strip().strip('"').strip("'")
    return {
        'host': creds.get('DB_HOST') or '127.0.0.1',
        'name': creds.get('DB_DATABASE') or creds.get('DB_NAME') or '',
        'user': creds.get('DB_USERNAME') or creds.get('DB_USER') or '',
        'pass': creds.get('DB_PASSWORD') or creds.get('DB_PASS') or '',
    }


def main() -> None:
    if len(sys.argv) < 2:
        print('사용: python scripts/apply_update_sql.py <updateSQL 파일명>', file=sys.stderr)
        sys.exit(1)
    local = SCHEMA_DIR / sys.argv[1]
    if not local.is_file():
        print(f'[FAIL] 파일이 없습니다: {local}', file=sys.stderr)
        sys.exit(1)
    if not PASSWORD:
        print('[FAIL] LABELUP_SSH_PASSWORD 환경변수가 없습니다.', file=sys.stderr)
        sys.exit(1)

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    sftp = paramiko.SFTPClient.from_transport(transport)
    remote = posixpath.join('/tmp', local.name)
    try:
        creds = db_creds(ssh)
        if not creds['name'] or not creds['user']:
            print('[FAIL] 원격 .env 에서 DB 접속 정보를 읽지 못했습니다.', file=sys.stderr)
            sys.exit(1)
        sftp.put(str(local), remote)
        mysql = (
            f"mysql --default-character-set=utf8mb4 -h{creds['host']} "
            f"-u{creds['user']} -p'{creds['pass']}' {creds['name']}"
        )
        print(f'=== 적용: {local.name}')
        run(ssh, f'{mysql} < {remote}')
        run(ssh, f'rm -f {remote}', quiet=True)
        print('=== 남은 미입력 규격')
        run(ssh, f'''{mysql} -e "SELECT id, name, labels_per_sheet FROM label_specs '''
                 f'''WHERE columns_count IS NULL OR rows_count IS NULL ORDER BY id"''')
    finally:
        sftp.close()
        transport.close()


if __name__ == '__main__':
    main()
