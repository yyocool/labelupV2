<?php
/**
 * 스토리보드: 환불·취소
 * 메뉴코드: 02-04-02
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-04-02';
$adminTitle = '환불·취소';
$adminSub = '취소·반품·환불 요청 승인 및 정산';
$adminActive = '02-04';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '환불·취소 관리'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/orders/refunds'),
    array('dt' => '연관 테이블', 'dd' => 'refunds · returns · payments · point_ledger'),
    array('dt' => '접근 권한', 'dd' => '관리자 · CS (환불 승인은 admin)'),
    array('dt' => '화면 목적', 'dd' => '환불/취소 요청 검토·승인·PG 환불·포인트 복원'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 주문관리 › 환불·취소'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '요청 KPI', 'el' => '접수·승인대기·처리완료·이달 환불액', 'link' => '—'),
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '필터', 'el' => '유형(취소/반품/교환)·상태·사유·기간', 'link' => '—'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '요청 테이블', 'el' => '주문번호·유형·사유·금액·상태·요청일', 'link' => '02-04-01'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '처리 패널', 'el' => '요청 상세·검토·승인/반려·환불 금액 조정', 'link' => '02-09-02'),
);

$adminUx = array(
    array('item' => '유형 구분', 'desc' => '단순 취소(배송전)·반품(배송후)·교환. 유형별 절차 분기'),
    array('item' => '환불 계산', 'desc' => '상품가−사용 포인트−쿠폰 반영. 부분 환불·배송비 차감 지원'),
    array('item' => 'PG 연동', 'desc' => '승인 시 PG 자동 환불 요청. 결과 콜백으로 상태 갱신'),
    array('item' => '포인트 복원', 'desc' => '취소 시 사용 포인트 복원·적립 포인트 회수'),
    array('item' => '반려 사유', 'desc' => '반려 시 사유 필수. 고객 알림 자동 발송·이력 기록'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>환불·취소</h3><p>승인 대기 7건</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">⤓ 환불 내역</span><span class="sb-adm-btn sb-adm-btn--primary">선택 승인</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">이달 접수</div><div class="val">142</div><div class="delta down">▲ 1.5%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">승인 대기</div><div class="val">7</div><div class="delta down">처리 필요</div></div>
    <div class="sb-adm-kpi"><div class="lbl">처리 완료</div><div class="val">129</div><div class="delta up">평균 6h</div></div>
    <div class="sb-adm-kpi"><div class="lbl">이달 환불액</div><div class="val">8.2M</div><div class="delta">순매출 반영</div></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-chip is-active">전체</span><span class="sb-adm-chip">취소</span><span class="sb-adm-chip">반품</span><span class="sb-adm-chip">교환</span>
    <span class="sb-adm-input sel">상태: 승인대기</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">조회</span>
</div>

<div class="sb-adm-grid sb-adm-grid--2-1">
    <div class="sb-adm-table-wrap">
        <table class="sb-adm-table">
            <thead><tr><th><span class="sb-adm-checkbox"></span></th><th>주문번호</th><th>유형</th><th>사유</th><th class="num">환불액</th><th>상태</th></tr></thead>
            <tbody>
                <tr><td><span class="sb-adm-checkbox"></span></td><td class="strong">ORD-20901</td><td><span class="sb-adm-badge sb-adm-badge--red">반품</span></td><td>단순 변심</td><td class="num">22,000</td><td><span class="sb-adm-badge sb-adm-badge--amber">승인대기</span></td></tr>
                <tr><td><span class="sb-adm-checkbox"></span></td><td class="strong">ORD-20888</td><td><span class="sb-adm-badge sb-adm-badge--gray">취소</span></td><td>배송 전 취소</td><td class="num">38,000</td><td><span class="sb-adm-badge sb-adm-badge--amber">승인대기</span></td></tr>
                <tr><td><span class="sb-adm-checkbox"></span></td><td class="strong">ORD-20854</td><td><span class="sb-adm-badge sb-adm-badge--blue">교환</span></td><td>규격 오배송</td><td class="num">0</td><td><span class="sb-adm-badge sb-adm-badge--amber">확인중</span></td></tr>
                <tr><td><span class="sb-adm-checkbox"></span></td><td class="strong">ORD-20812</td><td><span class="sb-adm-badge sb-adm-badge--red">반품</span></td><td>인쇄 불량</td><td class="num">64,000</td><td><span class="sb-adm-badge sb-adm-badge--green">완료</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>환불 처리 — ORD-20901</h4></div>
        <div class="sb-adm-form-grid">
            <div class="sb-adm-form-row full"><label>요청 유형 / 사유</label><div class="box">반품 · 단순 변심</div></div>
            <div class="sb-adm-form-row"><label>상품 금액</label><div class="box">22,000</div></div>
            <div class="sb-adm-form-row"><label>사용 포인트 복원</label><div class="box">+2,000 P</div></div>
            <div class="sb-adm-form-row"><label>반품 배송비 차감</label><div class="box">-3,000</div></div>
            <div class="sb-adm-form-row"><label>최종 환불액</label><div class="box strong">19,000</div></div>
            <div class="sb-adm-form-row full"><label>처리 메모</label><div class="box area">회수 완료 확인 후 승인</div></div>
        </div>
        <div class="sb-adm-head-actions" style="margin-top:12px"><span class="sb-adm-btn sb-adm-btn--danger">반려</span><span class="sb-adm-btn sb-adm-btn--primary">환불 승인</span></div>
    </div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
