#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
ROOTPW='qlqjs@Elql3#!'
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
cmd=f"mysql -uroot -p'{ROOTPW}' -e \"GRANT ALL PRIVILEGES ON labelupdev.* TO 'labelupdev'@'localhost' IDENTIFIED BY 'LabelUpDev2026!'; FLUSH PRIVILEGES;\""
_,o,e=ssh.exec_command(cmd)
print(o.read().decode()); print(e.read().decode())
_,o,_=ssh.exec_command('sudo -u www php8.1 /tmp/dbtest.php'); print('dbtest',o.read().decode())
_,o,_=ssh.exec_command('curl -s http://127.0.0.1/api/health -H "Host: labelupdev.gagamkorea.kr"'); print('health',o.read().decode())
_,o,_=ssh.exec_command('curl -s -X POST http://127.0.0.1/api/system/migrate -H "Host: labelupdev.gagamkorea.kr"'); print('migrate',o.read().decode())
t.close()
