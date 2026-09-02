#!/usr/bin/env python3
import os, paramiko
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
cmds=[
 'tail -8 /home/wwwlogs/labelupdev.error.log',
 'grep -R "labelupdev" /www/server/php/81/etc/php-fpm.d/ /etc/php/8.1/fpm/pool.d/ 2>/dev/null | head -20',
 'grep -R "open_basedir" /www/server/panel/vhost/open_basedir/nginx/ 2>/dev/null | head -10',
 'find /www -name "*labelupdev*" 2>/dev/null | head -20',
 'ls -la /home/labelupdev/public/.user.ini',
]
for c in cmds:
    _,o,e=ssh.exec_command(c)
    print('$',c)
    print(o.read().decode('utf-8',errors='replace')[:2000])
t.close()
