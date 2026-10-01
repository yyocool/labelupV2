#!/usr/bin/env python3
"""서비스 사이트(dev/) 원격 배포 설정 — PHPS 전용.

프로젝트관리(project/)와 분리한다. SSH 비밀번호는 LABELUP_SSH_PASSWORD.
"""
import os

# SSH (서비스 전용)
HOST = '115.41.222.123'
USER = 'uptube1'
PASSWORD = os.environ.get('LABELUP_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/uptube1'
# 로컬 dev/public  ↔  원격 www (PHPS 문서루트)
REMOTE_PUBLIC = REMOTE_ROOT + '/www'

# 공개 URL
APP_URL = 'https://www.labelup.co.kr'
PUBLIC_HOST = 'www.labelup.co.kr'

# MySQL
DB_HOST = 'db01.phps.co.kr'
DB_PORT = '3306'
DB_DATABASE = 'uptube1'
DB_USERNAME = 'uptube1'
DB_PASSWORD = os.environ.get('LABELUP_DB_PASSWORD', '')
