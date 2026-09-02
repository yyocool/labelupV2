<?php
/**
 * Zone 데이터: 공유 템플릿 (01-05-02-03)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '템플릿 하위 · 공유 템플릿 active', 'menu' => '01-05-02', 'ux' => '무료/프리미엄/공유 전환'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피 · 템플릿 허브 버튼', 'menu' => '01-05-02-03', 'ux' => '버튼 클릭 → 01-05-02'),
        'M-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '정렬 탭', 'elements' => '인기 · 최신 · 다운로드', 'menu' => '01-05-02-03', 'ux' => '탭 전환 → 그리드 재정렬'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '공유 템플릿 그리드', 'elements' => '작성자 · 다운로드 수 · 공유 배지 카드 8종', 'menu' => '01-05', 'ux' => '클릭 → 미리보기 → 사용하기'),
    );
}
