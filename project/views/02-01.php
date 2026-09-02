<?php
/**
 * 스토리보드: 대시보드 (그룹 개요)
 * 메뉴코드: 02-01
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-01';
$adminTitle = '대시보드';
$adminSub = '실시간 현황 · 매출 통계 · 포인트 관리 진입 허브';
$adminActive = '02-01';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '대시보드 개요'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/dashboard'),
    array('dt' => '하위 화면', 'dd' => '실시간 현황(02-01-01) · 매출 통계(02-01-02) · 포인트 관리(02-01-03)'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 운영자'),
    array('dt' => '갱신 주기', 'dd' => '실시간(30초) / 통계 일 1회 집계'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 대시보드'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '핵심 KPI', 'el' => '매출·주문·회원·전환율 카드 4종', 'link' => '02-01-02'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '매출/방문 추이', 'el' => '이중축 라인 차트(매출·방문자)', 'link' => '02-01-02'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '실시간 현황 요약', 'el' => '접속자·진행 주문·결제 실패', 'link' => '02-01-01'),
    array('id' => 'M-04', 'kind' => 'ui', 'block' => '포인트 요약', 'el' => '적립/사용/잔액 · 만료 예정', 'link' => '02-01-03'),
    array('id' => 'M-05', 'kind' => 'ui', 'block' => '인기 상품 TOP', 'el' => '판매 상위 상품 랭킹', 'link' => '02-03-01'),
);

$adminUx = array(
    array('item' => '기간 필터', 'desc' => '오늘/7일/30일/사용자지정. 전 위젯 동기화'),
    array('item' => '위젯 이동', 'desc' => '각 위젯 헤더 → 해당 상세 화면으로 딥링크'),
    array('item' => '내보내기', 'desc' => '대시보드 스냅샷 PDF/엑셀 다운로드'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>대시보드</h3><p>기간: 최근 30일 · 매장 전체</p></div>
    <div class="sb-adm-head-actions">
        <span class="sb-adm-chip">오늘</span><span class="sb-adm-chip is-active">30일</span>
        <span class="sb-adm-btn">⤓ 내보내기</span>
    </div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">총 매출</span><span class="ic-badge">₩</span></div><div class="val">328.4M</div><div class="delta up">▲ 14.2%</div></div>
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">총 주문</span><span class="ic-badge">▦</span></div><div class="val">8,942</div><div class="delta up">▲ 6.7%</div></div>
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">신규 회원</span><span class="ic-badge">◕</span></div><div class="val">3,215</div><div class="delta up">▲ 9.1%</div></div>
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">구매 전환율</span><span class="ic-badge">↗</span></div><div class="val">3.8%</div><div class="delta down">▼ 0.3%p</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>매출 · 방문자 추이</h4><span class="more">매출 통계 →</span></div>
        <div class="sb-adm-chart sb-adm-chart--line"></div>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>인기 상품 TOP 5</h4></div>
        <ul class="sb-adm-list">
            <li><span class="rank">1</span><span class="grow">방수 유광 라벨 A4 24칸</span><span class="val">1,284</span></li>
            <li><span class="rank">2</span><span class="grow">투명 PET 원형 라벨 40mm</span><span class="val">982</span></li>
            <li><span class="rank">3</span><span class="grow">감열 배송 라벨 100×150</span><span class="val">871</span></li>
            <li><span class="rank">4</span><span class="grow">무광 크라프트 사각 라벨</span><span class="val">654</span></li>
            <li><span class="rank">5</span><span class="grow">바코드 프린터 소모품 세트</span><span class="val">512</span></li>
        </ul>
    </div>
</div>

<div class="sb-adm-grid sb-adm-grid--3">
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>실시간 현황</h4><span class="more">02-01-01 →</span></div>
        <ul class="sb-adm-list">
            <li><span class="grow">현재 접속자</span><span class="val">327</span></li>
            <li><span class="grow">진행 중 주문</span><span class="val">54</span></li>
            <li><span class="grow">결제 실패(1h)</span><span class="val">3</span></li>
        </ul></div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>포인트 요약</h4><span class="more">02-01-03 →</span></div>
        <ul class="sb-adm-list">
            <li><span class="grow">이달 적립</span><span class="val">2.4M P</span></li>
            <li><span class="grow">이달 사용</span><span class="val">1.8M P</span></li>
            <li><span class="grow">만료 예정(30일)</span><span class="val">340K P</span></li>
        </ul></div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>채널별 매출</h4></div>
        <div class="sb-adm-chart sb-adm-chart--donut" style="height:150px">
            <div class="sb-adm-donut"></div>
            <div class="sb-adm-legend" style="margin-left:14px">
                <span><i style="background:#6366f1"></i>웹 45%</span>
                <span><i style="background:#22c55e"></i>모바일 25%</span>
                <span><i style="background:#f59e0b"></i>앱 18%</span>
                <span><i style="background:#cbd5e1"></i>기타 12%</span>
            </div>
        </div></div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
