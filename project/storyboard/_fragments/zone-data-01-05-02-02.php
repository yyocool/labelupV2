<?php
/**
 * Zone 데이터: 프리미엄 템플릿 (01-05-02-02)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '템플릿 하위 · 프리미엄 템플릿 active', 'menu' => '01-05-02', 'ux' => '무료/프리미엄/공유 전환'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피 · 템플릿 허브 버튼', 'menu' => '01-05-02-02', 'ux' => '버튼 클릭 → 01-05-02'),
        'M-02' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => 'Pro 잠금 배너', 'elements' => 'Pro 안내 카피 · 업그레이드 버튼', 'menu' => '01-06', 'ux' => '클릭 → 요금제/결제'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '업종 칩', 'elements' => '브랜드 패키지 · 고급 · 시즌', 'menu' => '01-05-02-02', 'ux' => '칩 선택 → 그리드 필터링'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '프리미엄 템플릿 그리드', 'elements' => 'PRO 배지 · 잠금 아이콘 카드 8종', 'menu' => '01-06', 'ux' => '클릭 → 워터마크 미리보기 → 업그레이드/적용'),
    );
}
