#!/usr/bin/env python3
"""Export specs, generate images, upload and backfill on remote dev."""
from __future__ import annotations

import os
import subprocess
import sys
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
REMOTE = "/home/labelupdev"
HOST = "115.71.237.145"
USER = "root"


def run_remote(ssh, cmd: str) -> str:
    _, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    if err.strip():
        print(err, file=sys.stderr)
    return out


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    password = os.environ.get("LABELUP_SSH_PASSWORD", "")
    if not password:
        print("LABELUP_SSH_PASSWORD not set", file=sys.stderr)
        return 1

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=password)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    sftp = paramiko.SFTPClient.from_transport(transport)

    for remote_dir in (f"{REMOTE}/storage/imports", f"{REMOTE}/scripts", f"{REMOTE}/public/assets/specs"):
        run_remote(ssh, f"mkdir -p {remote_dir}")

    uploads = [
        (ROOT / "scripts" / "export_specs.php", f"{REMOTE}/scripts/export_specs.php"),
        (ROOT / "scripts" / "backfill_spec_images.php", f"{REMOTE}/scripts/backfill_spec_images.php"),
        (ROOT / "app" / "Repositories" / "ShopRepository.php", f"{REMOTE}/app/Repositories/ShopRepository.php"),
    ]
    for local, remote in uploads:
        sftp.put(str(local), remote)

    print(run_remote(ssh, f"sudo -u www php8.1 {REMOTE}/scripts/export_specs.php"))
    sftp.get(f"{REMOTE}/storage/imports/specs_export.json", str(ROOT / "storage" / "imports" / "specs_export.json"))

    subprocess.check_call([sys.executable, str(ROOT / "scripts" / "generate_spec_images.py")])

    specs_dir = ROOT / "public" / "assets" / "specs"
    for image in specs_dir.glob("spec_*.webp"):
        sftp.put(str(image), f"{REMOTE}/public/assets/specs/{image.name}")
        print(f"uploaded {image.name}")

    sftp.close()
    print(run_remote(ssh, "curl -s -X POST http://127.0.0.1/api/system/migrate -H 'Host: labelupdev.gagamkorea.kr'"))
    print(run_remote(ssh, f"sudo -u www php8.1 {REMOTE}/scripts/backfill_spec_images.php"))
    transport.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
