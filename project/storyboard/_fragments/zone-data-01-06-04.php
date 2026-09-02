<?php
/**
 * Zone 데이터: 마이페이지 — 디자인 관리 (01-06-04)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '글로벌 사이드바', 'elements' => '홈 · 템플릿 · 용지/라벨 · AI디자인 · 내디자인 · 주문내역 · 브랜드관리 · 하단: 가이드·마이페이지(active)·고객센터·설정', 'menu' => '01-06', 'ux' => '마이페이지 active · Mobile: 드로어'),
        'L-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '보유 포인트', 'elements' => '12,450 P · 포인트 충전', 'menu' => '01-06', 'ux' => '사이드바 하단 고정'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 바 · 서브탭', 'elements' => '검색·장바구니·알림·프로필 + 내 정보/주문·배송/결제·구독/디자인 관리(active) 탭', 'menu' => '01-06-04', 'ux' => '탭 전환 → 각 마이페이지 하위 화면'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '상태 필터 탭', 'elements' => '전체 · 작업중 · 완료 · 공유', 'menu' => '01-06-04', 'ux' => '탭 클릭 시 그리드 필터링'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '디자인 그리드', 'elements' => '썸네일 카드 · 선택 체크박스 · 상태 태그 · 새 디자인(+)', 'menu' => '01-06-04', 'ux' => '카드 클릭 → 편집기 · 체크 → 일괄 작업'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '일괄 작업 바', 'elements' => '선택 개수 · 공유 · 복제 · 삭제', 'menu' => '01-06-04', 'ux' => '1개 이상 선택 시 노출'),
    );
}
