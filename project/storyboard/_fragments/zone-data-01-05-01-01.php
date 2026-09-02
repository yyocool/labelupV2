<?php
/**
 * Zone 데이터: 새 디자인 만들기 (01-05-01-01)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '디자인 · 템플릿 · 규격 검색 · 편집 도구', 'menu' => '01-05', 'ux' => '새 디자인 만들기 active'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피 · 「내 디자인」 버튼', 'menu' => '01-05-01-01', 'ux' => '버튼 클릭 → 01-05-01-02'),
        'M-02' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '시작 옵션 4종', 'elements' => '규격 선택 · 빈 템플릿 · AI 생성 · PDF 업로드', 'menu' => '01-05-03 / 01-05-05', 'ux' => '카드 클릭 → 각 시작 플로우'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '최근 사용 규격', 'elements' => '규격 칩 5종 · 더 찾아보기', 'menu' => '01-05-03', 'ux' => '칩 클릭 → 해당 규격으로 편집기 진입'),
    );
}
