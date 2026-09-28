"""관리자 대시보드에 뜰 '용지 규격 이상' 목록을 서버에서 그대로 뽑아 본다.

ShopAdminService::specGeometryIssues() 를 CLI로 호출해, 화면에 나갈 내용과
같은 결과인지 확인한다(화면 렌더링 전에 로직만 검증).
"""
from __future__ import annotations

import os
import sys

import paramiko

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelupdev'

REMOTE_SCRIPT = '/tmp/lu_spec_issue_report.php'

# 쉘이 $변수를 먼저 먹어치우므로 -r 대신 파일로 올려서 실행한다.
PHP = """<?php
require '/home/labelupdev/bootstrap.php';
$svc = new App\\Services\\ShopAdminService();
$issues = $svc->specGeometryIssues();
foreach ($issues as $i) {
    echo '#', $i['id'], ' ', $i['sku'], ' ', $i['name'], ' (상품 ', $i['products'], '개)', PHP_EOL;
    foreach ($i['messages'] as $m) {
        echo '   - ', $m, PHP_EOL;
    }
}
echo '--- 총 ', count($issues), '건', PHP_EOL;
"""


def main() -> None:
    if not PASSWORD:
        print('[FAIL] LABELUP_SSH_PASSWORD 환경변수가 없습니다.', file=sys.stderr)
        sys.exit(1)

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    sftp = paramiko.SFTPClient.from_transport(transport)
    try:
        with sftp.open(REMOTE_SCRIPT, 'w') as fh:
            fh.write(PHP)
        _, stdout, stderr = ssh.exec_command(f'cd {REMOTE_ROOT} && php {REMOTE_SCRIPT}', timeout=180)
        out = stdout.read().decode('utf-8', 'replace')
        err = stderr.read().decode('utf-8', 'replace').strip()
        print(out)
        if err:
            print(err, file=sys.stderr)
        ssh.exec_command(f'rm -f {REMOTE_SCRIPT}', timeout=60)
    finally:
        sftp.close()
        transport.close()


if __name__ == '__main__':
    main()
