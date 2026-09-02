#!/usr/bin/env python3
import os, paramiko
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
LOCAL=os.path.abspath(os.path.join(os.path.dirname(__file__),'..','public','.user.ini'))
REMOTE='/home/labelupdev/public/.user.ini'
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
sftp=paramiko.SFTPClient.from_transport(t)
sftp.put(LOCAL, REMOTE)
sftp.close()
ssh=paramiko.SSHClient(); ssh._transport=t
for cmd in [
    'cat /home/labelupdev/public/.user.ini',
    'systemctl restart php8.1-fpm || service php8.1-fpm restart',
    "curl -s http://127.0.0.1/api/health -H 'Host: labelupdev.gagamkorea.kr'",
    "curl -s -X POST http://127.0.0.1/api/system/migrate -H 'Host: labelupdev.gagamkorea.kr'",
    "curl -sI http://127.0.0.1/ -H 'Host: labelupdev.gagamkorea.kr' | head -3",
]:
    _,o,e=ssh.exec_command(cmd)
    print('$',cmd)
    print(o.read().decode('utf-8',errors='replace').strip()[:1500])
t.close()
