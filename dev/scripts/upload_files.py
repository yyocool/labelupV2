#!/usr/bin/env python3
"""고른 파일 몇 개만 실서버(PHPS)에 올린다.

deploy.py 는 dev/ 를 통째로 올리고 원격 .env 까지 새로 쓴다. 한두 파일을 고치고
그걸 돌리면 작업 복사본에 들어 있는 에디터 빌드(dev/public/editor, 338개 파일)까지
함께 올라가, 다른 사람이 올려 둔 에디터를 통째로 덮어쓴다. 그 사고가 한 번 있었다.
그래서 급한 수정은 이 스크립트로 집어서 올린다.

쓰는 법 (dev/ 에서):
    python scripts/upload_files.py public/js/foo.js app/Services/Bar.php

경로는 dev/ 기준 상대경로다. public/ 아래는 원격 www/ 로 간다(deploy.py 와 같은 규칙).
"""
import os
import sys
import time

import paramiko

from remote_config import HOST, USER, PASSWORD, REMOTE_ROOT, REMOTE_PUBLIC

LOCAL_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))

# PHPS 호스팅용 설정은 서버 것을 유지한다. 로컬 파일로 덮으면 open_basedir 이 깨지고,
# /editor/ 아래 .htaccess 가 생기면 편집기가 500 을 낸다.
PRESERVE = {
    REMOTE_PUBLIC + '/.htaccess',
    REMOTE_PUBLIC + '/.user.ini',
    REMOTE_PUBLIC + '/editor/.htaccess',
    REMOTE_ROOT + '/.env',
}

# 서버에서 쓰이지 않는 폴더. 실수로 올려도 막는다.
LOCAL_ONLY_DIRS = ('scripts/', 'editor-src/')


def remote_target(rel: str) -> str:
    """dev/ 기준 상대경로를 원격 경로로 바꾼다. public/ → www/."""
    rel = rel.replace('\\', '/').lstrip('/')
    if rel == 'public' or rel.startswith('public/'):
        return REMOTE_PUBLIC + rel[len('public'):]
    return REMOTE_ROOT + '/' + rel


def ensure_remote_dir(sftp, remote_dir: str) -> None:
    cur = ''
    for part in remote_dir.strip('/').split('/'):
        cur += '/' + part
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)


def resolve(args: list[str]) -> list[tuple[str, str, str]]:
    """(로컬경로, dev 기준 상대경로, 원격경로) 목록을 만든다. 하나라도 틀리면 멈춘다."""
    out = []
    bad = 0
    for raw in args:
        rel = os.path.relpath(os.path.abspath(raw), LOCAL_ROOT).replace('\\', '/')
        local = os.path.join(LOCAL_ROOT, rel)
        if rel.startswith('..'):
            print(f"{raw}: dev/ 밖이라 올릴 수 없다", file=sys.stderr)
            bad += 1
            continue
        if not os.path.isfile(local):
            print(f"{rel}: 파일이 없다", file=sys.stderr)
            bad += 1
            continue
        if rel.startswith(LOCAL_ONLY_DIRS):
            print(f"{rel}: 서버에서 쓰지 않는 폴더다", file=sys.stderr)
            bad += 1
            continue
        target = remote_target(rel)
        if target in PRESERVE:
            print(f"{rel}: 서버 것을 유지해야 하는 파일이다", file=sys.stderr)
            bad += 1
            continue
        out.append((local, rel, target))
    if bad:
        sys.exit(1)
    return out


def main() -> None:
    args = sys.argv[1:]
    if not args:
        print(__doc__, file=sys.stderr)
        sys.exit(2)
    if not PASSWORD:
        print('Set LABELUP_SSH_PASSWORD environment variable', file=sys.stderr)
        sys.exit(1)

    files = resolve(args)
    print(f'Connecting to {USER}@{HOST}')
    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    try:
        for local, rel, target in files:
            size = os.path.getsize(local)
            ensure_remote_dir(sftp, os.path.dirname(target))
            # 이 호스트는 전송을 끊고도 짧은 파일을 남겨 놓은 적이 있다. 잘린 PHP 는
            # 전송 실패로 잡히지 않고 그냥 사이트를 죽이므로, 올린 뒤 크기를 확인한다.
            for attempt in range(1, 4):
                sftp.put(local, target)
                if sftp.stat(target).st_size == size:
                    break
                print(f'  retry {attempt}/3  {rel}')
                time.sleep(1)
            else:
                raise RuntimeError(f'{rel}: 크기가 3번 모두 맞지 않았다')
            print(f'  {rel}  →  {target}  ({size:,} bytes)')
    finally:
        sftp.close()
        transport.close()
    print(f'올린 파일 {len(files)}개')


if __name__ == '__main__':
    main()
