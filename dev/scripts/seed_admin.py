#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
php='''<?php
require "/home/labelupdev/bootstrap.php";
(new App\\Services\\UserService())->ensureAdminExists();
echo "admin ok\\n";
'''
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
sftp=paramiko.SFTPClient.from_transport(t)
with sftp.open('/tmp/seed_admin.php','w') as f: f.write(php)
sftp.close()
ssh=paramiko.SSHClient(); ssh._transport=t
for c in [
 'sudo -u www php8.1 /tmp/seed_admin.php',
 "curl -s -X POST http://127.0.0.1/api/auth/login -H 'Host: labelupdev.gagamkorea.kr' -H 'Content-Type: application/json' -d '{\"email\":\"admin@labelup.kr\",\"password\":\"admin1234!\"}'",
 "curl -sI http://127.0.0.1/login -H 'Host: labelupdev.gagamkorea.kr' | head -3",
]:
    _,o,_=ssh.exec_command(c)
    print(c); print(o.read().decode('utf-8','replace')[:500])
t.close()
