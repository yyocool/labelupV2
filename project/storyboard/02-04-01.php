<?php
/**
 * 스토리보드: 주문목록
 * 메뉴코드: 02-04-01
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-04-01';
$adminTitle = '주문목록';
$adminSub = '주문 검색·상태 변경·배송/송장 처리';
$adminActive = '02-04';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '주문목록 (리스트 + 상세)'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/orders/list'),
    array('dt' => '연관 테이블', 'dd' => 'orders · order_items · shipments · payments'),
    array('dt' => '접근 권한', 'dd' => '관리자 · CS · 물류'),
    array('dt' => '화면 목적', 'dd' => '주문 조회·상태 관리·송장 등록·배송 처리'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 주문관리 › 주문목록'),
);

$adminZones = array(
    array('id' => 'F-01', 'kind' => 'nav', 'block' => '검색/필터', 'el' => '주문번호·주문자·기간·상태·결제수단·채널', 'link' => '—'),
    array('id' => 'A-01', 'kind' => 'cta', 'block' => '일괄 처리', 'el' => '배송준비·송장 일괄 등록·상태 변경·내보내기', 'link' => '—'),
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '주문 테이블', 'el' => '주문번호·주문자·상품·금액·결제·상태·주문일', 'link' => '02-02-01'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '주문 상세', 'el' => '상품 구성·배송지·결제·타임라인·CS 메모', 'link' => '02-04-02'),
    array('id' => 'P-01', 'kind' => 'nav', 'block' => '페이지네이션', 'el' => '페이지 이동·표시 개수', 'link' => '—'),
);

$adminUx = array(
    array('item' => '상태 흐름', 'desc' => '결제완료 → 배송준비 → 배송중 → 배송완료 → 구매확정. 역행 제한'),
    array('item' => '송장 등록', 'desc' => '택배사+운송장 입력/엑셀 일괄. 자동 배송중 전환·알림 발송'),
    array('item' => '상세 타임라인', 'desc' => '주문/결제/배송/CS 이벤트 시간순. 담당자 기록'),
    array('item' => '부분 처리', 'desc' => '부분 배송/부분 취소 지원. 항목별 상태 관리'),
    array('item' => '결제 대사', 'desc' => 'PG 결제 상태와 대사. 불일치 주문 플래그'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>주문목록</h3><p>총 8,942건 · 처리 대기 86</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">🚚 송장 일괄 등록</span><span class="sb-adm-btn">⤓ 엑셀</span><span class="sb-adm-btn sb-adm-btn--primary">배송준비 처리</span></div>
</div>

<div class="sb-adm-toolbar">
    <span class="sb-adm-input" style="min-width:200px">🔍 주문번호·주문자</span>
    <span class="sb-adm-input sel">기간: 최근 7일</span>
    <span class="sb-adm-input sel">결제수단: 전체</span>
    <span class="sb-adm-btn sb-adm-btn--primary sb-adm-spacer">검색</span>
</div>

<div class="sb-adm-tabs">
    <span class="sb-adm-tab is-active">전체 8,942</span>
    <span class="sb-adm-tab">결제완료 86</span>
    <span class="sb-adm-tab">배송준비 18</span>
    <span class="sb-adm-tab">배송중 214</span>
    <span class="sb-adm-tab">완료 6,842</span>
    <span class="sb-adm-tab">취소/환불 782</span>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr>
            <th><span class="sb-adm-checkbox"></span></th><th>주문번호</th><th>주문자</th><th>대표 상품</th>
            <th class="num">결제금액</th><th>결제수단</th><th>상태</th><th>주문일시</th><th></th>
        </tr></thead>
        <tbody>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td class="strong">ORD-20981</td><td>김라벨</td><td>방수 라벨 A4 24칸 외 2건</td>
                <td class="num strong">38,000</td><td>카드</td><td><span class="sb-adm-badge sb-adm-badge--blue">결제완료</span></td><td>07-05 14:20</td>
                <td><span class="sb-adm-btn sb-adm-btn--sm">상세</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td class="strong">ORD-20975</td><td>박프린트</td><td>바코드 리본 세트</td>
                <td class="num strong">64,000</td><td>계좌이체</td><td><span class="sb-adm-badge sb-adm-badge--amber">배송준비</span></td><td>07-05 11:02</td>
                <td><span class="sb-adm-btn sb-adm-btn--sm">상세</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td class="strong">ORD-20960</td><td>최라벨업</td><td>감열 라벨 100×150 외 4건</td>
                <td class="num strong">128,500</td><td>카드</td><td><span class="sb-adm-badge sb-adm-badge--purple">배송중</span></td><td>07-04 18:41</td>
                <td><span class="sb-adm-btn sb-adm-btn--sm">상세</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td class="strong">ORD-20933</td><td>정디자인</td><td>PET 원형 라벨 40mm</td>
                <td class="num strong">12,500</td><td>간편결제</td><td><span class="sb-adm-badge sb-adm-badge--green">배송완료</span></td><td>07-03 09:15</td>
                <td><span class="sb-adm-btn sb-adm-btn--sm">상세</span></td>
            </tr>
            <tr>
                <td><span class="sb-adm-checkbox"></span></td>
                <td class="strong">ORD-20901</td><td>한스티커</td><td>무광 크라프트 라벨</td>
                <td class="num strong">22,000</td><td>카드</td><td><span class="sb-adm-badge sb-adm-badge--red">환불요청</span></td><td>07-02 20:33</td>
                <td><span class="sb-adm-btn sb-adm-btn--sm">상세</span></td>
            </tr>
        </tbody>
    </table>
    <div class="sb-adm-pager"><span>총 8,942건 · 페이지당 20</span><div class="sb-adm-pager-nums"><span>‹</span><span class="is-active">1</span><span>2</span><span>3</span><span>4</span><span>›</span></div></div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
