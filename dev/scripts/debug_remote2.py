#!/usr/bin/env python3
import os, paramiko
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
for cmd in [
    'tail -15 /home/wwwlogs/labelupdev.error.log',
    'REQUEST_URI=/api/health REQUEST_METHOD=GET SCRIPT_NAME=/index.php php8.1 /home/labelupdev/public/index.php 2>&1',
    'ls -la /home/labelupdev/bootstrap.php /home/labelupdev/app/Router.php',
]:
    _,o,e=ssh.exec_command(cmd)
    print('$',cmd)
    print(o.read().decode('utf-8',errors='replace')[:2000])
    err=e.read().decode('utf-8',errors='replace')
    if err.strip(): print('ERR',err[:500])
t.close()
