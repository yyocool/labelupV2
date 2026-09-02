#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
cmds=[
 'curl -s http://127.0.0.1/api/health -H "Host: labelupdev.gagamkorea.kr"',
 'curl -s -w "\nHTTP:%{http_code}\n" -X POST http://127.0.0.1/api/system/migrate -H "Host: labelupdev.gagamkorea.kr"',
 'curl -s -w "\nHTTP:%{http_code}\n" http://127.0.0.1/ -H "Host: labelupdev.gagamkorea.kr" | head -c 500',
]
for c in cmds:
    _,o,_=ssh.exec_command(c)
    print('===',c)
    print(o.read().decode('utf-8','replace'))
t.close()
