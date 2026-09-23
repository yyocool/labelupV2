#!/usr/bin/env python3
"""프로젝트/보관함/휴지통 + 편집기 503 패치 타깃 배포."""
from __future__ import annotations

import os
import sys
import urllib.request

import paramiko

from remote_config import HOST, USER, PASSWORD, REMOTE_ROOT, REMOTE_PUBLIC, APP_URL

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

LOCAL_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

FILES = [
    ('app/Repositories/EditorWorkspaceRepository.php', 'root'),
    ('app/Services/EditorWorkspaceService.php', 'root'),
    ('app/Repositories/UserAiClipartRepository.php', 'root'),
    ('app/Services/UserAiClipartService.php', 'root'),
    ('app/Controllers/LibraryController.php', 'root'),
    ('app/Controllers/Api/LibraryApiController.php', 'root'),
    ('app/Router.php', 'root'),
    ('app/Middleware/AuthMiddleware.php', 'root'),
    ('views/library/projects.php', 'root'),
    ('views/library/locker.php', 'root'),
    ('views/library/trash.php', 'root'),
    ('views/account/layout.php', 'root'),
    ('views/account/index.php', 'root'),
    ('views/home/partials/sidebar.php', 'root'),
    ('views/home/partials/profile-menu.php', 'root'),
    ('database/migrations/051_workspace_clipart_trash.sql', 'root'),
    ('scripts/apply_trash_columns.php', 'root'),
    ('public/js/library.js', 'public'),
    ('public/css/account.css', 'public'),
    ('public/editor/.htaccess', 'public'),
    ('public/editor/index.html', 'public'),
    ('public/editor/index.php', 'public'),
    ('public/editor/js/editor.js', 'public'),
    ('public/editor/js/tutorial.js', 'public'),
    ('public/editor/css/editor.css', 'public'),
]


def ensure_remote_dir(sftp, remote_dir: str) -> None:
    current = ''
    for part in remote_dir.strip('/').split('/'):
        current += '/' + part
        try:
            sftp.stat(current)
        except FileNotFoundError:
            sftp.mkdir(current)


def remote_path(rel: str, kind: str) -> str:
    rel = rel.replace('\\', '/')
    if kind == 'public':
        return REMOTE_PUBLIC + rel[len('public'):]
    return REMOTE_ROOT + '/' + rel


def main() -> int:
    if not PASSWORD:
        print('Set LABELUP_SSH_PASSWORD', file=sys.stderr)
        return 1

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    ssh = paramiko.SSHClient()
    ssh._transport = transport

    count = 0
    for rel, kind in FILES:
        local = os.path.join(LOCAL_ROOT, rel.replace('/', os.sep))
        if not os.path.isfile(local):
            print('[SKIP]', rel)
            continue
        remote = remote_path(rel, kind)
        ensure_remote_dir(sftp, os.path.dirname(remote))
        sftp.put(local, remote)
        print('uploaded', rel, '->', remote)
        count += 1

    cmds = [
        f'php {REMOTE_ROOT}/scripts/apply_trash_columns.php',
        f'curl -sI -o /tmp/lu_ed.hdr -w "%{{http_code}}" https://www.labelup.co.kr/editor/; echo; head -n 20 /tmp/lu_ed.hdr',
        f'curl -sI -o /tmp/lu_ed2.hdr -w "%{{http_code}}" http://127.0.0.1/editor/; echo; head -n 15 /tmp/lu_ed2.hdr',
        'ls -la /home/uptube1/www/editor/index.html /home/uptube1/www/editor/index.php /home/uptube1/www/editor/.htaccess',
        'echo "=== editor htaccess ==="; cat /home/uptube1/www/editor/.htaccess',
        'echo "=== www htaccess head ==="; head -n 40 /home/uptube1/www/.htaccess',
    ]
    for cmd in cmds:
        print('$', cmd[:160])
        _, stdout, stderr = ssh.exec_command(cmd)
        out = stdout.read().decode('utf-8', 'replace')
        err = stderr.read().decode('utf-8', 'replace')
        if out.strip():
            print(out.strip()[:2000])
        if err.strip():
            print('ERR:', err.strip()[:600])

    sftp.close()
    transport.close()
    print('done', count, 'files')

    try:
        req = urllib.request.Request(
            APP_URL.rstrip('/') + '/api/health',
            headers={'User-Agent': 'LabelUp-LibraryDeploy/1.0'},
        )
        with urllib.request.urlopen(req, timeout=20) as res:
            print('health', res.status, res.read()[:200])
    except Exception as exc:
        print('health error', exc)

    try:
        req = urllib.request.Request(
            'https://www.labelup.co.kr/editor/',
            headers={'User-Agent': 'LabelUp-LibraryDeploy/1.0'},
        )
        with urllib.request.urlopen(req, timeout=20) as res:
            print('editor', res.status, res.read()[:120])
    except Exception as exc:
        print('editor error', exc)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
