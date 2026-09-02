#!/usr/bin/env python3
import os, paramiko, sys
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'; PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
users_sql = """CREATE TABLE IF NOT EXISTS `users` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('member','admin') NOT NULL DEFAULT 'member',
    status ENUM('active','inactive','withdrawn') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;"""
t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
sftp=paramiko.SFTPClient.from_transport(t)
with sftp.open('/tmp/fix_users.sql','w') as f: f.write(users_sql)
sftp.close()
for c in [
 "mysql -ulabelupdev -p'LabelUpDev2026!' labelupdev < /tmp/fix_users.sql",
 "mysql -ulabelupdev -p'LabelUpDev2026!' labelupdev -e 'SHOW TABLES'",
 "curl -s -X POST http://127.0.0.1/api/system/migrate -H 'Host: labelupdev.gagamkorea.kr'",
 "curl -s http://127.0.0.1/api/auth/check-email?email=test@x.com -H 'Host: labelupdev.gagamkorea.kr'",
]:
    _,o,e=ssh.exec_command(c)
    print(c)
    print(o.read().decode('utf-8','replace'))
    err=e.read().decode('utf-8','replace')
    if err.strip(): print('ERR',err[:300])
t.close()
