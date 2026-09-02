<?php
/**
 * 스토리보드: 규격 검색
 * 메뉴코드: 01-05-03
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '규격 검색';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-03';
$pageSubtitle = '제조사·형태·용도로 규격을 필터링하고 호환 규격까지 확인';
$metaCards = array(
    array('화면명', '규격 검색'),
    array('URL (예상)', '/spec/search'),
    array('레이아웃', '전역 사이드 + 좌측 필터 + 우측 결과 테이블'),
    array('접근 권한', '전체 사용자'),
    array('화면 목적', '조건에 맞는 라벨 규격을 찾고 편집기로 바로 연결'),
    array('IA 레벨', '라벨 편집기 › 규격 검색 (01-05-03)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '규격 검색 active', '01-05-03'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피 · 통합 검색창', '01-05-03'),
    array('M-02', 'ui', 'UI', '좌측 필터', '제조사 · 형태 · 용도 체크박스 그룹', '01-05-03'),
    array('M-03', 'ui', 'UI', '결과 테이블', '규격번호 · 치수 · 제조사 · 호환', '01-05-01-01'),
);
$uxRows = array(
    array('진입', '사이드 「규격 검색」 · 새 디자인 만들기의 「규격 선택」 옵션'),
    array('필터', '체크박스 다중 선택 시 결과 테이블 즉시 갱신 · 필터 초기화 버튼'),
    array('주요 액션', '행 클릭 → 규격 상세 팝업 · 「이 규격으로 시작」 → 편집기(01-05-01-01) 이동'),
    array('반응형', 'Tablet 필터 상단 접이식 · Mobile 필터 바텀시트 · 테이블 가로 스크롤'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-03.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-03-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
