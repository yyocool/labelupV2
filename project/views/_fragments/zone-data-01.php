<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '홈·라벨디자인·템플릿·규격검색·인쇄·맞춤제작·자료실·고객센터 (8개 아이콘, 현재 홈 active)', 'menu' => '01 하위 전역', 'ux' => '클릭 시 해당 1depth 메뉴로 이동 + 사이드 패널 메뉴 그룹 전환'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비 패널', 'elements' => '로고 · 대시보드 · AI 추천 NEW · 최근 작업 · 즐겨찾기 · 공지 · 이벤트', 'menu' => '01-01 (HOME)', 'ux' => 'Desktop: 항상 노출. Tablet: 패널 접기. Mobile: 햄버거 드로어'),
        'L-03' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '바로가기 (사이드)', 'elements' => '새 디자인 · 템플릿 검색 · 규격 검색 · 주문 내역 · 이용 가이드', 'menu' => '01-02 하위', 'ux' => ''),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '통합 검색 · AI 추천 받기 · 알림(3) · 프로필(김라벨님 ▼)', 'menu' => '—', 'ux' => 'Enter 또는 자동완성 드롭다운 → 통합 검색 결과'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '히어로 (좌)', 'elements' => '배지 · H1 "상상한 라벨, 바로 디자인하고 출력까지" · 설명 · CTA 2개 · 소셜 프루프', 'menu' => '—', 'ux' => ''),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '히어로 비주얼 (중)', 'elements' => '마케팅용 정적 이미지. 라벨 편집 화면을 연상시키는 일러스트/목업 이미지 1장', 'menu' => '01-02', 'ux' => '정적 이미지 1장 (PNG/WebP). 클릭 동작 없음 (optional: 편집기 링크)'),
        'R-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '바로가기 (우측)', 'elements' => '새 디자인 · AI 생성 · 규격 · 바코드 · QR · 엑셀 연동 (6항목)', 'menu' => '01-02 하위', 'ux' => ''),
        'R-02' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => 'AI 프로모 카드', 'elements' => '파일 업로드 → AI 라벨·템플릿 추천 · 로봇 캐릭터 · 지금 시작하기', 'menu' => '01-02-05', 'ux' => 'Desktop: 우측 컬럼 고정. Mobile: 하단 배너 또는 숨김'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '기능 하이라이트', 'elements' => 'AI 라벨 생성 · 템플릿 · 규격 검색 · 프리미엄 라벨지 · 인쇄 서비스 (5카드)', 'menu' => '각 섹션', 'ux' => ''),
        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '추천 템플릿', 'elements' => '제목 + 더보기 · 카테고리 필터 8종 · 가로 스크롤 템플릿 카드 6+ · 좋아요 수', 'menu' => '01-02-02', 'ux' => '필터 탭 클릭 시 AJAX 필터링. 좌우 화살표 또는 드래그 스크롤'),
    );
}
