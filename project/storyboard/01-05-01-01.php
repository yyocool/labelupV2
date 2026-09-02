<?php
/**
 * 스토리보드: 새 디자인 만들기
 * 메뉴코드: 01-05-01-01
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '새 디자인 만들기';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-01-01';
$pageSubtitle = '규격 선택 · 빈 템플릿 · AI 생성 · PDF 업로드 중 하나로 새 디자인을 시작';
$metaCards = array(
    array('화면명', '새 디자인 만들기'),
    array('URL (예상)', '/editor/new'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '로그인 사용자'),
    array('화면 목적', '4가지 시작 방식 중 하나를 선택해 편집기로 진입'),
    array('IA 레벨', '라벨 편집기 › 디자인 › 새 디자인 만들기 (01-05-01-01)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '디자인 그룹 · 새 디자인 만들기 active', '01-05-01-01'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피 · 「내 디자인」 바로가기', '01-05-01-01'),
    array('M-02', 'ui', 'UI', '시작 옵션 4종', '규격 선택 · 빈 템플릿 · AI 생성 · PDF 업로드', '01-05-03 / 01-05-05'),
    array('M-03', 'ui', 'UI', '최근 사용 규격', '규격 칩 5종 · 규격 검색 바로가기', '01-05-03'),
);
$uxRows = array(
    array('진입', '사이드 「새 디자인 만들기」 클릭 · 편집기 최초 진입 시 기본 화면'),
    array('주요 액션', '옵션 카드 클릭 → 규격 선택 모달 / 빈 캔버스 / AI 생성 화면 / 파일 업로드 다이얼로그'),
    array('보조 액션', '최근 사용 규격 칩 클릭 시 해당 규격으로 즉시 편집기 진입'),
    array('반응형', 'Tablet 2열 · Mobile 1열 · 사이드 숨김(하단 탭바로 대체)'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-01-01.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-01-01-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
