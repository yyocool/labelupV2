<?php
/**
 * 스토리보드: 재고관리
 * 메뉴코드: 02-03-03
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-03-03';
$adminTitle = '재고관리';
$adminSub = '재고 현황·입출고·안전재고 알림';
$adminActive = '02-03';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '재고관리 (현황 + 입출고)'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/shop/inventory'),
    array('dt' => '연관 테이블', 'dd' => 'inventory · stock_movements · warehouses'),
    array('dt' => '접근 권한', 'dd' => '관리자 · 물류 담당'),
    array('dt' => '화면 목적', 'dd' => '실물 재고 추적·입출고 기록·안전재고 관리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 쇼핑몰 관리 › 재고관리'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '재고 KPI', 'el' => '총 SKU·품절·안전재고 이하·재고 자산액', 'link' => '—'),
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '필터', 'el' => '창고·카테고리·재고상태 필터', 'link' => '—'),
    array('id' => 'A-01', 'kind' => 'cta', 'block' => '입출고 등록', 'el' => '입고·출고·조정·이동 처리', 'link' => '—'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '재고 테이블', 'el' => '상품·현재고·안전재고·가용·창고·상태', 'link' => '02-03-01'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '입출고 이력', 'el' => '최근 재고 변동 로그', 'link' => '—'),
);

$adminUx = array(
    array('item' => '안전재고', 'desc' => 'SKU별 임계 설정. 이하 시 대시보드/알림 경고'),
    array('item' => '입출고', 'desc' => '입고/출고/조정/창고이동 유형. 사유·수량·담당자 기록'),
    array('item' => '가용 재고', 'desc' => '현재고 − 주문 예약분 = 가용. 실시간 반영'),
    array('item' => '이력 추적', 'desc' => '모든 변동은 원장 기록(불변). 되돌리기는 반대 거래로 처리'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>재고관리</h3><p>중앙 물류창고 기준</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">⤓ 재고 실사표</span><span class="sb-adm-btn sb-adm-btn--primary">＋ 입출고 등록</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">총 SKU</div><div class="val">1,284</div><div class="delta">활성</div></div>
    <div class="sb-adm-kpi"><div class="lbl">품절</div><div class="val">37</div><div class="delta down">확인 필요</div></div>
    <div class="sb-adm-kpi"><div class="lbl">안전재고 이하</div><div class="val">52</div><div class="delta down">발주 검토</div></div>
    <div class="sb-adm-kpi"><div class="lbl">재고 자산액</div><div class="val">184.2M</div><div class="delta">원가 기준</div></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-input sel">창고: 중앙 물류</span>
    <span class="sb-adm-input sel">카테고리: 전체</span>
    <span class="sb-adm-chip">전체</span><span class="sb-adm-chip is-active">부족+품절</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">조회</span>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-table-wrap">
        <table class="sb-adm-table">
            <thead><tr><th>상품 / SKU</th><th class="num">현재고</th><th class="num">안전</th><th class="num">가용</th><th>상태</th></tr></thead>
            <tbody>
                <tr><td>방수 라벨 A4 40칸<br><small class="muted">SKU-LB-A4-40W</small></td><td class="num">0</td><td class="num">50</td><td class="num">0</td><td><span class="sb-adm-badge sb-adm-badge--red">품절</span></td></tr>
                <tr><td>감열 라벨 100×150<br><small class="muted">SKU-TH-100150</small></td><td class="num">12</td><td class="num">80</td><td class="num">4</td><td><span class="sb-adm-badge sb-adm-badge--amber">부족</span></td></tr>
                <tr><td>프린터 리본 65mm<br><small class="muted">SKU-RB-65</small></td><td class="num">8</td><td class="num">30</td><td class="num">8</td><td><span class="sb-adm-badge sb-adm-badge--amber">부족</span></td></tr>
                <tr><td>유광 라벨 A4 24칸<br><small class="muted">SKU-LB-A4-24W</small></td><td class="num">1,240</td><td class="num">200</td><td class="num">1,186</td><td><span class="sb-adm-badge sb-adm-badge--green">정상</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>최근 입출고</h4></div>
        <ul class="sb-adm-list">
            <li><span class="sb-adm-badge sb-adm-badge--green">입고</span><span class="grow">유광 라벨 A4 24칸</span><span class="val">+500</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--blue">출고</span><span class="grow">감열 라벨 100×150</span><span class="val">-120</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--amber">조정</span><span class="grow">리본 65mm(실사)</span><span class="val">-3</span></li>
            <li><span class="sb-adm-badge sb-adm-badge--purple">이동</span><span class="grow">PET 원형(→ 2창고)</span><span class="val">-200</span></li>
        </ul>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
