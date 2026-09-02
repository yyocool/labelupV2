#!/usr/bin/env python3
import os, paramiko
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')

def run(ssh,cmd):
    print('$',cmd)
    _,o,e=ssh.exec_command(cmd)
    out=o.read().decode(); err=e.read().decode()
    if out.strip(): print(out.strip())
    if err.strip(): print('ERR:',err.strip())

t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
run(ssh,'tail -30 /home/wwwlogs/labelupdev.error.log 2>/dev/null || tail -30 /usr/local/nginx/logs/error.log')
run(ssh,'ls -la /home/labelupdev/public/index.php')
run(ssh,'cd /home/labelupdev/public && php -d display_errors=1 index.php 2>&1 | head -40')
run(ssh,"REQUEST_URI=/api/health REQUEST_METHOD=GET SCRIPT_NAME=/index.php php -d display_errors=1 /home/labelupdev/public/index.php 2>&1 | head -20")
run(ssh,'cat /usr/local/nginx/conf/enable-php.conf')
t.close()
