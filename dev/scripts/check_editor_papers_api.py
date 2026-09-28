"""편집기가 실제로 받는 /api/editor/papers 응답에서 특정 규격의 행·열을 확인한다.

DB에만 값이 있고 API 응답에 안 실리면 편집기는 여전히 추정하게 되므로,
DB가 아니라 API 쪽에서 확인한다.
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
TARGET_IDS = {13, 48, 50, 54, 62}


def main() -> None:
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

    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        print('[FAIL] JSON 파싱 실패. 응답 앞부분:', raw[:400], file=sys.stderr)
        sys.exit(1)

    # jsonSuccess 는 {ok, data:{items:[...]}} 로 감싸 내려준다.
    node = data
    for key in ('data', 'result', 'payload'):
        if isinstance(node, dict) and key in node:
            node = node[key]
    items = node.get('items') if isinstance(node, dict) else node
    if not isinstance(items, list):
        print('[FAIL] items 를 찾지 못했습니다. 응답 키:',
              list(data.keys()) if isinstance(data, dict) else type(data), file=sys.stderr)
        sys.exit(1)

    found = 0
    missing = 0
    for item in items:
        if not isinstance(item, dict):
            continue
        spec_id = item.get('specId') or item.get('id')
        cols, rows = item.get('columnsCount'), item.get('rowsCount')
        if cols in (None, 0) or rows in (None, 0):
            missing += 1
            print(f"[비어있음] spec {spec_id} | {item.get('specName') or item.get('name')} "
                  f"| 상품 {item.get('id')} {item.get('sku')}")
        if spec_id in TARGET_IDS:
            found += 1
            print(f"spec {spec_id:>3} | {item.get('specName') or item.get('name')} | "
                  f"{cols}x{rows} | paper={item.get('paperSize')} "
                  f"| margin={item.get('leftMarginMm')},{item.get('topMarginMm')} "
                  f"| gap={item.get('hGapMm')},{item.get('vGapMm')}")

    print(f'--- 응답 항목 {len(items)}건, 대상 {found}건 확인, 행·열 비어 있는 항목 {missing}건')


if __name__ == '__main__':
    main()
