#!/usr/bin/env python3
"""Upload the editor paper API files (PHP only) and verify the endpoint."""
from __future__ import annotations

import json
import os
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
LOCAL_ROOT = Path(__file__).resolve().parents[1]
VERIFY_URL = 'http://labelupdev.gagamkorea.kr/api/editor/papers'
VERIFY_ORIGIN = 'http://localhost:5224'

FILES = [
    'app/Helpers/Cors.php',
    'app/Services/EditorPaperCatalogService.php',
    'app/Controllers/Api/EditorPaperApiController.php',
    'app/Router.php',
    'config/app.php',
]


def ensure_remote_dir(sftp: paramiko.SFTPClient, remote_dir: str) -> None:
    current = ''
    for part in remote_dir.strip('/').split('/'):
        current += '/' + part
        try:
            sftp.stat(current)
        except FileNotFoundError:
            sftp.mkdir(current)


def upload(sftp: paramiko.SFTPClient) -> int:
    count = 0
    for rel in FILES:
        local = LOCAL_ROOT / rel
        if not local.is_file():
            print(f'[SKIP] missing local file: {rel}', file=sys.stderr)
            continue
        remote = f'{REMOTE_ROOT}/{rel}'
        ensure_remote_dir(sftp, remote.rsplit('/', 1)[0])
        sftp.put(str(local), remote)
        print('uploaded', rel)
        count += 1
    return count


def verify() -> bool:
    request = urllib.request.Request(VERIFY_URL, headers={'Origin': VERIFY_ORIGIN})
    try:
        with urllib.request.urlopen(request, timeout=20) as response:
            payload = json.loads(response.read().decode('utf-8', errors='replace'))
            allow_origin = response.headers.get('Access-Control-Allow-Origin', '')
    except (urllib.error.URLError, ValueError) as exc:
        print(f'[FAIL] {VERIFY_URL}: {exc}', file=sys.stderr)
        return False

    total = (payload.get('data') or {}).get('total', 0)
    print(f'[OK] {VERIFY_URL} items={total} Access-Control-Allow-Origin={allow_origin!r}')
    if allow_origin != VERIFY_ORIGIN:
        print('[FAIL] CORS 헤더가 로컬 편집기 출처를 허용하지 않습니다.', file=sys.stderr)
        return False
    return bool(payload.get('success')) and total > 0


def main() -> int:
    if not PASSWORD:
        print('Set LABELUP_SSH_PASSWORD environment variable', file=sys.stderr)
        return 1

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    try:
        count = upload(sftp)
    finally:
        sftp.close()
        transport.close()
    print('done', count, 'files')

    return 0 if verify() else 2


if __name__ == '__main__':
    raise SystemExit(main())
