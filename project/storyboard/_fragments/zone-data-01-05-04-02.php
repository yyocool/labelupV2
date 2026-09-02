<?php
/**
 * Zone 데이터: 바코드·QR (01-05-04-02)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '편집 도구 하위 · 바코드·QR active', 'menu' => '01-05-04', 'ux' => '도구 4종 전환'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피', 'menu' => '01-05-04-02', 'ux' => '정적 헤더'),
        'M-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '코드 유형 탭', 'elements' => 'CODE128 · EAN13 · QR · WIFI', 'menu' => '01-05-04-02', 'ux' => '탭 전환 → 입력 폼 구성 변경'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '입력 폼', 'elements' => '데이터 · 크기 · 색상 · 여백', 'menu' => '01-05-04-02', 'ux' => '입력 시 미리보기 실시간 갱신'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '미리보기 박스', 'elements' => '코드 렌더링 · 캔버스에 추가 버튼', 'menu' => '01-05', 'ux' => '클릭 → 01-05 편집기 캔버스에 삽입'),
    );
}
