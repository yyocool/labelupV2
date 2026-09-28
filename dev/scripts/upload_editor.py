#!/usr/bin/env python3
"""퍼블리시된 편집기(public/editor)에서 변경된 파일만 서버로 올린다.

전체가 37MB라 매번 다 보내지 않고 md5가 다른 파일만 전송한다.
크기 비교로는 blazor.boot.json 의 해시 문자열처럼 길이가 같은 변경을 놓치는데,
그 파일이 빠지면 무결성 검증이 깨져 편집기가 아예 뜨지 않는다.
비밀번호는 환경변수 LABELUP_SSH_PASSWORD 로만 받는다.
"""
from __future__ import annotations

import hashlib
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
EDITOR_DIR = 'public/editor'
BOOT_JSON = '_framework/blazor.boot.json'


def ensure_remote_dir(sftp: paramiko.SFTPClient, remote_dir: str) -> None:
    current = ''
    for part in remote_dir.strip('/').split('/'):
        current += '/' + part
        try:
            sftp.stat(current)
        except FileNotFoundError:
            sftp.mkdir(current)


def md5_of(path: Path) -> str:
    digest = hashlib.md5()
    with path.open('rb') as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b''):
            digest.update(chunk)
    return digest.hexdigest()


def remote_md5_map(ssh: paramiko.SSHClient) -> dict[str, str]:
    remote_base = posixpath.join(REMOTE_ROOT, EDITOR_DIR)
    _, stdout, _ = ssh.exec_command(
        f"find {remote_base} -type f -exec md5sum {{}} +", timeout=600)
    table: dict[str, str] = {}
    for line in stdout.read().decode('utf-8', errors='replace').splitlines():
        parts = line.split('  ', 1)
        if len(parts) != 2:
            continue
        digest, path = parts[0].strip(), parts[1].strip()
        if path.startswith(remote_base + '/'):
            table[path[len(remote_base) + 1:]] = digest
    return table


def upload_changed(ssh: paramiko.SSHClient, sftp: paramiko.SFTPClient) -> int:
    base = LOCAL_ROOT / EDITOR_DIR
    if not base.is_dir():
        print(f'[FAIL] {EDITOR_DIR} 가 없습니다. publish_editor.py 를 먼저 실행하세요.', file=sys.stderr)
        return -1

    remote_hashes = remote_md5_map(ssh)
    count = 0
    for local in sorted(base.rglob('*')):
        if not local.is_file():
            continue
        key = local.relative_to(base).as_posix()
        if remote_hashes.get(key) == md5_of(local):
            continue
        rel = local.relative_to(LOCAL_ROOT).as_posix()
        remote = posixpath.join(REMOTE_ROOT, rel)
        ensure_remote_dir(sftp, posixpath.dirname(remote))
        sftp.put(str(local), remote)
        print('uploaded', rel)
        count += 1
    return count


def verify_boot_hash() -> bool:
    local = json.loads((LOCAL_ROOT / EDITOR_DIR / BOOT_JSON).read_text(encoding='utf-8'))
    url = f'http://{REMOTE_HOSTNAME}/editor/{BOOT_JSON}'
    request = urllib.request.Request(
        url, headers={'User-Agent': 'LabelUp-Deploy/1.0', 'Accept-Encoding': 'identity'})
    try:
        with urllib.request.urlopen(request, timeout=30) as response:
            remote = json.loads(response.read().decode('utf-8', errors='replace'))
    except (urllib.error.URLError, ValueError) as exc:
        print(f'[FAIL] {url}: {exc}', file=sys.stderr)
        return False

    key = 'LabelUp.Editor.wasm'
    remote_hash = (remote.get('resources', {}).get('assembly') or {}).get(key)
    local_hash = (local.get('resources', {}).get('assembly') or {}).get(key)
    print(f'remote {remote_hash}')
    print(f'local  {local_hash}')
    if remote_hash != local_hash:
        print('[FAIL] 서버가 아직 예전 빌드를 서빙합니다.', file=sys.stderr)
        return False
    print('[OK] 서버가 최신 편집기 빌드를 서빙합니다.')
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
        count = upload_changed(ssh, sftp)
        if count < 0:
            return 2
        print('done', count, 'files')
        if count:
            _, out, _ = ssh.exec_command(
                f'chown -R www:www {REMOTE_ROOT}/{EDITOR_DIR}', timeout=300)
            out.read()
    finally:
        sftp.close()
        transport.close()

    return 0 if verify_boot_hash() else 3


if __name__ == '__main__':
    raise SystemExit(main())
