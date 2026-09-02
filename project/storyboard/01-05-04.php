<?php
/**
 * 스토리보드: 편집 도구
 * 메뉴코드: 01-05-04
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '편집 도구';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-04';
$pageSubtitle = '디자인 편집 · 바코드·QR · 데이터 연동 · 출력·저장 도구 모음';
$metaCards = array(
    array('화면명', '편집 도구'),
    array('URL (예상)', '/editor/tools'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '로그인 사용자'),
    array('화면 목적', '편집기 관련 4가지 도구로 빠르게 진입'),
    array('IA 레벨', '라벨 편집기 › 편집 도구 (01-05-04)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '편집 도구 그룹 active', '01-05-04'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피', '01-05-04'),
    array('M-02', 'ui', 'UI', '도구 카드 4종', '디자인 편집 · 바코드·QR · 데이터 연동 · 출력·저장', '01-05-04-01 / 02 / 03 / 04'),
);
$uxRows = array(
    array('진입', '사이드 「편집 도구」 · 편집기 상단 도구 메뉴'),
    array('주요 액션', '카드 클릭 → 각 도구 화면(01-05-04-01~04) 이동'),
    array('반응형', 'Tablet 2열 · Mobile 1열'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-04.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-04-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
