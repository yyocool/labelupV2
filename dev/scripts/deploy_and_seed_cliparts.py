#!/usr/bin/env python3
"""Deploy, migrate, and seed cliparts on remote."""
import os
import sys
import urllib.request

import paramiko

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelupdev'


def main():
    if not PASSWORD:
        print('Set LABELUP_SSH_PASSWORD', file=sys.stderr)
        sys.exit(1)

    # run normal deploy first
    os.system(f'"{sys.executable}" "{os.path.join(os.path.dirname(__file__), "deploy.py")}"')

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASSWORD, timeout=30)

    cmds = [
        # migrate via PHP if available, else curl API
        f"cd {REMOTE_ROOT} && (php scripts/seed_cliparts.php 2>/dev/null || /usr/local/php/bin/php scripts/seed_cliparts.php 2>/dev/null || /usr/bin/php8.1 scripts/seed_cliparts.php)",
        f"chown -R www-data:www-data {REMOTE_ROOT}/public/assets/cliparts {REMOTE_ROOT}/storage/imports",
        f"chmod -R 777 {REMOTE_ROOT}/public/assets/cliparts",
    ]

    # migrate first via HTTP
    print('Running remote migrate API...')
    try:
        req = urllib.request.Request(
            'http://labelupdev.gagamkorea.kr/api/system/migrate',
            data=b'{}',
            headers={'Content-Type': 'application/json'},
            method='POST',
        )
        with urllib.request.urlopen(req, timeout=120) as resp:
            print(resp.read().decode('utf-8', 'replace'))
    except Exception as e:
        print('migrate API:', e)

    for c in cmds:
        print('$', c)
        _, stdout, stderr = ssh.exec_command(c, timeout=300)
        print(stdout.read().decode('utf-8', 'replace'))
        err = stderr.read().decode('utf-8', 'replace')
        if err.strip():
            print(err, file=sys.stderr)

    ssh.close()
    print('Done.')


if __name__ == '__main__':
    main()
