<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '홈·라벨디자인·템플릿·규격검색·바코드·데이터·주문·설정 (8개 아이콘)', 'menu' => '01 하위 전역', 'ux' => '비로그인 상태에서도 노출 · 클릭 시 로그인 유도'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '로고 · 홈 · 라벨디자인 · 템플릿 · 규격검색 · 바코드/QR · 데이터연동 · 주문/인쇄 · 설정 · 고객센터(하단)', 'menu' => '01 전역', 'ux' => 'Desktop: 고정 · Mobile: 드로어'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바', 'elements' => '통합 검색 · 알림(3) · 로그인/회원가입 버튼', 'menu' => '01-02', 'ux' => '검색 Enter · 알림 드롭다운 · 로그인/회원가입 → 01-02'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '페이지 헤더', 'elements' => 'H1 "회원가입" · "라벨업에서 더 많은 기능을…" 부제', 'menu' => '01-01', 'ux' => '정적 타이틀'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '프로모 + 비주얼', 'elements' => '배지 · H1 카피 · 히어로 정적 이미지 (라벨 편집 연상)', 'menu' => '—', 'ux' => '정적 이미지 · 클릭 없음'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '가입 혜택 카드', 'elements' => '10,000+ 사용자 · 5,000+ 템플릿 · 안전한 서비스 · 빠른 고객지원 (4카드)', 'menu' => '—', 'ux' => 'Desktop: 4열 · Mobile: 2×2'),
        'R-01' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '회원가입 폼', 'elements' => '소셜 3종 · 이름·이메일·비밀번호·확인 · 휴대폰+인증 · 약관 3종 · 회원가입 버튼', 'menu' => '01-01', 'ux' => '유효성 검사 · SMS 인증 · 필수 약관 체크 · API: POST /auth/signup'),
        'R-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '라벨업 혜택 목록', 'elements' => 'AI 라벨 디자인 · 다양한 템플릿 · 정확한 규격 검색 · 빠른 인쇄 · 안전한 데이터 관리 (5항목)', 'menu' => '—', 'ux' => 'Desktop: 우측 고정 · Mobile: 폼 하단'),
    );
}
