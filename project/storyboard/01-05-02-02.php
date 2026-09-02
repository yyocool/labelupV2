<?php
/**
 * 스토리보드: 프리미엄 템플릿
 * 메뉴코드: 01-05-02-02
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '프리미엄 템플릿';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-02-02';
$pageSubtitle = 'Pro 멤버십 전용 고급 템플릿 · 업그레이드 유도';
$metaCards = array(
    array('화면명', '프리미엄 템플릿'),
    array('URL (예상)', '/templates/premium'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '전체 사용자 열람 · 사용은 Pro 멤버십'),
    array('화면 목적', '프리미엄 템플릿을 미리 보여주고 Pro 업그레이드로 전환'),
    array('IA 레벨', '라벨 편집기 › 템플릿 › 프리미엄 템플릿 (01-05-02-02)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '템플릿 그룹 · 프리미엄 템플릿 active', '01-05-02-02'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피 · 템플릿 허브로', '01-05-02'),
    array('M-02', 'cta', 'CTA', 'Pro 잠금 배너', 'Pro 멤버십 안내 · 업그레이드 버튼', '01-06'),
    array('M-03', 'ui', 'UI', '업종 칩', '브랜드 패키지 · 고급 · 시즌', '01-05-02-02'),
    array('M-04', 'ui', 'UI', '프리미엄 템플릿 그리드', 'PRO 배지 · 잠금 아이콘 카드', '01-06'),
);
$uxRows = array(
    array('진입', '템플릿 허브 「프리미엄 템플릿」 카드 · 사이드 「프리미엄 템플릿」'),
    array('주요 액션', '카드 클릭 → 미리보기(워터마크) → 비회원/Free는 업그레이드 유도, Pro는 즉시 적용'),
    array('배너', '상단 잠금 배너의 「업그레이드」 클릭 → 요금제/결제 화면(01-06)'),
    array('반응형', 'Tablet 그리드 3열 · Mobile 2열 · 배너 세로 스택'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-02-02.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-02-02-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
