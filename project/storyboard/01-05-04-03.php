<?php
/**
 * 스토리보드: 데이터 연동
 * 메뉴코드: 01-05-04-03
 *
 * @var array $menu
 * @var array|null $storyboard
 * @var array $sbFsMenuTree
 * @var int $sbFsMenuId
 * @var string $sbFsLinkBase
 * @var array $sbFsContentStatusMap
 */
$pageTitle = isset($menu['title']) ? $menu['title'] : '데이터 연동';
$menuCode = isset($menu['menu_code']) ? $menu['menu_code'] : '01-05-04-03';
$pageSubtitle = 'Excel·CSV를 업로드해 필드를 매핑하고 라벨을 일괄 생성';
$metaCards = array(
    array('화면명', '데이터 연동'),
    array('URL (예상)', '/editor/tools/data'),
    array('레이아웃', '전역 사이드 + 허브 본문'),
    array('접근 권한', '로그인 사용자'),
    array('화면 목적', '외부 데이터를 업로드해 라벨 요소에 매핑하고 대량 생성'),
    array('IA 레벨', '라벨 편집기 › 편집 도구 › 데이터 연동 (01-05-04-03)'),
);
$layoutRows = array(
    array('L-01', 'layout', 'Layout', '아이콘 레일', '8개 · 라벨디자인 active', '01 전역'),
    array('L-02', 'nav', 'Nav', '사이드 네비', '편집 도구 · 데이터 연동 active', '01-05-04-03'),
    array('M-01', 'nav', 'Nav', '상단 헤더', '타이틀 · 서브카피', '01-05-04-03'),
    array('M-02', 'ui', 'UI', '업로드 존', 'Excel 업로드 · CSV 업로드 (드래그앤드롭)', '01-05-04-03'),
    array('M-03', 'ui', 'UI', '필드 매핑 테이블', '데이터 필드 · 라벨 요소 · 예시 값', '01-05-04-03'),
    array('M-04', 'cta', 'CTA', '일괄 생성 CTA', '감지 건수 안내 · 일괄 생성 버튼', '01-05'),
);
$uxRows = array(
    array('진입', '편집 도구 허브 「데이터 연동」 카드 · 편집기 툴 레일'),
    array('업로드', '파일 드래그앤드롭 또는 클릭 선택 → 헤더 행 자동 인식'),
    array('매핑', '데이터 필드를 라벨의 텍스트·바코드 오브젝트에 드롭다운으로 연결'),
    array('주요 액션', '「일괄 생성」 클릭 → 매핑된 데이터 수만큼 라벨 페이지 자동 생성 후 편집기(01-05)로 이동'),
    array('반응형', 'Tablet 업로드 존 세로 스택 · Mobile 매핑 테이블 가로 스크롤'),
);
$sbWfRootClass = 'sb-wf sb-wf--hifi sb-wf--hub sb-wf-annotate';
$sbHubZoneFile = __DIR__ . '/_fragments/zone-data-01-05-04-03.php';
$sbHubBodyFile = __DIR__ . '/_fragments/01-05-04-03-hifi-wireframe-body.php';
$sbHubStyles = array(__DIR__ . '/_fragments/01-05-hub-shared-styles.php');
include __DIR__ . '/_fragments/storyboard-hub-boot.inc.php';
