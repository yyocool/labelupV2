#!/usr/bin/env python3
from __future__ import annotations

"""서비스 서버에 DB 일일 백업을 올리고, 매일 03:20 크론을 등록한 뒤 한 번 실행한다.

SSH 비밀번호: LABELUP_SSH_PASSWORD
"""
import os
import sys

import paramiko

sys.path.insert(0, os.path.dirname(__file__))
from remote_config import HOST as DEFAULT_HOST
from remote_config import PASSWORD as DEFAULT_PASSWORD
from remote_config import REMOTE_ROOT as DEFAULT_ROOT
from remote_config import USER as DEFAULT_USER

LOCAL_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
MARKER = 'labelup-db-backup'


def ensure_remote_dir(sftp, remote_dir: str) -> None:
    parts = remote_dir.strip('/').split('/')
    cur = ''
    for part in parts:
        cur += '/' + part
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)


def upload_text(sftp, local_rel: str, remote_path: str) -> None:
    local_path = os.path.join(LOCAL_ROOT, local_rel.replace('/', os.sep))
    with open(local_path, 'r', encoding='utf-8', newline=None) as f:
        text = f.read().replace('\r\n', '\n').replace('\r', '\n')
    ensure_remote_dir(sftp, os.path.dirname(remote_path).replace('\\', '/'))
    with sftp.open(remote_path, 'w') as remote:
        remote.write(text)
    print('uploaded', remote_path)


def run(ssh, cmd: str, timeout: int | None = 60) -> tuple[int, str, str]:
    print('$', cmd[:180])
    _, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', 'replace')
    err = stderr.read().decode('utf-8', 'replace')
    code = stdout.channel.recv_exit_status()
    if out.strip():
        print(out.strip()[:2000])
    if err.strip():
        print('ERR:', err.strip()[:800])
    return code, out, err


def main() -> None:
    host = os.environ.get('LABELUP_BACKUP_HOST') or DEFAULT_HOST
    user = os.environ.get('LABELUP_BACKUP_USER') or DEFAULT_USER
    password = os.environ.get('LABELUP_BACKUP_PASSWORD') or os.environ.get('LABELUP_SSH_PASSWORD') or DEFAULT_PASSWORD
    remote_root = os.environ.get('LABELUP_BACKUP_ROOT') or DEFAULT_ROOT
    remote_bin = remote_root + '/bin/backup_db.php'
    remote_dir = remote_root + '/storage/backups/db'
    remote_log = remote_dir + '/cron.log'
    if not password:
        print('Set LABELUP_SSH_PASSWORD or LABELUP_BACKUP_PASSWORD', file=sys.stderr)
        sys.exit(1)

    print('Connecting to', f'{user}@{host}', '→', remote_root)
    transport = paramiko.Transport((host, 22))
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    ssh = paramiko.SSHClient()
    ssh._transport = transport

    remote_sh = remote_root + '/bin/backup_db.sh'
    upload_text(sftp, 'bin/backup_db.php', remote_bin)
    upload_text(sftp, 'bin/backup_db.sh', remote_sh)

    _, owner_out, _ = run(ssh, f'stat -c %U {remote_root}')
    owner = owner_out.strip().splitlines()[-1].strip() if owner_out.strip() else 'www'
    if not owner or any(ch.isspace() for ch in owner):
        owner = 'www'

    run(ssh, f'mkdir -p {remote_dir} && chown {owner}:{owner} {remote_dir} && chmod 700 {remote_dir}')
    run(ssh, f'chown {owner}:{owner} {remote_bin} {remote_sh} && chmod 640 {remote_bin} && chmod 750 {remote_sh}')

    code, php_out, _ = run(ssh, 'command -v php || command -v php82 || command -v php81 || command -v php80')
    php = php_out.strip().splitlines()[-1].strip() if php_out.strip() else ''
    if code != 0 or not php.startswith('/'):
        print('원격에서 php 실행 파일을 찾지 못했습니다.', file=sys.stderr)
        sys.exit(1)

    lint, _, _ = run(ssh, f'{php} -l {remote_bin}')
    if lint != 0:
        print('backup_db.php 문법 오류', file=sys.stderr)
        sys.exit(1)

    _, tz_out, _ = run(ssh, 'date +%z')
    offset = tz_out.strip().splitlines()[-1].strip() if tz_out.strip() else '+0900'
    # 한국시간 03:20. 서버가 CST(+0800)이면 02:20.
    hour = 2 if offset == '+0800' else 3
    _, dump_out, _ = run(ssh, 'command -v mysqldump || true')
    dump_bin = dump_out.strip().splitlines()[-1].strip() if dump_out.strip() else ''
    cron_target = remote_sh if dump_bin.startswith('/') else f'{php} {remote_bin}'
    cron_line = f'20 {hour} * * * {cron_target} >> {remote_log} 2>&1'
    install_cron = f"""
set -e
tmp=$(mktemp)
crontab -u {owner} -l 2>/dev/null | grep -v '{MARKER}' | grep -v 'backup_db.php' | grep -v 'backup_db.sh' | sed '/^$/d' > "$tmp" || true
echo '# {MARKER} 03:20 Asia/Seoul' >> "$tmp"
echo '{cron_line}' >> "$tmp"
crontab -u {owner} "$tmp"
rm -f "$tmp"
echo '--- crontab {owner} ---'
crontab -u {owner} -l
echo '--- cron service ---'
systemctl is-active cron 2>/dev/null || systemctl is-active crond 2>/dev/null || true
"""
    cron_code, _, cron_err = run(ssh, install_cron)
    if cron_code != 0:
        print('크론 등록 실패. 서버 크론에 아래 줄을 직접 넣어 주세요.')
        print(cron_line)
        if cron_err.strip():
            print(cron_err.strip()[:500])

    print('Running first backup as', owner)
    backup_cmd = remote_sh if dump_bin.startswith('/') else f'{php} {remote_bin}'
    backup_code, _, _ = run(
        ssh,
        f"su -s /bin/bash {owner} -c '{backup_cmd}'",
        timeout=None,
    )
    run(ssh, f'cat {remote_dir}/latest.json')
    run(ssh, f'ls -l {remote_dir}/*.sql.gz 2>/dev/null | tail -n 5')

    sftp.close()
    transport.close()
    if backup_code != 0:
        sys.exit(backup_code)
    print('DB backup install complete.')


if __name__ == '__main__':
    main()
