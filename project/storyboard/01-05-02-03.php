<?php
/**
 * 스토리보드: 공유 템플릿
 * 메뉴코드: 01-05-02-03
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '공유 템플릿';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-02-03';
$pageSubtitle = '다른 사용자가 공유한 템플릿을 인기·최신·다운로드 순으로 탐색';
$metaCards = array(
    array('화면명', '공유 템플릿'),
    array('URL (예상)', '/templates/shared'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '전체 사용자'),
    array('화면 목적', '커뮤니티가 공유한 템플릿을 정렬 기준으로 탐색하고 사용'),
    array('IA 레벨', '라벨 편집기 › 템플릿 › 공유 템플릿 (01-05-02-03)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '템플릿 그룹 · 공유 템플릿 active', '01-05-02-03'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피 · 템플릿 허브로', '01-05-02'),
    array('M-02', 'nav', 'Nav', '정렬 탭', '인기 · 최신 · 다운로드', '01-05-02-03'),
    array('M-03', 'ui', 'UI', '공유 템플릿 그리드', '작성자 · 다운로드 수 · 공유 배지', '01-05'),
);
$uxRows = array(
    array('진입', '템플릿 허브 「공유 템플릿」 카드 · 사이드 「공유 템플릿」'),
    array('정렬', '탭 클릭 시 그리드 즉시 재정렬 (기본: 인기)'),
    array('주요 액션', '카드 클릭 → 상세 미리보기 · 작성자 프로필 · 「사용하기」 → 편집기 적용'),
    array('반응형', 'Tablet 그리드 3열 · Mobile 2열 · 탭 가로 스크롤'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-02-03.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-02-03-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
