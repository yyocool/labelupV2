#!/usr/bin/env python3
"""Upload only public/editor to remote (faster than full deploy)."""
import os, sys, paramiko
sys.stdout.reconfigure(encoding='utf-8', errors='replace')
HOST='115.71.237.145'; USER='root'
PASSWORD=os.environ.get('LABELUP_SSH_PASSWORD','')
LOCAL=os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'public', 'editor'))
REMOTE='/home/labelupdev/public/editor'

def ensure(sftp, remote_dir):
    parts=remote_dir.strip('/').split('/')
    cur=''
    for p in parts:
        cur+='/'+p
        try: sftp.stat(cur)
        except FileNotFoundError: sftp.mkdir(cur)

t=paramiko.Transport((HOST,22)); t.connect(username=USER,password=PASSWORD)
ssh=paramiko.SSHClient(); ssh._transport=t
# Drop stale fingerprinted framework files so boot.json cannot reference missing assets.
_,o,e=ssh.exec_command(f'rm -rf {REMOTE}/_framework && mkdir -p {REMOTE}/_framework && echo wiped')
print(o.read().decode(errors='replace').strip(), e.read().decode(errors='replace').strip())
sftp=paramiko.SFTPClient.from_transport(t)
count=0
for root, dirs, files in os.walk(LOCAL):
    rel=os.path.relpath(root, LOCAL).replace('\\','/')
    rdir=REMOTE if rel=='.' else REMOTE+'/'+rel
    ensure(sftp, rdir)
    for name in files:
        lp=os.path.join(root, name)
        rp=rdir+'/'+name
        sftp.put(lp, rp)
        count+=1
        if count%50==0: print('uploaded', count)
sftp.close(); t.close()
print('done', count, 'files')
