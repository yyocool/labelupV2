#!/usr/bin/env python3
"""Upload only public/editor to PHPS remote (www/editor).

예전 labelupdev 서버가 아니라 서비스 사이트(uptube1 / www)로 올린다.
_framework 잔존 파일이 boot.json 과 어긋나지 않도록 먼저 비운다.
"""
import os
import sys

import paramiko

from remote_config import HOST, PASSWORD, REMOTE_PUBLIC, USER

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

LOCAL = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "public", "editor"))
REMOTE = REMOTE_PUBLIC + "/editor"


def ensure(sftp, remote_dir: str) -> None:
    parts = remote_dir.strip("/").split("/")
    cur = ""
    for part in parts:
        cur += "/" + part
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)


def main() -> int:
    if not PASSWORD:
        print("Set LABELUP_SSH_PASSWORD", file=sys.stderr)
        return 1
    if not os.path.isdir(LOCAL):
        print("Missing local editor:", LOCAL, file=sys.stderr)
        return 1

    print(f"Connecting {USER}@{HOST}")
    print(f"Local  {LOCAL}")
    print(f"Remote {REMOTE}")

    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = t

    # Drop stale fingerprinted framework files so boot.json cannot reference missing assets.
    wipe = (
        f"rm -rf {REMOTE}/_framework {REMOTE}/css {REMOTE}/js {REMOTE}/img "
        f"{REMOTE}/fonts {REMOTE}/_content 2>/dev/null; "
        f"mkdir -p {REMOTE}/_framework && echo wiped_ok"
    )
    _, out, err = ssh.exec_command(wipe)
    print(out.read().decode("utf-8", "replace").strip())
    e = err.read().decode("utf-8", "replace").strip()
    if e:
        print("wipe_err", e[:400])

    sftp = paramiko.SFTPClient.from_transport(t)
    count = 0
    for root, _dirs, files in os.walk(LOCAL):
        rel = os.path.relpath(root, LOCAL).replace("\\", "/")
        rdir = REMOTE if rel == "." else f"{REMOTE}/{rel}"
        ensure(sftp, rdir)
        for name in files:
            # PHPS: /editor/ 안 .htaccess 가 있으면 편집기 500
            if name == ".htaccess":
                print("skip preserve", f"{rdir}/{name}")
                continue
            lp = os.path.join(root, name)
            rp = f"{rdir}/{name}"
            sftp.put(lp, rp)
            count += 1
            if count % 50 == 0:
                print("uploaded", count)

    checks = (
        "index.html",
        "index.php",
        "_framework/blazor.boot.json",
        "_framework/LabelUp.Editor.wasm",
        "js/editor.js",
        "css/editor.css",
    )
    for rel in checks:
        lp = os.path.join(LOCAL, *rel.split("/"))
        rp = f"{REMOTE}/{rel}"
        local_size = os.path.getsize(lp) if os.path.isfile(lp) else -1
        try:
            remote_size = sftp.stat(rp).st_size
        except FileNotFoundError:
            remote_size = -1
        ok = "OK" if local_size == remote_size and local_size >= 0 else "MISMATCH"
        print(f"{ok} {rel} local={local_size} remote={remote_size}")

    sftp.close()
    t.close()
    print("done", count, "files →", REMOTE)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
