#!/usr/bin/env python3
import os
import sys
import urllib.request
import paramiko

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from remote_config import HOST, USER, PASSWORD, APP_URL, PUBLIC_HOST, REMOTE_ROOT

sys.stdout.reconfigure(encoding='utf-8', errors='replace')


def main():
    if not PASSWORD:
        print('Set LABELUP_PROJECT_SSH_PASSWORD', file=sys.stderr)
        sys.exit(1)

    print(f'=== public ({APP_URL}/)')
    try:
        req = urllib.request.Request(APP_URL.rstrip('/') + '/', headers={'User-Agent': 'LabelUp-ProjectTest/1.0'})
        with urllib.request.urlopen(req, timeout=20) as res:
            print(f'HTTP:{res.status}')
            print(res.read().decode('utf-8', 'replace')[:300])
    except Exception as e:
        print('public error:', e)

    print(f'=== SSH ({USER}@{HOST})')
    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = t
    cmds = [
        f'test -f {REMOTE_ROOT}/index.php && echo project_index_ok',
        f'ls {REMOTE_ROOT} | head -15',
        f'curl -s -o /tmp/pj.out -w "%{{http_code}}" -H "Host: {PUBLIC_HOST}" http://127.0.0.1/project/; echo; head -c 200 /tmp/pj.out',
    ]
    for c in cmds:
        _, o, _ = ssh.exec_command(c)
        print('===', c)
        print(o.read().decode('utf-8', 'replace'))
    t.close()


if __name__ == '__main__':
    main()
