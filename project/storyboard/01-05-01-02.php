<?php
/**
 * 스토리보드: 내 디자인
 * 메뉴코드: 01-05-01-02
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '내 디자인';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-01-02';
$pageSubtitle = '작업중 · 저장완료 · 공유 · 즐겨찾기 상태별로 내 디자인을 관리';
$metaCards = array(
    array('화면명', '내 디자인'),
    array('URL (예상)', '/editor/mine'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '로그인 사용자'),
    array('화면 목적', '내가 작업한 디자인을 상태별로 탐색·검색하고 다시 편집'),
    array('IA 레벨', '라벨 편집기 › 디자인 › 내 디자인 (01-05-01-02)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '디자인 그룹 · 내 디자인 active', '01-05-01-02'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피 · 새 디자인 만들기', '01-05-01-01'),
    array('M-02', 'nav', 'Nav', '상태 탭', '작업중 · 저장완료 · 공유 · 즐겨찾기', '01-05-01-02'),
    array('M-04', 'ui', 'UI', '툴바', '검색 입력 · 정렬(최근 수정순) · 보기 전환', '01-05-01-02'),
    array('M-03', 'ui', 'UI', '디자인 카드 그리드', '썸네일 · 상태 배지 · 이름 · 규격 · 수정일', '01-05'),
);
$uxRows = array(
    array('진입', '사이드 「내 디자인」 · 편집기 저장 후 자동 이동'),
    array('탭 전환', '상태 탭 클릭 시 그리드 필터링 · URL 쿼리로 상태 유지'),
    array('검색/정렬', '이름·규격 검색 · 최근 수정순/이름순 정렬'),
    array('주요 액션', '카드 클릭 → 편집기 재진입 · 카드 메뉴(⋯) → 이름변경/복제/공유/삭제'),
    array('반응형', 'Tablet 3열 · Mobile 2열 · 탭 가로 스크롤'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-01-02.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-01-02-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
