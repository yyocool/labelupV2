"""SKU로 /api/editor/papers 의 용지 배치값을 찾아 본다.

사용: python scripts/check_paper_sku.py A308-20 A309-20
"""
from __future__ import annotations

import json
import os
import sys

import paramiko

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_HOSTNAME = 'labelupdev.gagamkorea.kr'


def main() -> None:
    skus = [s.upper() for s in sys.argv[1:]]
    if not skus:
        print('사용: python scripts/check_paper_sku.py <SKU> [...]', file=sys.stderr)
        sys.exit(1)
    if not PASSWORD:
        print('[FAIL] LABELUP_SSH_PASSWORD 환경변수가 없습니다.', file=sys.stderr)
        sys.exit(1)

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    try:
        cmd = f"curl -s http://127.0.0.1/api/editor/papers -H 'Host: {REMOTE_HOSTNAME}'"
        _, stdout, _ = ssh.exec_command(cmd, timeout=120)
        raw = stdout.read().decode('utf-8', 'replace')
    finally:
        transport.close()

    node = json.loads(raw)
    for key in ('data', 'result', 'payload'):
        if isinstance(node, dict) and key in node:
            node = node[key]
    items = node.get('items') if isinstance(node, dict) else node

    for item in items or []:
        sku = str(item.get('sku') or '').upper()
        if sku not in skus:
            continue
        print(f"{sku} | spec {item.get('specId')} | {item.get('specName')}")
        print(f"   {item.get('columnsCount')}열 x {item.get('rowsCount')}행"
              f" | 라벨 {item.get('widthMm')}x{item.get('heightMm')}mm"
              f" | 칸수 {item.get('labelsPerSheet')}"
              f" | 용지 {item.get('paperSize')}")
        print(f"   여백 좌{item.get('leftMarginMm')} 상{item.get('topMarginMm')}"
              f" | 간격 가로{item.get('hGapMm')} 세로{item.get('vGapMm')}")


if __name__ == '__main__':
    main()
