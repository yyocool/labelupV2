#!/usr/bin/env python3
"""Upload only the published Blazor editor to PHPS (www.labelup.co.kr).

Why a separate script from deploy.py: deploy.py pushes all of dev/ and rewrites
the remote .env. When nothing but the editor changed, that is far more blast
radius than the job needs.

SFTP is always binary, so the .wasm/.br/.gz corruption that an FTP client in
ASCII mode causes cannot happen here.

index.html and _framework must land together. index.html carries the boot cache
token (a hash of blazor.boot.json), and that token is the only thing that can
invalidate the fixed-name _framework files. Ship one without the other and a
returning browser will pair cached old assemblies with the new ones, which kills
the app on the first native call the old module never had.

Password comes from LABELUP_SSH_PASSWORD. Nothing is written to the repo.
"""
from __future__ import annotations

import os
import posixpath
import stat
import sys
import time

import paramiko

from remote_config import HOST, PASSWORD, PUBLIC_HOST, REMOTE_PUBLIC, USER

LOCAL_EDITOR = os.path.abspath(
    os.path.join(os.path.dirname(__file__), "..", "public", "editor")
)
REMOTE_EDITOR = REMOTE_PUBLIC + "/editor"

# PHPS 는 /editor/ 안에 .htaccess 가 있으면 편집기가 500 이 된다. 서버 쪽 설정을
# 건드리지 않도록 올리지도, 지우지도 않는다.
PRESERVE = {".htaccess", ".user.ini"}


def walk_local() -> list[tuple[str, str]]:
    """Return (local_path, remote_rel) for every file to upload."""
    out = []
    for root, _dirs, files in os.walk(LOCAL_EDITOR):
        for name in files:
            if name in PRESERVE:
                continue
            local = os.path.join(root, name)
            rel = os.path.relpath(local, LOCAL_EDITOR).replace("\\", "/")
            out.append((local, rel))
    out.sort(key=lambda pair: pair[1])
    return out


def ensure_dirs(sftp: paramiko.SFTPClient, remote_dirs: set[str]) -> None:
    for d in sorted(remote_dirs, key=lambda p: p.count("/")):
        try:
            sftp.stat(d)
        except FileNotFoundError:
            sftp.mkdir(d)


def remote_sizes(sftp: paramiko.SFTPClient, base: str) -> dict[str, int]:
    """Map remote relative path -> size, for verification and stale detection."""
    found: dict[str, int] = {}

    def walk(path: str) -> None:
        try:
            entries = sftp.listdir_attr(path)
        except FileNotFoundError:
            return
        for e in entries:
            full = posixpath.join(path, e.filename)
            if stat.S_ISDIR(e.st_mode or 0):
                walk(full)
            else:
                found[posixpath.relpath(full, base)] = e.st_size or 0

    walk(base)
    return found


def main() -> int:
    if not PASSWORD:
        print("Set LABELUP_SSH_PASSWORD environment variable", file=sys.stderr)
        return 1
    if not os.path.isdir(LOCAL_EDITOR):
        print(f"Missing published editor: {LOCAL_EDITOR}", file=sys.stderr)
        print("Run: python scripts/publish_editor.py", file=sys.stderr)
        return 1

    files = walk_local()
    index = os.path.join(LOCAL_EDITOR, "index.html")
    boot = os.path.join(LOCAL_EDITOR, "_framework", "blazor.boot.json")
    for required in (index, boot, os.path.join(LOCAL_EDITOR, "index.php")):
        if not os.path.isfile(required):
            print(f"Missing required file: {required}", file=sys.stderr)
            return 1

    total = sum(os.path.getsize(p) for p, _ in files)
    print(f"{USER}@{HOST}  ->  {REMOTE_EDITOR}   ({PUBLIC_HOST})")
    print(f"{len(files)} files, {total / 1048576:.1f} MB")

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    if sftp is None:
        print("Could not open SFTP channel", file=sys.stderr)
        return 1

    try:
        dirs = {REMOTE_EDITOR}
        for _local, rel in files:
            parent = posixpath.dirname(rel)
            while parent:
                dirs.add(REMOTE_EDITOR + "/" + parent)
                parent = posixpath.dirname(parent)
        ensure_dirs(sftp, dirs)

        sent = 0
        retried = 0
        started = time.time()
        for n, (local, rel) in enumerate(files, 1):
            # This host drops SFTP connections under load and has silently left a
            # 1 KB-short LabelUp.Editor.wasm behind. A truncated assembly does not
            # fail the transfer, it just kills the app at runtime, so check the
            # size right after every put and retry rather than trusting the write.
            size = os.path.getsize(local)
            target = REMOTE_EDITOR + "/" + rel
            for attempt in range(1, 4):
                sftp.put(local, target)
                if sftp.stat(target).st_size == size:
                    break
                retried += 1
                print(f"  retry {attempt}/3  {rel}")
                time.sleep(1)
            else:
                raise RuntimeError(f"{rel}: size never matched after 3 tries")
            sent += size
            if n % 25 == 0 or n == len(files):
                pct = sent / total * 100 if total else 100
                print(f"  {n}/{len(files)}  {pct:5.1f}%  {time.time() - started:5.0f}s")
        if retried:
            print(f"재전송한 파일 {retried}개")

        print("Verifying sizes")
        remote = remote_sizes(sftp, REMOTE_EDITOR)
        bad = [
            rel for local, rel in files
            if remote.get(rel) != os.path.getsize(local)
        ]
        stale = sorted(set(remote) - {rel for _l, rel in files} - PRESERVE)

        if bad:
            print(f"SIZE MISMATCH on {len(bad)} files:", file=sys.stderr)
            for rel in bad[:20]:
                print(f"  {rel}", file=sys.stderr)
            return 1
        print(f"  all {len(files)} files match")
        if stale:
            print(f"  남은 예전 파일 {len(stale)}개 (지우지 않음, blazor.boot.json 이")
            print("  가리키지 않으므로 해롭지는 않다):")
            for rel in stale[:20]:
                print(f"    {rel}")
    finally:
        sftp.close()
        transport.close()

    print(f"Done. https://{PUBLIC_HOST}/editor/")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
