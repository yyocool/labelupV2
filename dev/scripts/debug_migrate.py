#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
cmds=[
 "mysql -ulabelupdev -p'LabelUpDev2026!' labelupdev -e 'SELECT * FROM migrations'",
 "curl -s -X POST http://127.0.0.1/api/system/migrate -H 'Host: labelupdev.gagamkorea.kr'",
 "mysql -ulabelupdev -p'LabelUpDev2026!' labelupdev -e 'SHOW TABLES'",
]
for c in cmds:
    _,o,e=ssh.exec_command(c)
    print('===',c[:80])
    print(o.read().decode('utf-8','replace'))
    err=e.read().decode('utf-8','replace')
    if err.strip(): print('ERR',err[:500])
t.close()
