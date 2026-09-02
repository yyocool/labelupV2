<?php
/**
 * 스토리보드: 출력·저장
 * 메뉴코드: 01-05-04-04
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '출력·저장';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-04-04';
$pageSubtitle = 'PDF·PNG 내보내기 · 인쇄 설정 · 저장 위치를 한 화면에서 관리';
$metaCards = array(
    array('화면명', '출력·저장'),
    array('URL (예상)', '/editor/tools/export'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '로그인 사용자'),
    array('화면 목적', '완성된 디자인을 원하는 형식으로 내보내거나 인쇄·저장'),
    array('IA 레벨', '라벨 편집기 › 편집 도구 › 출력·저장 (01-05-04-04)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '편집 도구 · 출력·저장 active', '01-05-04-04'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피', '01-05-04-04'),
    array('M-02', 'ui', 'UI', '내보내기 옵션 3종', 'PDF · PNG · 직접 출력', '01-05-04-04'),
    array('M-03', 'ui', 'UI', '인쇄 설정', '용지 크기 · 매수 · 컬러/흑백 · 페이지당 수량', '01-05-04-04'),
    array('M-04', 'ui', 'UI', '저장 위치', '내 컴퓨터 · 내 디자인 · 클라우드 · 인쇄소 주문', '01-05-01-02'),
);
$uxRows = array(
    array('진입', '편집 도구 허브 「출력·저장」 카드 · 편집기 상단 저장/내보내기 버튼'),
    array('주요 액션', '옵션 선택 후 「내보내기」 → 형식별 다운로드/인쇄 다이얼로그 · 「인쇄소로 주문」 → 쇼핑몰 인쇄 의뢰 연계'),
    array('보조 액션', '저장 위치 선택 시 「내 디자인(01-05-01-02)」에 자동 저장'),
    array('반응형', 'Tablet 옵션 2열 · Mobile 1열 · 설정/저장 위치 아코디언'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-04-04.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-04-04-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
