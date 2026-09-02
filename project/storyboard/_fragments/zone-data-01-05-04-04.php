<?php
/**
 * Zone 데이터: 출력·저장 (01-05-04-04)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '편집 도구 하위 · 출력·저장 active', 'menu' => '01-05-04', 'ux' => '도구 4종 전환'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피', 'menu' => '01-05-04-04', 'ux' => '정적 헤더'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '내보내기 옵션', 'elements' => 'PDF · PNG · 직접 출력', 'menu' => '01-05-04-04', 'ux' => '옵션 선택 → 형식별 다운로드/인쇄 다이얼로그'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '인쇄 설정', 'elements' => '용지 크기 · 매수 · 컬러/흑백 · 페이지당 수량', 'menu' => '01-05-04-04', 'ux' => '직접 출력 선택 시에만 노출'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '저장 위치', 'elements' => '내 컴퓨터 · 내 디자인 · 클라우드 · 인쇄소 주문', 'menu' => '01-05-01-02', 'ux' => '클릭 → 각 저장/주문 플로우'),
    );
}
