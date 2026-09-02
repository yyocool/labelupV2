<?php
/**
 * Zone 데이터: 내 디자인 (01-05-01-02)
 */
if (!isset($sbZoneDataMap)) {
    $sbZoneDataMap = array(
        'L-01' => array('type' => 'Layout', 'typeKey' => 'layout', 'block' => '아이콘 레일', 'elements' => '8개 · 라벨디자인 active', 'menu' => '01 전역', 'ux' => '1depth 이동 · Mobile: 드로어'),
        'L-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '사이드 네비', 'elements' => '디자인 · 템플릿 · 규격 검색 · 편집 도구', 'menu' => '01-05', 'ux' => '내 디자인 active'),
        'M-01' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상단 헤더', 'elements' => '타이틀 · 서브카피 · 새 디자인 만들기 버튼', 'menu' => '01-05-01-02', 'ux' => '버튼 클릭 → 01-05-01-01'),
        'M-02' => array('type' => 'Nav', 'typeKey' => 'nav', 'block' => '상태 탭', 'elements' => '작업중 · 저장완료 · 공유 · 즐겨찾기', 'menu' => '01-05-01-02', 'ux' => '탭 전환 → 그리드 필터링'),
        'M-04' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '툴바', 'elements' => '검색 · 정렬 · 보기 전환(그리드/리스트)', 'menu' => '01-05-01-02', 'ux' => '입력 시 그리드 즉시 필터링'),
        'M-03' => array('type' => 'UI', 'typeKey' => 'ui', 'block' => '디자인 카드 그리드', 'elements' => '썸네일 · 상태 배지 · 이름 · 규격 · 수정일 · ⋯메뉴', 'menu' => '01-05', 'ux' => '클릭 → 편집기 · ⋯ → 복제/공유/삭제'),
    );
}
