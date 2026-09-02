#!/usr/bin/env python3
import os, paramiko
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
CONF='/usr/local/nginx/conf/vhost/labelupdev.gagamkorea.kr.conf'
REMOTE_ROOT='/home/labelupdev'

NEW_CONF = f"""server {{
    listen 80;
    server_name labelupdev.gagamkorea.kr;
    root {REMOTE_ROOT}/public;
    index index.php index.html;
    client_max_body_size 32m;

    access_log /home/wwwlogs/labelupdev.access.log;
    error_log /home/wwwlogs/labelupdev.error.log;

    location / {{
        try_files $uri $uri/ /index.php?$query_string;
    }}

    location ~ \\.(css|js|png|jpg|jpeg|gif|webp|svg|woff2?)$ {{
        expires 7d;
        access_log off;
    }}

    location ~ [^/]\\.php(/|$) {{
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi.conf;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }}

    location ~ /\\. {{
        deny all;
    }}
}}
"""

def run(ssh,cmd):
    _,o,e=ssh.exec_command(cmd)
    out=o.read().decode('utf-8',errors='replace')
    err=e.read().decode('utf-8',errors='replace')
    print('$',cmd[:100])
    if out.strip(): print(out.strip())
    if err.strip(): print('ERR:',err.strip())

t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
sftp=paramiko.SFTPClient.from_transport(t)
with sftp.open(CONF,'w') as f: f.write(NEW_CONF)
sftp.close()
run(ssh,'ls -la /var/run/php/php8.1-fpm.sock /run/php/php8.1-fpm.sock 2>/dev/null')
run(ssh,'/usr/local/nginx/sbin/nginx -t && /usr/local/nginx/sbin/nginx -s reload')
run(ssh,"curl -s http://127.0.0.1/api/health -H 'Host: labelupdev.gagamkorea.kr'")
run(ssh,"curl -s -X POST http://127.0.0.1/api/system/migrate -H 'Host: labelupdev.gagamkorea.kr'")
run(ssh,"curl -sI http://127.0.0.1/ -H 'Host: labelupdev.gagamkorea.kr' | head -3")
t.close()
