<?php
/**
 * 스토리보드: 공지사항
 * 메뉴코드: 02-08-01
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-08-01';
$adminTitle = '공지사항';
$adminSub = '공지 작성·노출 기간·상단 고정 관리';
$adminActive = '02-08';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '공지사항 관리'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/content/notices'),
    array('dt' => '연관 테이블', 'dd' => 'notices · notice_categories'),
    array('dt' => '접근 권한', 'dd' => '관리자 · CS'),
    array('dt' => '화면 목적', 'dd' => '공지 CRUD·노출/고정·예약 발행'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 콘텐츠 관리 › 공지사항'),
);

$adminZones = array(
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '검색/필터', 'el' => '제목·분류·상태·기간', 'link' => '—'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '공지 테이블', 'el' => '제목·분류·고정·노출기간·조회수·상태', 'link' => '01-09-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '작성/수정', 'el' => '제목·분류·리치 에디터·첨부·노출 설정', 'link' => '—'),
);

$adminUx = array(
    array('item' => '에디터', 'desc' => '리치텍스트(이미지/표/링크). 이미지 업로드→CDN'),
    array('item' => '상단 고정', 'desc' => '고정 토글 + 고정 순서. 노출 기간 예약'),
    array('item' => '대상 노출', 'desc' => '전체/등급별/특정 채널 노출 대상 지정'),
    array('item' => '미리보기', 'desc' => '프론트(01-09-01) 뷰 미리보기'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>공지사항</h3><p>총 142개 · 노출 8 · 고정 2</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn sb-adm-btn--primary">＋ 공지 작성</span></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-input" style="min-width:200px">🔍 제목 검색</span>
    <span class="sb-adm-input sel">분류: 전체</span>
    <span class="sb-adm-input sel">상태: 전체</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">검색</span>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr><th><span class="sb-adm-checkbox"></span></th><th>제목</th><th>분류</th><th>고정</th><th>노출 기간</th><th class="num">조회수</th><th>상태</th><th></th></tr></thead>
        <tbody>
            <tr><td><span class="sb-adm-checkbox"></span></td><td class="strong">[중요] 폭우로 인한 배송 지연 안내</td><td>배송</td><td>📌</td><td>07-04 ~ 07-10</td><td class="num">4,821</td><td><span class="sb-adm-badge sb-adm-badge--green">노출</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
            <tr><td><span class="sb-adm-checkbox"></span></td><td class="strong">7월 정기 시스템 점검 안내</td><td>서비스</td><td>📌</td><td>07-01 ~ 07-15</td><td class="num">2,140</td><td><span class="sb-adm-badge sb-adm-badge--green">노출</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
            <tr><td><span class="sb-adm-checkbox"></span></td><td>여름 시즌 템플릿 업데이트</td><td>이벤트</td><td>—</td><td>07-08 ~ 07-31</td><td class="num">0</td><td><span class="sb-adm-badge sb-adm-badge--amber">예약</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
            <tr><td><span class="sb-adm-checkbox"></span></td><td>개인정보처리방침 개정</td><td>약관</td><td>—</td><td>06-01 ~ 06-30</td><td class="num">1,022</td><td><span class="sb-adm-badge sb-adm-badge--gray">종료</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
        </tbody>
    </table>
    <div class="sb-adm-pager"><span>총 142개</span><div class="sb-adm-pager-nums"><span>‹</span><span class="is-active">1</span><span>2</span><span>›</span></div></div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
