<?php
/**
 * 스토리보드: 실시간 현황
 * 메뉴코드: 02-01-01
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-01-01';
$adminTitle = '실시간 현황';
$adminSub = '실시간 접속·주문·결제 모니터링';
$adminActive = '02-01';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '실시간 현황 모니터'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/dashboard/realtime'),
    array('dt' => '데이터 소스', 'dd' => 'WebSocket / 30초 폴링 폴백'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 운영자'),
    array('dt' => '화면 목적', 'dd' => '실시간 트래픽·주문·결제 이상 감지'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 대시보드 › 실시간 현황'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '실시간 KPI', 'el' => '접속자·분당 주문·분당 매출·결제 실패', 'link' => '—'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '실시간 트래픽 차트', 'el' => '최근 30분 접속자/주문 라인', 'link' => '—'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '실시간 주문 스트림', 'el' => '방금 발생한 주문 리스트(자동 추가)', 'link' => '02-04-01'),
    array('id' => 'M-04', 'kind' => 'ui', 'block' => '접속 채널/지역', 'el' => '디바이스·유입경로·지역 분포', 'link' => '—'),
    array('id' => 'M-05', 'kind' => 'cta', 'block' => '이상 알림', 'el' => '결제 실패 급증·재고 소진 경고 배너', 'link' => '02-03-03'),
);

$adminUx = array(
    array('item' => '실시간 갱신', 'desc' => 'WebSocket 푸시. 연결 끊김 시 폴링 폴백 + 상태 표시등'),
    array('item' => '주문 스트림', 'desc' => '신규 주문 상단에 애니메이션 추가. 클릭 → 주문 상세'),
    array('item' => '이상 감지', 'desc' => '결제 실패율·재고 임계 초과 시 경고 토스트 + 소리(옵션)'),
    array('item' => '일시정지', 'desc' => '스트림 일시정지 토글로 특정 시점 데이터 확인'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>실시간 현황 <span class="sb-adm-badge sb-adm-badge--green">● LIVE</span></h3><p>WebSocket 연결됨 · 마지막 갱신 방금 전</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">⏸ 일시정지</span><span class="sb-adm-btn sb-adm-btn--primary">↻ 새로고침</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">현재 접속자</span><span class="ic-badge">◕</span></div><div class="val">327</div><div class="delta up">▲ 실시간</div></div>
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">분당 주문</span><span class="ic-badge">▦</span></div><div class="val">4.2</div><div class="delta up">▲ 12%</div></div>
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">분당 매출</span><span class="ic-badge">₩</span></div><div class="val">86K</div><div class="delta up">▲ 5%</div></div>
    <div class="sb-adm-kpi"><div class="lbl-row"><span class="lbl">결제 실패(1h)</span><span class="ic-badge">⚠</span></div><div class="val">3</div><div class="delta down">정상 범위</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>최근 30분 트래픽</h4></div>
        <div class="sb-adm-chart sb-adm-chart--line"></div>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>실시간 주문 스트림</h4></div>
        <ul class="sb-adm-list">
            <li><span class="sb-adm-badge sb-adm-badge--green">신규</span><span class="grow">#ORD-20981 · 라벨 A4</span><span class="val">₩38,000</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--green">신규</span><span class="grow">#ORD-20980 · PET 원형</span><span class="val">₩12,500</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--blue">결제</span><span class="grow">#ORD-20979 · 소모품</span><span class="val">₩64,000</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--green">신규</span><span class="grow">#ORD-20978 · 감열 라벨</span><span class="val">₩22,000</span></li>
        </ul>
    </div>
</div>

<div class="sb-adm-grid sb-adm-grid--3">
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>디바이스</h4></div>
        <ul class="sb-adm-list"><li><span class="grow">모바일</span><span class="val">58%</span></li><li><span class="grow">데스크톱</span><span class="val">34%</span></li><li><span class="grow">태블릿</span><span class="val">8%</span></li></ul></div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>유입 경로</h4></div>
        <ul class="sb-adm-list"><li><span class="grow">검색</span><span class="val">41%</span></li><li><span class="grow">직접</span><span class="val">30%</span></li><li><span class="grow">광고</span><span class="val">29%</span></li></ul></div>
    <div class="sb-adm-panel"><div class="sb-adm-panel-head"><h4>지역 TOP</h4></div>
        <ul class="sb-adm-list"><li><span class="grow">서울</span><span class="val">38%</span></li><li><span class="grow">경기</span><span class="val">27%</span></li><li><span class="grow">부산</span><span class="val">9%</span></li></ul></div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
