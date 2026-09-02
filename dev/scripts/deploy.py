#!/usr/bin/env python3
"""Deploy dev/ to labelupdev remote server via SFTP."""
import os
import sys
import stat
import paramiko

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelupdev'
LOCAL_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

SKIP_DIRS = {'.git', 'node_modules', 'vendor', '.env'}
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
            remote_file = remote_dir + '/' + name
            sftp.put(local_file, remote_file)


def run_ssh(ssh, cmd: str):
    print('$', cmd)
    _, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    if out.strip():
        print(out.strip())
    if err.strip():
        print(err.strip(), file=sys.stderr)
    return stdout.channel.recv_exit_status()


def load_local_env_value(key: str) -> str:
    local_env = os.path.join(LOCAL_ROOT, '.env')
    if not os.path.isfile(local_env):
        return ''
    try:
        with open(local_env, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith('#') or '=' not in line:
                    continue
                k, _, v = line.partition('=')
                if k.strip() == key:
                    return v.strip().strip('"').strip("'")
    except OSError:
        return ''
    return ''


def main():
    if not PASSWORD:
        print('Set LABELUP_SSH_PASSWORD environment variable', file=sys.stderr)
        sys.exit(1)

    print('Connecting to', HOST)
    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    ssh = paramiko.SSHClient()
    ssh._transport = transport

    print('Uploading files to', REMOTE_ROOT)
    ensure_remote_dir(sftp, REMOTE_ROOT)
    upload_dir(sftp, LOCAL_ROOT, REMOTE_ROOT)

    openai_key = load_local_env_value('OPENAI_API_KEY')
    openai_model = load_local_env_value('OPENAI_MODEL') or 'gpt-4o-mini'
    openai_max = load_local_env_value('OPENAI_MAX_TOKENS') or '1800'
    openai_image = load_local_env_value('OPENAI_IMAGE_MODEL') or 'gpt-image-1'
    openai_image_quality = load_local_env_value('OPENAI_IMAGE_QUALITY') or 'medium'

    env_content = f"""APP_NAME=LabelUp
APP_ENV=remote
APP_DEBUG=true
APP_URL=http://labelupdev.gagamkorea.kr

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=labelupdev
DB_USERNAME=labelupdev
DB_PASSWORD=LabelUpDev2026!
SESSION_LIFETIME=7200
TIMEZONE=Asia/Seoul
OPENAI_API_KEY={openai_key}
OPENAI_MODEL={openai_model}
OPENAI_MAX_TOKENS={openai_max}
OPENAI_IMAGE_MODEL={openai_image}
OPENAI_IMAGE_QUALITY={openai_image_quality}
"""
    with sftp.open(REMOTE_ROOT + '/.env', 'w') as f:
        f.write(env_content)

    cmds = [
        f"mkdir -p {REMOTE_ROOT}/storage/{{uploads,designs,pdf,logs,ai-clipart,imports}}",
        f"mkdir -p {REMOTE_ROOT}/public/assets/ai-clipart",
        f"mkdir -p {REMOTE_ROOT}/public/assets/cliparts",
        # labelupdev PHP-FPM runs as www-data (php8.1)
        f"chown -R www-data:www-data {REMOTE_ROOT}/public/assets/ai-clipart {REMOTE_ROOT}/public/assets/cliparts {REMOTE_ROOT}/storage/ai-clipart {REMOTE_ROOT}/storage/imports",
        f"chmod -R 775 {REMOTE_ROOT}/storage",
        f"chmod 777 {REMOTE_ROOT}/public/assets/ai-clipart {REMOTE_ROOT}/public/assets/cliparts {REMOTE_ROOT}/storage/ai-clipart {REMOTE_ROOT}/storage/imports",
    ]
    for cmd in cmds:
        run_ssh(ssh, cmd)

    sftp.close()
    transport.close()
    print('Deploy upload complete.')


if __name__ == '__main__':
    main()
