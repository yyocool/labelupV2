<?php
/**
 * Zone 데이터: 데이터 연동 (01-05-04-03)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '편집 도구 하위 · 데이터 연동 active', 'menu' => '01-05-04', 'ux' => '도구 4종 전환'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피', 'menu' => '01-05-04-03', 'ux' => '정적 헤더'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '업로드 존', 'elements' => 'Excel 업로드 · CSV 업로드 (드래그앤드롭)', 'menu' => '01-05-04-03', 'ux' => '업로드 시 필드 자동 인식 → 매핑 테이블 노출'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '필드 매핑 테이블', 'elements' => '데이터 필드 · 라벨 요소 · 예시 값', 'menu' => '01-05-04-03', 'ux' => '드롭다운으로 필드-오브젝트 연결'),
        'M-04' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '일괄 생성 CTA', 'elements' => '감지 건수 안내 · 일괄 생성 버튼', 'menu' => '01-05', 'ux' => '클릭 → 라벨 자동 생성 후 편집기 이동'),
    );
}
