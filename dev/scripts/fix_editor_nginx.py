#!/usr/bin/env python3
"""Ensure nginx serves Blazor WASM editor correctly (no stale framework cache)."""
import os
import paramiko

HOST = "115.71.237.145"
USER = "root"
PASSWORD = os.environ.get("LABELUP_SSH_PASSWORD", "")
CONF = "/usr/local/nginx/conf/vhost/labelupdev.gagamkorea.kr.conf"
REMOTE_ROOT = "/home/labelupdev"

# Blazor boot.json + assemblies must not be long-cached on HTTP:
# browsers can mix old/new .wasm after deploy and Blazor then fails to start.
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

    # First matching regex wins: keep Blazor framework fresh after each deploy.
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
        fastcgi_param PHP_ADMIN_VALUE "open_basedir=/home/labelupdev/:/tmp/:/proc/";
    }}
}}
"""


def run(ssh, cmd):
    print("$", cmd[:160])
    _, o, e = ssh.exec_command(cmd)
    out = o.read().decode("utf-8", errors="replace")
    err = e.read().decode("utf-8", errors="replace")
    if out.strip():
        print(out.strip()[:2000])
    if err.strip():
        print("ERR", err.strip()[:500])
    return o.channel.recv_exit_status()


def main():
    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient()
    ssh._transport = t
    sftp = paramiko.SFTPClient.from_transport(t)

    run(ssh, f"cp -a {CONF} {CONF}.bak.$(date +%Y%m%d%H%M) 2>/dev/null || true")
    with sftp.file(CONF, "w") as f:
        f.write(NEW_CONF)
    run(ssh, "/usr/local/nginx/sbin/nginx -t")
    run(ssh, "/usr/local/nginx/sbin/nginx -s reload || systemctl reload nginx")
    run(
        ssh,
        "curl -sI http://127.0.0.1/editor/_framework/blazor.boot.json -H 'Host: labelupdev.gagamkorea.kr' | head -20",
    )
    run(
        ssh,
        "curl -sI http://127.0.0.1/editor/_framework/LabelUp.Editor.wasm -H 'Host: labelupdev.gagamkorea.kr' | head -20",
    )
    t.close()


if __name__ == "__main__":
    main()
