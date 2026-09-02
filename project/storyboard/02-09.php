<?php
/**
 * 스토리보드: 통계·정산 (그룹 개요)
 * 메뉴코드: 02-09
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-09';
$adminTitle = '통계·정산';
$adminSub = '매출 통계 운영 허브';
$adminActive = '02-09';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '통계·정산 개요'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/finance'),
    array('dt' => '하위 화면', 'dd' => '매출통계(02-09-01)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 재무'),
    array('dt' => '화면 목적', 'dd' => '매출 지표 관리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 통계·정산'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '재무 KPI', 'el' => '총매출·순매출·주문수·객단가', 'link' => '02-09-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '매출 추이', 'el' => '월별 매출/순매출 차트', 'link' => '02-09-01'),
);

$adminUx = array(
    array('item' => '기간 필터', 'desc' => '일/월/분기/연 단위 전환. 회계 기간 지원'),
    array('item' => '내보내기', 'desc' => '재무 리포트/세금계산서 자료 다운로드'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>통계·정산</h3><p>2026년 7월 (진행 중)</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn sb-adm-btn--primary">⤓ 재무 리포트</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">총 매출</div><div class="val">328.4M</div><div class="delta up">▲ 14.2%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">순매출</div><div class="val">320.2M</div><div class="delta up">▲ 14.6%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">주문 수</div><div class="val">12,480</div><div class="delta up">▲ 9.1%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">객단가</div><div class="val">26,300</div><div class="delta up">▲ 4.7%</div></div>
</div>

<div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>월별 매출 · 순매출</h4><span class="more">02-09-01 →</span></div>
    <div class="sb-adm-chart">
        <div class="sb-adm-bar" style="height:55%"></div><div class="sb-adm-bar" style="height:62%"></div>
        <div class="sb-adm-bar" style="height:70%"></div><div class="sb-adm-bar" style="height:66%"></div>
        <div class="sb-adm-bar" style="height:80%"></div><div class="sb-adm-bar" style="height:92%"></div>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
