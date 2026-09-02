<?php
/**
 * Zone 데이터: 디자인 편집 (01-05-04-01)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '편집 도구 하위 · 디자인 편집 active', 'menu' => '01-05-04', 'ux' => '도구 4종 전환'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피 · 편집기로 열기 버튼', 'menu' => '01-05', 'ux' => '버튼 클릭 → 전체 편집기(01-05)'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '캔버스 미리보기', 'elements' => '툴 레일 요약(텍스트·이미지·바코드·표·도형) · 캔버스 목업', 'menu' => '01-05', 'ux' => '실제 편집은 01-05에서 진행'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '속성 패널 미리보기', 'elements' => '위치 X/Y · 크기 W/H · 폰트 · 색상', 'menu' => '01-05', 'ux' => '오브젝트 선택 시 노출되는 패널 요약'),
    );
}
