<?php
/**
 * 스토리보드: 배너관리
 * 메뉴코드: 02-08-03
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-08-03';
$adminTitle = '배너관리';
$adminSub = '노출 위치·기간·순서·타겟팅 관리';
$adminActive = '02-08';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '배너관리'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/content/banners'),
    array('dt' => '연관 테이블', 'dd' => 'banners · banner_positions · banner_stats'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 마케팅'),
    array('dt' => '화면 목적', 'dd' => '배너 등록·노출 위치/기간·순서·성과 관리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 콘텐츠 관리 › 배너관리'),
);

$adminZones = array(
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '위치 필터', 'el' => '메인 히어로·쇼핑몰·팝업·띠배너', 'link' => '01-04-01'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '배너 테이블', 'el' => '미리보기·제목·위치·기간·순서·상태·클릭수', 'link' => '—'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '등록/수정', 'el' => '이미지·링크·위치·기간·타겟·순서', 'link' => '—'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '성과 요약', 'el' => '노출·클릭·CTR', 'link' => '02-09-01'),
);

$adminUx = array(
    array('item' => '위치별 노출', 'desc' => '위치별 슬롯 수·순서 관리. 반응형 이미지(PC/모바일) 별도'),
    array('item' => '기간 예약', 'desc' => '노출 시작/종료 예약. 종료 시 자동 비노출'),
    array('item' => '타겟팅', 'desc' => '비로그인/등급/신규회원 등 노출 대상 조건'),
    array('item' => '성과', 'desc' => '노출·클릭·CTR 집계. A/B 테스트 지원'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>배너관리</h3><p>총 24개 · 노출 6</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn sb-adm-btn--primary">＋ 배너 등록</span></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-chip is-active">전체</span><span class="sb-adm-chip">메인 히어로</span><span class="sb-adm-chip">쇼핑몰</span><span class="sb-adm-chip">팝업</span><span class="sb-adm-chip">띠배너</span>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr><th>미리보기</th><th>제목</th><th>노출 위치</th><th>노출 기간</th><th class="num">순서</th><th class="num">CTR</th><th>상태</th><th></th></tr></thead>
        <tbody>
            <tr><td><span class="sb-adm-thumb" style="width:64px;height:32px">IMG</span></td><td class="strong">여름 프로모션 최대 40%</td><td>메인 히어로</td><td>07-01 ~ 07-31</td><td class="num">1</td><td class="num">4.2%</td><td><span class="sb-adm-badge sb-adm-badge--green">노출</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
            <tr><td><span class="sb-adm-thumb" style="width:64px;height:32px">IMG</span></td><td class="strong">신규가입 3,000P 지급</td><td>팝업</td><td>07-08 ~ 07-20</td><td class="num">1</td><td class="num">—</td><td><span class="sb-adm-badge sb-adm-badge--amber">예약</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
            <tr><td><span class="sb-adm-thumb" style="width:64px;height:32px">IMG</span></td><td>프린터 소모품 기획전</td><td>쇼핑몰</td><td>06-15 ~ 06-30</td><td class="num">2</td><td class="num">3.1%</td><td><span class="sb-adm-badge sb-adm-badge--gray">종료</span></td><td><span class="sb-adm-btn sb-adm-btn--sm">수정</span></td></tr>
        </tbody>
    </table>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
