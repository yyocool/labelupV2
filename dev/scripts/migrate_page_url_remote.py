#!/usr/bin/env python3
import os
import paramiko

PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
t = paramiko.Transport(('115.71.237.145', 22))
t.connect(username='root', password=PASSWORD)
ssh = paramiko.SSHClient()
ssh._transport = t

def run(cmd):
    print('===', cmd[:140])
    _, o, e = ssh.exec_command(cmd)
    out = o.read().decode('utf-8', 'replace')
    err = e.read().decode('utf-8', 'replace')
    if out.strip():
        print(out.strip()[:2000])
    if err.strip() and 'Warning' not in err:
        print('ERR', err[:400])
    return out

cnt = run(
    "mysql -uroot -p'qlqjs@Elql3#!' -N -e "
    "\"SELECT COUNT(*) FROM information_schema.COLUMNS "
    "WHERE TABLE_SCHEMA='labelup' AND TABLE_NAME='dev_scope_items' AND COLUMN_NAME='page_url'\" 2>/dev/null"
).strip()
print('COUNT', cnt)
if cnt == '0':
    run(
        "mysql -uroot -p'qlqjs@Elql3#!' labelup -e \""
        "ALTER TABLE dev_scope_items ADD COLUMN page_url VARCHAR(1000) NULL COMMENT 'page url' AFTER review_status"
        "\" 2>&1"
    )
run("mysql -uroot -p'qlqjs@Elql3#!' labelup -e \"SHOW COLUMNS FROM dev_scope_items LIKE 'page_url'\" 2>/dev/null")
t.close()
