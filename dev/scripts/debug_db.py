#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
php='''<?php
require "/home/labelupdev/bootstrap.php";
try {
  $db = App\\Helpers\\Database::connection();
  echo "connected\\n";
} catch (Throwable $e) {
  echo "err: ".$e->getMessage()."\\n";
}
print_r(require "/home/labelupdev/config/database.php");
'''
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
sftp=paramiko.SFTPClient.from_transport(t)
with sftp.open('/tmp/boottest.php','w') as f: f.write(php)
sftp.close()
ssh=paramiko.SSHClient(); ssh._transport=t
_,o,e=ssh.exec_command('sudo -u www php8.1 /tmp/boottest.php')
print(o.read().decode()); print(e.read().decode())
t.close()
