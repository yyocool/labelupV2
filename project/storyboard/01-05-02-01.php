<?php
/**
 * 스토리보드: 무료 템플릿
 * 메뉴코드: 01-05-02-01
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '무료 템플릿';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-02-01';
$pageSubtitle = '업종 칩으로 필터링하고 무료 템플릿을 바로 적용';
$metaCards = array(
    array('화면명', '무료 템플릿'),
    array('URL (예상)', '/templates/free'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '전체 사용자'),
    array('화면 목적', '업종별 무료 템플릿을 검색·필터링하여 즉시 사용'),
    array('IA 레벨', '라벨 편집기 › 템플릿 › 무료 템플릿 (01-05-02-01)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '템플릿 그룹 · 무료 템플릿 active', '01-05-02-01'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피 · 템플릿 허브로', '01-05-02'),
    array('M-02', 'ui', 'UI', '검색 + 업종 칩', '검색창 · 식품/화장품/물류/네임 칩', '01-05-02-01'),
    array('M-03', 'ui', 'UI', '무료 템플릿 그리드', 'FREE 배지 카드 · 규격 표시', '01-05'),
);
$uxRows = array(
    array('진입', '템플릿 허브 「무료 템플릿」 카드 · 사이드 「무료 템플릿」'),
    array('필터', '업종 칩 다중 선택 · 검색어와 동시 적용 (AND)'),
    array('주요 액션', '카드 클릭 → 미리보기 팝업 → 「이 템플릿으로 시작」 → 편집기 진입'),
    array('반응형', 'Tablet 그리드 3열 · Mobile 2열 · 칩 가로 스크롤'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-02-01.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-02-01-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
