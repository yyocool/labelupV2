<?php
/**
 * Zone 데이터: 마이페이지 — 주문·배송 (01-06-02)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '글로벌 사이드바', 'elements' => '홈 · 템플릿 · 용지/라벨 · AI디자인 · 내디자인 · 주문내역 · 브랜드관리 · 하단: 가이드·마이페이지(active)·고객센터·설정', 'menu' => '01-06', 'ux' => '마이페이지 active · Mobile: 드로어'),
        'L-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 포인트 충전', 'menu' => '01-06', 'ux' => '사이드바 하단 고정'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바 · 서브탭', 'elements' => '검색·장바구니·알림·프로필 + 내 정보/주문·배송(active)/결제·구독/디자인 관리 탭', 'menu' => '01-06-02', 'ux' => '탭 전환 → 각 마이페이지 하위 화면'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '주문 상태 필터', 'elements' => '전체 · 배송중 · 완료 · 취소', 'menu' => '01-06-02', 'ux' => '탭 클릭 시 표 필터링'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '주문 내역 표', 'elements' => '주문번호 · 상품 · 금액 · 상태 · 주문일', 'menu' => '01-06-02', 'ux' => '행 클릭 → 주문 상세'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '배송지 관리', 'elements' => '기본 배송지 카드 · 배송지 추가', 'menu' => '01-06-02', 'ux' => '→ 배송지 CRUD'),
    );
}
