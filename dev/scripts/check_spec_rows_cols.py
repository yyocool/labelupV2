"""격자(행·열) 정보가 비어 있는 규격을 서버 DB에서 그대로 읽어 온다.

미리보기·인쇄는 label_specs 의 columns_count/rows_count 를 그대로 쓰기 때문에
값이 비면 편집기가 추정하게 된다. 어떤 규격이 비어 있고, 같은 행의 다른 값
(labels_per_sheet·크기·용지)으로 행·열을 되계산할 수 있는지 확인하는 용도다.
"""
from __future__ import annotations

import os
import sys

import paramiko

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelupdev'


def run(ssh: paramiko.SSHClient, cmd: str) -> str:
    _, stdout, stderr = ssh.exec_command(cmd, timeout=120)
    out = stdout.read().decode('utf-8', 'replace')
    err = stderr.read().decode('utf-8', 'replace').strip()
    if err and 'Warning' not in err:
        print(err, file=sys.stderr)
    return out


def db_creds(ssh: paramiko.SSHClient) -> dict[str, str]:
    raw = run(ssh, f'cat {REMOTE_ROOT}/.env')
    creds: dict[str, str] = {}
    for line in raw.splitlines():
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        creds[key.strip()] = value.strip().strip('"').strip("'")
    return {
        'host': creds.get('DB_HOST') or '127.0.0.1',
        'name': creds.get('DB_DATABASE') or creds.get('DB_NAME') or '',
        'user': creds.get('DB_USERNAME') or creds.get('DB_USER') or '',
        'pass': creds.get('DB_PASSWORD') or creds.get('DB_PASS') or '',
    }


QUERIES = {
    '격자 비어 있는 규격(상품 연결 포함)': """
        SELECT s.id, s.name, s.paper_size, s.width_mm, s.height_mm, s.labels_per_sheet,
               s.columns_count, s.rows_count, s.top_margin_mm, s.left_margin_mm,
               s.h_gap_mm, s.v_gap_mm, COUNT(p.id) AS products
        FROM label_specs s
        LEFT JOIN shop_products p ON p.spec_id = s.id
        WHERE s.columns_count IS NULL OR s.rows_count IS NULL
           OR s.columns_count = 0 OR s.rows_count = 0
        GROUP BY s.id
        ORDER BY products DESC, s.id
    """,
    '전체 채움 현황': """
        SELECT COUNT(*) AS total,
               SUM(columns_count IS NOT NULL AND rows_count IS NOT NULL
                   AND columns_count > 0 AND rows_count > 0) AS filled
        FROM label_specs
    """,
    '해당 규격에 걸린 상품': """
        SELECT p.id, p.spec_id, p.sku, p.name, s.name AS spec_name
        FROM shop_products p JOIN label_specs s ON s.id = p.spec_id
        WHERE p.spec_id IN (13, 48, 50, 62)
    """,
    '같은 크기 형제 규격(42x107 / 그 밖 9칸)': """
        SELECT id, name, paper_size, width_mm, height_mm, labels_per_sheet,
               columns_count, rows_count, top_margin_mm, left_margin_mm, h_gap_mm, v_gap_mm
        FROM label_specs
        WHERE (width_mm = 42 AND height_mm = 107) OR labels_per_sheet = 9
        ORDER BY id
    """,
    '칸수와 행x열이 어긋나는 규격': """
        SELECT id, name, labels_per_sheet, columns_count, rows_count,
               left_margin_mm, top_margin_mm, h_gap_mm, v_gap_mm, width_mm, height_mm
        FROM label_specs
        WHERE columns_count > 0 AND rows_count > 0 AND labels_per_sheet IS NOT NULL
          AND columns_count * rows_count <> labels_per_sheet
        ORDER BY id
    """,
    '가로/세로가 A4를 넘치는 규격': """
        SELECT id, name, width_mm, height_mm, columns_count, rows_count,
               left_margin_mm, top_margin_mm, h_gap_mm, v_gap_mm,
               ROUND(COALESCE(left_margin_mm,0) + columns_count * width_mm
                     + (columns_count - 1) * COALESCE(h_gap_mm,0), 2) AS need_w,
               ROUND(COALESCE(top_margin_mm,0) + rows_count * height_mm
                     + (rows_count - 1) * COALESCE(v_gap_mm,0), 2) AS need_h
        FROM label_specs
        WHERE columns_count > 0 AND rows_count > 0
        HAVING need_w > 210.5 OR need_h > 297.5
        ORDER BY id
    """,
    '비슷한 크기의 이미 채워진 규격(관례 확인)': """
        SELECT id, name, paper_size, width_mm, height_mm, labels_per_sheet,
               columns_count, rows_count, top_margin_mm, left_margin_mm, h_gap_mm, v_gap_mm
        FROM label_specs
        WHERE columns_count IS NOT NULL AND rows_count IS NOT NULL
          AND (labels_per_sheet IN (1, 9, 16, 60)
               OR (width_mm BETWEEN 36 AND 44) OR (width_mm BETWEEN 96 AND 102))
        ORDER BY labels_per_sheet, id
    """,
}


def main() -> None:
    if not PASSWORD:
        print('[FAIL] LABELUP_SSH_PASSWORD 환경변수가 없습니다.', file=sys.stderr)
        sys.exit(1)

    transport = paramiko.Transport((HOST, 22))
    transport.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = transport
    try:
        creds = db_creds(ssh)
        if not creds['name'] or not creds['user']:
            print('[FAIL] 원격 .env 에서 DB 접속 정보를 읽지 못했습니다.', file=sys.stderr)
            sys.exit(1)
        mysql = (
            f"mysql --default-character-set=utf8mb4 -h{creds['host']} "
            f"-u{creds['user']} -p'{creds['pass']}' {creds['name']} -e"
        )
        for title, sql in QUERIES.items():
            print(f'=== {title}')
            one_line = ' '.join(sql.split())
            print(run(ssh, f'{mysql} "{one_line}"'))
    finally:
        transport.close()


if __name__ == '__main__':
    main()
