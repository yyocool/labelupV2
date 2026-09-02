#!/usr/bin/env python3
import os
import sys
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
REMOTE = "/home/labelupdev/storage/imports/labelup-products-schedule.xlsx"
LOCAL = ROOT / "storage" / "imports" / "labelup-products-schedule.xlsx"

PW = os.environ.get("LABELUP_SSH_PASSWORD", "")
if not PW:
    print("LABELUP_SSH_PASSWORD not set", file=sys.stderr)
    sys.exit(1)

LOCAL.parent.mkdir(parents=True, exist_ok=True)
t = paramiko.Transport(("115.71.237.145", 22))
t.connect(username="root", password=PW)
sftp = paramiko.SFTPClient.from_transport(t)
sftp.get(REMOTE, str(LOCAL))
sftp.close()
t.close()
print(f"Downloaded -> {LOCAL}")
