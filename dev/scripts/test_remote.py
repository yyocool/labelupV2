#!/usr/bin/env python3
import sys
import paramiko
import urllib.request

from remote_config import HOST, USER, PASSWORD, APP_URL, PUBLIC_HOST

sys.stdout.reconfigure(encoding='utf-8', errors='replace')


def http_get(url: str) -> tuple[int, str]:
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'LabelUp-RemoteTest/1.0'})
        with urllib.request.urlopen(req, timeout=20) as res:
            body = res.read().decode('utf-8', 'replace')
            return res.status, body
    except Exception as e:
        return 0, str(e)


def main():
    if not PASSWORD:
        print('Set LABELUP_SSH_PASSWORD', file=sys.stderr)
        sys.exit(1)

    print(f'=== public health ({APP_URL}/api/health)')
    code, body = http_get(APP_URL.rstrip('/') + '/api/health')
    print(f'HTTP:{code}')
    print(body[:500])

    print(f'=== public migrate POST')
    try:
        req = urllib.request.Request(
            APP_URL.rstrip('/') + '/api/system/migrate',
            data=b'',
            method='POST',
            headers={'User-Agent': 'LabelUp-RemoteTest/1.0'},
        )
        with urllib.request.urlopen(req, timeout=60) as res:
            print(f'HTTP:{res.status}')
            print(res.read().decode('utf-8', 'replace')[:500])
    except Exception as e:
        print('migrate error:', e)

    print(f'=== SSH smoke ({USER}@{HOST})')
    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = t
    cmds = [
        f'curl -s -o /tmp/lu_health.out -w "%{{http_code}}" {APP_URL}/api/health; echo; cat /tmp/lu_health.out',
        f'curl -s -o /tmp/lu_home.out -w "%{{http_code}}" {APP_URL}/; echo; head -c 200 /tmp/lu_home.out',
        f'test -f /home/uptube1/www/index.php && echo www_ok',
        f'test -f /home/uptube1/.env && echo env_ok',
    ]
    for c in cmds:
        _, o, _ = ssh.exec_command(c)
        print('===', c)
        print(o.read().decode('utf-8', 'replace'))
    t.close()
    print(f'Public host: {PUBLIC_HOST}')


if __name__ == '__main__':
    main()
