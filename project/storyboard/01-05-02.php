<?php
/**
 * 스토리보드: 템플릿
 * 메뉴코드: 01-05-02
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '템플릿';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-02';
$pageSubtitle = '무료 · 프리미엄 · 공유 템플릿 카테고리와 추천 템플릿을 한눈에';
$metaCards = array(
    array('화면명', '템플릿'),
    array('URL (예상)', '/templates'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '로그인 사용자 (일부 비로그인 열람 가능)'),
    array('화면 목적', '템플릿 카테고리로 진입하거나 추천 템플릿을 바로 사용'),
    array('IA 레벨', '라벨 편집기 › 템플릿 (01-05-02)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '템플릿 그룹 · 템플릿 active', '01-05-02'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피 · 새 디자인 만들기', '01-05-01-01'),
    array('M-02', 'cta', 'CTA', '프로모션 배너', '이번 주 추천 템플릿 카피 · 둘러보기 버튼', '01-05-02'),
    array('M-03', 'ui', 'UI', '카테고리 진입 카드 3종', '무료 · 프리미엄 · 공유', '01-05-02-01 / 02 / 03'),
    array('M-04', 'ui', 'UI', '추천 템플릿 그리드', '카드 8종 · FREE/PRO/공유 배지', '01-05-02-01 / 02 / 03'),
);
$uxRows = array(
    array('진입', '사이드 「템플릿」 · 새 디자인 만들기 옵션에서 이동'),
    array('주요 액션', '카테고리 카드 클릭 → 하위 목록(01-05-02-01/02/03) · 추천 카드 클릭 → 즉시 편집기 적용'),
    array('배지', 'FREE(초록) · PRO(주황, 잠금) · 공유(파랑)로 구분'),
    array('반응형', 'Tablet 카테고리 1열 · 그리드 3열 · Mobile 그리드 2열'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-02.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-02-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
