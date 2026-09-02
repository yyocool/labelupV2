#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
ROOTPW='qlqjs@Elql3#!'
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
cmds=[
 f"mysql -uroot -p'{ROOTPW}' -e 'SELECT VERSION();'",
 f"mysql -uroot -p'{ROOTPW}' -e \"CREATE DATABASE IF NOT EXISTS labelupdev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"",
 f"mysql -uroot -p'{ROOTPW}' -e \"DROP USER IF EXISTS 'labelupdev'@'localhost'; CREATE USER 'labelupdev'@'localhost' IDENTIFIED BY 'LabelUpDev2026!'; GRANT ALL PRIVILEGES ON labelupdev.* TO 'labelupdev'@'localhost'; FLUSH PRIVILEGES;\"",
 'cat > /tmp/dbtest.php <<\'PHP\'\n<?php\ntry {\n  $p=new PDO("mysql:host=127.0.0.1;dbname=labelupdev;charset=utf8mb4","labelupdev","LabelUpDev2026!");\n  echo "ok";\n} catch(Exception $e){ echo $e->getMessage(); }\nPHP',
 'sudo -u www php8.1 /tmp/dbtest.php',
]
for c in cmds:
    _,o,e=ssh.exec_command(c)
    print('===',c[:80])
    print(o.read().decode('utf-8','replace'))
    err=e.read().decode('utf-8','replace')
    if err.strip(): print('ERR',err[:300])
env="""APP_NAME=LabelUp
APP_ENV=remote
APP_DEBUG=true
APP_URL=http://labelupdev.gagamkorea.kr
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=labelupdev
DB_USERNAME=labelupdev
DB_PASSWORD=LabelUpDev2026!
SESSION_KEY=labelupdev_session
SESSION_LIFETIME=7200
TIMEZONE=Asia/Seoul
"""
sftp=paramiko.SFTPClient.from_transport(t)
with sftp.open('/home/labelupdev/.env','w') as f: f.write(env)
sftp.close()
_,o,_=ssh.exec_command('curl -s http://127.0.0.1/api/health -H "Host: labelupdev.gagamkorea.kr"')
print('health',o.read().decode())
_,o,_=ssh.exec_command('curl -s -X POST http://127.0.0.1/api/system/migrate -H "Host: labelupdev.gagamkorea.kr"')
print('migrate',o.read().decode())
t.close()
