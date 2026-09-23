#!/usr/bin/env python3
"""Deploy local dev/ to PHPS remote (/home/uptube1, public → www)."""
import os
import sys
import paramiko

from remote_config import (
    HOST,
    USER,
    PASSWORD,
    REMOTE_ROOT,
    REMOTE_PUBLIC,
    APP_URL,
    DB_HOST,
    DB_PORT,
    DB_DATABASE,
    DB_USERNAME,
    DB_PASSWORD,
)

LOCAL_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

SKIP_DIRS = {
    '.git', 'node_modules', 'vendor', '.env', '__pycache__', 'editor-src',
    # 로컬 전용: 배포 스크립트와 에디터 발행 작업 폴더
    'scripts',
    '_editor_publish', '_editor_publish_shop', '_editor_publish_tmp',
    '_editor_publish_zorder', '_publish_vendor_ui', '_tmp_pw',
}
SKIP_FILES = {'.env'}
# PHPS 호스팅용 설정은 서버에 유지 (로컬 파일로 덮으면 open_basedir이 깨진다)
SKIP_REMOTE_OVERWRITE = {
    REMOTE_PUBLIC + '/.htaccess',
    REMOTE_PUBLIC + '/.user.ini',
}


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


def remote_target(local_file: str) -> str:
    """Map local path under LOCAL_ROOT to remote path (public → www)."""
    rel = os.path.relpath(local_file, LOCAL_ROOT).replace('\\', '/')
    if rel == 'public' or rel.startswith('public/'):
        return REMOTE_PUBLIC + rel[len('public'):]
    return REMOTE_ROOT + '/' + rel


def upload_dir(sftp, local: str):
    for root, dirs, files in os.walk(local):
        dirs[:] = [d for d in dirs if d not in SKIP_DIRS]
        for name in files:
            if name in SKIP_FILES:
                continue
            local_file = os.path.join(root, name)
            if should_skip(local_file):
                continue
            remote_file = remote_target(local_file)
            if remote_file in SKIP_REMOTE_OVERWRITE:
                print('skip preserve', remote_file)
                continue
            ensure_remote_dir(sftp, os.path.dirname(remote_file).replace('\\', '/'))
            sftp.put(local_file, remote_file)


def run_ssh(ssh, cmd: str):
    print('$', cmd[:140])
    _, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', 'replace')
    err = stderr.read().decode('utf-8', 'replace')
    if out.strip():
        print(out.strip()[:800])
    if err.strip():
        print('ERR:', err.strip()[:400])


def read_remote_env(sftp, path: str) -> dict:
    try:
        with sftp.open(path, 'r') as f:
            raw = f.read().decode('utf-8', 'replace')
    except FileNotFoundError:
        return {}
    result = {}
    for line in raw.splitlines():
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        k, v = line.split('=', 1)
        result[k.strip()] = v.strip()
    return result


def load_local_env_value(key: str) -> str:
    local_env = os.path.join(LOCAL_ROOT, '.env')
    if not os.path.isfile(local_env):
        return ''
    try:
        with open(local_env, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if line.startswith(key + '='):
                    return line.split('=', 1)[1].strip()
    except OSError:
        return ''
    return ''


def pick_env(existing: dict, key: str, default: str = '') -> str:
    local = load_local_env_value(key)
    if local:
        return local
    return existing.get(key, default) or default


def main():
    if not PASSWORD:
        print('Set LABELUP_SSH_PASSWORD environment variable', file=sys.stderr)
        sys.exit(1)

    print('Connecting to', f'{USER}@{HOST}', '→', REMOTE_ROOT)
    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    ssh = paramiko.SSHClient()
    ssh._transport = transport

    print('Uploading files (public → www)')
    ensure_remote_dir(sftp, REMOTE_ROOT)
    ensure_remote_dir(sftp, REMOTE_PUBLIC)
    upload_dir(sftp, LOCAL_ROOT)

    existing_env = read_remote_env(sftp, REMOTE_ROOT + '/.env')
    openai_key = pick_env(existing_env, 'OPENAI_API_KEY')
    openai_model = pick_env(existing_env, 'OPENAI_MODEL', 'gpt-4o-mini')
    openai_max = pick_env(existing_env, 'OPENAI_MAX_TOKENS', '1800')
    openai_image = pick_env(existing_env, 'OPENAI_IMAGE_MODEL', 'gpt-image-1')
    openai_image_quality = pick_env(existing_env, 'OPENAI_IMAGE_QUALITY', 'low')
    naver_id = pick_env(existing_env, 'NAVER_CLIENT_ID')
    naver_secret = pick_env(existing_env, 'NAVER_CLIENT_SECRET')
    kakao_rest = pick_env(existing_env, 'KAKAO_REST_API_KEY')
    kakao_secret = pick_env(existing_env, 'KAKAO_CLIENT_SECRET')
    google_id = pick_env(existing_env, 'GOOGLE_CLIENT_ID')
    google_secret = pick_env(existing_env, 'GOOGLE_CLIENT_SECRET')
    session_key = existing_env.get('SESSION_KEY') or 'labelup_session'
    app_url = existing_env.get('APP_URL') or APP_URL
    # DB는 로컬 .env로 덮지 않음 (원격 전용)
    db_host = DB_HOST
    db_port = DB_PORT
    db_name = DB_DATABASE
    db_user = DB_USERNAME
    db_password = DB_PASSWORD or existing_env.get('DB_PASSWORD', '')
    if not db_password:
        print('DB_PASSWORD missing: set LABELUP_DB_PASSWORD or keep remote .env', file=sys.stderr)
        sys.exit(1)

    env_content = f"""APP_NAME=LabelUp
APP_ENV=remote
APP_DEBUG=true
APP_URL={app_url}

DB_HOST={db_host}
DB_PORT={db_port}
DB_DATABASE={db_name}
DB_USERNAME={db_user}
DB_PASSWORD={db_password}
SESSION_LIFETIME=7200
SESSION_KEY={session_key}
TIMEZONE=Asia/Seoul
OPENAI_API_KEY={openai_key}
OPENAI_MODEL={openai_model}
OPENAI_MAX_TOKENS={openai_max}
OPENAI_IMAGE_MODEL={openai_image}
OPENAI_IMAGE_QUALITY={openai_image_quality}
NAVER_CLIENT_ID={naver_id}
NAVER_CLIENT_SECRET={naver_secret}
KAKAO_REST_API_KEY={kakao_rest}
KAKAO_CLIENT_SECRET={kakao_secret}
GOOGLE_CLIENT_ID={google_id}
GOOGLE_CLIENT_SECRET={google_secret}
"""
    with sftp.open(REMOTE_ROOT + '/.env', 'w') as f:
        f.write(env_content)

    cmds = [
        f"mkdir -p {REMOTE_ROOT}/storage/{{uploads,designs,pdf,logs,ai-clipart,imports}}",
        f"mkdir -p {REMOTE_PUBLIC}/assets/{{ai-clipart,cliparts,hero,editor-previews,editor-media,shop-page}}",
        f"chmod -R u+rwX,g+rwX {REMOTE_ROOT}/storage",
        f"chmod -R u+rwX,g+rwX {REMOTE_PUBLIC}/assets/ai-clipart {REMOTE_PUBLIC}/assets/cliparts {REMOTE_PUBLIC}/assets/hero {REMOTE_PUBLIC}/assets/editor-previews {REMOTE_PUBLIC}/assets/editor-media {REMOTE_PUBLIC}/assets/shop-page {REMOTE_ROOT}/storage/ai-clipart {REMOTE_ROOT}/storage/imports 2>/dev/null || true",
        f"chmod 640 {REMOTE_ROOT}/.env",
    ]
    for cmd in cmds:
        run_ssh(ssh, cmd)

    sftp.close()
    transport.close()
    print('Deploy upload complete.')


if __name__ == '__main__':
    main()
