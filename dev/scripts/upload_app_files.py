"""지정한 서버 파일을 업로드하고, PHP 파일은 원격에서 문법 검사까지 한다.

사용: python scripts/upload_app_files.py app/Services/Foo.php views/admin/bar.php
경로는 gitfolder/dev 기준 상대경로. 비밀번호는 환경변수 LABELUP_SSH_PASSWORD 로만 받는다.
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
LOCAL_ROOT = Path(__file__).resolve().parents[1]


def run(ssh: paramiko.SSHClient, cmd: str) -> tuple[int, str]:
    _, stdout, stderr = ssh.exec_command(cmd, timeout=300)
    out = stdout.read().decode('utf-8', 'replace')
    code = stdout.channel.recv_exit_status()
    err = stderr.read().decode('utf-8', 'replace').strip()
    if err:
        out += '\n' + err
    return code, out.strip()


def ensure_remote_dir(sftp: paramiko.SFTPClient, path: str) -> None:
    parts = [p for p in path.strip('/').split('/') if p]
    cur = ''
    for part in parts:
        cur = f'{cur}/{part}'
        try:
            sftp.stat(cur)
        except IOError:
            sftp.mkdir(cur)


def main() -> None:
    rels = sys.argv[1:]
    if not rels:
        print('사용: python scripts/upload_app_files.py <상대경로> [...]', file=sys.stderr)
        sys.exit(1)
    if not PASSWORD:
        print('[FAIL] LABELUP_SSH_PASSWORD 환경변수가 없습니다.', file=sys.stderr)
        sys.exit(1)

    locals_ = []
    for rel in rels:
        local = LOCAL_ROOT / rel
        if not local.is_file():
            print(f'[FAIL] 파일이 없습니다: {local}', file=sys.stderr)
            sys.exit(1)
        locals_.append((rel.replace('\\', '/'), local))

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    sftp = paramiko.SFTPClient.from_transport(transport)
    failed = False
    try:
        for rel, local in locals_:
            remote = posixpath.join(REMOTE_ROOT, rel)
            ensure_remote_dir(sftp, posixpath.dirname(remote))
            sftp.put(str(local), remote)
            print(f'uploaded {rel}')
            if rel.endswith('.php'):
                code, out = run(ssh, f'php -l {remote}')
                print(f'  php -l: {out}')
                if code != 0:
                    failed = True
        run(ssh, f'chown -R www:www {REMOTE_ROOT}/app {REMOTE_ROOT}/views {REMOTE_ROOT}/public')
    finally:
        sftp.close()
        transport.close()

    if failed:
        print('[FAIL] PHP 문법 오류가 있습니다.', file=sys.stderr)
        sys.exit(1)
    print(f'done {len(locals_)} files')


if __name__ == '__main__':
    main()
