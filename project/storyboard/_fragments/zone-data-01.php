<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '홈·라벨디자인·템플릿·규격검색·인쇄·맞춤제작·자료실·고객센터 (8개, 홈 active)', 'menu' => '01 하위 전역', 'ux' => '공개 탐색용 1depth · 로그인 가드가 필요한 메뉴는 클릭 시 로그인 유도'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비 (비로그인)', 'elements' => '로고 · 메인 · 서비스 소개 · 요금 안내 · 공지 · 이벤트', 'menu' => '01 / 01-04-02', 'ux' => '대시보드·최근작업·즐겨찾기는 로그인 후(01-04)에만 노출'),
        'L-03' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '둘러보기 (사이드)', 'elements' => '새 디자인 · 템플릿 · 규격 검색 · 이용 가이드 · 고객센터', 'menu' => '01-05 / 01-07', 'ux' => '주문 내역·내 디자인은 비로그인 미노출'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더 (비로그인)', 'elements' => '통합 검색 · 로그인 · 회원가입', 'menu' => '01-01 / 01-02', 'ux' => '알림·프로필은 로그인 후만 · CTA로 가입 유도'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '히어로 (좌)', 'elements' => '배지 · H1 · 설명 · CTA 2개 · 소셜 프루프', 'menu' => '—', 'ux' => '「새 디자인」클릭 시 비로그인 → 회원가입/로그인'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '히어로 비주얼 (중)', 'elements' => '마케팅용 정적 이미지. 라벨 편집 연상 일러스트/목업 1장', 'menu' => '01-05', 'ux' => '정적 이미지 · 클릭 동작 없음(optional 링크)'),
        'R-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '둘러보기 (우측)', 'elements' => '새 디자인 · AI 체험 · 템플릿 · 규격 · 서비스 소개 · 요금 안내', 'menu' => '공개 탐색', 'ux' => '엑셀 연동·주문 등 회원 기능은 제외'),
        'R-02' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => 'AI 프로모 카드', 'elements' => 'AI 라벨 소개 · 지금 시작하기', 'menu' => '01-05-05', 'ux' => '비로그인 시 체험/가입 플로우로 연결'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '기능 하이라이트', 'elements' => 'AI · 템플릿 · 규격 · 라벨지 · 인쇄 (5카드)', 'menu' => '각 섹션', 'ux' => '서비스 가치 소개(공개)'),
        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '추천 템플릿', 'elements' => '제목+더보기 · 카테고리 필터 · 가로 스크롤 카드', 'menu' => '01-05-02', 'ux' => '둘러보기 가능 · 저장/적용은 로그인 후'),
    );
}
