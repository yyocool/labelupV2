#!/usr/bin/env python3
"""Extract Excel spec images and link them as product thumbnails on remote dev."""
from __future__ import annotations

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

    extract_script = ROOT / "scripts" / "extract_product_images_from_excel.py"
    xlsx_arg = sys.argv[1:] if len(sys.argv) > 1 else []
    cmd = [sys.executable, str(extract_script), *xlsx_arg]
    subprocess.check_call(cmd)

    manifest = ROOT / "storage" / "imports" / "product_images_manifest.json"
    products_dir = ROOT / "public" / "assets" / "products"
    if not manifest.exists():
        print("product_images_manifest.json missing", file=sys.stderr)
        return 1

    password = os.environ.get("LABELUP_SSH_PASSWORD", "")
    if not password:
        print("LABELUP_SSH_PASSWORD not set", file=sys.stderr)
        return 1

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=password)
    ssh = paramiko.SSHClient()
    ssh._transport = transport

    for remote_dir in (
        f"{REMOTE_ROOT}/storage/imports",
        f"{REMOTE_ROOT}/scripts",
        f"{REMOTE_ROOT}/public/assets/products",
        f"{REMOTE_ROOT}/app/Repositories",
    ):
        _, stdout, _ = ssh.exec_command(f"mkdir -p {remote_dir}")
        stdout.channel.recv_exit_status()

    sftp = paramiko.SFTPClient.from_transport(transport)

    uploads = [
        (manifest, f"{REMOTE_ROOT}/storage/imports/product_images_manifest.json"),
        (ROOT / "scripts" / "import_product_images.php", f"{REMOTE_ROOT}/scripts/import_product_images.php"),
        (ROOT / "app" / "Repositories" / "ShopRepository.php", f"{REMOTE_ROOT}/app/Repositories/ShopRepository.php"),
    ]
    for local, remote in uploads:
        sftp.put(str(local), remote)
        print(f"uploaded {local.name}")

    for image in products_dir.glob("spec_*"):
        remote = f"{REMOTE_ROOT}/public/assets/products/{image.name}"
        sftp.put(str(image), remote)
        print(f"uploaded {image.name}")

    sftp.close()

    php_cmd = f"sudo -u www php8.1 {REMOTE_ROOT}/scripts/import_product_images.php {REMOTE_ROOT}/storage/imports/product_images_manifest.json"
    _, stdout, stderr = ssh.exec_command(php_cmd)
    print(stdout.read().decode("utf-8", "replace"))
    err = stderr.read().decode("utf-8", "replace")
    if err.strip():
        print(err, file=sys.stderr)

    transport.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
