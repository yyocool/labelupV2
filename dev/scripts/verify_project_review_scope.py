#!/usr/bin/env python3
import os
import paramiko

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')

t = paramiko.Transport((HOST, 22))
t.connect(username=USER, password=PASSWORD)
ssh = paramiko.SSHClient()
ssh._transport = t

def run(cmd):
    print('===', cmd[:120])
    _, o, e = ssh.exec_command(cmd)
    out = o.read().decode('utf-8', 'replace')
    err = e.read().decode('utf-8', 'replace')
    if out.strip():
        print(out.strip()[:2000])
    if err.strip() and 'Warning' not in err:
        print('ERR', err[:400])
    return out

# Check existing columns
cols = run("mysql -uroot -p'qlqjs@Elql3#!' labelup -N -e \"SHOW COLUMNS FROM dev_scope_items\" 2>/dev/null | awk '{print $1}'")
names = set(cols.split())

alters = []
if 'client_confirmed' not in names:
    alters.append(
        "ALTER TABLE dev_scope_items "
        "ADD COLUMN client_confirmed TINYINT(1) NOT NULL DEFAULT 0 AFTER status, "
        "ADD COLUMN client_confirmed_at DATETIME NULL AFTER client_confirmed, "
        "ADD COLUMN client_confirmed_by INT UNSIGNED DEFAULT NULL AFTER client_confirmed_at"
    )
if 'review_confirmed' not in names:
    after = 'client_confirmed_by' if ('client_confirmed_by' in names or 'client_confirmed' not in names) else 'status'
    # if we just queued client columns, after will be client_confirmed_by
    if 'client_confirmed' not in names:
        after = 'client_confirmed_by'
    alters.append(
        "ALTER TABLE dev_scope_items "
        f"ADD COLUMN review_confirmed TINYINT(1) NOT NULL DEFAULT 0 AFTER {after}, "
        "ADD COLUMN review_confirmed_at DATETIME NULL AFTER review_confirmed, "
        "ADD COLUMN review_confirmed_by INT UNSIGNED DEFAULT NULL AFTER review_confirmed_at, "
        "ADD COLUMN review_comment TEXT NULL AFTER review_confirmed_by"
    )

for sql in alters:
    run("mysql -uroot -p'qlqjs@Elql3#!' labelup -e \"%s\" 2>&1" % sql.replace('"', '\\"'))

run("mysql -uroot -p'qlqjs@Elql3#!' labelup -N -e \"SHOW COLUMNS FROM dev_scope_items LIKE 'client_%'; SHOW COLUMNS FROM dev_scope_items LIKE 'review_%'\" 2>/dev/null")
run("curl -sI http://127.0.0.1/project/review-scope.php -H 'Host: labelup.gagamkorea.kr' | head -12")
run("curl -s -o /dev/null -w 'HTTP %{http_code}\\n' http://127.0.0.1/project/review-scope.php -H 'Host: labelup.gagamkorea.kr'")
run("curl -s -o /dev/null -w 'HTTP %{http_code}\\n' http://127.0.0.1/project/dev-scope.php?phase=phase-1 -H 'Host: labelup.gagamkorea.kr'")

t.close()
