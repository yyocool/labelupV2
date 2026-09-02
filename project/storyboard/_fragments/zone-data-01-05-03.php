<?php
/**
 * Zone 데이터: 규격 검색 (01-05-03)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '디자인 · 템플릿 · 규격 검색 active · 편집 도구', 'menu' => '01-05', 'ux' => '규격 검색 active'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피 · 통합 검색창', 'menu' => '01-05-03', 'ux' => '검색어 입력 → 결과 테이블 갱신'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '좌측 필터', 'elements' => '제조사 · 형태 · 용도 체크박스', 'menu' => '01-05-03', 'ux' => '다중 선택 · 필터 초기화'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '결과 테이블', 'elements' => '규격번호 · 치수 · 제조사 · 호환', 'menu' => '01-05-01-01', 'ux' => '행 클릭 → 상세 팝업 → 이 규격으로 시작'),
    );
}
