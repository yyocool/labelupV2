#!/usr/bin/env python3
import os, paramiko
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
cmds=[
 'chown -R www:www /home/labelupdev/storage && chmod -R 775 /home/labelupdev/storage',
 "curl -sv http://127.0.0.1/ -H 'Host: labelupdev.gagamkorea.kr' 2>&1 | tail -20",
 "curl -s http://127.0.0.1/api/health -H 'Host: labelupdev.gagamkorea.kr'",
 "curl -s -X POST http://127.0.0.1/api/system/migrate -H 'Host: labelupdev.gagamkorea.kr'",
 'tail -5 /home/wwwlogs/labelupdev.error.log',
]
for c in cmds:
    _,o,e=ssh.exec_command(c)
    print('===',c)
    print(o.read().decode('utf-8',errors='replace')[:2500])
t.close()
