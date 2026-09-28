#!/usr/bin/env python3
"""규격 배치값(열·행·간격·모서리·바탕색) 기능 배포.

1) 관리자 규격 저장/조회 PHP·JS·CSS·뷰 업로드
2) label_specs 컬럼 추가 마이그레이션(051) 적용
3) 엑셀에서 뽑은 배치값(updateSQL_260923_2.sql) 반영
4) /api/editor/papers 응답에 배치값이 실리는지 검증

편집기 산출물(public/editor)은 scripts/upload_editor.py 로 따로 올린다.

비밀번호는 환경변수 LABELUP_SSH_PASSWORD 로만 받는다.
"""
from __future__ import annotations

import json
import os
import posixpath
import sys
import urllib.error
import urllib.request
from pathlib import Path

import paramiko

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelupdev'
REMOTE_HOSTNAME = 'labelupdev.gagamkorea.kr'
LOCAL_ROOT = Path(__file__).resolve().parents[1]
SCHEMA_DIR = LOCAL_ROOT.parents[0] / '.dbSchema'
DATA_SQL = SCHEMA_DIR / 'updateSQL_260923_2.sql'
VERIFY_URL = f'http://{REMOTE_HOSTNAME}/api/editor/papers'

APP_FILES = [
    'app/Repositories/ShopRepository.php',
    'app/Services/ShopAdminService.php',
    'public/js/shop-admin.js',
    'public/css/admin.css',
    'views/admin/shop/specs.php',
    'database/migrations/051_label_spec_geometry.sql',
]


def ensure_remote_dir(sftp: paramiko.SFTPClient, remote_dir: str) -> None:
    current = ''
    for part in remote_dir.strip('/').split('/'):
        current += '/' + part
        try:
            sftp.stat(current)
        except FileNotFoundError:
            sftp.mkdir(current)


def put(sftp: paramiko.SFTPClient, local: Path, rel: str) -> None:
    remote = posixpath.join(REMOTE_ROOT, rel)
    ensure_remote_dir(sftp, posixpath.dirname(remote))
    sftp.put(str(local), remote)


def upload_app_files(sftp: paramiko.SFTPClient) -> int:
    count = 0
    for rel in APP_FILES:
        local = LOCAL_ROOT / rel
        if not local.is_file():
            print(f'[SKIP] 로컬 파일 없음: {rel}', file=sys.stderr)
            continue
        put(sftp, local, rel)
        print('uploaded', rel)
        count += 1
    return count


def run(ssh: paramiko.SSHClient, command: str, quiet: bool = False) -> str:
    _, stdout, stderr = ssh.exec_command(command, timeout=300)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if not quiet:
        if out.strip():
            print(out.strip())
        if err.strip():
            print('[stderr]', err.strip(), file=sys.stderr)
    return out


def remote_db_credentials(ssh: paramiko.SSHClient) -> dict[str, str]:
    raw = run(ssh, f'cat {REMOTE_ROOT}/.env', quiet=True)
    creds: dict[str, str] = {}
    for line in raw.splitlines():
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        creds[key.strip()] = value.strip().strip('"').strip("'")

    # 원격 .env 는 DB_DATABASE/DB_USERNAME 표기를 쓴다.
    return {
        'DB_HOST': creds.get('DB_HOST') or '127.0.0.1',
        'DB_NAME': creds.get('DB_DATABASE') or creds.get('DB_NAME') or '',
        'DB_USER': creds.get('DB_USERNAME') or creds.get('DB_USER') or '',
        'DB_PASSWORD': creds.get('DB_PASSWORD') or creds.get('DB_PASS') or '',
    }


def apply_data_sql(ssh: paramiko.SSHClient, sftp: paramiko.SFTPClient) -> None:
    if not DATA_SQL.is_file():
        print(f'[SKIP] 데이터 SQL 없음: {DATA_SQL}', file=sys.stderr)
        return

    creds = remote_db_credentials(ssh)
    missing = [k for k in ('DB_NAME', 'DB_USER', 'DB_PASSWORD') if not creds.get(k)]
    if missing:
        print(f'[FAIL] 원격 .env 에서 {", ".join(missing)} 를 읽지 못했습니다.', file=sys.stderr)
        return

    remote_sql = '/tmp/updateSQL_260923_2.sql'
    sftp.put(str(DATA_SQL), remote_sql)
    print('uploaded', DATA_SQL.name, '->', remote_sql)

    mysql = (
        f"mysql --default-character-set=utf8 -h{creds.get('DB_HOST', '127.0.0.1')} "
        f"-u{creds['DB_USER']} -p'{creds['DB_PASSWORD']}' {creds['DB_NAME']}"
    )
    print('--- 배치값 반영')
    run(ssh, f'{mysql} < {remote_sql}')
    print('--- 반영 결과 확인')
    run(ssh, f'''{mysql} -e "SELECT COUNT(*) AS filled FROM label_specs WHERE columns_count IS NOT NULL AND rows_count IS NOT NULL"''')
    run(ssh, f'''{mysql} -e "SELECT name, paper_size, columns_count, rows_count, h_gap_mm, v_gap_mm, corner_radius_x_mm, label_color FROM label_specs WHERE columns_count IS NOT NULL ORDER BY id LIMIT 5"''')
    run(ssh, f'rm -f {remote_sql}')


def verify() -> bool:
    request = urllib.request.Request(VERIFY_URL, headers={'User-Agent': 'LabelUp-Deploy/1.0'})
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            payload = json.loads(response.read().decode('utf-8', errors='replace'))
    except (urllib.error.URLError, ValueError) as exc:
        print(f'[FAIL] {VERIFY_URL}: {exc}', file=sys.stderr)
        return False

    items = (payload.get('data') or {}).get('items') or []
    if not items:
        print('[FAIL] 용지 목록이 비어 있습니다.', file=sys.stderr)
        return False

    with_layout = [i for i in items if i.get('columnsCount') and i.get('rowsCount')]
    print(f'[OK] items={len(items)} 배치값 보유={len(with_layout)}')
    if not with_layout:
        print('[FAIL] columnsCount/rowsCount 가 여전히 비어 있습니다.', file=sys.stderr)
        return False

    sample = with_layout[0]
    print('  예시:', json.dumps({
        k: sample.get(k) for k in (
            'sku', 'paperSize', 'widthMm', 'heightMm',
            'columnsCount', 'rowsCount', 'hGapMm', 'vGapMm',
            'cornerRadiusXMm', 'labelColor')
    }, ensure_ascii=False))
    return True


def main() -> int:
    if not PASSWORD:
        print('LABELUP_SSH_PASSWORD 환경변수를 설정하세요.', file=sys.stderr)
        return 1

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    sftp = paramiko.SFTPClient.from_transport(transport)
    try:
        print('=== 1) 서버 파일 업로드')
        print('done', upload_app_files(sftp), 'files')

        print('=== 2) 마이그레이션 적용')
        run(ssh, f"curl -s -X POST http://127.0.0.1/api/system/migrate -H 'Host: {REMOTE_HOSTNAME}'")

        print('=== 3) 엑셀 배치값 반영')
        apply_data_sql(ssh, sftp)

        run(ssh, f'chown -R www:www {REMOTE_ROOT}/app {REMOTE_ROOT}/views {REMOTE_ROOT}/public {REMOTE_ROOT}/database')
        print('=== 4) 편집기 산출물은 scripts/upload_editor.py 로 올린다(md5 비교)')
    finally:
        sftp.close()
        transport.close()

    print('=== 5) API 검증')

    return 0 if verify() else 2


if __name__ == '__main__':
    raise SystemExit(main())
