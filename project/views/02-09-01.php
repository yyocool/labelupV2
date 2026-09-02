<?php
/**
 * 스토리보드: 매출통계
 * 메뉴코드: 02-09-01
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-09-01';
$adminTitle = '매출통계';
$adminSub = '기간·상품·채널·지역별 매출 심층 분석';
$adminActive = '02-09';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '매출통계 분석'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/finance/sales'),
    array('dt' => '집계 소스', 'dd' => 'orders · order_items · refunds (일 배치)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 재무·MD'),
    array('dt' => '화면 목적', 'dd' => '매출 다차원 분석·목표 대비·리포트 산출'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 통계·정산 › 매출통계'),
);

$adminZones = array(
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '분석 필터', 'el' => '기간·단위·분석축(상품/채널/지역/등급)', 'link' => '—'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '요약 KPI', 'el' => '총매출·순매출·주문·객단가·목표달성률', 'link' => '02-01-02'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '추이 차트', 'el' => '매출 추이 + 목표선 + 전년 비교', 'link' => '—'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '분석축 랭킹', 'el' => '상품/카테고리/지역별 매출 순위', 'link' => '02-03-01'),
    array('id' => 'M-04', 'kind' => 'ui', 'block' => '상세 테이블', 'el' => '축별 매출·비중·증감·환불', 'link' => '—'),
);

$adminUx = array(
    array('item' => '다차원 분석', 'desc' => '분석축(상품/카테고리/채널/지역/등급) 전환. 교차 필터'),
    array('item' => '목표 관리', 'desc' => '월/분기 목표 설정. 달성률 게이지·미달 경고'),
    array('item' => '비교', 'desc' => '전기간·전년 동기 비교 오버레이'),
    array('item' => '리포트', 'desc' => '예약 리포트 메일. 엑셀/CSV/PDF 내보내기'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>매출통계</h3><p>2026-07 · 목표 대비 92%</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-input sel">축: 카테고리</span><span class="sb-adm-btn sb-adm-btn--primary">⤓ 리포트</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">총 매출</div><div class="val">328.4M</div><div class="delta up">▲ 14.2%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">순매출</div><div class="val">320.2M</div><div class="delta up">▲ 14.6%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">객단가</div><div class="val">₩36,720</div><div class="delta up">▲ 3.1%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">목표 달성률</div><div class="val">92%</div><div class="delta down">목표 357M</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>일별 매출 (목표·전년 비교)</h4></div>
        <div class="sb-adm-chart sb-adm-chart--line"></div>
    </div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>카테고리별 비중</h4></div>
        <div class="sb-adm-chart sb-adm-chart--donut" style="height:180px">
            <div class="sb-adm-donut"></div>
            <div class="sb-adm-legend" style="margin-left:14px">
                <span><i style="background:#6366f1"></i>라벨·스티커 45%</span>
                <span><i style="background:#22c55e"></i>프린터·소모품 25%</span>
                <span><i style="background:#f59e0b"></i>인쇄 의뢰 18%</span>
                <span><i style="background:#cbd5e1"></i>기타 12%</span>
            </div>
        </div>
    </div>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr><th>카테고리</th><th class="num">주문</th><th class="num">매출</th><th class="num">비중</th><th class="num">전월 대비</th><th class="num">환불률</th></tr></thead>
        <tbody>
            <tr><td class="strong">라벨·스티커</td><td class="num">6,024</td><td class="num strong">147,780,000</td><td class="num">45%</td><td class="num" style="color:#16a34a">▲ 16%</td><td class="num">2.1%</td></tr>
            <tr><td class="strong">프린터·소모품</td><td class="num">1,912</td><td class="num strong">82,100,000</td><td class="num">25%</td><td class="num" style="color:#16a34a">▲ 9%</td><td class="num">1.4%</td></tr>
            <tr><td class="strong">인쇄 의뢰</td><td class="num">642</td><td class="num strong">59,120,000</td><td class="num">18%</td><td class="num" style="color:#dc2626">▼ 3%</td><td class="num">3.8%</td></tr>
            <tr><td class="strong">기타</td><td class="num">364</td><td class="num strong">39,400,000</td><td class="num">12%</td><td class="num" style="color:#16a34a">▲ 5%</td><td class="num">2.6%</td></tr>
        </tbody>
    </table>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
