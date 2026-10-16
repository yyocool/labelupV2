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
# 0 = 크기 제한 없음 (용량 증설 후 전체 업로드)
MAX_UPLOAD_BYTES = 0


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
    # 매니페스트 + 로컬에만 있는 pdv_*.svg 전부 업로드
    names: set[str] = set()
    for it in items:
        name = Path(str(it.get("image_path") or "")).name
        if name.startswith("pdv_"):
            names.add(name)
    if LOCAL_CLIP.is_dir():
        for p in LOCAL_CLIP.glob("pdv_*.svg"):
            names.add(p.name)
    print(f"manifest items: {len(items)} local_pdv_targets: {len(names)}")

    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    # 대용량 전송: 윈도우·대기 시간 여유
    t.set_keepalive(30)
    sftp = paramiko.SFTPClient.from_transport(t)

    remote_clip = f"{REMOTE_PUBLIC}/assets/cliparts"
    ensure_dir(sftp, remote_clip)
    uploaded = 0
    upload_fail = 0
    skipped_same = 0
    checked = 0
    total = len(names)
    for name in sorted(names):
        local = LOCAL_CLIP / name
        if not local.is_file():
            continue
        size = local.stat().st_size
        if size < 50:
            continue
        if MAX_UPLOAD_BYTES > 0 and size > MAX_UPLOAD_BYTES:
            continue
        checked += 1
        remote = f"{remote_clip}/{name}"
        try:
            st = sftp.stat(remote)
            if st.st_size == size:
                skipped_same += 1
                if checked % 500 == 0:
                    print(
                        f"progress {checked}/{total} uploaded={uploaded} "
                        f"skip_same={skipped_same} fail={upload_fail}",
                        flush=True,
                    )
                continue
        except FileNotFoundError:
            pass
        ok = False
        last_err = ""
        for _attempt in range(3):
            try:
                sftp.put(str(local), remote)
                st2 = sftp.stat(remote)
                if st2.st_size == size:
                    ok = True
                    break
                last_err = f"size_mismatch local={size} remote={st2.st_size}"
            except OSError as ex:
                last_err = str(ex)
                # 할당량 초과 시 즉시 중단 (재시도해도 동일)
                if "quota" in last_err.lower() or "Disk quota" in last_err:
                    print(f"quota exceeded after uploaded={uploaded}: {last_err}", file=sys.stderr)
                    upload_fail += 1
                    sftp.close()
                    t.close()
                    return 2
                continue
        if not ok:
            upload_fail += 1
            if upload_fail <= 15 or upload_fail % 50 == 0:
                print(f"FAIL {name} size={size} err={last_err}", flush=True)
            continue
        uploaded += 1
        if uploaded % 50 == 0 or checked % 500 == 0:
            print(
                f"progress {checked}/{total} uploaded={uploaded} "
                f"skip_same={skipped_same} fail={upload_fail}",
                flush=True,
            )

    ensure_dir(sftp, f"{REMOTE_ROOT}/storage/imports")
    sftp.put(str(MANIFEST), f"{REMOTE_ROOT}/storage/imports/clipart_pdv_manifest.json")
    sftp.put(str(SEED_PHP), "/tmp/seed_pdv.php")
    sftp.close()

    ssh = paramiko.SSHClient()
    ssh._transport = t
    # PHP 5.2 CLI memory 한도 상향 + 배치 시드
    _, stdout, stderr = ssh.exec_command(
        "php -d memory_limit=512M /tmp/seed_pdv.php",
        timeout=1800,
    )
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    print(
        f"uploaded_new={uploaded} skip_same={skipped_same} upload_fail={upload_fail}",
        flush=True,
    )
    print(out.strip() or "(no stdout)")
    if err.strip():
        print("stderr:", err[:800])
    t.close()
    return 0 if upload_fail == 0 else 1


if __name__ == "__main__":
    raise SystemExit(main())
