#!/usr/bin/env python3
"""Pin open_basedir for labelupdev via nginx PHP_ADMIN_VALUE (shared FPM pool safety)."""
import os
import sys
import paramiko

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

HOST = "115.71.237.145"
USER = "root"
PASSWORD = os.environ.get("LABELUP_SSH_PASSWORD", "")
CONF = "/usr/local/nginx/conf/vhost/labelupdev.gagamkorea.kr.conf"
REMOTE_ROOT = "/home/labelupdev"

NEW_CONF = f"""server {{
    listen 80;
    server_name labelupdev.gagamkorea.kr;
    root {REMOTE_ROOT}/public;
    index index.php index.html;
    client_max_body_size 32m;

    access_log /home/wwwlogs/labelupdev.access.log;
    error_log /home/wwwlogs/labelupdev.error.log;

    location /editor/ {{
        try_files $uri $uri/ /editor/index.html;
        types {{
            application/wasm wasm;
            application/octet-stream dll;
            application/octet-stream dat;
            application/javascript js;
            text/css css;
            text/html html;
            application/json json;
            image/png png;
            image/jpeg jpg jpeg;
            image/svg+xml svg;
            font/woff woff;
            font/woff2 woff2;
        }}
        default_type application/octet-stream;
    }}

    location ~* ^/editor/_framework/ {{
        expires -1;
        add_header Cache-Control "no-cache, no-store, must-revalidate" always;
        add_header Pragma "no-cache" always;
        access_log off;
        types {{
            application/wasm wasm;
            application/octet-stream dll;
            application/octet-stream dat;
            application/javascript js;
            application/json json;
        }}
        default_type application/octet-stream;
    }}

    location / {{
        try_files $uri $uri/ /index.php?$query_string;
    }}

    location ~ \\.(css|js|png|jpg|jpeg|gif|webp|svg|woff2?|wasm|dll|dat)$ {{
        expires 7d;
        access_log off;
    }}

    location ~ [^/]\\.php(/|$) {{
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        # Shared FPM pool: force basedir so another site's .user.ini cannot leak in
        fastcgi_param PHP_ADMIN_VALUE "open_basedir={REMOTE_ROOT}/:/tmp/:/proc/";
    }}
}}
"""

t = paramiko.Transport((HOST, 22))
t.connect(username=USER, password=PASSWORD)
sftp = paramiko.SFTPClient.from_transport(t)
with sftp.open(CONF, "w") as f:
    f.write(NEW_CONF)
sftp.close()

ssh = paramiko.SSHClient()
ssh._transport = t
for cmd in [
    "/usr/local/nginx/sbin/nginx -t && /usr/local/nginx/sbin/nginx -s reload",
    "curl -s -o /dev/null -w 'admin=%{http_code}\\n' http://127.0.0.1/admin/login -H 'Host: labelupdev.gagamkorea.kr'",
    "curl -s -o /dev/null -w 'home=%{http_code}\\n' http://127.0.0.1/ -H 'Host: labelupdev.gagamkorea.kr'",
    """cat > /home/labelupdev/public/_ob.php <<'EOF'
<?php header('Content-Type: text/plain'); echo ini_get('open_basedir');
EOF
curl -s http://127.0.0.1/_ob.php -H 'Host: labelupdev.gagamkorea.kr'; echo; rm -f /home/labelupdev/public/_ob.php""",
]:
    print("$", cmd[:100])
    _, o, e = ssh.exec_command(cmd)
    print(o.read().decode("utf-8", errors="replace").strip()[:1500])
    err = e.read().decode("utf-8", errors="replace").strip()
    if err:
        print("ERR", err[:800])
t.close()
