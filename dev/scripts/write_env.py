#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
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
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
sftp=paramiko.SFTPClient.from_transport(t)
with sftp.open('/home/labelupdev/.env','w') as f: f.write(env)
sftp.close()
ssh=paramiko.SSHClient(); ssh._transport=t
_,o,_=ssh.exec_command('curl -s http://127.0.0.1/api/health -H "Host: labelupdev.gagamkorea.kr"'); print(o.read().decode())
_,o,_=ssh.exec_command('curl -s -X POST http://127.0.0.1/api/system/migrate -H "Host: labelupdev.gagamkorea.kr"'); print(o.read().decode())
t.close()
