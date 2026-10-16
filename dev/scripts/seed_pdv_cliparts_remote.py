#!/usr/bin/env python3
"""Upload PDV clipart SVGs + seed via remote PHP 5.2 mysqli."""
from __future__ import annotations

import json
import sys
from pathlib import Path

import paramiko

sys.path.insert(0, str(Path(__file__).resolve().parent))
from remote_config import HOST, PASSWORD, REMOTE_PUBLIC, REMOTE_ROOT, USER

ROOT = Path(__file__).resolve().parents[1]
MANIFEST = ROOT / "storage" / "imports" / "clipart_pdv_manifest.json"
LOCAL_CLIP = ROOT / "public" / "assets" / "cliparts"
SEED_PHP = ROOT / "scripts" / "_remote_seed_pdv.php"


def ensure_dir(sftp, remote_dir: str) -> None:
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
    if not MANIFEST.exists():
        print("Missing manifest", MANIFEST, file=sys.stderr)
        return 1

    data = json.loads(MANIFEST.read_text(encoding="utf-8"))
    items = [it for it in data.get("items", []) if isinstance(it, dict)]
    print(f"manifest items: {len(items)}")

    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(t)

    remote_clip = f"{REMOTE_PUBLIC}/assets/cliparts"
    ensure_dir(sftp, remote_clip)
    uploaded = 0
    upload_fail = 0
    for it in items:
        name = Path(str(it.get("image_path") or "")).name
        if not name.startswith("pdv_"):
            continue
        local = LOCAL_CLIP / name
        if not local.is_file() or local.stat().st_size < 50:
            continue
        remote = f"{remote_clip}/{name}"
        try:
            st = sftp.stat(remote)
            if st.st_size == local.stat().st_size:
                continue
        except FileNotFoundError:
            pass
        ok = False
        for _attempt in range(3):
            try:
                sftp.put(str(local), remote)
                st2 = sftp.stat(remote)
                if st2.st_size == local.stat().st_size:
                    ok = True
                    break
            except OSError:
                continue
        if not ok:
            upload_fail += 1
            continue
        uploaded += 1
        if uploaded % 100 == 0:
            print(f"uploaded {uploaded}")

    ensure_dir(sftp, f"{REMOTE_ROOT}/storage/imports")
    sftp.put(str(MANIFEST), f"{REMOTE_ROOT}/storage/imports/clipart_pdv_manifest.json")
    sftp.put(str(SEED_PHP), "/tmp/seed_pdv.php")
    sftp.close()

    ssh = paramiko.SSHClient()
    ssh._transport = t
    _, stdout, stderr = ssh.exec_command("php /tmp/seed_pdv.php", timeout=900)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    print(f"uploaded_new={uploaded} upload_fail={upload_fail}")
    print(out.strip() or "(no stdout)")
    if err.strip():
        print("stderr:", err[:800])
    t.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
