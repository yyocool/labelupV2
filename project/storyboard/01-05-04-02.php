<?php
/**
 * 스토리보드: 바코드·QR
 * 메뉴코드: 01-05-04-02
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '바코드·QR';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-04-02';
$pageSubtitle = 'CODE128 · EAN13 · QR · WIFI 코드를 생성해 라벨에 삽입';
$metaCards = array(
    array('화면명', '바코드·QR'),
    array('URL (예상)', '/editor/tools/barcode'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '로그인 사용자'),
    array('화면 목적', '코드 유형별 데이터를 입력해 바코드·QR을 생성하고 캔버스에 삽입'),
    array('IA 레벨', '라벨 편집기 › 편집 도구 › 바코드·QR (01-05-04-02)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '편집 도구 · 바코드·QR active', '01-05-04-02'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피', '01-05-04-02'),
    array('M-02', 'nav', 'Nav', '코드 유형 탭', 'CODE128 · EAN13 · QR · WIFI', '01-05-04-02'),
    array('M-03', 'ui', 'UI', '입력 폼', '데이터 · 크기 · 색상 · 여백', '01-05-04-02'),
    array('M-04', 'ui', 'UI', '미리보기 박스', '실시간 코드 렌더링 · 캔버스에 추가', '01-05'),
);
$uxRows = array(
    array('진입', '편집 도구 허브 「바코드·QR」 카드 · 편집기 툴 레일'),
    array('탭 전환', '코드 유형 탭에 따라 입력 폼 필드 구성이 달라짐 (WIFI는 SSID/비밀번호 등)'),
    array('주요 액션', '데이터 입력 시 미리보기 실시간 갱신 · 「캔버스에 추가」 → 01-05 편집기에 오브젝트 삽입'),
    array('반응형', 'Tablet 폼/미리보기 세로 스택 · Mobile 탭 가로 스크롤'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-04-02.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-04-02-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
