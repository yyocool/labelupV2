#!/usr/bin/env python3
"""QR 템플릿을 그룹No. 단위로 바꾼 파일을 PHPS 서비스 서버에 반영."""
from __future__ import annotations

import os
import sys
import urllib.request

import paramiko

from remote_config import HOST, USER, PASSWORD, REMOTE_ROOT, REMOTE_PUBLIC, APP_URL

sys.stdout.reconfigure(encoding='utf-8', errors='replace')

LOCAL_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

FILES = [
    ('app/Services/QrCouponAdminService.php', 'root'),
    ('app/Controllers/Api/QrCouponAdminApiController.php', 'root'),
    ('views/admin/qr-coupons.php', 'root'),
    ('public/js/qr-print-template.js', 'public'),
    ('public/js/qr-label-print.js', 'public'),
    ('public/css/admin.css', 'public'),
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
        print('Set LABELUP_SSH_PASSWORD environment variable', file=sys.stderr)
        return 1

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)

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

    sftp.close()
    transport.close()
    print('done', count, 'files')

    try:
        req = urllib.request.Request(
            APP_URL.rstrip('/') + '/api/health',
            headers={'User-Agent': 'LabelUp-QrTplDeploy/1.0'},
        )
        with urllib.request.urlopen(req, timeout=20) as res:
            print('health', res.status, res.read()[:160])
    except Exception as exc:
        print('health error', exc)

    try:
        req = urllib.request.Request(
            'https://www.labelup.co.kr/admin/qr-coupons',
            headers={'User-Agent': 'LabelUp-QrTplDeploy/1.0'},
        )
        with urllib.request.urlopen(req, timeout=20) as res:
            print('qr-coupons', res.status)
    except Exception as exc:
        # 로그인 필요면 302/401 가능
        print('qr-coupons', exc)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
