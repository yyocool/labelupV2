#!/usr/bin/env python3
import os, sys, paramiko

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelupdev'

NGINX_BLOCK = f"""
server {{
    listen 80;
    server_name labelupdev.gagamkorea.kr;
    root {REMOTE_ROOT}/public;
    index index.php index.html;
    client_max_body_size 32m;

    location / {{
        try_files $uri $uri/ /index.php?$query_string;
    }}

    location ~ \\.(css|js|png|jpg|jpeg|gif|webp|svg|woff2?)$ {{
        expires 7d;
        access_log off;
    }}

    location ~ \\.php$ {{
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }}
}}
"""


def run(ssh, cmd):
    print('$', cmd[:120])
    _, stdout, stderr = ssh.exec_command(cmd)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    code = stdout.channel.recv_exit_status()
    if out.strip(): print(out.strip())
    if err.strip(): print(err.strip(), file=sys.stderr)
    return code


def main():
    t = paramiko.Transport((HOST, 22))
    t.connect(username=USER, password=PASSWORD)
    ssh = paramiko.SSHClient(); ssh._transport = t

    run(ssh, 'which nginx; nginx -V 2>&1 | head -1')
    run(ssh, 'ls -la /usr/local/nginx/conf/ /etc/nginx/ 2>/dev/null')
    run(ssh, 'php -v 2>&1 | head -1; ls /var/run/php* 2>/dev/null; ss -lntp | grep -E "9000|php" || netstat -lntp 2>/dev/null | grep -E "9000|php"')
    run(ssh, 'grep -R "labelupdev" /usr/local/nginx/conf /etc/nginx 2>/dev/null | head')

    conf_path = '/usr/local/nginx/conf/vhost_labelupdev.conf'
    sftp = paramiko.SFTPClient.from_transport(t)
    with sftp.open(conf_path, 'w') as f:
        f.write(NGINX_BLOCK.strip() + '\n')
    sftp.close()

    # include vhost if missing
    run(ssh, f"grep -q vhost_labelupdev /usr/local/nginx/conf/nginx.conf || sed -i '/http {{/a\\    include vhost_labelupdev.conf;' /usr/local/nginx/conf/nginx.conf")
    run(ssh, '/usr/local/nginx/sbin/nginx -t')
    run(ssh, '/usr/local/nginx/sbin/nginx -s reload || /usr/local/nginx/sbin/nginx')
    run(ssh, "curl -s http://127.0.0.1/api/health -H 'Host: labelupdev.gagamkorea.kr'")
    run(ssh, "curl -s -X POST http://127.0.0.1/api/system/migrate -H 'Host: labelupdev.gagamkorea.kr'")
    t.close()

if __name__ == '__main__':
    main()
