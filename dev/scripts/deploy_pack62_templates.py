#!/usr/bin/env python3
"""Upload pack62 template assets + seed on remote."""
from __future__ import annotations

import os
import sys
from pathlib import Path

import paramiko

HOST = "115.71.237.145"
USER = "root"
PASSWORD = os.environ.get("LABELUP_SSH_PASSWORD", "")
REMOTE = "/home/labelupdev"
LOCAL = Path(__file__).resolve().parents[1]

if not PASSWORD:
    print("LABELUP_SSH_PASSWORD required", file=sys.stderr)
    sys.exit(1)


def ensure(sftp, remote_dir: str) -> None:
    parts = remote_dir.strip("/").split("/")
    cur = ""
    for part in parts:
        cur += "/" + part
        try:
            sftp.stat(cur)
        except FileNotFoundError:
            sftp.mkdir(cur)


def main() -> None:
    files: list[tuple[Path, str]] = []
    for rel in [
        "app/Services/LabelTemplateService.php",
        "app/Services/LabelTemplatePack60SeedService.php",
        "scripts/generate_template_pack62.py",
        "scripts/lib_pack60_arts.py",
        "storage/imports/template_pack62_manifest.json",
        "storage/imports/pack62_reference.png",
    ]:
        local = LOCAL / rel
        if local.is_file():
            files.append((local, f"{REMOTE}/{rel}"))

    art_dir = LOCAL / "public" / "assets" / "templates" / "pack62"
    for p in sorted(art_dir.glob("*.png")):
        files.append((p, f"{REMOTE}/public/assets/templates/pack62/{p.name}"))

    print("files", len(files))
    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(t)
    assert sftp is not None
    for local, remote in files:
        ensure(sftp, str(Path(remote).as_posix().rsplit("/", 1)[0]))
        sftp.put(str(local), remote)
    print("upload done")
    sftp.close()
    t.close()

    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASSWORD)
    for cmd in [
        "cd /home/labelupdev && php scripts/seed_templates.php --force",
        "ls /home/labelupdev/public/assets/templates/pack62/*_art.png 2>/dev/null | wc -l",
    ]:
        print("$", cmd)
        _, o, e = ssh.exec_command(cmd)
        out = o.read().decode("utf-8", "replace").strip()
        err = e.read().decode("utf-8", "replace").strip()
        if out:
            print(out)
        if err:
            print(err)
    ssh.close()


if __name__ == "__main__":
    main()
