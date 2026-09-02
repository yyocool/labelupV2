#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
PW='qlqjs@Elql3#!'
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
sql=f"""
CREATE DATABASE IF NOT EXISTS labelupdev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'labelupdev'@'localhost' IDENTIFIED BY 'LabelUpDev2026!';
GRANT ALL PRIVILEGES ON labelupdev.* TO 'labelupdev'@'localhost';
FLUSH PRIVILEGES;
"""
cmd=f"mysql -uroot -p'{PW}' -e \"{sql}\""
_,o,e=ssh.exec_command(cmd)
print(o.read().decode()); print(e.read().decode())
env=f"""APP_NAME=LabelUp
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
for c in [
 'curl -s http://127.0.0.1/api/health -H "Host: labelupdev.gagamkorea.kr"',
 'curl -s -X POST http://127.0.0.1/api/system/migrate -H "Host: labelupdev.gagamkorea.kr"',
]:
    _,o,_=ssh.exec_command(c); print(c); print(o.read().decode())
t.close()
