#!/usr/bin/env python3
"""프로젝트관리(project/) 원격 배포 설정 — 기존 gagamkorea 서버.

서비스 사이트(dev/)와 분리한다.
SSH 비밀번호: LABELUP_PROJECT_SSH_PASSWORD (미설정 시 LABELUP_SSH_PASSWORD 폴백 없음 — 혼동 방지).
"""
import os

HOST = '115.71.237.145'
USER = 'root'
PASSWORD = os.environ.get('LABELUP_PROJECT_SSH_PASSWORD', '')
REMOTE_ROOT = '/home/labelup/project'
APP_URL = 'http://labelup.gagamkorea.kr/project'
PUBLIC_HOST = 'labelup.gagamkorea.kr'
