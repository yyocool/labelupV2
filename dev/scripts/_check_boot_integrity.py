#!/usr/bin/env python3
import os, sys, json, hashlib, base64, paramiko
from pathlib import Path
sys.stdout.reconfigure(encoding='utf-8', errors='replace')

LOCAL=Path(r'c:\phpstudy_pro\WWW\labelup\dev\public\editor\_framework')
boot=json.loads((LOCAL/'blazor.boot.json').read_text(encoding='utf-8'))

def b64sha(data: bytes) -> str:
    return 'sha256-' + base64.b64encode(hashlib.sha256(data).digest()).decode()

mism=[]
ok=0
resources=boot.get('resources',{})
for section, items in resources.items():
    if not isinstance(items, dict):
        continue
    for name, expected in items.items():
        if not isinstance(expected, str) or not expected.startswith('sha256-'):
            continue
        p=LOCAL/name
        if not p.exists():
            mism.append((section,name,'MISSING',expected,''))
            continue
        actual=b64sha(p.read_bytes())
        if actual!=expected:
            mism.append((section,name,'HASH',expected,actual))
        else:
            ok+=1
print('local integrity ok', ok, 'mismatch', len(mism))
for m in mism[:30]:
    print(m)

# remote sample check
PW=os.environ['LABELUP_SSH_PASSWORD']
t=paramiko.Transport(('115.71.237.145',22)); t.connect(username='root',password=PW)
sftp=paramiko.SFTPClient.from_transport(t)
ssh=paramiko.SSHClient(); ssh._transport=t
with sftp.open('/home/labelupdev/public/editor/_framework/blazor.boot.json','r') as f:
    rboot=json.loads(f.read().decode('utf-8'))
rmism=[]
rok=0
for section, items in rboot.get('resources',{}).items():
    if not isinstance(items, dict):
        continue
    for name, expected in list(items.items())[:]:
        if not isinstance(expected, str) or not expected.startswith('sha256-'):
            continue
        path=f'/home/labelupdev/public/editor/_framework/{name}'
        try:
            with sftp.open(path,'rb') as rf:
                data=rf.read()
        except FileNotFoundError:
            rmism.append((name,'MISSING'))
            continue
        actual=b64sha(data)
        if actual!=expected:
            rmism.append((name,'HASH',expected,actual,len(data)))
        else:
            rok+=1
print('remote integrity ok', rok, 'mismatch', len(rmism))
for m in rmism[:40]:
    print(m)
sftp.close(); t.close()
