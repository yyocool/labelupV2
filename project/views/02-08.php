<?php
/**
 * 스토리보드: 콘텐츠 관리 (그룹 개요)
 * 메뉴코드: 02-08
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-08';
$adminTitle = '콘텐츠 관리';
$adminSub = '공지·FAQ·배너 등 사이트 콘텐츠 운영 허브';
$adminActive = '02-08';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '콘텐츠 관리 개요'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/content'),
    array('dt' => '하위 화면', 'dd' => '공지사항(02-08-01) · FAQ(02-08-02) · 배너관리(02-08-03)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 마케팅·CS'),
    array('dt' => '화면 목적', 'dd' => '사이트 노출 콘텐츠 통합 관리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 콘텐츠 관리'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '콘텐츠 KPI', 'el' => '공지·FAQ·배너·노출중', 'link' => '02-08-01'),
    array('id' => 'M-02', 'kind' => 'nav', 'block' => '바로가기', 'el' => '공지 작성·FAQ 등록·배너 등록', 'link' => '각 화면'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '최근 등록', 'el' => '최근 콘텐츠 활동 리스트', 'link' => '—'),
);

$adminUx = array(
    array('item' => '빠른 작성', 'desc' => '각 카드 CTA → 해당 콘텐츠 작성 화면'),
    array('item' => '노출 상태', 'desc' => '예약/노출/종료 상태 한눈에'),
    array('item' => '권한', 'desc' => '마케팅(배너)·CS(공지/FAQ) 담당 분리 가능'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>콘텐츠 관리</h3><p>공지 · FAQ · 배너</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn sb-adm-btn--primary">＋ 콘텐츠 작성</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">공지사항</div><div class="val">142</div><div class="delta">노출 8</div></div>
    <div class="sb-adm-kpi"><div class="lbl">FAQ</div><div class="val">86</div><div class="delta">12개 분류</div></div>
    <div class="sb-adm-kpi"><div class="lbl">배너</div><div class="val">24</div><div class="delta up">노출 6</div></div>
    <div class="sb-adm-kpi"><div class="lbl">예약 콘텐츠</div><div class="val">5</div><div class="delta">노출 대기</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--3">
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>공지사항</h4><span class="more">02-08-01 →</span></div>
        <ul class="sb-adm-list"><li><span class="sb-adm-badge sb-adm-badge--red">중요</span><span class="grow">배송 지연 안내</span></li><li><span class="grow">7월 정기 점검</span></li></ul></div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>FAQ</h4><span class="more">02-08-02 →</span></div>
        <ul class="sb-adm-list"><li><span class="grow">주문/결제 (24)</span></li><li><span class="grow">배송/반품 (18)</span></li></ul></div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>배너</h4><span class="more">02-08-03 →</span></div>
        <ul class="sb-adm-list"><li><span class="sb-adm-badge sb-adm-badge--green">노출</span><span class="grow">여름 프로모션</span></li><li><span class="sb-adm-badge sb-adm-badge--amber">예약</span><span class="grow">신규가입 혜택</span></li></ul></div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
