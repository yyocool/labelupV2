<?php
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '글로벌 사이드바', 'elements' => '홈 · 템플릿 · 용지/라벨 · AI디자인 · 내디자인 · 주문내역 · 브랜드관리 · 하단: 가이드·마이페이지(active)·고객센터·설정', 'menu' => '01-06', 'ux' => '마이페이지 active · Mobile: 드로어'),
        'L-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 포인트 충전', 'menu' => '01-06', 'ux' => '사이드바 하단 고정'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바', 'elements' => '검색 · 장바구니(2) · 알림(3) · 프로필', 'menu' => '01-06', 'ux' => '통합 검색 · 알림 드롭다운'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '회원·플랜 카드', 'elements' => '프로필 · Pro Plan · 이메일/연락처 · 회원정보/비밀번호 변경 · 이용기간·잔여일·월 사용량 · 퀵 아이콘 4종', 'menu' => '01-06-01', 'ux' => '플랜 배지 · 사용량 프로그레스'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '요약 통계', 'elements' => '보유 포인트 · 쿠폰(3) · 최근 주문(3) · 배송중(1)', 'menu' => '01-06', 'ux' => '각 카드 → 상세 화면'),
        'M-04' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '바로가기', 'elements' => '라벨 편집 · 새 라벨 · AI 디자인 · 엑셀 제작 · 주문 · 샘플 신청', 'menu' => '01-06', 'ux' => '아이콘 6종 → 각 기능 화면'),
        'M-05' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '최근 디자인', 'elements' => '가로 스크롤 카드 · 상태 태그 · 새 디자인(+) · 전체 보기', 'menu' => '01-06-04', 'ux' => '카드 클릭 → 편집기'),
        'M-06' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '최근 주문 내역', 'elements' => '썸네일 · 상품명·규격 · 주문일 · 상태 배지', 'menu' => '01-06-02', 'ux' => '항목 클릭 → 주문 상세'),
        'M-07' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '라벨 편집 도구', 'elements' => '에디터 · 엑셀 · AI · 내 템플릿 4항목', 'menu' => '01-02', 'ux' => '→ 각 도구 화면'),
        'M-08' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '내 템플릿', 'elements' => '저장 템플릿 목록 · 항목 수', 'menu' => '01-06-04', 'ux' => '→ 템플릿 관리'),
        'M-09' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '브랜드 관리', 'elements' => '브랜드 로고·이름 · 새 브랜드 추가', 'menu' => '01-06', 'ux' => '→ 브랜드 설정'),
        'M-10' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '배송지 관리', 'elements' => '기본 배송지 · 배송지 추가', 'menu' => '01-06-02', 'ux' => '→ 배송지 CRUD'),
        'M-11' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '계정·설정', 'elements' => '회원정보 · 알림설정 · 결제/구독 · 보안 4아이콘', 'menu' => '01-06-01', 'ux' => '→ 각 설정 화면'),
        'M-12' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '도움말 배너', 'elements' => '1:1 문의 · FAQ · 헤드셋 일러스트', 'menu' => '01-07', 'ux' => '→ 고객센터'),
    );
}
