<?php
/**
 * Zone 데이터: 템플릿 (01-05-02)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '디자인 · 템플릿 · 규격 검색 · 편집 도구', 'menu' => '01-05', 'ux' => '템플릿 active'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피 · 새 디자인 만들기 버튼', 'menu' => '01-05-02', 'ux' => '버튼 클릭 → 01-05-01-01'),
        'M-02' => array('type' => 'CTA', 'typeKey' => 'cta', 'block' => '프로모션 배너', 'elements' => '이번 주 추천 템플릿 카피 · 둘러보기', 'menu' => '01-05-02', 'ux' => '클릭 → 추천 그리드로 스크롤'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '카테고리 진입 카드', 'elements' => '무료 · 프리미엄 · 공유 3종', 'menu' => '01-05-02-01 / 02 / 03', 'ux' => '카드 클릭 → 각 하위 목록'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '추천 템플릿 그리드', 'elements' => '카드 8종 · FREE/PRO/공유 배지', 'menu' => '01-05-02-01 / 02 / 03', 'ux' => '클릭 → 상세 미리보기 → 편집기 적용'),
    );
}
