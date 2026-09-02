<?php
/**
 * 스토리보드: 디자인 편집
 * 메뉴코드: 01-05-04-01
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '디자인 편집';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-04-01';
$pageSubtitle = '캔버스와 속성 패널 미리보기 · 전체 편집기는 01-05에서 제공';
$metaCards = array(
    array('화면명', '디자인 편집'),
    array('URL (예상)', '/editor/tools/design'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '로그인 사용자'),
    array('화면 목적', '편집 도구 중 디자인 편집 기능을 간단 미리보기로 소개하고 전체 편집기로 연결'),
    array('IA 레벨', '라벨 편집기 › 편집 도구 › 디자인 편집 (01-05-04-01)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '편집 도구 · 디자인 편집 active', '01-05-04-01'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피 · 편집기 열기', '01-05'),
    array('M-02', 'ui', 'UI', '캔버스 미리보기', '툴 레일 요약 · 캔버스 목업 · 전체 편집기 안내', '01-05'),
    array('M-03', 'ui', 'UI', '속성 패널 미리보기', '위치·크기·폰트·색상 필드 목업', '01-05'),
);
$uxRows = array(
    array('진입', '편집 도구 허브 「디자인 편집」 카드 · 사이드 「디자인 편집」'),
    array('주요 액션', '「편집기로 열기」 클릭 → 전체 편집기(01-05)로 이동, 여기서는 요약 미리보기만 제공'),
    array('안내', '이 화면은 기능 소개용 · 실제 편집은 01-05 라벨 편집기 화면에서 진행'),
    array('반응형', 'Tablet 캔버스/속성 세로 스택 · Mobile 속성 패널 하단 시트'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-04-01.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-04-01-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
