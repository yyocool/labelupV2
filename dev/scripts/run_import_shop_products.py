#!/usr/bin/env python3
"""Parse Excel and import shop products on remote dev server."""
from __future__ import annotations

import json
import os
import subprocess
import sys
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
REMOTE_ROOT = "/home/labelupdev"
HOST = "115.71.237.145"
USER = "root"


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

    parse_script = ROOT / "scripts" / "parse_shop_products.py"
    subprocess.check_call([sys.executable, str(parse_script)])

    json_path = ROOT / "storage" / "imports" / "products_import.json"
    if not json_path.exists():
        print("products_import.json missing", file=sys.stderr)
        return 1

    password = os.environ.get("LABELUP_SSH_PASSWORD", "")
    if not password:
        print("LABELUP_SSH_PASSWORD not set", file=sys.stderr)
        return 1

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=password)

    ssh = paramiko.SSHClient()
    ssh._transport = transport
    _, stdout, _ = ssh.exec_command(f"mkdir -p {REMOTE_ROOT}/storage/imports {REMOTE_ROOT}/scripts")
    stdout.channel.recv_exit_status()

    sftp = paramiko.SFTPClient.from_transport(transport)

    remote_json = f"{REMOTE_ROOT}/storage/imports/products_import.json"
    remote_php = f"{REMOTE_ROOT}/scripts/import_shop_products.php"

    for local, remote in [
        (json_path, remote_json),
        (ROOT / "scripts" / "import_shop_products.php", remote_php),
        (ROOT / "app" / "Services" / "ShopProductImportService.php", f"{REMOTE_ROOT}/app/Services/ShopProductImportService.php"),
        (ROOT / "app" / "Repositories" / "ShopRepository.php", f"{REMOTE_ROOT}/app/Repositories/ShopRepository.php"),
    ]:
        sftp.put(str(local), remote)
        print(f"uploaded {local.name}")

    sftp.close()

    cmd = f"sudo -u www php8.1 {remote_php} {remote_json}"
    _, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    print(out)
    if err.strip():
        print(err, file=sys.stderr)
    transport.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
