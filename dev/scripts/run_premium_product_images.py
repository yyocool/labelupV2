#!/usr/bin/env python3
"""Generate premium product thumbs and sync to remote."""
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


def run(ssh, cmd: str) -> str:
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

    subprocess.check_call([sys.executable, str(ROOT / "scripts" / "generate_premium_product_images.py")])

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=password)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    sftp = paramiko.SFTPClient.from_transport(transport)

    run(ssh, f"mkdir -p {REMOTE}/public/assets/products {REMOTE}/scripts")
    sftp.put(str(ROOT / "scripts" / "backfill_premium_product_images.php"), f"{REMOTE}/scripts/backfill_premium_product_images.php")
    sftp.put(str(ROOT / "app" / "Repositories" / "ShopRepository.php"), f"{REMOTE}/app/Repositories/ShopRepository.php")

    products = ROOT / "public" / "assets" / "products"
    count = 0
    # force-upload all regenerated premium files (webp primary + png fallbacks)
    for pattern in ("prod_*.webp", "prod_*.png", "spec_*.png"):
        for image in sorted(products.glob(pattern)):
            if image.name.startswith("spec_") and image.stat().st_size < 8000:
                continue
            remote = f"{REMOTE}/public/assets/products/{image.name}"
            sftp.put(str(image), remote)
            count += 1
            if count % 40 == 0:
                print(f"uploaded {count} files...")
    print(f"uploaded {count} image files")
    # bump mtimes so ?v= cache-bust refreshes for every browser
    run(ssh, f"find {REMOTE}/public/assets/products -name 'prod_*' -exec touch {{}} +; find {REMOTE}/public/assets/products -name 'spec_*.png' -size +8k -exec touch {{}} +")

    sftp.close()
    print(run(ssh, f"sudo -u www php8.1 {REMOTE}/scripts/backfill_premium_product_images.php"))
    transport.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
