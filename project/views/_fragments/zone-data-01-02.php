<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '홈·라벨디자인·템플릿·규격검색·쇼핑몰·맞춤서비스·자료실·고객센터 (8개 아이콘)', 'menu' => '01 하위 전역', 'ux' => '클릭 시 해당 1depth 메뉴로 이동. 로그인 전에도 노출(링크는 로그인 유도)'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비 패널', 'elements' => '로고 · 홈 · 라벨 디자인 · 템플릿 · 규격 검색 · 쇼핑몰 · 맞춤 서비스 · 자료실 · 고객센터 · 마이페이지(하단)', 'menu' => '01 전역', 'ux' => 'Desktop: 항상 노출. Tablet: 패널 접기. Mobile: 햄버거 드로어'),
        'M-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '프로모 카피', 'elements' => '배지 "AI가 도와주는…" · H1 · 서비스 설명 문구', 'menu' => '—', 'ux' => '마케팅 카피. CTA 버튼 없음(로그인 유도는 우측 폼)'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '히어로 비주얼', 'elements' => '라벨 편집 화면을 연상시키는 정적 일러스트/목업 이미지 1장', 'menu' => '—', 'ux' => '정적 이미지 (PNG/WebP). 클릭 동작 없음'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '신뢰 지표 카드', 'elements' => '10,000+ 사용자 · 5,000+ 템플릿 · 10,000+ 규격 데이터 · 빠른 인쇄 당일 출고 (4카드)', 'menu' => '—', 'ux' => 'Desktop: 4열 그리드. Mobile: 2×2 또는 가로 스크롤'),
        'R-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '로그인 폼 카드', 'elements' => '환영 문구 · 이메일 · 비밀번호(표시 토글) · 로그인 유지 · 비밀번호 찾기 · 로그인 버튼 · 소셜 3종 · 회원가입 링크', 'menu' => '01-02', 'ux' => 'Enter 제출 · 유효성 검사 · 소셜 OAuth 리다이렉트 · 회원가입 → 가입 페이지'),
    );
}
