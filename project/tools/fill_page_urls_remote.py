#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import re
import subprocess

BASE = 'https://www.labelup.co.kr'
rules = [
    (r'이용약관|개인정보|정책 게시', BASE + '/terms'),
    (r'FAQ|공지|1:1 문의|고객센터|헬프센터', BASE + '/faq'),
    (r'호환|다브랜드 규격|규격 검색|규격 코드', BASE + '/compat'),
    (r'회원가입|로그인|비밀번호 찾기|소셜 로그인', BASE + '/login'),
    (r'마이페이지|내 정보|주문·배송', BASE + '/account'),
    (r'장바구니|결제|PG 연동', BASE + '/shop/cart'),
    (r'상품 상세|상세 섹션|스펙 테이블|CTA:|연관:|SEO', BASE + '/shop/products'),
    (r'쇼핑몰 홈|상품 목록|LNB:|라벨 편집하기|쇼핑몰', BASE + '/shop'),
    (r'카테고리 트리|규격 마스터|관리자 CRUD|Backoffice|매출 통계|쿠폰·포인트|관리자|CS SLA|알림:|운영서버|PG 선정|알림톡|SSL|KEY 발급|준비사항', BASE + '/admin'),
    (r'폼텍|아이라벨|애니라벨|타사|가져오기|임포트|외부포맷|변환|편집기|캔버스|워드아트|라벨복사|미리보기 패널|데이터 연동|시트 미니|디자인 저장|특화기능|라벨편집|에셋|레이어|텍스트·이미지|A4/', BASE + '/editor/'),
    (r'AI 기능|프롬프트|파일 첨부|이미지 첨부|전문가 모드|사용량|사용자 홈|AI\(', BASE + '/'),
    (r'약관|정책', BASE + '/terms'),
    (r'모바일', BASE + '/'),
    (r'데이터|상품|마스터', BASE + '/shop/products'),
    (r'기타|사용자장터', BASE + '/'),
]

out = subprocess.check_output(
    ["mysql", "-uroot", "-pqlqjs@Elql3#!", "labelup", "-N", "-e",
     "SELECT id, title FROM dev_scope_items WHERE phase_key='phase-1'"],
    stderr=subprocess.DEVNULL,
)
lines = out.decode('utf-8', 'replace').strip().splitlines()
sql = []
for line in lines:
    parts = line.split('\t', 1)
    if len(parts) < 2:
        continue
    iid, title = parts[0], parts[1]
    url = ''
    for pat, u in rules:
        if re.search(pat, title):
            url = u
            break
    if not url:
        url = BASE + '/'
    sql.append("UPDATE dev_scope_items SET page_url='%s', updated_at=NOW() WHERE id=%s;" % (url, iid))

open('/tmp/sql_page_urls2.sql', 'w', encoding='utf-8').write('\n'.join(sql))
print('writes', len(sql))
subprocess.check_call(["mysql", "-uroot", "-pqlqjs@Elql3#!", "labelup"], stdin=open('/tmp/sql_page_urls2.sql', encoding='utf-8'), stderr=subprocess.DEVNULL)
filled = subprocess.check_output(
    ["mysql", "-uroot", "-pqlqjs@Elql3#!", "labelup", "-N", "-e",
     "SELECT COUNT(*) FROM dev_scope_items WHERE phase_key='phase-1' AND page_url IS NOT NULL AND page_url<>''"],
    stderr=subprocess.DEVNULL,
).decode().strip()
print('filled', filled)
empty = subprocess.check_output(
    ["mysql", "-uroot", "-pqlqjs@Elql3#!", "labelup", "-N", "-e",
     "SELECT id, title FROM dev_scope_items WHERE phase_key='phase-1' AND (page_url IS NULL OR page_url='')"],
    stderr=subprocess.DEVNULL,
).decode('utf-8', 'replace')
print('empty:\n', empty[:500])
