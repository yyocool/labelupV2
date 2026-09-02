<?php
/**
 * 스토리보드: 포인트 관리
 * 메뉴코드: 02-01-03
 *
 * @var array $menu
 * @var array $breadcrumb
 */
$adminCode = '02-01-03';
$adminTitle = '포인트 관리';
$adminSub = '적립·사용·만료 정책 및 수동 지급/차감';
$adminActive = '02-01';

$adminMeta = array(
    array('dt' => '화면명', 'dd' => '포인트 관리'),
    array('dt' => 'URL (예상)', 'dd' => '/admin/dashboard/points'),
    array('dt' => '연관 테이블', 'dd' => 'point_ledger · point_policy'),
    array('dt' => '접근 권한', 'dd' => '관리자 (지급/차감은 admin 롤)'),
    array('dt' => '화면 목적', 'dd' => '포인트 잔액 모니터링 · 정책 설정 · 수동 조정'),
    array('dt' => 'IA 레벨', 'dd' => 'Backoffice › 대시보드 › 포인트 관리'),
);

$adminZones = array(
    array('id' => 'M-01', 'kind' => 'ui', 'block' => '포인트 KPI', 'el' => '총 발행·사용·잔액·만료예정', 'link' => '—'),
    array('id' => 'M-02', 'kind' => 'ui', 'block' => '적립/사용 추이', 'el' => '월별 적립·사용 막대 차트', 'link' => '—'),
    array('id' => 'T-01', 'kind' => 'nav', 'block' => '정책 설정', 'el' => '적립률·유효기간·최소사용액', 'link' => '—'),
    array('id' => 'M-03', 'kind' => 'ui', 'block' => '지급/차감 내역', 'el' => '회원별 포인트 원장 테이블', 'link' => '02-02-01'),
    array('id' => 'C-01', 'kind' => 'cta', 'block' => '수동 지급', 'el' => '보상·프로모션 일괄 지급 모달', 'link' => '—'),
);

$adminUx = array(
    array('item' => '정책 저장', 'desc' => '적립률/유효기간 변경 이력 기록. 소급 미적용 안내'),
    array('item' => '수동 지급', 'desc' => '대상(개별/등급/전체) 선택 → 사유 필수 → 이중 확인'),
    array('item' => '원장 추적', 'desc' => '적립/사용/만료/조정 유형별 필터 + 회원 검색'),
    array('item' => '만료 배치', 'desc' => '만료 예정 30/7일 자동 알림. 만료 처리 배치 로그'),
);

$adminMockup = function () {
?>
<div class="sb-adm-head">
    <div><h3>포인트 관리</h3><p>발행 포인트 · 정책 · 수동 조정</p></div>
    <div class="sb-adm-head-actions"><span class="sb-adm-btn">⚙ 정책 설정</span><span class="sb-adm-btn sb-adm-btn--primary">＋ 수동 지급</span></div>
</div>

<div class="sb-adm-kpis">
    <div class="sb-adm-kpi"><div class="lbl">총 발행</div><div class="val">42.6M P</div><div class="delta up">▲ 5.2%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">총 사용</div><div class="val">31.2M P</div><div class="delta up">▲ 7.1%</div></div>
    <div class="sb-adm-kpi"><div class="lbl">잔여 포인트</div><div class="val">11.4M P</div><div class="delta">부채 계정</div></div>
    <div class="sb-adm-kpi"><div class="lbl">만료 예정(30일)</div><div class="val">340K P</div><div class="delta down">알림 발송</div></div>
</div>

<div class="sb-adm-grid sb-adm-grid--2">
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>포인트 정책</h4><span class="more">편집</span></div>
        <div class="sb-adm-form-grid">
            <div class="sb-adm-form-row"><label>기본 적립률</label><div class="box">구매액의 1.0%</div></div>
            <div class="sb-adm-form-row"><label>유효기간</label><div class="box">적립일로부터 12개월</div></div>
            <div class="sb-adm-form-row"><label>최소 사용 금액</label><div class="box">1,000 P 이상</div></div>
            <div class="sb-adm-form-row"><label>사용 단위</label><div class="box">100 P 단위</div></div>
        </div>
    </div>
    <div class="sb-adm-panel">
        <div class="sb-adm-panel-head"><h4>월별 적립 · 사용</h4></div>
        <div class="sb-adm-chart">
            <div class="sb-adm-bar" style="height:60%"></div><div class="sb-adm-bar" style="height:48%"></div>
            <div class="sb-adm-bar" style="height:72%"></div><div class="sb-adm-bar" style="height:55%"></div>
            <div class="sb-adm-bar" style="height:80%"></div><div class="sb-adm-bar" style="height:66%"></div>
        </div>
    </div>
</div>

<div class="sb-adm-table-wrap">
    <table class="sb-adm-table">
        <thead><tr><th>일시</th><th>회원</th><th>유형</th><th class="num">포인트</th><th>사유</th><th class="num">잔액</th></tr></thead>
        <tbody>
            <tr><td>07-05 14:20</td><td>김라벨</td><td><span class="sb-adm-badge sb-adm-badge--green">적립</span></td><td class="num">+380</td><td>주문 결제 적립</td><td class="num">12,830</td></tr>
            <tr><td>07-05 13:02</td><td>이스티커</td><td><span class="sb-adm-badge sb-adm-badge--blue">사용</span></td><td class="num">-2,000</td><td>주문 결제 시 사용</td><td class="num">4,120</td></tr>
            <tr><td>07-05 11:41</td><td>박프린트</td><td><span class="sb-adm-badge sb-adm-badge--purple">지급</span></td><td class="num">+5,000</td><td>이벤트 보상(수동)</td><td class="num">7,540</td></tr>
            <tr><td>07-04 23:00</td><td>정디자인</td><td><span class="sb-adm-badge sb-adm-badge--gray">만료</span></td><td class="num muted">-1,200</td><td>유효기간 만료</td><td class="num">0</td></tr>
        </tbody>
    </table>
    <div class="sb-adm-pager"><span>총 8,942건 중 1–4</span><div class="sb-adm-pager-nums"><span class="is-active">1</span><span>2</span><span>3</span><span>›</span></div></div>
</div>
<?php
};

include __DIR__ . '/_fragments/admin-doc-shell.php';
