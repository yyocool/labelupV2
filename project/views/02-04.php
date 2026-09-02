<?php
/**
 * 스토리보드: 주문관리 (그룹 개요)
 * 메뉴코드: 02-04
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-04';
$adminTitle = '주문관리';
$adminSub = '주문 현황·배송·환불/취소 운영 허브';
$adminActive = '02-04';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '주문관리 개요'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/orders'),
    array('dt' => '하위 화면', 'dd' => '주문목록(02-04-01) · 환불·취소(02-04-02)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · CS·물류 담당'),
    array('dt' => '화면 목적', 'dd' => '주문 상태별 현황 파악·처리 대기 관리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 주문관리'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '주문 상태 KPI', 'el' => '결제완료·배송준비·배송중·완료·취소', 'link' => '02-04-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '주문 추이', 'el' => '일별 주문 수/금액 차트', 'link' => '02-09-01'),
    array('id' => 'M-03', 'kind' => 'cta', 'block' => '처리 대기', 'el' => '배송준비·환불요청 큐', 'link' => '02-04-02'),
    array('id' => 'M-04', 'kind' => 'ui', 'block' => '최근 주문', 'el' => '방금 접수된 주문 리스트', 'link' => '02-04-01'),
);

$adminUx = array(
    array('item' => '상태 진입', 'desc' => 'KPI 카드 클릭 → 해당 상태 필터된 주문목록'),
    array('item' => '처리 대기', 'desc' => '배송준비/환불요청 카운트. 클릭 → 처리 화면'),
    array('item' => 'SLA', 'desc' => '배송준비 지연(24h↑) 주문 강조 표시'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>주문관리</h3><p>오늘 접수 327건</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">⤓ 주문 내보내기</span><span class="sb-adm-btn sb-adm-btn--primary">배송 처리</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">결제완료</div><div class="val">86</div><div class="delta down">배송준비 대기</div></div>
    <div class="sb-adm-kpi"><div class="lbl">배송중</div><div class="val">214</div><div class="delta up">정상</div></div>
    <div class="sb-adm-kpi"><div class="lbl">배송완료</div><div class="val">6,842</div><div class="delta up">▲ 6.7%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">환불/취소 요청</div><div class="val">7</div><div class="delta down">처리 필요</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>일별 주문 추이</h4></div>
        <div class="sb-adm-chart">
            <div class="sb-adm-bar" style="height:52%"></div><div class="sb-adm-bar" style="height:64%"></div>
            <div class="sb-adm-bar" style="height:58%"></div><div class="sb-adm-bar" style="height:76%"></div>
            <div class="sb-adm-bar" style="height:62%"></div><div class="sb-adm-bar" style="height:82%"></div>
            <div class="sb-adm-bar" style="height:70%"></div><div class="sb-adm-bar" style="height:90%"></div>
        </div>
    </div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>처리 대기 큐</h4></div>
        <ul class="sb-adm-list">
            <li><span class="sb-adm-badge sb-adm-badge--amber">배송준비</span><span class="grow">24h 이상 지연</span><span class="val">18</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--red">환불요청</span><span class="grow">승인 대기</span><span class="val">7</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--blue">교환요청</span><span class="grow">확인 대기</span><span class="val">3</span></li>
        </ul>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
