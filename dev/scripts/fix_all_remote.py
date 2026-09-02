#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
cmds=[
 'chown -R www:www /home/labelupdev/storage /home/labelupdev/views /home/labelupdev/app /home/labelupdev/config /home/labelupdev/bootstrap.php',
 'chmod -R 775 /home/labelupdev/storage',
 'sudo -u www php8.1 -r "try { new PDO(\"mysql:host=127.0.0.1;dbname=labelupdev;charset=utf8mb4\",\"root\",\"qlqjs@Elql3#!\"); echo \"db ok\\n\"; } catch(Exception $e){ echo $e->getMessage(); }"',
 'curl -s -X POST http://127.0.0.1/api/system/migrate -H "Host: labelupdev.gagamkorea.kr"',
 'curl -s http://127.0.0.1/api/health -H "Host: labelupdev.gagamkorea.kr"',
]
for c in cmds:
    _,o,e=ssh.exec_command(c)
    print('===',c)
    print(o.read().decode('utf-8','replace'))
    err=e.read().decode('utf-8','replace')
    if err.strip(): print('ERR',err)
t.close()
