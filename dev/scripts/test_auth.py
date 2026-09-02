#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
tests=[
 ("login", "curl -s -X POST http://127.0.0.1/api/auth/login -H 'Host: labelupdev.gagamkorea.kr' -H 'Content-Type: application/json' -d '{\"email\":\"admin@labelup.kr\",\"password\":\"admin1234!\"}'"),
 ("register", "curl -s -X POST http://127.0.0.1/api/auth/register -H 'Host: labelupdev.gagamkorea.kr' -H 'Content-Type: application/json' -d '{\"email\":\"demo@labelup.kr\",\"password\":\"demo1234\",\"name\":\"데모사용자\"}'"),
 ("check-email", "curl -s 'http://127.0.0.1/api/auth/check-email?email=demo@labelup.kr' -H 'Host: labelupdev.gagamkorea.kr'"),
 ("login-page", "curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/login -H 'Host: labelupdev.gagamkorea.kr'"),
]
for name, cmd in tests:
    _,o,_=ssh.exec_command(cmd)
    print(name, o.read().decode('utf-8','replace')[:300])
t.close()
