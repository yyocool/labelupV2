<?php
/**
 * 스토리보드: PG 정산
 * 메뉴코드: 02-09-02
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-09-02';
$adminTitle = 'PG 정산';
$adminSub = 'PG사별 결제·수수료·정산 대사 및 내역 관리';
$adminActive = '02-09';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => 'PG 정산 관리'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/finance/settlement'),
    array('dt' => '연관 테이블', 'dd' => 'payments · settlements · pg_reconciliation'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 재무'),
    array('dt' => '화면 목적', 'dd' => 'PG사 정산 예정/완료·수수료·대사 불일치 관리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 통계·정산 › PG 정산'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '정산 KPI', 'el' => '결제총액·수수료·정산예정·정산완료', 'link' => '—'),
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '필터', 'el' => 'PG사·결제수단·기간·정산상태', 'link' => '02-04-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '정산 테이블', 'el' => '정산일·PG사·건수·결제액·수수료·정산액·상태', 'link' => '—'),
    array('id' => 'M-03', 'kind' => 'cta', 'block' => '대사', 'el' => 'PG 데이터 업로드→자동 대사·불일치 표시', 'link' => '02-04-02'),
);

$adminUx = array(
    array('item' => '대사', 'desc' => 'PG사 정산 파일 업로드 → 내부 결제와 자동 매칭. 불일치 하이라이트'),
    array('item' => '수수료', 'desc' => 'PG·결제수단별 수수료율 적용. 부가세 분리 계산'),
    array('item' => '상태', 'desc' => '정산예정/정산완료/보류. 예정일 캘린더 표시'),
    array('item' => '증빙', 'desc' => '세금계산서·정산명세서 다운로드. 회계 시스템 연동'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>PG 정산</h3><p>2026-07 · 정산 예정 42.6M</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">⤒ 대사 파일 업로드</span><span class="sb-adm-btn sb-adm-btn--primary">⤓ 정산명세서</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">결제 총액</div><div class="val">328.4M</div><div class="delta">8,942건</div></div>
    <div class="sb-adm-kpi"><div class="lbl">PG 수수료</div><div class="val">8.9M</div><div class="delta">평균 2.7%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">정산 예정</div><div class="val">42.6M</div><div class="delta down">3영업일 내</div></div>
    <div class="sb-adm-kpi"><div class="lbl">대사 불일치</div><div class="val">2</div><div class="delta down">확인 필요</div></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-input sel">PG사: 전체</span>
    <span class="sb-adm-input sel">기간: 이달</span>
    <span class="sb-adm-chip is-active">전체</span><span class="sb-adm-chip">정산예정</span><span class="sb-adm-chip">완료</span><span class="sb-adm-chip">불일치</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">조회</span>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr><th>정산 예정일</th><th>PG사</th><th class="num">건수</th><th class="num">결제액</th><th class="num">수수료</th><th class="num">정산액</th><th>상태</th></tr></thead>
        <tbody>
            <tr><td>2026-07-08</td><td class="strong">토스페이먼츠</td><td class="num">3,204</td><td class="num">98,400,000</td><td class="num muted">-2,460,000</td><td class="num strong">95,940,000</td><td><span class="sb-adm-badge sb-adm-badge--amber">정산예정</span></td></tr>
            <tr><td>2026-07-08</td><td class="strong">KG이니시스</td><td class="num">2,110</td><td class="num">64,200,000</td><td class="num muted">-1,798,000</td><td class="num strong">62,402,000</td><td><span class="sb-adm-badge sb-adm-badge--amber">정산예정</span></td></tr>
            <tr><td>2026-07-05</td><td class="strong">카카오페이</td><td class="num">1,842</td><td class="num">48,900,000</td><td class="num muted">-1,222,000</td><td class="num strong">47,678,000</td><td><span class="sb-adm-badge sb-adm-badge--green">완료</span></td></tr>
            <tr><td>2026-07-05</td><td class="strong">네이버페이</td><td class="num">1,786</td><td class="num">42,300,000</td><td class="num muted">-1,015,000</td><td class="num strong">41,285,000</td><td><span class="sb-adm-badge sb-adm-badge--red">불일치</span></td></tr>
        </tbody>
    </table>
    <div class="sb-adm-pager"><span>합계 정산액 247.3M</span><div class="sb-adm-pager-nums"><span>‹</span><span class="is-active">1</span><span>2</span><span>›</span></div></div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
