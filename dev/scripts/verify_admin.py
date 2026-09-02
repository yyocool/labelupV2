#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
cmds=[
 "curl -s -o /dev/null -w 'admin:%{http_code}' http://127.0.0.1/admin -H 'Host: labelupdev.gagamkorea.kr'",
 "curl -s -o /dev/null -w ' admin_login:%{http_code}' http://127.0.0.1/admin/login -H 'Host: labelupdev.gagamkorea.kr'",
 "curl -s -o /dev/null -w ' user_login:%{http_code}' http://127.0.0.1/login -H 'Host: labelupdev.gagamkorea.kr'",
 'test -f /home/labelupdev/views/admin/login.php && echo login_view_ok',
]
for c in cmds:
    _,o,_=ssh.exec_command(c)
    print(o.read().decode('utf-8','replace').strip())
t.close()
