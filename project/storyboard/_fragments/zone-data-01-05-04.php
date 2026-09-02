<?php
/**
 * Zone 데이터: 편집 도구 (01-05-04)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '편집 도구 그룹 active', 'menu' => '01-05', 'ux' => '편집 도구 하위 4종 노출'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피', 'menu' => '01-05-04', 'ux' => '정적 헤더'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '도구 카드 4종', 'elements' => '디자인 편집 · 바코드·QR · 데이터 연동 · 출력·저장', 'menu' => '01-05-04-01 / 02 / 03 / 04', 'ux' => '카드 클릭 → 각 도구 화면'),
    );
}
