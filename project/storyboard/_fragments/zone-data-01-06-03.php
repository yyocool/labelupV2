<?php
/**
 * Zone 데이터: 마이페이지 — 결제·구독 (01-06-03)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '글로벌 사이드바', 'elements' => '홈 · 템플릿 · 용지/라벨 · AI디자인 · 내디자인 · 주문내역 · 브랜드관리 · 하단: 가이드·마이페이지(active)·고객센터·설정', 'menu' => '01-06', 'ux' => '마이페이지 active · Mobile: 드로어'),
        'L-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 포인트 충전', 'menu' => '01-06', 'ux' => '사이드바 하단 고정'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바 · 서브탭', 'elements' => '검색·장바구니·알림·프로필 + 내 정보/주문·배송/결제·구독(active)/디자인 관리 탭', 'menu' => '01-06-03', 'ux' => '탭 전환 → 각 마이페이지 하위 화면'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '현재 플랜 카드', 'elements' => 'Pro Plan · 이용기간 · 잔여일 · 월 사용량 프로그레스', 'menu' => '01-06-03', 'ux' => '플랜 비교 · 업그레이드'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '결제 수단', 'elements' => '등록 카드 정보 · 변경 · 결제 수단 추가', 'menu' => '01-06-03', 'ux' => '카드 변경 모달'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '결제 내역', 'elements' => '월별 구독 결제 목록 · 영수증 전체 보기', 'menu' => '01-06-03', 'ux' => '항목 클릭 → 영수증 상세'),
        'M-05' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '업그레이드 배너', 'elements' => 'Business 플랜 안내 · 업그레이드 CTA', 'menu' => '01-06-03', 'ux' => '→ 플랜 변경 화면'),
    );
}
