#!/usr/bin/env python3
"""Deploy local project/ to old server /home/labelup/project."""
import os
import sys
import paramiko

sys.path.insert(0, os.path.dirname(__file__))
from remote_config import HOST, USER, PASSWORD, REMOTE_ROOT

LOCAL_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
SKIP_DIRS = {'.git', 'node_modules', 'vendor', '__pycache__', 'storage'}
SKIP_FILES = {'.env'}


def should_skip(path: str) -> bool:
    parts = path.replace('\\', '/').split('/')
    return any(p in SKIP_DIRS for p in parts) or os.path.basename(path) in SKIP_FILES


def ensure_remote_dir(sftp, remote_dir: str):
    parts = remote_dir.strip('/').split('/')
    cur = ''
    for part in parts:
        cur += '/' + part
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)


def upload_dir(sftp, local: str, remote: str):
    for root, dirs, files in os.walk(local):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        rel = os.path.relpath(root, local)
        remote_dir = remote if rel == '.' else remote + '/' + rel.replace('\\', '/')
        ensure_remote_dir(sftp, remote_dir)
        for name in files:
            if name in SKIP_FILES:
                continue
            local_file = os.path.join(root, name)
            if should_skip(local_file):
                continue
            sftp.put(local_file, remote_dir + '/' + name)


def main():
    if not PASSWORD:
        print('Set LABELUP_PROJECT_SSH_PASSWORD', file=sys.stderr)
        sys.exit(1)

    print('Connecting to', f'{USER}@{HOST}', '→', REMOTE_ROOT)
    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    ssh = paramiko.SSHClient()
    ssh._transport = transport

    print('Uploading project/')
    ensure_remote_dir(sftp, REMOTE_ROOT)
    upload_dir(sftp, LOCAL_ROOT, REMOTE_ROOT)

    cmds = [
        f'mkdir -p {REMOTE_ROOT}/storage',
        f'chown -R www:www {REMOTE_ROOT}/storage 2>/dev/null || chown -R www-data:www-data {REMOTE_ROOT}/storage 2>/dev/null || true',
        f'chmod -R 775 {REMOTE_ROOT}/storage',
    ]
    for cmd in cmds:
        print('$', cmd)
        _, o, e = ssh.exec_command(cmd)
        out = o.read().decode('utf-8', 'replace').strip()
        err = e.read().decode('utf-8', 'replace').strip()
        if out:
            print(out[:400])
        if err:
            print('ERR:', err[:300])

    sftp.close()
    transport.close()
    print('Project deploy complete.')


if __name__ == '__main__':
    main()
