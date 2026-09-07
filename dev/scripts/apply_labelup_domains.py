#!/usr/bin/env python3
import os
import sys
from pathlib import Path

import paramiko

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

HOST = "115.71.237.145"
USER = "root"
PASSWORD = os.environ.get("LABELUP_SSH_PASSWORD", "")
HERE = Path(__file__).resolve().parent

FILES = {
    HERE / "_vhost_www.labelup.co.kr.conf": "/usr/local/nginx/conf/vhost/www.labelup.co.kr.conf",
    HERE / "_vhost_www.labelup.kr.conf": "/usr/local/nginx/conf/vhost/www.labelup.kr.conf",
    HERE.parent / "app/Services/OAuthService.php": "/home/labelupdev/app/Services/OAuthService.php",
}


def run(ssh, cmd):
    print("$", cmd[:180])
    _, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    code = stdout.channel.recv_exit_status()
    if out.strip():
        print(out.strip())
    if err.strip():
        print(err.strip())
    return code, out


def main():
    if not PASSWORD:
        raise SystemExit("LABELUP_SSH_PASSWORD is empty")

    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = t
    sftp = paramiko.SFTPClient.from_transport(t)

    run(ssh, "cp -a /usr/local/nginx/conf/vhost/www.labelup.co.kr.conf /usr/local/nginx/conf/vhost/www.labelup.co.kr.conf.bak.20260906")
    for local, remote in FILES.items():
        data = local.read_bytes()
        with sftp.open(remote, "wb") as fh:
            fh.write(data)
        print("uploaded", remote, "bytes", len(data))

    code, out = run(ssh, "/usr/local/nginx/sbin/nginx -t")
    if code != 0 or "successful" not in out:
        print("nginx -t failed; not reloading")
        sftp.close()
        t.close()
        raise SystemExit(1)

    run(ssh, "/usr/local/nginx/sbin/nginx -s reload")
    tests = [
        'curl -sI https://www.labelup.co.kr/ | head -20',
        'curl -s https://www.labelup.co.kr/api/health',
        'curl -sI https://labelup.co.kr/ | head -12',
        'curl -sI http://www.labelup.kr/ | head -15',
        'curl -s http://www.labelup.kr/api/health',
        'curl -sI http://labelupdev.gagamkorea.kr/api/health | head -12',
    ]
    for cmd in tests:
        print("===", cmd)
        run(ssh, cmd)

    sftp.close()
    t.close()


if __name__ == "__main__":
    main()
