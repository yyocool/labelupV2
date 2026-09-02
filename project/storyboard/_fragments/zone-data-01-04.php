<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · HOME active', 'menu' => '01 전역', 'ux' => '1depth 메뉴 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '로고 · 대시보드~설정 · AI 사용 그룹', 'menu' => '01-04', 'ux' => '대시보드 active · 2depth 메뉴'),
        'L-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 충전/내역 보기', 'menu' => '01-06', 'ux' => '사이드바 하단 고정'),
        'L-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'AI 사용량 카드', 'elements' => '320/1,000 크레딧 · 프로그레스바 · "12일 후 초기화" · 업그레이드 버튼', 'menu' => '01-06', 'ux' => '로그인 후에만 노출 · → 결제/구독'),
        'L-05' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '프리미엄 배너', 'elements' => '프리미엄 이용 중 · 혜택 보기 버튼', 'menu' => '01-06', 'ux' => '로그인 후에만 노출 · 사이드바 하단'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바', 'elements' => '검색(⌘K) · 알림(3) · 도움말 · 프로필(Pro)', 'menu' => '01-04', 'ux' => '통합 검색 · 알림 드롭다운'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '인사 + 퀵 액션', 'elements' => '김라벨님 인사 · 4개 빠른 작업 버튼', 'menu' => '01-04', 'ux' => 'CTA → 각 기능 화면'),
        'M-03' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => 'AI 히어로 배너', 'elements' => 'AI 라벨 디자인 NEW · 로봇 · 시작하기', 'menu' => '01-02-05', 'ux' => '→ AI 디자인 생성'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '최근 작업', 'elements' => '4개 카드 · 썸네일 · 수정 시간 · 전체 보기', 'menu' => '01-02', 'ux' => '카드 클릭 → 편집기'),
        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => 'AI 사용 현황', 'elements' => '도넛 75% · 750/1,000 · 항목별 사용 · 업그레이드', 'menu' => '01-06', 'ux' => '자세히 보기 → 마이페이지'),
        'M-06' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '빠른 AI 도구', 'elements' => '4색 카드 · 라벨/텍스트/이미지/규격', 'menu' => '01-02-05', 'ux' => '카드 클릭 → 해당 AI 기능'),
        'M-07' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '알림', 'elements' => '템플릿·주문·포인트 3건 · 시간', 'menu' => '01-04', 'ux' => '항목 클릭 → 상세'),
        'M-08' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '활용 팁', 'elements' => '단축키 · 디자인 · 규격 가이드 3링크', 'menu' => '01-02', 'ux' => '→ 가이드/자료실'),
    );
}
